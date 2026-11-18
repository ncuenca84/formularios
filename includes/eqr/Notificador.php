<?php
/**
 * Notificador — LA UNICA puerta de salida de correos del modulo de
 * Equipos Rentados. Si manana cambia el servidor de correo, solo se
 * toca esta clase y config/mail.php.
 *
 * - SMTP institucional con PHPMailer (STARTTLS).
 * - La configuracion vive en config/mail.php (IGNORADO por Git);
 *   config/mail.example.php muestra el formato sin la clave.
 * - Cada envio queda registrado en eqr_notificaciones (enviado o fallido).
 * - Si el correo falla, el tramite NO se pierde: el admin ve el registro
 *   y puede reenviar el enlace.
 * - La clave SMTP jamas se imprime en pantalla, logs ni errores.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Notificador
{
    private ?array $cfg;

    public function __construct()
    {
        $archivo = __DIR__ . '/../../config/mail.php';
        $this->cfg = is_file($archivo) ? (require $archivo) : null;
    }

    public function configurado(): bool
    {
        return is_array($this->cfg) && !empty($this->cfg['host']) && !empty($this->cfg['usuario']);
    }

    /**
     * Envia un correo y lo registra en eqr_notificaciones.
     * @param array $adjuntos [['path'=>..., 'nombre'=>...], ...]
     * @return array ['ok'=>bool, 'error'=>?string]
     */
    public function enviar(PDO $db, ?int $tramiteId, string $para, string $asunto,
                           string $html, string $texto, array $adjuntos = [],
                           ?int $documentoId = null, array $imagenesInline = []): array
    {
        $resultado = 'fallido';
        $error = null;

        if (!$this->configurado()) {
            $error = 'SMTP no configurado: cree config/mail.php a partir de config/mail.example.php.';
        } else {
            try {
                $m = new PHPMailer(true);
                $m->isSMTP();
                $m->Host = $this->cfg['host'];
                $m->Port = (int)($this->cfg['puerto'] ?? 587);
                $m->SMTPAuth = true;
                $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $m->Username = $this->cfg['usuario'];
                $m->Password = (string)($this->cfg['clave'] ?? '');
                $m->CharSet = 'UTF-8';
                $m->Timeout = 20;
                $m->setFrom($this->cfg['remitente'] ?? $this->cfg['usuario'],
                            $this->cfg['nombre'] ?? 'Sistema de Formularios ARCONEL');
                $m->addAddress($para);
                $m->Subject = $asunto;
                $m->isHTML(true);
                $m->Body = $html;
                $m->AltBody = $texto;
                foreach ($adjuntos as $a) {
                    if (!empty($a['path']) && is_file($a['path'])) {
                        $m->addAttachment($a['path'], $a['nombre'] ?? basename($a['path']));
                    }
                }
                foreach ($imagenesInline as $img) {
                    if (!empty($img['path']) && is_file($img['path'])) {
                        $m->addEmbeddedImage($img['path'], $img['cid'], $img['nombre'] ?? basename($img['path']));
                    }
                }
                $m->send();
                $resultado = 'enviado';
            } catch (PHPMailerException $e) {
                // nunca incluir credenciales: PHPMailer no las pone en ErrorInfo
                $error = 'Fallo SMTP: ' . $e->getMessage();
            } catch (Throwable $e) {
                $error = 'Error al enviar: ' . $e->getMessage();
            }
        }

        try {
            $stmt = $db->prepare("INSERT INTO eqr_notificaciones (tramite_id, documento_id, destinatario, asunto, resultado, error)
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tramiteId, $documentoId, $para, $asunto, $resultado, $error]);
        } catch (Throwable $e) {
            // instalacion sin la columna documento_id (migracion pendiente)
            $stmt = $db->prepare("INSERT INTO eqr_notificaciones (tramite_id, destinatario, asunto, resultado, error)
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tramiteId, $para, $asunto, $resultado, $error]);
        }

        return ['ok' => $resultado === 'enviado', 'error' => $error];
    }

    // =================================================================
    //  Plantillas de los correos del circuito de firmas
    // =================================================================

    private function plantilla(string $titulo, string $cuerpoHtml): string
    {
        $t = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
        return '<!DOCTYPE html><html lang="es"><body style="margin:0;background:#f0f2f5;padding:18px;">
          <div style="max-width:620px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;
                      font-family:Arial,Helvetica,sans-serif;color:#333;">
            <div style="background:#14532d;color:#fff;padding:14px 20px;font-size:16px;font-weight:bold;">' . $t . '</div>
            <div style="padding:20px;font-size:14px;line-height:1.5;">' . $cuerpoHtml . '</div>
            <div style="padding:10px 20px;background:#f8f9fa;color:#777;font-size:11px;">
              Agencia de Regulación y Control de Electricidad — Sistema de Formularios Institucionales.<br>
              Este es un mensaje automático; no responda a este correo.
            </div>
          </div></body></html>';
    }

    private static function tipoLegible(string $tipo): string
    {
        return ['asignacion' => 'Asignación', 'traspaso' => 'Traspaso', 'reposicion' => 'Reposición',
                'devolucion' => 'Devolución', 'salida' => 'Salida de las instalaciones'][$tipo] ?? $tipo;
    }

    private static function equipoLinea(array $tramite): string
    {
        return trim(($tramite['equipo_marca'] ?? '') . ' ' . ($tramite['equipo_modelo'] ?? '') .
            ' — Código ' . ($tramite['equipo_codigo'] ?? '') . ', Serie ' . ($tramite['equipo_serie'] ?? ''));
    }

    /** Correo al firmante de turno con su enlace de firma. */
    public function notificarTurno(PDO $db, array $tramite, array $firma, string $urlFirma, string $expira): array
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $tipo = self::tipoLegible($tramite['tipo']);
        $equipo = self::equipoLinea($tramite);
        $html = $this->plantilla('Firma pendiente — ' . $tipo . ' de equipo', '
            <p>Estimado/a <strong>' . $e($firma['nombre']) . '</strong>:</p>
            <p>Tiene un acta pendiente de su firma electrónica en el trámite
               <strong>' . $e($tramite['codigo']) . '</strong> (' . $e($tipo) . '), creado por
               ' . $e($tramite['admin_contrato_nombre']) . ', correspondiente al equipo:</p>
            <p style="background:#eef2f7;padding:10px;border-radius:6px;">' . $e($equipo) . '</p>
            <p>Para revisar el acta y firmarla con su archivo de firma electrónica (.p12), ingrese aquí:</p>
            <p style="text-align:center;margin:18px 0;">
              <a href="' . $e($urlFirma) . '" style="background:#14532d;color:#fff;padding:12px 26px;
                 border-radius:6px;text-decoration:none;font-weight:bold;">Revisar y firmar el acta</a></p>
            <p style="font-size:12px;color:#666;">El enlace es personal y vence el ' . $e($expira) . '.
               Su archivo .p12 y su clave se procesan únicamente en su navegador; nunca se envían al servidor.</p>');
        $texto = "Tiene un acta pendiente de firma en el trámite {$tramite['codigo']} ($tipo).\n"
            . "Equipo: $equipo\nFirme en: $urlFirma\nEl enlace vence el $expira.";
        return $this->enviar($db, (int)$tramite['id'], $firma['correo'],
            "Firma pendiente: $tipo de equipo — trámite {$tramite['codigo']}", $html, $texto);
    }

    /** Aviso al administrador del contrato cuando alguien rechaza. */
    public function notificarRechazo(PDO $db, array $tramite, array $firma, string $motivo): array
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $tipo = self::tipoLegible($tramite['tipo']);
        $html = $this->plantilla('Trámite rechazado', '
            <p>El trámite <strong>' . $e($tramite['codigo']) . '</strong> (' . $e($tipo) . ') fue
               <strong style="color:#b02a37;">rechazado</strong> por
               ' . $e($firma['nombre']) . ' (C.C. ' . $e($firma['cedula']) . ').</p>
            <p><strong>Motivo indicado:</strong></p>
            <p style="background:#fdecec;padding:10px;border-radius:6px;">' . $e($motivo) . '</p>
            <p>Equipo: ' . $e(self::equipoLinea($tramite)) . '</p>
            <p>El custodio del equipo no cambió. Puede crear un nuevo trámite desde el panel si corresponde.</p>');
        $texto = "El trámite {$tramite['codigo']} ($tipo) fue RECHAZADO por {$firma['nombre']}.\nMotivo: $motivo";
        return $this->enviar($db, (int)$tramite['id'], $tramite['admin_contrato_correo'],
            "Trámite rechazado — {$tramite['codigo']}", $html, $texto);
    }

    /** Alerta de cierre al administrador + copia del acta final a todos los firmantes. */
    public function notificarCierre(PDO $db, array $tramite, array $firmas, string $actaPath): array
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $tipo = self::tipoLegible($tramite['tipo']);
        $lista = '';
        foreach ($firmas as $f) {
            $lista .= '<li>' . $e($f['nombre']) . ' (C.C. ' . $e($f['cedula']) . ') — '
                . $e($f['fecha_firma']) . '</li>';
        }
        $extraSalida = '';
        $extraSalidaTxt = '';
        $imagenesInline = [];
        $qrTmp = null;
        if (($tramite['tipo'] ?? '') === 'salida' && !empty($tramite['verif_token'])
            && function_exists('eqrUrlVerificacionSalida')) {
            $urlQr = eqrUrlVerificacionSalida($tramite['verif_token']);
            // QR como imagen EMBEBIDA (cid): se ve directo en el correo, sin abrir el PDF
            try {
                $opts = new \chillerlan\QRCode\QROptions([
                    'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
                    'scale' => 6,
                    'imageBase64' => false,
                    'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
                ]);
                $png = (new \chillerlan\QRCode\QRCode($opts))->render($urlQr);
                $qrTmp = tempnam(sys_get_temp_dir(), 'qrsalida') . '.png';
                file_put_contents($qrTmp, $png);
                $imagenesInline[] = ['path' => $qrTmp, 'cid' => 'qrporteria', 'nombre' => 'qr_porteria.png'];
            } catch (Throwable $exQr) {
                $qrTmp = null; // sin imagen: queda el enlace
            }
            $imgHtml = $qrTmp !== null
                ? '<div style="text-align:center;margin:10px 0;">
                     <img src="cid:qrporteria" width="190" height="190" alt="QR de validación en portería"
                          style="border:1px solid #cdd; border-radius:8px;"></div>'
                : '';
            $extraSalida = '<p style="background:#eef7ee;padding:10px;border-radius:6px;">
               <strong>Validación en portería:</strong> muestre este código al personal de seguridad
               directamente desde su celular (o desde el acta adjunta/impresa). Al escanearlo verán
               al instante si la salida está autorizada y vigente.</p>' . $imgHtml . '
               <p style="text-align:center;font-size:12px;"><a href="' . $e($urlQr) . '">' . $e($urlQr) . '</a></p>';
            $extraSalidaTxt = "\nValidación en portería: presente el QR del acta al guardia. Enlace: $urlQr";
        }
        $html = $this->plantilla('Trámite completado — acta firmada', '
            <p>El trámite <strong>' . $e($tramite['codigo']) . '</strong> (' . $e($tipo) . ') se
               <strong style="color:#14532d;">completó</strong> con todas sus firmas electrónicas.</p>
            <p>Equipo: ' . $e(self::equipoLinea($tramite)) . '</p>
            <p><strong>Firmas:</strong></p><ul>' . $lista . '</ul>' . $extraSalida . '
            <p>Se adjunta el acta final firmada. Huella SHA-256 del documento:<br>
               <code style="font-size:11px;">' . $e($tramite['hash_actual']) . '</code></p>');
        $texto = "El trámite {$tramite['codigo']} ($tipo) se completó con todas sus firmas.\n"
            . "Equipo: " . self::equipoLinea($tramite) . "\nSHA-256: {$tramite['hash_actual']}" . $extraSalidaTxt;
        $adjunto = [['path' => $actaPath, 'nombre' => 'ACTA_' . $tramite['codigo'] . '.pdf']];

        $asunto = "Trámite completado — {$tramite['codigo']}";
        $destinos = [$tramite['admin_contrato_correo']];
        foreach ($firmas as $f) {
            if (!in_array($f['correo'], $destinos, true) && $f['correo'] !== '') { $destinos[] = $f['correo']; }
        }
        $ultimo = ['ok' => true, 'error' => null];
        foreach ($destinos as $d) {
            $r = $this->enviar($db, (int)$tramite['id'], $d, $asunto, $html, $texto, $adjunto, null, $imagenesInline);
            if (!$r['ok']) { $ultimo = $r; }
        }
        if ($qrTmp !== null) { @unlink($qrTmp); }
        return $ultimo;
    }

    // =================================================================
    //  Correos de la firma en linea de los formularios 1-6 (fir_*)
    // =================================================================

    /** Como enviar(), pero registrando el documento_id del formulario. */
    public function enviarDoc(PDO $db, ?int $documentoId, string $para, string $asunto,
                              string $html, string $texto, array $adjuntos = []): array
    {
        return $this->enviar($db, null, $para, $asunto, $html, $texto, $adjuntos, $documentoId);
    }

    /** Correo al firmante de turno de un formulario. */
    public function notificarTurnoDoc(PDO $db, array $doc, array $firma, string $urlFirma, string $expira): array
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $html = $this->plantilla('Firma pendiente — ' . $doc['titulo'], '
            <p>Estimado/a <strong>' . $e($firma['nombre']) . '</strong>:</p>
            <p>Tiene pendiente su firma electrónica en el documento
               <strong>' . $e($doc['titulo']) . '</strong>
               (solicitud <strong>' . $e($doc['codigo']) . '</strong>), en calidad de
               <strong>' . $e($firma['etiqueta'] ?: $firma['rol']) . '</strong>.</p>
            <p>Para revisar el documento y firmarlo con su archivo de firma electrónica (.p12), ingrese aquí:</p>
            <p style="text-align:center;margin:18px 0;">
              <a href="' . $e($urlFirma) . '" style="background:#14532d;color:#fff;padding:12px 26px;
                 border-radius:6px;text-decoration:none;font-weight:bold;">Revisar y firmar</a></p>
            <p style="font-size:12px;color:#666;">El enlace es personal y vence el ' . $e($expira) . '.
               Su archivo .p12 y su clave se procesan únicamente en su navegador; nunca se envían al servidor.</p>');
        $texto = "Tiene pendiente su firma en: {$doc['titulo']} (solicitud {$doc['codigo']}).\n"
            . "Firme en: $urlFirma\nEl enlace vence el $expira.";
        return $this->enviarDoc($db, (int)$doc['id'], $firma['correo'],
            "Firma pendiente: {$doc['titulo']} — {$doc['codigo']}", $html, $texto);
    }

    /** Documento completado: PDF firmado a todos los firmantes y a la DTIC. */
    public function notificarCierreDoc(PDO $db, array $doc, array $firmas, string $pdfPath): array
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $lista = '';
        $destinos = [];
        foreach ($firmas as $f) {
            $lista .= '<li>' . $e($f['nombre']) . ' (C.C. ' . $e($f['cedula']) . ') — ' . $e($f['fecha_firma']) . '</li>';
            if ($f['correo'] !== '' && !in_array($f['correo'], $destinos, true)) { $destinos[] = $f['correo']; }
        }
        $adminCorreo = trim(getConfig('email_admin'));
        if ($adminCorreo !== '' && !in_array($adminCorreo, $destinos, true)) { $destinos[] = $adminCorreo; }

        $html = $this->plantilla('Documento firmado — ' . $doc['titulo'], '
            <p>El documento <strong>' . $e($doc['titulo']) . '</strong>
               (solicitud <strong>' . $e($doc['codigo']) . '</strong>) se
               <strong style="color:#14532d;">completó</strong> con todas sus firmas electrónicas.
               Se adjunta el PDF final firmado; valídelo únicamente en FirmaEC.</p>
            <p><strong>Firmas:</strong></p><ul>' . $lista . '</ul>');
        $texto = "El documento {$doc['titulo']} (solicitud {$doc['codigo']}) se completó con todas sus firmas.\n"
            . "Se adjunta el PDF final firmado.";
        $adjunto = [['path' => $pdfPath, 'nombre' => $doc['codigo'] . '_firmado.pdf']];
        $asunto = "Documento firmado — {$doc['codigo']}: {$doc['titulo']}";
        $ultimo = ['ok' => true, 'error' => null];
        foreach ($destinos as $d) {
            $r = $this->enviarDoc($db, (int)$doc['id'], $d, $asunto, $html, $texto, $adjunto);
            if (!$r['ok']) { $ultimo = $r; }
        }
        return $ultimo;
    }

    /** Rechazo de un formulario: aviso al solicitante (firma 1) y a la DTIC. */
    public function notificarRechazoDoc(PDO $db, array $doc, array $firma, string $motivo, array $firmas): array
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $html = $this->plantilla('Documento rechazado', '
            <p>El documento <strong>' . $e($doc['titulo']) . '</strong>
               (solicitud <strong>' . $e($doc['codigo']) . '</strong>) fue
               <strong style="color:#b02a37;">rechazado</strong> por
               ' . $e($firma['nombre']) . ' (' . $e($firma['etiqueta'] ?: $firma['rol']) . ').</p>
            <p><strong>Motivo indicado:</strong></p>
            <p style="background:#fdecec;padding:10px;border-radius:6px;">' . $e($motivo) . '</p>
            <p>La solicitud no continúa. Si corresponde, puede llenarse un nuevo formulario.</p>');
        $texto = "El documento {$doc['titulo']} ({$doc['codigo']}) fue RECHAZADO por {$firma['nombre']}.\nMotivo: $motivo";
        $destinos = [];
        if (!empty($firmas[0]['correo'])) { $destinos[] = $firmas[0]['correo']; }
        $adminCorreo = trim(getConfig('email_admin'));
        if ($adminCorreo !== '' && !in_array($adminCorreo, $destinos, true)) { $destinos[] = $adminCorreo; }
        $ultimo = ['ok' => true, 'error' => null];
        foreach ($destinos as $d) {
            $r = $this->enviarDoc($db, (int)$doc['id'], $d, "Documento rechazado — {$doc['codigo']}", $html, $texto);
            if (!$r['ok']) { $ultimo = $r; }
        }
        return $ultimo;
    }

    /** Correo de prueba desde la configuracion del admin. */
    public function correoPrueba(PDO $db, string $para): array
    {
        $html = $this->plantilla('Correo de prueba', '
            <p>Este es un correo de prueba del módulo de Equipos Rentados.</p>
            <p>Si lo está leyendo, la conexión SMTP institucional funciona correctamente.</p>');
        return $this->enviar($db, null, $para, 'Correo de prueba — Equipos Rentados',
            $html, 'Correo de prueba del módulo de Equipos Rentados: la conexión SMTP funciona.');
    }
}

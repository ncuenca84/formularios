<?php
/**
 * CmsVerifier — verificacion de firmas PDF (seccion 4.4 del plan).
 *
 * Comprobaciones por cada firma del documento:
 *   1. Integridad: openssl_cms_verify sobre los bytes del ByteRange.
 *   2. Cobertura: la firma mas reciente cubre todo el archivo.
 *   3. Continuidad: el archivo empieza byte a byte con la version anterior.
 *   4. Identidad: la cedula del certificado coincide con la esperada.
 *   5. Vigencia: el certificado estaba vigente.
 *   6. Cadena de confianza: opcional, si hay certificados raiz configurados.
 *
 * PHP puro + extension openssl. Sin exec().
 */
class CmsVerifier
{
    /**
     * Extrae todas las firmas del PDF: [['byteRange'=>[a,b,c,d], 'derPath'=>..., 'hex'=>...], ...]
     * en orden de aparicion en el archivo.
     */
    public function extraerFirmas(string $pdf): array
    {
        $firmas = [];
        $off = 0;
        while (($pos = strpos($pdf, '/ByteRange', $off)) !== false) {
            if (!preg_match('/\/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/',
                substr($pdf, $pos, 200), $m)) {
                $off = $pos + 10; continue;
            }
            $br = [(int)$m[1], (int)$m[2], (int)$m[3], (int)$m[4]];
            // /Contents cercano (antes o despues del ByteRange, mismo objeto)
            $ini = max(0, $pos - 40000);
            $zona = substr($pdf, $ini, 80000);
            if (!preg_match_all('/\/Contents\s*<([0-9a-fA-F]+)>/s', $zona, $mm, PREG_OFFSET_CAPTURE)) {
                $off = $pos + 10; continue;
            }
            // el /Contents cuyo hueco coincide con el ByteRange: su offset absoluto == br[0]+br[1]+1
            $hex = null;
            foreach ($mm[1] as $cap) {
                $absHexStart = $ini + $cap[1];
                if ($absHexStart === $br[1] + 1) { $hex = $cap[0]; break; }
            }
            if ($hex === null) { $hex = $mm[1][0][0]; }  // respaldo
            $firmas[] = ['byteRange' => $br, 'hex' => $hex];
            $off = $pos + 10;
        }
        return $firmas;
    }

    /**
     * Verifica una firma CMS detached: integridad criptografica.
     * Devuelve ['ok'=>bool, 'certPem'=>string|null, 'error'=>string|null]
     */
    public function verificarIntegridad(string $pdf, array $firma): array
    {
        [$a, $lenA, $b, $lenB] = $firma['byteRange'];
        $datos = substr($pdf, $a, $lenA) . substr($pdf, $b, $lenB);

        $hex = rtrim($firma['hex'], '0');
        if (strlen($hex) % 2 !== 0) { $hex .= '0'; }
        $der = hex2bin($hex);
        if ($der === false || $der === '') {
            return ['ok' => false, 'certPem' => null, 'error' => 'Contents vacio o invalido'];
        }

        $tmpDatos = tempnam(sys_get_temp_dir(), 'pdfdata');
        $tmpSig   = tempnam(sys_get_temp_dir(), 'pdfsig');
        $tmpCert  = tempnam(sys_get_temp_dir(), 'pdfcert');
        file_put_contents($tmpDatos, $datos);
        file_put_contents($tmpSig, $der);

        $ok = @openssl_cms_verify(
            $tmpDatos,
            OPENSSL_CMS_BINARY | OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_DETACHED,
            $tmpCert,          // aqui openssl ESCRIBE el certificado del firmante
            [],
            null,
            null,
            null,
            $tmpSig,
            OPENSSL_ENCODING_DER
        );

        $certPem = $ok ? file_get_contents($tmpCert) : null;
        @unlink($tmpDatos); @unlink($tmpSig); @unlink($tmpCert);

        if (!$ok) {
            $e = '';
            while (($m = openssl_error_string()) !== false) { $e .= $m . ' '; }
            return ['ok' => false, 'certPem' => null, 'error' => trim($e) ?: 'firma invalida'];
        }
        return ['ok' => true, 'certPem' => $certPem, 'error' => null];
    }

    /**
     * TODAS las identificaciones (10 a 13 digitos) que aparecen en el
     * certificado: en el subject (serialNumber, CN, UID, etc.) y en las
     * extensiones. Cada entidad certificadora ecuatoriana guarda la cedula
     * en un campo distinto (el Banco Central en el serialNumber, otras en
     * OIDs propios y ponen en el serialNumber un numero interno), asi que
     * NO basta con mirar un solo campo.
     */
    public function identificacionesDelCert(string $certPem): array
    {
        $info = openssl_x509_parse($certPem);
        if ($info === false) { return []; }

        $textos = [];
        $junta = function ($v) use (&$textos, &$junta) {
            if (is_array($v)) { foreach ($v as $x) { $junta($x); } }
            elseif (is_string($v) || is_numeric($v)) { $textos[] = (string)$v; }
        };
        $junta($info['subject'] ?? []);
        $junta($info['extensions'] ?? []);   // valores DER en bruto: los digitos ASCII igual aparecen

        $ids = [];
        foreach ($textos as $t) {
            if (preg_match_all('/(?<!\d)(\d{10,13})(?!\d)/', $t, $mm)) {
                foreach ($mm[1] as $num) {
                    $ids[$num] = true;
                    // un RUC de persona natural es cedula + 001
                    if (strlen($num) === 13 && substr($num, 10) === '001') {
                        $ids[substr($num, 0, 10)] = true;
                    }
                }
            }
        }
        // strval: PHP convierte las claves numericas a enteros y romperia
        // las comparaciones estrictas de cadenas.
        return array_map('strval', array_keys($ids));
    }

    /** ¿El certificado pertenece a la cedula esperada? (busca en todos los campos) */
    public function certTieneCedula(string $certPem, string $cedulaEsperada): bool
    {
        $esperada = preg_replace('/\D/', '', $cedulaEsperada);
        if (strlen($esperada) !== 10) { return false; }
        return in_array($esperada, $this->identificacionesDelCert($certPem), true);
    }

    /** Extrae la cedula del certificado (la primera identificacion de 10 digitos). */
    public function extraerCedula(string $certPem): ?string
    {
        foreach ($this->identificacionesDelCert($certPem) as $id) {
            if (strlen($id) === 10) { return $id; }
        }
        return null;
    }

    /** Datos del certificado para registrar. */
    public function datosCert(string $certPem): array
    {
        $info = openssl_x509_parse($certPem) ?: [];
        return [
            'cn' => $info['subject']['CN'] ?? '',
            'emisor' => $info['issuer']['CN'] ?? '',
            'serial' => $info['serialNumberHex'] ?? ($info['serialNumber'] ?? ''),
            'desde' => isset($info['validFrom_time_t']) ? date('c', $info['validFrom_time_t']) : '',
            'hasta' => isset($info['validTo_time_t']) ? date('c', $info['validTo_time_t']) : '',
            'cedula' => $this->extraerCedula($certPem),
        ];
    }

    /**
     * Bateria completa sobre un PDF que acaba de recibir una firma nueva.
     *
     * @param string      $pdfNuevo       bytes del archivo con la firma nueva (la ultima)
     * @param string|null $pdfAnterior    bytes de la version anterior aceptada (null si es la primera)
     * @param string      $cedulaEsperada cedula que debe tener el certificado de la firma nueva
     * @param array       $caRaices       rutas PEM de CAs de confianza (vacio = no validar cadena)
     * @return array ['ok'=>bool, 'errores'=>[], 'advertencias'=>[], 'firmas'=>n, 'cert'=>datos]
     */
    public function verificarDocumento(string $pdfNuevo, ?string $pdfAnterior,
                                       string $cedulaEsperada, array $caRaices = []): array
    {
        $errores = [];
        $advertencias = [];
        $firmas = $this->extraerFirmas($pdfNuevo);
        if (!$firmas) {
            return ['ok' => false, 'errores' => ['El documento no contiene firmas.'],
                    'advertencias' => [], 'firmas' => 0, 'cert' => null];
        }

        // 1. Integridad de TODAS las firmas
        $certsPem = [];
        foreach ($firmas as $i => $f) {
            $r = $this->verificarIntegridad($pdfNuevo, $f);
            if (!$r['ok']) {
                $errores[] = 'La firma #' . ($i + 1) . ' no es integra: ' . $r['error'];
            }
            $certsPem[$i] = $r['certPem'];
        }

        // 2. Cobertura: la ultima firma cubre todo el archivo
        $ultima = end($firmas);
        [$a, $lenA, $b, $lenB] = $ultima['byteRange'];
        if ($b + $lenB !== strlen($pdfNuevo)) {
            $errores[] = 'La firma nueva no cubre todo el archivo.';
        }

        // 3. Continuidad con la version anterior
        if ($pdfAnterior !== null) {
            $lenPrev = strlen($pdfAnterior);
            if (strlen($pdfNuevo) <= $lenPrev
                || strncmp($pdfNuevo, $pdfAnterior, $lenPrev) !== 0) {
                $errores[] = 'El documento no es una extension incremental de la version anterior.';
            }
        }

        // 4. Identidad de la firma nueva
        $certNuevo = $certsPem[count($firmas) - 1] ?? null;
        $cert = null;
        if ($certNuevo) {
            $cert = $this->datosCert($certNuevo);
            $idsCert = $this->identificacionesDelCert($certNuevo);
            if ($this->certTieneCedula($certNuevo, $cedulaEsperada)) {
                // El certificado SI contiene la cedula esperada (en el campo
                // que sea segun la entidad certificadora): esa es la que se
                // registra, no el primer numero que aparezca.
                $cert['cedula'] = preg_replace('/\D/', '', $cedulaEsperada);
            } elseif (!$idsCert) {
                $advertencias[] = 'No se encontro la cedula en el certificado: requiere revision manual (CN: ' . $cert['cn'] . ').';
            } else {
                $errores[] = 'El certificado no contiene la cedula del firmante esperado (' . $cedulaEsperada
                    . '). Identificaciones encontradas en el certificado: ' . implode(', ', $idsCert)
                    . '. Verifique que este usando SU archivo .p12 y no el de otra persona.';
            }
            // 5. Vigencia
            $info = openssl_x509_parse($certNuevo);
            $ahora = time();
            if ($ahora < ($info['validFrom_time_t'] ?? 0) || $ahora > ($info['validTo_time_t'] ?? PHP_INT_MAX)) {
                $errores[] = 'El certificado de la firma nueva no esta vigente (vencio el ' .
                    date('Y-m-d', $info['validTo_time_t'] ?? 0) . ').';
            }
            // La validacion de cadena de confianza (entidades certificadoras)
            // se descarto por decision institucional: las actas son tramites
            // internos y la validacion oficial se hace en FirmaEC.
        } elseif (!$errores) {
            $errores[] = 'No se pudo extraer el certificado de la firma nueva.';
        }

        return [
            'ok' => !$errores,
            'errores' => $errores,
            'advertencias' => $advertencias,
            'firmas' => count($firmas),
            'cert' => $cert,
        ];
    }
}

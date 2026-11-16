<?php
/**
 * PdfSigner — Fase 0 (prueba de concepto)
 *
 * Prepara un PDF para recibir una firma CMS/PKCS#7 detached mediante
 * ACTUALIZACION INCREMENTAL: los bytes de la version anterior no se tocan,
 * solo se agregan al final los objetos nuevos (campo de firma visible,
 * apariencia, AcroForm, xref y trailer con /Prev).
 *
 * Flujo por firma:
 *   1) preparar(): agrega el placeholder y devuelve los "bytes a firmar"
 *      (todo el archivo menos el hueco hexadecimal de /Contents).
 *   2) El navegador firma esos bytes (CMS detached, SHA-256) con el .p12.
 *   3) completar(): inserta la firma DER (hex) en el hueco.
 *
 * El estado entre firmas (numeros de objeto, offsets) se guarda en un
 * archivo JSON al lado del PDF ("sidecar"), para no re-parsear PDFs propios.
 *
 * Sin exec(), sin librerias externas: PHP puro.
 */
class PdfSigner
{
    /** Bytes reservados para la firma DER dentro de /Contents (en binario). */
    const RESERVA_FIRMA = 16384;          // 16 KB -> 32768 caracteres hex
    const ANCHO_BYTERANGE = 10;           // digitos por numero en /ByteRange

    /**
     * Prepara la siguiente firma del documento.
     *
     * @param string $pdfPath   PDF actual (original DomPDF o ya con firmas).
     * @param string $statePath Sidecar JSON de estado (se crea si no existe).
     * @param array  $firmante  ['nombre'=>, 'cedula'=>, 'rol'=>, 'rect'=>[x1,y1,x2,y2], 'pagina'=>1]
     * @return array ['prepPath'=>..., 'bytesAFirmar'=>string binario,
     *                'holeStart'=>int, 'holeEnd'=>int]
     */
    public function preparar(string $pdfPath, string $statePath, array $firmante): array
    {
        $pdf = file_get_contents($pdfPath);
        if ($pdf === false || strlen($pdf) < 100) {
            throw new RuntimeException('No se pudo leer el PDF.');
        }

        $state = is_file($statePath)
            ? json_decode(file_get_contents($statePath), true)
            : null;

        if ($state === null) {
            $state = $this->analizarPdfOriginal($pdf);
        }

        // --- numeros de objeto nuevos -----------------------------------
        // Las fuentes y el QR se crean en CADA actualizacion de firma: son
        // objetos minusculos y asi un intento descartado (verificacion
        // fallida) nunca deja referencias colgadas en el estado.
        $n = $state['maxObj'];
        $objFontA = ++$n;                        // Courier (texto pequeno)
        $objFontB = ++$n;                        // Courier-Bold (nombre)
        $tieneQr  = !empty($firmante['qrMatrix']);
        $objImg   = $tieneQr ? ++$n : null;      // imagen del QR
        $objAp    = ++$n;                        // apariencia (Form XObject)
        $objField = ++$n;                        // campo de firma + widget
        $objSig   = ++$n;                        // diccionario /Sig
        $objAcro  = ++$n;                        // AcroForm (nueva version)
        $objPage  = $state['pageObj'];           // se redefine (misma numeracion)
        $objRoot  = $state['rootObj'];           // se redefine solo la 1a vez

        $update = '';
        $offsets = [];           // numObj => offset absoluto
        $base = strlen($pdf);
        if (substr($pdf, -1) !== "\n") { $update .= "\n"; }

        $add = function (int $num, string $cuerpo) use (&$update, &$offsets, $base) {
            $offsets[$num] = $base + strlen($update);
            $update .= "$num 0 obj\n$cuerpo\nendobj\n";
        };

        // --- fuentes estandar (objetos pequenos, una por update) --------
        $add($objFontA, '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>');
        $add($objFontB, '<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>');

        // --- imagen del QR (DeviceGray comprimida) ----------------------
        if ($tieneQr) {
            $mz = $firmante['qrMatrix'];
            $nq = count($mz);
            $raw = '';
            foreach ($mz as $fila) {
                foreach ($fila as $mod) { $raw .= $mod ? "\x00" : "\xff"; }
            }
            $comp = gzcompress($raw, 9);
            $add($objImg,
                "<< /Type /XObject /Subtype /Image /Width $nq /Height $nq /ColorSpace /DeviceGray\n" .
                "/BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($comp) . " >>\n" .
                "stream\n$comp\nendstream");
        }

        // --- apariencia visible (estilo FirmaEC) ------------------------
        [$x1, $y1, $x2, $y2] = $firmante['rect'];
        $w = round($x2 - $x1, 2); $h = round($y2 - $y1, 2);

        if ($tieneQr) {
            // QR a la izquierda; a la derecha: dos lineas pequenas y el
            // nombre en negrita grande (como el sello de FirmaEC).
            $qrLado = min($h - 6.0, 74.0);
            $qx = 3.0;
            $qy = ($h - $qrLado) / 2;
            $tx = $qx + $qrLado + 6.0;
            $anchoTexto = $w - $tx - 3.0;

            $nombre = mb_strtoupper($firmante['nombre']);
            $lineasNombre = [];
            $fsNombre = 11.0;
            foreach ([11.0, 9.5, 8.0, 6.5] as $fs) {
                $porLinea = max(4, (int)floor($anchoTexto / (0.6 * $fs)));
                $lineasNombre = $this->partirTexto($nombre, $porLinea);
                $maxLineas = (int)floor(($h - 26) / ($fs + 1.5));
                if (count($lineasNombre) <= max(1, $maxLineas)) { $fsNombre = $fs; break; }
            }

            $stream = "q\n";
            $stream .= sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Im1 Do Q\n", $qrLado, $qrLado, $qx, $qy);
            $stream .= "BT\n0 0 0 rg\n";
            $stream .= sprintf("/F1 7 Tf\n1 0 0 1 %.2f %.2f Tm\n(%s) Tj\n",
                $tx, $h - 12, $this->textoPdf('Validar únicamente en FirmaEC.'));
            $stream .= sprintf("1 0 0 1 %.2f %.2f Tm\n(%s) Tj\n",
                $tx, $h - 21, $this->textoPdf('Firmado electrónicamente por:'));
            $ty = $h - 24 - $fsNombre;
            foreach ($lineasNombre as $ln) {
                $stream .= sprintf("/F2 %.1f Tf\n1 0 0 1 %.2f %.2f Tm\n(%s) Tj\n",
                    $fsNombre, $tx, $ty, $this->textoPdf($ln));
                $ty -= $fsNombre + 1.5;
            }
            $stream .= "ET\nQ";
        } else {
            // respaldo sin QR: texto simple
            $lineas = [
                'Firmado electronicamente por:',
                mb_strtoupper($firmante['nombre']),
                'C.C. ' . $firmante['cedula'],
                'Fecha: ' . date('Y-m-d H:i:s'),
            ];
            $stream = "q\nBT\n0 0 0 rg\n";
            $ty = $h - 12;
            foreach ($lineas as $i => $l) {
                $f = ($i === 1) ? '/F2 9' : '/F1 8';
                $stream .= sprintf("%s Tf\n1 0 0 1 %.2f %.2f Tm\n(%s) Tj\n", $f, 4, $ty, $this->textoPdf($l));
                $ty -= 12;
            }
            $stream .= "ET\nQ";
        }

        $recursos = "/Font << /F1 $objFontA 0 R /F2 $objFontB 0 R >>"
            . ($tieneQr ? " /XObject << /Im1 $objImg 0 R >>" : '');
        $add($objAp,
            "<< /Type /XObject /Subtype /Form /FormType 1 /BBox [0 0 $w $h]\n" .
            "/Resources << $recursos >>\n" .
            "/Length " . strlen($stream) . " >>\nstream\n$stream\nendstream");

        // --- campo de firma + widget ------------------------------------
        $nombreCampo = 'Firma' . (count($state['fields']) + 1);
        $rect = sprintf('[%.2f %.2f %.2f %.2f]', $x1, $y1, $x2, $y2);
        $add($objField,
            "<< /Type /Annot /Subtype /Widget /FT /Sig\n" .
            "/T ($nombreCampo) /V $objSig 0 R\n" .
            "/Rect $rect /F 132 /P $objPage 0 R\n" .
            "/AP << /N $objAp 0 R >> >>");

        // --- diccionario /Sig con placeholder ---------------------------
        $hexLen = self::RESERVA_FIRMA * 2;
        $brPH = '/ByteRange [0 ' .
            str_repeat('0', self::ANCHO_BYTERANGE) . ' ' .
            str_repeat('0', self::ANCHO_BYTERANGE) . ' ' .
            str_repeat('0', self::ANCHO_BYTERANGE) . ']';
        $sigBody =
            "<< /Type /Sig /Filter /Adobe.PPKLite /SubFilter /adbe.pkcs7.detached\n" .
            $brPH . "\n" .
            "/Contents <" . str_repeat('0', $hexLen) . ">\n" .
            "/Name (" . $this->textoPdf($firmante['nombre']) . ")\n" .
            "/M (D:" . gmdate('YmdHis') . "+00'00')\n>>";
        $add($objSig, $sigBody);

        // --- AcroForm (version nueva con el campo agregado) -------------
        $fields = $state['fields'];
        $fields[] = $objField;
        $refs = implode(' ', array_map(fn($f) => "$f 0 R", $fields));
        $add($objAcro, "<< /Fields [$refs] /SigFlags 3 >>");

        // --- pagina (redefinida con el nuevo widget) --------------------
        $annots = $state['annots'];
        $annots[] = $objField;
        $refsA = implode(' ', array_map(fn($a) => "$a 0 R", $annots));
        $pageDict = $this->insertarEnDict($state['pageDict'], '/Annots', "[$refsA]");
        $add($objPage, $pageDict);

        // --- Root (solo la primera vez: engancha el AcroForm) -----------
        // En firmas siguientes tambien se redefine porque /AcroForm cambia de objeto.
        $rootDict = $this->insertarEnDict($state['rootDictOriginal'], '/AcroForm', "$objAcro 0 R");
        $add($objRoot, $rootDict);

        // --- xref del update y trailer ----------------------------------
        $xrefPos = $base + strlen($update);
        ksort($offsets);
        $xref = "xref\n";
        foreach ($offsets as $num => $off) {
            $xref .= "$num 1\n" . sprintf("%010d 00000 n \n", $off);
        }
        $size = max(array_keys($offsets)) + 1;
        if ($size < $state['size']) { $size = $state['size']; }
        $trailer = "trailer\n<< /Size $size /Root $objRoot 0 R /Prev {$state['startxref']}";
        if (!empty($state['infoRef']))  { $trailer .= " /Info {$state['infoRef']}"; }
        if (!empty($state['idArray'])) { $trailer .= " /ID {$state['idArray']}"; }
        $trailer .= " >>\nstartxref\n$xrefPos\n%%EOF\n";
        $update .= $xref . $trailer;

        $prep = $pdf . $update;

        // --- localizar el hueco de /Contents y fijar /ByteRange ---------
        $sigAbs = $offsets[$objSig];
        $holeStart = strpos($prep, '/Contents <', $sigAbs);
        if ($holeStart === false) { throw new RuntimeException('Placeholder no encontrado.'); }
        $holeStart = $holeStart + strlen('/Contents <');     // primer caracter hex
        $holeEnd   = $holeStart + $hexLen;                   // caracter '>' (exclusivo)

        $total = strlen($prep);
        $a = $holeStart - 1;              // hasta el '<' (incluido el '<'? no: ByteRange cubre [0,a) y [b,...])
        // ByteRange: parte1 = [0, holeStart-1)  -> incluye hasta el caracter '<'
        //            parte2 = [holeEnd+1, fin)  -> desde despues de '>'
        $br1 = $holeStart - 1;            // longitud de la parte 1 (termina antes de '<'+1 ... ver nota)
        // Convencion estandar: el hueco EXCLUYE los delimitadores < > del rango firmado:
        //   [0, inicioHueco) y [finHueco, resto) donde inicioHueco apunta al '<'
        $inicioHueco = $holeStart - 1;    // offset del '<'
        $finHueco    = $holeEnd + 1;      // offset tras el '>'
        $lenResto    = $total - $finHueco;

        $brReal = sprintf('/ByteRange [0 %0' . self::ANCHO_BYTERANGE . 'd %0' . self::ANCHO_BYTERANGE . 'd %0' . self::ANCHO_BYTERANGE . 'd]',
            $inicioHueco, $finHueco, $lenResto);
        $posBR = strpos($prep, $brPH, $sigAbs);
        if ($posBR === false || strlen($brReal) !== strlen($brPH)) {
            throw new RuntimeException('No se pudo fijar ByteRange.');
        }
        $prep = substr_replace($prep, $brReal, $posBR, strlen($brPH));

        $bytesAFirmar = substr($prep, 0, $inicioHueco) . substr($prep, $finHueco);

        // --- guardar preparado + estado pendiente -----------------------
        $prepPath = $pdfPath . '.prep';
        file_put_contents($prepPath, $prep);

        $state['pendiente'] = [
            'holeStart'  => $inicioHueco + 1,  // primer char hex
            'holeLenHex' => $hexLen,
            'firmante'   => $firmante,
            'prepPath'   => $prepPath,
        ];
        $state['_candidato'] = [               // sera el estado oficial si completa
            'maxObj'   => $size - 1,
            'fields'   => $fields,
            'annots'   => $annots,
            'startxref'=> $xrefPos,
            'size'     => $size,
            'acroObj'  => $objAcro,
        ];
        file_put_contents($statePath, json_encode($state, JSON_PRETTY_PRINT));

        return [
            'prepPath' => $prepPath,
            'bytesAFirmar' => $bytesAFirmar,
            'holeStart' => $inicioHueco + 1,
            'holeLenHex' => $hexLen,
        ];
    }

    /**
     * Inserta la firma DER (hex) en el hueco y devuelve la ruta del PDF firmado.
     * NO verifica: la verificacion (seccion 4.4) es responsabilidad del llamador
     * ANTES de aceptar el archivo como nueva version oficial.
     */
    public function completar(string $statePath, string $firmaHex): string
    {
        $state = json_decode(file_get_contents($statePath), true);
        if (empty($state['pendiente'])) {
            throw new RuntimeException('No hay una firma en preparacion.');
        }
        $p = $state['pendiente'];
        $firmaHex = strtolower(preg_replace('/[^0-9a-fA-F]/', '', $firmaHex));
        if ($firmaHex === '' || strlen($firmaHex) > $p['holeLenHex']) {
            throw new RuntimeException('La firma no cabe en el espacio reservado (' .
                strlen($firmaHex) . ' > ' . $p['holeLenHex'] . ').');
        }
        $firmaHex = str_pad($firmaHex, $p['holeLenHex'], '0');   // relleno con ceros

        $pdf = file_get_contents($p['prepPath']);
        $pdf = substr_replace($pdf, $firmaHex, $p['holeStart'], $p['holeLenHex']);

        $outPath = $p['prepPath'] . '.signed';
        file_put_contents($outPath, $pdf);
        return $outPath;
    }

    /**
     * El llamador confirma que la verificacion paso: el PDF firmado pasa a ser
     * la version oficial y el estado del sidecar avanza.
     */
    public function aceptar(string $statePath, string $signedPath, string $pdfPath): void
    {
        $state = json_decode(file_get_contents($statePath), true);
        $cand = $state['_candidato'];
        $nuevo = [
            'rootObj' => $state['rootObj'],
            'pageObj' => $state['pageObj'],
            'pageDict' => $state['pageDict'],
            'rootDictOriginal' => $state['rootDictOriginal'],
            'infoRef' => $state['infoRef'] ?? null,
            'idArray' => $state['idArray'] ?? null,
            'maxObj' => $cand['maxObj'],
            'fields' => $cand['fields'],
            'annots' => $cand['annots'],
            'startxref' => $cand['startxref'],
            'size' => $cand['size'],
        ];
        rename($signedPath, $pdfPath);
        @unlink($state['pendiente']['prepPath']);
        file_put_contents($statePath, json_encode($nuevo, JSON_PRETTY_PRINT));
    }

    // ===================================================================
    //  Analisis del PDF original (DomPDF, xref clasica, sin firmas)
    // ===================================================================
    private function analizarPdfOriginal(string $pdf): array
    {
        // startxref final
        if (!preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/s', substr($pdf, -200), $m)) {
            throw new RuntimeException('No se encontro startxref.');
        }
        $startxref = (int)$m[1];

        // trailer: /Size /Root /Info /ID
        $cola = substr($pdf, $startxref);
        if (!preg_match('/trailer\s*<<(.*?)>>\s*startxref/s', $cola, $m)) {
            throw new RuntimeException('No se encontro el trailer.');
        }
        $tr = $m[1];
        if (!preg_match('/\/Size\s+(\d+)/', $tr, $m)) { throw new RuntimeException('Trailer sin /Size.'); }
        $size = (int)$m[1];
        if (!preg_match('/\/Root\s+(\d+)\s+(\d+)\s+R/', $tr, $m)) { throw new RuntimeException('Trailer sin /Root.'); }
        $rootObj = (int)$m[1];
        $infoRef = preg_match('/\/Info\s+(\d+\s+\d+\s+R)/', $tr, $m) ? $m[1] : null;
        $idArray = preg_match('/\/ID\s*(\[.*?\])/s', $tr, $m) ? trim($m[1]) : null;

        // dict del Root
        $rootDict = $this->extraerObjeto($pdf, $rootObj);

        // primera pagina: Root -> /Pages -> /Kids[0]
        if (!preg_match('/\/Pages\s+(\d+)\s+\d+\s+R/', $rootDict, $m)) {
            throw new RuntimeException('Root sin /Pages.');
        }
        $pagesDict = $this->extraerObjeto($pdf, (int)$m[1]);
        if (!preg_match('/\/Kids\s*\[(.*?)\]/s', $pagesDict, $m)
            || !preg_match_all('/(\d+)\s+\d+\s+R/', $m[1], $mk)) {
            throw new RuntimeException('Pages sin /Kids.');
        }
        $kids = array_map('intval', $mk[1]);
        // Los recuadros de firma de las actas estan en la ULTIMA pagina.
        $pageObj = end($kids);
        $pageDict = $this->extraerObjeto($pdf, $pageObj);

        // annots existentes (DomPDF los escribe inline si hay links)
        $annots = [];
        if (preg_match('/\/Annots\s*\[(.*?)\]/s', $pageDict, $m)) {
            if (preg_match_all('/(\d+)\s+\d+\s+R/', $m[1], $mm)) {
                $annots = array_map('intval', $mm[1]);
            }
            // quitar /Annots del dict: lo re-insertamos nosotros
            $pageDict = preg_replace('/\/Annots\s*\[.*?\]/s', '', $pageDict, 1);
        }

        return [
            'rootObj' => $rootObj,
            'rootDictOriginal' => $rootDict,
            'pageObj' => $pageObj,
            'pageDict' => $pageDict,
            'annots' => $annots,
            'fields' => [],
            'maxObj' => $size - 1,
            'size' => $size,
            'startxref' => $startxref,
            'infoRef' => $infoRef,
            'idArray' => $idArray,
        ];
    }

    /** Devuelve el contenido (dict) de "N 0 obj ... endobj" buscando via xref o secuencial. */
    private function extraerObjeto(string $pdf, int $num): string
    {
        if (!preg_match('/(?<![0-9])' . $num . '\s+0\s+obj\b(.*?)endobj/s', $pdf, $m)) {
            throw new RuntimeException("Objeto $num no encontrado.");
        }
        return trim($m[1]);
    }

    /** Inserta (o reemplaza) una clave en el nivel superior de un dict << ... >>. */
    private function insertarEnDict(string $dict, string $clave, string $valor): string
    {
        // si ya existe la clave con una referencia simple, reemplazarla
        $pat = '/' . preg_quote($clave, '/') . '\s+\d+\s+\d+\s+R/';
        if (preg_match($pat, $dict)) {
            return preg_replace($pat, "$clave $valor", $dict, 1);
        }
        // insertar antes del ULTIMO '>>' (cierre del dict superior)
        $pos = strrpos($dict, '>>');
        if ($pos === false) { throw new RuntimeException('Dict invalido.'); }
        return substr($dict, 0, $pos) . " $clave $valor >>" . substr($dict, $pos + 2);
    }

    /** Parte un texto en lineas de a lo sumo $max caracteres, por palabras. */
    private function partirTexto(string $texto, int $max): array
    {
        $palabras = preg_split('/\s+/', trim($texto)) ?: [];
        $lineas = [];
        $actual = '';
        foreach ($palabras as $p) {
            while (mb_strlen($p) > $max) {           // palabra mas larga que la linea
                if ($actual !== '') { $lineas[] = $actual; $actual = ''; }
                $lineas[] = mb_substr($p, 0, $max);
                $p = mb_substr($p, $max);
            }
            $cand = $actual === '' ? $p : "$actual $p";
            if (mb_strlen($cand) <= $max) {
                $actual = $cand;
            } else {
                $lineas[] = $actual;
                $actual = $p;
            }
        }
        if ($actual !== '') { $lineas[] = $actual; }
        return $lineas ?: [''];
    }

    /** Texto a literal PDF: WinAnsi (tildes) + escape de ( ) \ */
    private function textoPdf(string $utf8): string
    {
        $win = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $utf8);
        if ($win === false) { $win = preg_replace('/[^\x20-\x7E]/', '?', $utf8); }
        return strtr($win, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => ' ', "\n" => ' ']);
    }
}

<?php
// CLI script: split & replace placeholder BAA/BAST dari template DOCX
// Usage: php process_bast.php <template> <baa_output> <bast_output> <json_file>

if ($argc < 5) {
    fprintf(STDERR, "Usage: php process_bast.php <template.docx> <baa.docx> <bast.docx> <json_file>\n");
    exit(1);
}

$templatePath = $argv[1];
$baaOutput    = $argv[2];
$bastOutput   = $argv[3];
$jsonFile     = $argv[4];

if (!file_exists($templatePath)) {
    fprintf(STDERR, "Template not found: %s\n", $templatePath);
    exit(1);
}

if (!file_exists($jsonFile)) {
    fprintf(STDERR, "JSON file not found: %s\n", $jsonFile);
    exit(1);
}

$r = json_decode(file_get_contents($jsonFile), true);
if (!is_array($r)) {
    fprintf(STDERR, "Invalid JSON in file\n");
    exit(1);
}

// Load template
$zip = new ZipArchive();
if ($zip->open($templatePath) !== true) {
    fprintf(STDERR, "Cannot open DOCX\n");
    exit(1);
}

$documentXml = $zip->getFromName('word/document.xml');
if ($documentXml === false) {
    fprintf(STDERR, "Missing word/document.xml\n");
    exit(1);
}

// Collect all other files
$otherFiles = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if ($name !== 'word/document.xml') {
        $otherFiles[$name] = $zip->getFromIndex($i);
    }
}
$zip->close();

// === REPLACE PLACEHOLDERS ===
$documentXml = replacePlaceholders($documentXml, $r);

// === SPLIT: find BERITA ACARA SERAH TERIMA marker ===
$bastSearch = 'BERITA ACARA SERAH TERIMA';
$bastPos = strpos($documentXml, $bastSearch);

if ($bastPos === false) {
    fprintf(STDERR, "Warning: BAST marker not found\n");
    writeDocx($baaOutput, $documentXml, $otherFiles);
    writeDocx($bastOutput, $documentXml, $otherFiles);
    exit(0);
}

$searchArea = substr($documentXml, 0, $bastPos);
$pStart = strrpos($searchArea, '<w:p ');
if ($pStart === false) {
    $pStart = strrpos($searchArea, '<w:p>');
}

$pEnd = strpos($documentXml, '</w:p>', $bastPos);
if ($pEnd !== false) {
    $pEnd += strlen('</w:p>');
}

$bodyOpen = strpos($documentXml, '<w:body>');

if ($pStart !== false && $pEnd !== false && $bodyOpen !== false) {
    // Extract section properties (w:sectPr) dari original - ini yang pegang referensi header (logo)
    preg_match_all('/<w:sectPr[^>]*>.*?<\/w:sectPr>|<w:sectPr[^>]*\/>/s', $documentXml, $sectMatches);
    $sectPrBlock = '';
    foreach ($sectMatches[0] as $sp) {
        $sectPrBlock .= $sp;
    }

    $bodyContent = substr($documentXml, $bodyOpen + 8);
    $bodyContentBaa = substr($bodyContent, 0, $pStart - $bodyOpen - 8);
    
    // Bersihkan trailing paragraphs BAA yang hanya berisi page-break atau kosong
    // (mencegah halaman kosong ke-2)
    $bodyContentBaa = preg_replace('/<w:p[^>]*>(\s|<[^>]+>)*(<w:r[^>]*>(\s|<[^>]+>)*<w:br[^>]*w:type="page"[^>]*\/>(\s|<[^>]+>)*<\/w:r>)?(\s|<[^>]+>)*<\/w:p>(\s)*$/i', '', $bodyContentBaa);
    // Buang sisa paragraf kosong di akhir
    while (preg_match('/<w:p[^>]*>(\s|<[^>]+>)*<\/w:p>(\s)*$/i', $bodyContentBaa, $m)) {
        $bodyContentBaa = preg_replace('/<w:p[^>]*>(\s|<[^>]+>)*<\/w:p>(\s)*$/i', '', $bodyContentBaa);
        if (strlen($m[0]) === 0) break;
    }
    
    $baaXml = substr($documentXml, 0, $bodyOpen + 8) 
            . $bodyContentBaa 
            . $sectPrBlock
            . '</w:body></w:document>';
    
    $bastBodyContent = substr($bodyContent, $pStart - $bodyOpen - 8);
    $bastXml = substr($documentXml, 0, $bodyOpen + 8)
             . $bastBodyContent;
    
    if (strpos($bastXml, '</w:body>') === false) {
        $bastXml .= '</w:body></w:document>';
    } else {
        // Pastikan BAST juga punya sectPr (untuk konsistensi header)
        if ($sectPrBlock && strpos($bastXml, '<w:sectPr') === false) {
            $bastXml = str_replace('</w:body>', $sectPrBlock . '</w:body>', $bastXml);
        }
    }

    writeDocx($baaOutput, $baaXml, $otherFiles);
    writeDocx($bastOutput, $bastXml, $otherFiles);
} else {
    writeDocx($baaOutput, $documentXml, $otherFiles);
    writeDocx($bastOutput, $documentXml, $otherFiles);
}

fprintf(STDOUT, "OK: BAA=%s BAST=%s\n", basename($baaOutput), basename($bastOutput));
exit(0);

// === FUNCTIONS ===

function replacePlaceholders(string $xml, array $r): string
{
    $valHari    = $r['hari'] ?? '';
    $valTglH    = $r['tanggal_huruf'] ?? '';
    $valBlnH    = $r['bulan_huruf'] ?? '';
    $valThnH    = $r['tahun_huruf'] ?? '';
    $valAlamat  = $r['alamat_instalasi'] ?? '';
    $valBw      = $r['bandwidth'] ?? '';
    $valTgl     = $r['tanggal'] ?? '';
    $valJenis   = $r['jenis_layanan'] ?? '';
    $valMrc     = $r['mrc_provider'] ?? '';
    $valOtc     = $r['otc_provider'] ?? '';
    $valPo      = $r['po_number'] ?? '';
    $valKntrkThn = $r['kontrak_tahun'] ?? '';
    $valKntrkStr = $r['kontrak_start'] ?? '';
    $valKntrkEnd = $r['kontrak_end'] ?? '';
    $valPpn     = $r['ppn'] ?? '';
    $valTotal   = $r['total'] ?? '';

    // 1. Fix split words / artifacts (word-level split)
    $xml = str_replace(['bul</w:t><w:t>an', 'Jaringa</w:t><w:t>n'], ['bulan', 'Jaringan'], $xml);
    
    // 2. Helper: separator pattern (whitespace OR XML tag)
    $sep = '(\s|<[^>]+>)*';
    
    // 3. Date dd/mm/yyyy (handles splits and case-insensitive)
    $xml = preg_replace('/dd' . $sep . '\/' . $sep . '(mm)?' . $sep . '\/' . $sep . 'yyyy/ui', $valTgl, $xml);
    
    // 4. Month (Bulan) - Match placeholder [bulan ditulis huruf] (dengan toleransi tag XML di antara kata)
    $xml = preg_replace('/\[' . $sep . 'bulan' . $sep . 'ditulis' . $sep . 'huruf' . $sep . '\]/ui', $valBlnH, $xml);

    // 4b. Year (Tahun) - Match placeholder [tahun ditulis huruf] (dengan toleransi tag XML di antara kata)
    $xml = preg_replace('/\[' . $sep . 'tahun' . $sep . 'ditulis' . $sep . 'huruf' . $sep . '\]/ui', $valThnH, $xml);
    $xml = preg_replace('/tahun' . $sep . 'Oktober/ui', $valThnH, $xml);
    // Fallback: kalau placeholder tidak pakai bracket, ganti full phrase dengan "tahun " + tahun
    $xml = preg_replace('/tahun' . $sep . 'tahun' . $sep . 'ditulis' . $sep . 'huruf/ui', 'tahun ' . $valThnH, $xml);
    $xml = preg_replace('/tahun' . $sep . 'bulan' . $sep . 'ditulis' . $sep . 'huruf/ui', 'tahun ' . $valThnH, $xml);

    // 5. Costs (Biaya Instalasi, MRC, OTC, PPN, Total)
    $xml = preg_replace('/biaya' . $sep . 'berdasarkan' . $sep . 'MRC' . $sep . 'provider/ui', $valMrc, $xml);
    $xml = preg_replace('/biaya' . $sep . 'berdasarkan' . $sep . 'OTC' . $sep . 'provider/ui', $valOtc, $xml);
    $xml = preg_replace('/biaya' . $sep . 'MRC' . $sep . 'provider' . $sep . '\*?' . $sep . '11%/ui', $valPpn, $xml);
    $xml = preg_replace('/biaya' . $sep . 'MRC' . $sep . 'provider' . $sep . '\+?' . $sep . 'PPN11%/ui', $valTotal, $xml);

    // 6. Contract Period (Jangka Waktu)
    $xml = preg_replace('/Jumlah' . $sep . 'Kontrak' . $sep . 'dijadikan' . $sep . 'tahun/ui', $valKntrkThn, $xml);
    $xml = preg_replace('/start' . $sep . 'kontrak/ui', $valKntrkStr, $xml);
    $xml = preg_replace('/akhir' . $sep . 'kontral?/ui', $valKntrkEnd, $xml);

    // 7. Bandwidth & Installation Address
    $xml = preg_replace('/bandwith' . $sep . 'berdasarkan' . $sep . 'detil' . $sep . 'order/ui', $valBw, $xml);
    $xml = preg_replace('/alamat' . $sep . 'berdasarkan' . $sep . 'detil' . $sep . 'order/ui', $valAlamat, $xml);

    // 7b. BAA New Placeholders: "Nama Client" & "Nomor PO berdasarkan PO dari Provider"
    // Nama Client -> nama_pelanggan (order->client->name)
    $xml = preg_replace('/Nama' . $sep . 'Client/ui', $r['nama_pelanggan'] ?? '', $xml);
    // Nomor PO berdasarkan PO dari Provider -> po_number (order->po_provider_number)
    $xml = preg_replace('/Nomor' . $sep . 'PO' . $sep . 'berdasarkan' . $sep . 'PO' . $sep . 'dari' . $sep . 'Provider/ui', $valPo, $xml);
    $xml = preg_replace('/Nomor' . $sep . 'PO' . $sep . 'berdasarkan/ui', $valPo, $xml);

    // 8. Standard mapping for clean text segments
    $replacements = [
        '[hari]'                             => $valHari,
        '[tanggal ditulis huruf]'            => $valTglH,
        '[bulan ditulis huruf]'              => $valBlnH,
        '[tahun ditulis huruf]'              => $valThnH,
        'alamat berdasarkan detil order'     => $valAlamat,
        'bandiwth'                           => $valBw,
        'bandwith berdasarkan detil order'   => $valBw,
        'Jenis Layanan yang di pakai'        => $valJenis,
        'Nomor BAA'                          => $r['nomor_baa'] ?? '',
        'Nomor BAST'                         => $r['nomor_bast'] ?? '',
    ];

    foreach ($replacements as $search => $replace) {
        $xml = str_replace($search, $replace, $xml);
    }

    // 9. Final Cleanup for static template text
    $xml = str_replace('Oktober', $valBlnH, $xml); 
    $xml = str_replace('Element Ubud', '', $xml);
    // Fallback cleanup kalau masih ada sisa teks PO
    $xml = str_replace('Nomor PO berdasarkan PO dari Provider', $valPo, $xml);
    $xml = str_replace('Nomor PO berdasarkan', $valPo, $xml);

    // 10. Customer info (handles split names and address)
    $xml = preg_replace('/PT\.Nettocyber(<[^>]+>)*\s*Indonesia/ui', $r['nama_pelanggan'] ?? '', $xml);
    $xml = str_replace('PT.Nettocyber Indonesia', $r['nama_pelanggan'] ?? '', $xml);
    $xml = str_replace('Rajawali Place 6th Flor, Jalan HR Rasuna Kav.B/4 Setiabudi,     Jakarta Selatan 12910', $r['alamat_pelanggan'] ?? '', $xml);

    return $xml;
}

function writeDocx(string $outputPath, string $documentXml, array $otherFiles): void
{
    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $zip = new ZipArchive();
    if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        fprintf(STDERR, "Cannot create: %s\n", $outputPath);
        exit(1);
    }

    if (isset($otherFiles['[Content_Types].xml'])) {
        $zip->addFromString('[Content_Types].xml', $otherFiles['[Content_Types].xml']);
    }

    if (isset($otherFiles['_rels/.rels'])) {
        $zip->addFromString('_rels/.rels', $otherFiles['_rels/.rels']);
    }

    $zip->addFromString('word/document.xml', $documentXml);

    foreach ($otherFiles as $name => $content) {
        if ($name !== '[Content_Types].xml' && $name !== '_rels/.rels' && $name !== 'word/document.xml') {
            $zip->addFromString($name, $content);
        }
    }

    $zip->close();
}
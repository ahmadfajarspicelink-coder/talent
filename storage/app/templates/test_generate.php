<?php
$jsonData = [
    'nomor_baa'       => 'BAA-001',
    'nomor_bast'      => 'BAST-001',
    'po_number'       => 'PO-2026',
    'nama_pelanggan'  => 'PT. Test',
    'alamat_pelanggan'=> 'Jakarta',
    'alamat_instalasi'=> 'Bali',
    'bandwidth'       => '100',
    'mrc_provider'    => 'Rp 5.000.000',
    'otc_provider'    => 'Rp 1.000.000',
    'ppn'             => 'Rp 550.000',
    'total'           => 'Rp 5.550.000',
    'jenis_layanan'   => 'Dedicated',
    'kontrak_tahun'   => '1',
    'kontrak_start'   => '14/06/2026',
    'kontrak_end'     => '14/06/2027',
    'hari'            => 'Sabtu',
    'tanggal_huruf'   => '14',
    'bulan_huruf'     => 'Juni',
    'tahun_huruf'     => 'dua ribu dua puluh enam',
    'tanggal'         => '14/06/2026',
];

// Write JSON to temp file
$jsonFile = __DIR__ . '/test_replacements.json';
file_put_contents($jsonFile, json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$template = __DIR__ . '/baa_bast_template.docx';
$baaOut   = __DIR__ . '/../public/bast-documents/test_baa.docx';
$bastOut  = __DIR__ . '/../public/bast-documents/test_bast.docx';

@mkdir(dirname($baaOut), 0755, true);

$cmd = sprintf(
    'php %s %s %s %s %s 2>&1',
    escapeshellarg(__DIR__ . '/process_bast.php'),
    escapeshellarg($template),
    escapeshellarg($baaOut),
    escapeshellarg($bastOut),
    escapeshellarg($jsonFile)
);

exec($cmd, $output, $exitCode);
echo "Exit: $exitCode\n";
echo implode("\n", $output) . "\n";
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BastGeneratorService
{
    private static array $hariMap = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    private static array $bulanMap = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Generate kedua dokumen (BAA & BAST) dari template DOCX.
     */
    public function generate(Order $order): array
    {
        $now = Carbon::now();
        $r = $this->buildReplacements($order, $now);

        $templatePath = base_path('storage/app/templates/baa_bast_template.docx');
        $dir = storage_path("app/public/bast-documents/{$order->id}");
        File::ensureDirectoryExists($dir);

        $timestamp = $now->format('YmdHis');
        $baaPath = "bast-documents/{$order->id}/BAA_{$order->id}_{$timestamp}.docx";
        $bastPath = "bast-documents/{$order->id}/BAST_{$order->id}_{$timestamp}.docx";

        $fullBaaPath = storage_path("app/public/{$baaPath}");
        $fullBastPath = storage_path("app/public/{$bastPath}");

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template tidak ditemukan: {$templatePath}");
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($templatePath) !== true) {
                throw new \RuntimeException("Gagal membuka template DOCX");
            }

            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                $zip->close();
                throw new \RuntimeException("Template DOCX tidak valid (missing document.xml)");
            }

            $otherFiles = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($name !== 'word/document.xml') {
                    $otherFiles[$name] = $zip->getFromIndex($i);
                }
            }
            $zip->close();

            // Replace placeholders (regex-based, toleransi split XML runs)
            $documentXml = $this->replacePlaceholders($documentXml, $r);

            // Header & footer juga punya placeholder
            foreach (['word/header1.xml', 'word/footer1.xml'] as $partName) {
                if (!isset($otherFiles[$partName])) {
                    continue;
                }
                $otherFiles[$partName] = $this->replacePlaceholders($otherFiles[$partName], $r);
            }

            // Split jadi BAA & BAST
            [$baaXml, $bastXml] = $this->splitBaaBast($documentXml);

            $this->writeDocx($fullBaaPath, $baaXml, $otherFiles);
            $this->writeDocx($fullBastPath, $bastXml, $otherFiles);

        } catch (\Throwable $e) {
            Log::error('BAST generation failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Gagal generate dokumen BAA/BAST: ' . $e->getMessage());
        }

        return [
            ['type' => 'BAA', 'path' => $baaPath],
            ['type' => 'BAST', 'path' => $bastPath],
        ];
    }

    private function buildReplacements(Order $order, Carbon $now): array
    {
        $mrc = (int) ($order->provider_mrc ?? 0);
        $otc = (int) ($order->provider_otc ?? 0);
        $ppn = (int) round($mrc * 0.11);
        $total = $mrc + $ppn;

        $kontrakTahun = $order->contract_months ? round($order->contract_months / 12, 1) : '—';
        $kontrakStart = $order->contract_start_date
            ? Carbon::parse($order->contract_start_date)->format('d/m/Y')
            : '—';
        $kontrakEnd = $order->contract_end_date
            ? Carbon::parse($order->contract_end_date)->format('d/m/Y')
            : '—';

        return [
            'nomor_baa'          => $order->baa_number ?? '—',
            'nomor_bast'         => $order->bast_number ?? '—',
            'po_number'          => $order->po_provider_number ?? '—',
            'nama_pelanggan'     => $order->client?->name ?? '—',
            'alamat_pelanggan'   => $order->client?->address ?? '—',
            'alamat_instalasi'   => $order->client?->address ?? '—',
            'bandwidth'          => (string) ($order->bandwidth ?? '—'),
            'mrc_provider'       => self::formatRupiah($mrc),
            'otc_provider'       => self::formatRupiah($otc),
            'ppn'                => self::formatRupiah($ppn),
            'total'              => self::formatRupiah($total),
            'jenis_layanan'      => $order->package?->name ?? '—',
            'kontrak_tahun'      => $kontrakTahun,
            'kontrak_start'      => $kontrakStart,
            'kontrak_end'        => $kontrakEnd,
            'hari'               => self::$hariMap[$now->format('l')] ?? $now->format('l'),
            'tanggal_huruf'      => self::terbilang((int) $now->format('j')),
            'bulan_huruf'        => self::$bulanMap[(int) $now->format('n')] ?? $now->format('F'),
            'tahun_huruf'        => self::terbilang((int) $now->format('Y')),
            'tanggal'            => $now->format('d/m/Y'),
        ];
    }

    /**
     * Replace placeholders menggunakan regex dengan separator pattern
     * yang handle Word's automatic text run splitting.
     * (Sama persis dengan logic process_bast.php yang sudah terbukti working.)
     */
    private function replacePlaceholders(string $xml, array $r): string
    {
        $valHari     = $r['hari'] ?? '';
        $valTglH     = $r['tanggal_huruf'] ?? '';
        $valBlnH     = $r['bulan_huruf'] ?? '';
        $valThnH     = $r['tahun_huruf'] ?? '';
        $valAlamat   = $r['alamat_instalasi'] ?? '';
        $valBw       = $r['bandwidth'] ?? '';
        $valTgl      = $r['tanggal'] ?? '';
        $valJenis    = $r['jenis_layanan'] ?? '';
        $valMrc      = $r['mrc_provider'] ?? '';
        $valOtc      = $r['otc_provider'] ?? '';
        $valPo       = $r['po_number'] ?? '';
        $valKntrkThn = $r['kontrak_tahun'] ?? '';
        $valKntrkStr = $r['kontrak_start'] ?? '';
        $valKntrkEnd = $r['kontrak_end'] ?? '';
        $valPpn      = $r['ppn'] ?? '';
        $valTotal    = $r['total'] ?? '';

        // 1. Fix split words (word-level split oleh Word)
        $xml = str_replace(['bul</w:t><w:t>an', 'Jaringa</w:t><w:t>n'], ['bulan', 'Jaringan'], $xml);

        // 2. Separator: whitespace ATAU XML tag
        $sep = '(\s|<[^>]+>)*';

        // 3. Date dd/mm/yyyy
        $xml = preg_replace('/dd' . $sep . '\/' . $sep . '(mm)?' . $sep . '\/' . $sep . 'yyyy/ui', $valTgl, $xml);

        // 4. Bulan
        $xml = preg_replace('/\[' . $sep . 'bulan' . $sep . 'ditulis' . $sep . 'huruf' . $sep . '\]/ui', $valBlnH, $xml);

        // 4b. Tahun
        $xml = preg_replace('/\[' . $sep . 'tahun' . $sep . 'ditulis' . $sep . 'huruf' . $sep . '\]/ui', $valThnH, $xml);
        $xml = preg_replace('/tahun' . $sep . 'Oktober/ui', $valThnH, $xml);
        $xml = preg_replace('/tahun' . $sep . 'tahun' . $sep . 'ditulis' . $sep . 'huruf/ui', 'tahun ' . $valThnH, $xml);
        $xml = preg_replace('/tahun' . $sep . 'bulan' . $sep . 'ditulis' . $sep . 'huruf/ui', 'tahun ' . $valThnH, $xml);

        // 5. Biaya (MRC, OTC, PPN, Total)
        $xml = preg_replace('/biaya' . $sep . 'berdasarkan' . $sep . 'MRC' . $sep . 'provider/ui', $valMrc, $xml);
        $xml = preg_replace('/biaya' . $sep . 'berdasarkan' . $sep . 'OTC' . $sep . 'provider/ui', $valOtc, $xml);
        $xml = preg_replace('/biaya' . $sep . 'MRC' . $sep . 'provider' . $sep . '\*?' . $sep . '11%/ui', $valPpn, $xml);
        $xml = preg_replace('/biaya' . $sep . 'MRC' . $sep . 'provider' . $sep . '\+?' . $sep . 'PPN11%/ui', $valTotal, $xml);

        // 6. Kontrak
        $xml = preg_replace('/Jumlah' . $sep . 'Kontrak' . $sep . 'dijadikan' . $sep . 'tahun/ui', $valKntrkThn, $xml);
        $xml = preg_replace('/start' . $sep . 'kontrak/ui', $valKntrkStr, $xml);
        $xml = preg_replace('/akhir' . $sep . 'kontral?/ui', $valKntrkEnd, $xml);

        // 7. Bandwidth & Alamat
        $xml = preg_replace('/bandwith' . $sep . 'berdasarkan' . $sep . 'detil' . $sep . 'order/ui', $valBw, $xml);
        $xml = preg_replace('/alamat' . $sep . 'berdasarkan' . $sep . 'detil' . $sep . 'order/ui', $valAlamat, $xml);

        // 7b. Nama Client & Nomor PO
        $xml = preg_replace('/Nama' . $sep . 'Client/ui', $r['nama_pelanggan'] ?? '', $xml);
        $xml = preg_replace('/Nomor' . $sep . 'PO' . $sep . 'berdasarkan' . $sep . 'PO' . $sep . 'dari' . $sep . 'Provider/ui', $valPo, $xml);
        $xml = preg_replace('/Nomor' . $sep . 'PO' . $sep . 'berdasarkan/ui', $valPo, $xml);

        // 8. Standard mapping (clean text segments)
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

        // 9. Final cleanup
        $xml = str_replace('Oktober', $valBlnH, $xml);
        $xml = str_replace('Element Ubud', '', $xml);
        $xml = str_replace('Nomor PO berdasarkan PO dari Provider', $valPo, $xml);
        $xml = str_replace('Nomor PO berdasarkan', $valPo, $xml);

        // 10. Customer info (handles split names)
        $xml = preg_replace('/PT\.Nettocyber(<[^>]+>)*\s*Indonesia/ui', $r['nama_pelanggan'] ?? '', $xml);
        $xml = str_replace('PT.Nettocyber Indonesia', $r['nama_pelanggan'] ?? '', $xml);
        $xml = str_replace('Rajawali Place 6th Flor, Jalan HR Rasuna Kav.B/4 Setiabudi,     Jakarta Selatan 12910', $r['alamat_pelanggan'] ?? '', $xml);

        return $xml;
    }

    private function splitBaaBast(string $documentXml): array
    {
        $bastSearch = 'BERITA ACARA SERAH TERIMA';
        $bastPos = strpos($documentXml, $bastSearch);

        if ($bastPos === false) {
            return [$documentXml, $documentXml];
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

        if ($pStart === false || $pEnd === false || $bodyOpen === false) {
            return [$documentXml, $documentXml];
        }

        // Extract sectPr (pegang referensi header/logo)
        preg_match_all('/<w:sectPr[^>]*>.*?<\/w:sectPr>|<w:sectPr[^>]*\/>/s', $documentXml, $sectMatches);
        $sectPrBlock = '';
        foreach ($sectMatches[0] as $sp) {
            $sectPrBlock .= $sp;
        }

        $bodyContent = substr($documentXml, $bodyOpen + strlen('<w:body>'));
        $bodyContentBaa = substr($bodyContent, 0, $pStart - $bodyOpen - strlen('<w:body>'));

        // Bersihkan trailing paragraphs BAA (page-break/kosong)
        $bodyContentBaa = preg_replace(
            '/<w:p[^>]*>(\s|<[^>]+>)*(<w:r[^>]*>(\s|<[^>]+>)*<w:br[^>]*w:type="page"[^>]*\/>(\s|<[^>]+>)*<\/w:r>)?(\s|<[^>]+>)*<\/w:p>(\s)*$/i',
            '',
            $bodyContentBaa
        );
        while (preg_match('/<w:p[^>]*>(\s|<[^>]+>)*<\/w:p>(\s)*$/i', $bodyContentBaa, $m)) {
            $bodyContentBaa = preg_replace('/<w:p[^>]*>(\s|<[^>]+>)*<\/w:p>(\s)*$/i', '', $bodyContentBaa);
            if (strlen($m[0]) === 0) {
                break;
            }
        }

        $baaXml = substr($documentXml, 0, $bodyOpen + strlen('<w:body>'))
                . $bodyContentBaa
                . $sectPrBlock
                . '</w:body></w:document>';

        $bastBodyContent = substr($bodyContent, $pStart - $bodyOpen - strlen('<w:body>'));
        $bastXml = substr($documentXml, 0, $bodyOpen + strlen('<w:body>'))
                 . $bastBodyContent;

        if (strpos($bastXml, '</w:body>') === false) {
            $bastXml .= '</w:body></w:document>';
        } else {
            // Pastikan BAST juga punya sectPr (konsistensi header)
            if ($sectPrBlock && strpos($bastXml, '<w:sectPr') === false) {
                $bastXml = str_replace('</w:body>', $sectPrBlock . '</w:body>', $bastXml);
            }
        }

        return [$baaXml, $bastXml];
    }

    private function writeDocx(string $outputPath, string $documentXml, array $otherFiles): void
    {
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Gagal membuat file: {$outputPath}");
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

    public static function formatRupiah(?int $value): string
    {
        if ($value === null) {
            return '—';
        }

        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    public static function terbilang(int $angka): string
    {
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];

        if ($angka < 10) {
            return $satuan[$angka];
        }
        if ($angka === 10) {
            return 'sepuluh';
        }
        if ($angka === 11) {
            return 'sebelas';
        }
        if ($angka < 20) {
            return $satuan[$angka % 10] . ' belas';
        }
        if ($angka < 100) {
            return $satuan[(int) ($angka / 10)] . ' puluh ' . $satuan[$angka % 10];
        }
        if ($angka < 200) {
            return 'seratus ' . self::terbilang($angka % 100);
        }
        if ($angka < 1000) {
            return $satuan[(int) ($angka / 100)] . ' ratus ' . self::terbilang($angka % 100);
        }
        if ($angka < 2000) {
            return 'seribu ' . self::terbilang($angka % 1000);
        }
        if ($angka < 10000) {
            return $satuan[(int) ($angka / 1000)] . ' ribu ' . self::terbilang($angka % 1000);
        }

        return (string) $angka;
    }
}
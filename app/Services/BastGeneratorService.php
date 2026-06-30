<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BastGeneratorService
{
    private const HARI_MAP = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];

    private const BULAN_MAP = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * XML separator pattern — matches whitespace or XML tags between words.
     */
    private const SEP = '(\s|<[^>]+>)*';

    /**
     * Generate kedua dokumen (BAA & BAST) dari template DOCX.
     *
     * @return array{type: string, path: string}[]
     */
    public function generate(Order $order): array
    {
        $now = Carbon::now();
        $replacements = $this->buildReplacements($order, $now);

        $templatePath = base_path('storage/app/templates/baa_bast_template.docx');
        $dir = storage_path("app/public/bast-documents/{$order->id}");
        File::ensureDirectoryExists($dir);

        $timestamp = $now->format('YmdHis');
        $baaPath  = "bast-documents/{$order->id}/BAA_{$order->id}_{$timestamp}.docx";
        $bastPath = "bast-documents/{$order->id}/BAST_{$order->id}_{$timestamp}.docx";

        $fullBaaPath  = storage_path("app/public/{$baaPath}");
        $fullBastPath = storage_path("app/public/{$bastPath}");

        if (! file_exists($templatePath)) {
            throw new \RuntimeException("Template tidak ditemukan: {$templatePath}");
        }

        try {
            ['documentXml' => $documentXml, 'otherFiles' => $otherFiles]
                = $this->extractDocx($templatePath);

            $documentXml = $this->replacePlaceholders($documentXml, $replacements);

            foreach (['word/header1.xml', 'word/footer1.xml'] as $partName) {
                if (isset($otherFiles[$partName])) {
                    $otherFiles[$partName] = $this->replacePlaceholders($otherFiles[$partName], $replacements);
                }
            }

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
            ['type' => 'BAA',  'path' => $baaPath],
            ['type' => 'BAST', 'path' => $bastPath],
        ];
    }

    /**
     * @return array{documentXml: string, otherFiles: array<string, string>}
     */
    private function extractDocx(string $templatePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($templatePath) !== true) {
            throw new \RuntimeException('Gagal membuka template DOCX');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new \RuntimeException('Template DOCX tidak valid (missing document.xml)');
        }

        $otherFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== 'word/document.xml') {
                $otherFiles[$name] = $zip->getFromIndex($i);
            }
        }

        $zip->close();

        return compact('documentXml', 'otherFiles');
    }

    /**
     * Build placeholder → value map.
     *
     * @return array<string, string>
     */
    private function buildReplacements(Order $order, Carbon $now): array
    {
        $mrc  = (int) ($order->provider_mrc ?? 0);
        $otc  = (int) ($order->provider_otc ?? 0);
        $ppn  = (int) round($mrc * 0.11);

        $contractMonths = $order->contract_months;
        $kontrakTahun = $contractMonths ? round($contractMonths / 12, 1) : '—';
        $kontrakStart = $order->contract_start_date
            ? Carbon::parse($order->contract_start_date)->format('d/m/Y') : '—';
        $kontrakEnd = $order->contract_end_date
            ? Carbon::parse($order->contract_end_date)->format('d/m/Y') : '—';

        return [
            'nomor_baa'        => $order->baa_number ?? '—',
            'nomor_bast'       => $order->bast_number ?? '—',
            'po_number'        => $order->po_provider_number ?? '—',
            'nama_pelanggan'   => $order->client?->name ?? '—',
            'alamat_pelanggan' => $order->client?->address ?? '—',
            'alamat_instalasi' => $order->client?->address ?? '—',
            'bandwidth'        => (string) ($order->bandwidth ?? '—'),
            'mrc_provider'     => self::formatRupiah($mrc),
            'otc_provider'     => self::formatRupiah($otc),
            'ppn'              => self::formatRupiah($ppn),
            'total'            => self::formatRupiah($mrc + $ppn),
            'jenis_layanan'    => $order->package?->name ?? '—',
            'kontrak_tahun'    => $kontrakTahun,
            'kontrak_start'    => $kontrakStart,
            'kontrak_end'      => $kontrakEnd,
            'hari'             => self::HARI_MAP[$now->format('l')] ?? $now->format('l'),
            'tanggal_huruf'    => self::terbilang((int) $now->format('j')),
            'bulan_huruf'      => self::BULAN_MAP[(int) $now->format('n')] ?? $now->format('F'),
            'tahun_huruf'      => self::terbilang((int) $now->format('Y')),
            'tanggal'          => $now->format('d/m/Y'),
        ];
    }

    /**
     * Replace placeholders using regex pattern that tolerates
     * Word's automatic text-run splitting.
     */
    private function replacePlaceholders(string $xml, array $r): string
    {
        $sep = self::SEP;

        // Fix known split words (Word XML run splitting)
        $xml = str_replace(
            ['bul</w:t><w:t>an', 'Jaringa</w:t><w:t>n'],
            ['bulan', 'Jaringan'],
            $xml,
        );

        // 1. Date dd/mm/yyyy
        $xml = preg_replace('/dd' . $sep . '\/' . $sep . '(mm)?' . $sep . '\/' . $sep . 'yyyy/ui', $r['tanggal'], $xml);

        // 2. Regex-based replacements: pattern → replacement key
        $regexPatterns = [
            '/\[' . $sep . 'bulan' . $sep . 'ditulis' . $sep . 'huruf' . $sep . '\]/ui'                 => 'bulan_huruf',
            '/\[' . $sep . 'tahun' . $sep . 'ditulis' . $sep . 'huruf' . $sep . '\]/ui'                 => 'tahun_huruf',
            '/tahun' . $sep . 'Oktober/ui'                                                               => 'tahun_huruf',
            '/tahun' . $sep . 'tahun' . $sep . 'ditulis' . $sep . 'huruf/ui'                             => 'tahun_huruf',
            '/tahun' . $sep . 'bulan' . $sep . 'ditulis' . $sep . 'huruf/ui'                             => 'tahun_huruf',
            '/biaya' . $sep . 'berdasarkan' . $sep . 'MRC' . $sep . 'provider/ui'                        => 'mrc_provider',
            '/biaya' . $sep . 'berdasarkan' . $sep . 'OTC' . $sep . 'provider/ui'                        => 'otc_provider',
            '/biaya' . $sep . 'MRC' . $sep . 'provider' . $sep . '\*?' . $sep . '11%/ui'                 => 'ppn',
            '/biaya' . $sep . 'MRC' . $sep . 'provider' . $sep . '\+?' . $sep . 'PPN11%/ui'             => 'total',
            '/Jumlah' . $sep . 'Kontrak' . $sep . 'dijadikan' . $sep . 'tahun/ui'                        => 'kontrak_tahun',
            '/start' . $sep . 'kontrak/ui'                                                               => 'kontrak_start',
            '/akhir' . $sep . 'kontral?/ui'                                                              => 'kontrak_end',
            '/bandwith' . $sep . 'berdasarkan' . $sep . 'detil' . $sep . 'order/ui'                      => 'bandwidth',
            '/alamat' . $sep . 'berdasarkan' . $sep . 'detil' . $sep . 'order/ui'                        => 'alamat_instalasi',
            '/Nama' . $sep . 'Client/ui'                                                                 => 'nama_pelanggan',
            '/Nomor' . $sep . 'PO' . $sep . 'berdasarkan' . $sep . 'PO' . $sep . 'dari' . $sep . 'Provider/ui' => 'po_number',
            '/Nomor' . $sep . 'PO' . $sep . 'berdasarkan/ui'                                            => 'po_number',
            '/PT\.Nettocyber(<[^>]+>)*\s*Indonesia/ui'                                                   => 'nama_pelanggan',
        ];

        foreach ($regexPatterns as $pattern => $key) {
            $xml = preg_replace($pattern, $r[$key], $xml);
        }

        // 3. Simple string replacements: search → replacement key
        $stringMap = [
            '[hari]'                            => 'hari',
            '[tanggal ditulis huruf]'           => 'tanggal_huruf',
            '[bulan ditulis huruf]'             => 'bulan_huruf',
            '[tahun ditulis huruf]'             => 'tahun_huruf',
            'alamat berdasarkan detil order'    => 'alamat_instalasi',
            'bandiwth'                          => 'bandwidth',
            'bandwith berdasarkan detil order'  => 'bandwidth',
            'Jenis Layanan yang di pakai'       => 'jenis_layanan',
            'Nomor BAA'                         => 'nomor_baa',
            'Nomor BAST'                        => 'nomor_bast',
            'PT.Nettocyber Indonesia'           => 'nama_pelanggan',
            'Oktober'                           => 'bulan_huruf',
            'Element Ubud'                      => '',
            'Nomor PO berdasarkan PO dari Provider' => 'po_number',
            'Nomor PO berdasarkan'              => 'po_number',
        ];

        foreach ($stringMap as $search => $key) {
            $xml = str_replace($search, $r[$key] ?? '', $xml);
        }

        // 4. Hardcoded address template → customer address
        $xml = str_replace(
            'Rajawali Place 6th Flor, Jalan HR Rasuna Kav.B/4 Setiabudi,     Jakarta Selatan 12910',
            $r['alamat_pelanggan'],
            $xml,
        );

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
        $pStart = strrpos($searchArea, '<w:p ') ?: strrpos($searchArea, '<w:p>');
        $pEnd = strpos($documentXml, '</w:p>', $bastPos);
        $bodyOpen = strpos($documentXml, '<w:body>');

        if ($pStart === false || $pEnd === false || $bodyOpen === false) {
            return [$documentXml, $documentXml];
        }

        $pEnd += strlen('</w:p>');
        $bodyOffset = $bodyOpen + strlen('<w:body>');

        // Extract sectPr (header/logo reference)
        preg_match_all('/<w:sectPr[^>]*>.*?<\/w:sectPr>|<w:sectPr[^>]*\/>/s', $documentXml, $sectMatches);
        $sectPrBlock = implode('', $sectMatches[0]);

        $bodyContent = substr($documentXml, $bodyOffset);
        $bodyContentBaa = substr($bodyContent, 0, $pStart - $bodyOffset);

        // Clean trailing empty / page-break paragraphs
        $bodyContentBaa = preg_replace(
            '/<w:p[^>]*>(\s|<[^>]+>)*(<w:r[^>]*>(\s|<[^>]+>)*<w:br[^>]*w:type="page"[^>]*\/>(\s|<[^>]+>)*<\/w:r>)?(\s|<[^>]+>)*<\/w:p>(\s)*$/i',
            '',
            $bodyContentBaa,
        );
        while (preg_match('/<w:p[^>]*>(\s|<[^>]+>)*<\/w:p>(\s)*$/i', $bodyContentBaa, $m)) {
            $bodyContentBaa = preg_replace('/<w:p[^>]*>(\s|<[^>]+>)*<\/w:p>(\s)*$/i', '', $bodyContentBaa);
            if (strlen($m[0]) === 0) {
                break;
            }
        }

        $documentPrefix = substr($documentXml, 0, $bodyOffset);

        $baaXml = $documentPrefix . $bodyContentBaa . $sectPrBlock . '</w:body></w:document>';

        $bastXml = $documentPrefix . substr($bodyContent, $pStart - $bodyOffset);

        if (strpos($bastXml, '</w:body>') === false) {
            $bastXml .= '</w:body></w:document>';
        } elseif ($sectPrBlock && ! str_contains($bastXml, '<w:sectPr')) {
            $bastXml = str_replace('</w:body>', $sectPrBlock . '</w:body>', $bastXml);
        }

        return [$baaXml, $bastXml];
    }

    private function writeDocx(string $outputPath, string $documentXml, array $otherFiles): void
    {
        File::ensureDirectoryExists(dirname($outputPath));

        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
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
            if (! in_array($name, ['[Content_Types].xml', '_rels/.rels', 'word/document.xml'], true)) {
                $zip->addFromString($name, $content);
            }
        }

        $zip->close();
    }

    public static function formatRupiah(?int $value): string
    {
        return $value === null ? '—' : 'Rp ' . number_format($value, 0, ',', '.');
    }

    public static function terbilang(int $angka): string
    {
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];

        return match (true) {
            $angka < 10   => $satuan[$angka],
            $angka === 10 => 'sepuluh',
            $angka === 11 => 'sebelas',
            $angka < 20   => $satuan[$angka % 10] . ' belas',
            $angka < 100  => $satuan[(int) ($angka / 10)] . ' puluh ' . $satuan[$angka % 10],
            $angka < 200  => 'seratus ' . self::terbilang($angka % 100),
            $angka < 1000 => $satuan[(int) ($angka / 100)] . ' ratus ' . self::terbilang($angka % 100),
            $angka < 2000 => 'seribu ' . self::terbilang($angka % 1000),
            $angka < 10000 => $satuan[(int) ($angka / 1000)] . ' ribu ' . self::terbilang($angka % 1000),
            default => (string) $angka,
        };
    }
}
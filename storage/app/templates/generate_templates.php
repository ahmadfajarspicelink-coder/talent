<?php
require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;

// BAA Template
$phpWord = new PhpWord();
$section = $phpWord->addSection();

$section->addText('BERITA ACARA AKTIVASI', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
$section->addText('No. {nomor_baa}', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
$section->addText('');
$section->addText('Pada hari ini {hari}, tanggal {tanggal_huruf} bulan {bulan_huruf} tahun {tahun_huruf} ({tanggal}),');
$section->addText('telah dilakukan pekerjaan berupa : Aktivasi Fiber Optic Element dengan nomor {po_number}, terhadap:');
$section->addText('');
$section->addText('DATA PELANGGAN :', ['bold' => true]);
$section->addText('Nama Pelanggan\t\t: {nama_pelanggan}');
$section->addText('Alamat\t\t\t: {alamat_pelanggan}');
$section->addText('Alamat Instalasi\t\t: {alamat_instalasi}');
$section->addText('No. Jaringan\t\t: -');
$section->addText('Kapasitas\t\t\t: {bandwidth} Mbps');
$section->addText('Biaya sewa\t\t: {mrc_provider}');
$section->addText('Biaya aktivasi\t\t: {otc_provider}');
$section->addText('');
$section->addText('Demikian Berita Acara ini dibuat dan terima kasih atas kerjasamanya.');
$section->addText('');
$section->addText('');
$section->addText('PT. Sano Komunikasi\t\t\t\t\tPelanggan');
$section->addText('');
$section->addText('(Andhy Sabli Tagijara)\t\t\t\t\t(…………………………)');

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save(__DIR__ . '/baa_template.docx');
echo "BAA template created\n";

// BAST Template
$phpWord2 = new PhpWord();
$section2 = $phpWord2->addSection();

$section2->addText('BERITA ACARA SERAH TERIMA', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
$section2->addText('No : {nomor_bast}', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
$section2->addText('');
$section2->addText('Pada hari ini {hari}, tanggal {tanggal_huruf} bulan {bulan_huruf} tahun {tahun_huruf} ({tanggal}),');
$section2->addText('Kami yang bertanda tangan di bawah ini:');
$section2->addText('');
$section2->addText('1. Nama        : Andhy Sabli Tagijara');
$section2->addText('   Jabatan     : Direktur');
$section2->addText('   Perusahaan  : PT. Sano Komunikasi');
$section2->addText('   Selanjutnya disebut "Spicelink"');
$section2->addText('');
$section2->addText('2. Nama        : ');
$section2->addText('   Jabatan     : ');
$section2->addText('   Perusahaan  : {nama_pelanggan}');
$section2->addText('   Selanjutnya disebut "Pelanggan"');
$section2->addText('');
$section2->addText('Spicelink dan Pelanggan secara bersama-sama selanjutnya disebut "Para Pihak", dengan ini menerangkan bahwa layanan sewa lokal link sebagai berikut:');
$section2->addText('');

// Table
$table = $section2->addTable(['borderSize' => 6, 'borderColor' => '000000']);
$table->addRow();
$table->addCell(800)->addText('No.', ['bold' => true]);
$table->addCell(4000)->addText('Keterangan', ['bold' => true]);
$table->addCell(6000)->addText('Data', ['bold' => true]);

$table->addRow();
$table->addCell(800)->addText('1');
$table->addCell(4000)->addText('Jangka waktu berlangganan');
$table->addCell(6000)->addText('{kontrak_tahun} tahun sejak {kontrak_start} s/d {kontrak_end}');

$table->addRow();
$table->addCell(800)->addText('2');
$table->addCell(4000)->addText('Nama Jasa');
$table->addCell(6000)->addText("Layanan  : {jenis_layanan}\nKapasitas : {bandwidth} Mbps");

$table->addRow();
$table->addCell(800)->addText('3');
$table->addCell(4000)->addText('Biaya Instalasi dan Biaya Berlangganan');
$table->addCell(6000)->addText("Biaya Instalasi : {otc_provider}\nBiaya           : {mrc_provider}\nPPN 11%         : {ppn}\nTotal           : {total}");

$section2->addText('');
$section2->addText('Pada tanggal tersebut telah selesai dipasang dan di-test dengan hasil baik, oleh karena itu terhitung sejak tanggal tersebut sudah dapat digunakan / dioperasikan.');
$section2->addText('');
$section2->addText('Demikian Berita Acara Serah Terima ini kami buat dan ditandatangani oleh Para Pihak dalam rangkap 2 (dua) yang sama bunyinya.');
$section2->addText('');
$section2->addText("\t\t\t\t\t\t\tSingaraja, {tanggal}");
$section2->addText('');
$section2->addText('PT. Sano Komunikasi\t\t\t\t\t{nama_pelanggan}');
$section2->addText('');
$section2->addText('Andhy Sabli Tagijara\t\t\t\t\t……………………………');
$section2->addText('     Direktur');

$objWriter2 = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord2, 'Word2007');
$objWriter2->save(__DIR__ . '/bast_template.docx');
echo "BAST template created\n";
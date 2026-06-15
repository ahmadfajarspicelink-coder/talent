<?php
$z = new ZipArchive();
$z->open(__DIR__ . '/baa_bast_template.docx');
$xml = $z->getFromName('word/document.xml');
$z->close();

if (preg_match('/biaya.{0,500}/', $xml, $m)) {
    echo "FOUND CONTEXT:\n" . $m[0] . "\n\n";
} else {
    echo "NOT FOUND\n";
}
<?php
$z = new ZipArchive();
$z->open(__DIR__ . '/baa_bast_template.docx');
$xml = $z->getFromName('word/document.xml');
echo substr($xml, 0, 8000);
echo "\n\n=== TOTAL LENGTH: " . strlen($xml) . " ===\n";
$z->close();
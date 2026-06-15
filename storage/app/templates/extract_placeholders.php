<?php
$z = new ZipArchive();
$z->open(__DIR__ . '/baa_bast_template.docx');
$xml = $z->getFromName('word/document.xml');
$z->close();

// Find all text content between <w:t> tags
preg_match_all('/<w:t[^>]*>([^<]+)<\/w:t>/i', $xml, $textMatches);

echo "All text segments:\n";
foreach ($textMatches[1] as $text) {
    $trimmed = trim($text);
    if ($trimmed !== '') {
        echo "  \"$trimmed\"\n";
    }
}

echo "\n\n--- Placeholder-like text (contains _) ---\n";
foreach ($textMatches[1] as $text) {
    $trimmed = trim($text);
    if ($trimmed !== '' && str_contains($trimmed, '_')) {
        echo "  \"$trimmed\"\n";
    }
}
<?php
$dir = __DIR__ . "/uploads";

if (!is_dir($dir)) {
    exit("❌ Folder uploads tidak ditemukan: " . $dir);
}

$files = scandir($dir);
echo "<h3>Isi folder uploads:</h3><ul>";
foreach ($files as $f) {
    echo "<li>$f</li>";
}
echo "</ul>";

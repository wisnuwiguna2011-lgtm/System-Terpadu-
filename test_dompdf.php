<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

<?php
require 'vendor/autoload.php'; // kalau via Composer
// require 'dompdf/autoload.inc.php'; // kalau manual

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml('<h1>Hello DOMPDF!</h1>');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("test.pdf", ["Attachment" => false]);

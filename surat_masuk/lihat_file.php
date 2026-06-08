<?php
if(!isset($_GET['file'])) {
    die("File tidak ditemukan.");
}

// Ambil path lengkap dari database
$file = $_GET['file'];
$filepath = __DIR__ . '/../' . ltrim($file, '/'); // hapus slash awal

if(!file_exists($filepath)) {
    die("File tidak ditemukan di server.");
}

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

switch($ext){
    case 'pdf':
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
        break;
    case 'jpg':
    case 'jpeg':
        header('Content-Type: image/jpeg');
        header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
        break;
    case 'png':
        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
        break;
    default:
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
}
readfile($filepath);
exit;
?>

<?php
include '../config.php';  // koneksi ke database

// Ambil id_disposisi dari URL
$id_disposisi = $_GET['id_disposisi'];

// Hapus data dari tabel disposisi
$query = "DELETE FROM disposisi WHERE id_disposisi='$id_disposisi'";
if(mysqli_query($conn, $query)){
    header("Location: index.php"); // arahkan kembali ke daftar disposisi
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}
?>

<?php include '../config/config.php';
$id=$_GET['id'];
mysqli_query($conn,"DELETE FROM surat_masuk WHERE id=$id");
echo "Data dihapus!";
?>
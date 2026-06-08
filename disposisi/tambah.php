<?php
session_start();
if(!isset($_SESSION['login'])) header("Location: ../login.php");

include '../config.php';
$error = '';
$success = '';

// Ambil daftar surat masuk untuk select box
$suratQuery = mysqli_query($conn, "SELECT id_masuk, no_surat FROM surat_masuk ORDER BY no_surat ASC");

if(isset($_POST['submit'])){
    $id_masuk = mysqli_real_escape_string($conn, $_POST['id_masuk']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $instruksi = mysqli_real_escape_string($conn, $_POST['instruksi']);
    $tanggal_disposisi = mysqli_real_escape_string($conn, $_POST['tanggal_disposisi']);

    if($id_masuk && $tujuan && $instruksi && $tanggal_disposisi){
        $insert = mysqli_query($conn, "INSERT INTO disposisi (id_masuk, tujuan, instruksi, tanggal_disposisi) VALUES ('$id_masuk','$tujuan','$instruksi','$tanggal_disposisi')");
        if($insert){
            $success = "Disposisi berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan disposisi: ".mysqli_error($conn);
        }
    } else {
        $error = "Semua field wajib diisi!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Disposisi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#f5f6fa; }
.card{border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,0.05);}
</style>
</head>
<body>
<div class="container py-4">
<div class="card p-4">
<h3 class="text-primary mb-4">Tambah Disposisi</h3>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<form method="post">
<div class="mb-3">
<label>No Surat Masuk</label>
<select class="form-select" name="id_masuk" required>
<option value="">-- Pilih Surat Masuk --</option>
<?php while($s=mysqli_fetch_assoc($suratQuery)): ?>
<option value="<?= $s['id_masuk'] ?>"><?= htmlspecialchars($s['no_surat']) ?></option>
<?php endwhile; ?>
</select>
</div>

<div class="mb-3">
<label>Tujuan</label>
<input type="text" name="tujuan" class="form-control" required>
</div>

<div class="mb-3">
<label>Instruksi</label>
<textarea name="instruksi" class="form-control" rows="3" required></textarea>
</div>

<div class="mb-3">
<label>Tanggal Disposisi</label>
<input type="date" name="tanggal_disposisi" class="form-control" required>
</div>

<button type="submit" name="submit" class="btn btn-success">Simpan</button>
<a href="index.php" class="btn btn-secondary">Kembali</a>
</form>
</div>
</div>
</body>
</html>

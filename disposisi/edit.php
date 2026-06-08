<?php
session_start();
if(!isset($_SESSION['login'])) header("Location: ../login.php");

include '../config.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data disposisi
$result = mysqli_query($conn, "SELECT * FROM disposisi WHERE id_disposisi=$id");
if(mysqli_num_rows($result) == 0){
    die("Data disposisi tidak ditemukan.");
}
$data = mysqli_fetch_assoc($result);

// Proses update
$error = '';
if(isset($_POST['simpan'])){
    $id_masuk = (int)$_POST['id_masuk'];
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $instruksi = mysqli_real_escape_string($conn, $_POST['instruksi']);
    $tanggal_disposisi = mysqli_real_escape_string($conn, $_POST['tanggal_disposisi']);

    // Cek foreign key valid
    $cekSurat = mysqli_query($conn, "SELECT id_masuk FROM surat_masuk WHERE id_masuk='$id_masuk'");
    if(mysqli_num_rows($cekSurat) == 0){
        $error = "ID Surat Masuk tidak valid!";
    } else {
        $update = mysqli_query($conn, "UPDATE disposisi SET 
            id_masuk='$id_masuk',
            tujuan='$tujuan',
            instruksi='$instruksi',
            tanggal_disposisi='$tanggal_disposisi'
            WHERE id_disposisi=$id
        ");
        if($update){
            header("Location: index.php"); // Kembali ke menu disposisi
            exit;
        } else {
            $error = "Gagal update data: ".mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Disposisi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;background:#f5f6fa;}
.container{max-width:700px;margin-top:50px;}
.card{border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
</style>
</head>
<body>
<div class="container">
<div class="card p-4">
<h4>Edit Disposisi</h4>
<?php if($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<form method="post">
    <div class="mb-3">
        <label for="id_masuk" class="form-label">Pilih Surat Masuk</label>
        <select name="id_masuk" id="id_masuk" class="form-control" required>
            <?php
            $suratMasuk = mysqli_query($conn, "SELECT id_masuk, no_surat FROM surat_masuk ORDER BY id_masuk DESC");
            while($s = mysqli_fetch_assoc($suratMasuk)){
                $selected = ($s['id_masuk'] == $data['id_masuk']) ? 'selected' : '';
                echo "<option value='".$s['id_masuk']."' $selected>".$s['no_surat']." (ID ".$s['id_masuk'].")</option>";
            }
            ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="tujuan" class="form-label">Tujuan</label>
        <input type="text" name="tujuan" id="tujuan" class="form-control" value="<?= htmlspecialchars($data['tujuan']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="instruksi" class="form-label">Instruksi</label>
        <textarea name="instruksi" id="instruksi" class="form-control" rows="3" required><?= htmlspecialchars($data['instruksi']) ?></textarea>
    </div>

    <div class="mb-3">
        <label for="tanggal_disposisi" class="form-label">Tanggal Disposisi</label>
        <input type="date" name="tanggal_disposisi" id="tanggal_disposisi" class="form-control" value="<?= $data['tanggal_disposisi'] ?>" required>
    </div>

    <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
    <a href="index.php" class="btn btn-secondary">Kembali</a>
</form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
include '../config.php';

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Ambil data surat berdasarkan ID
$result = mysqli_query($conn, "SELECT * FROM surat_masuk WHERE id_masuk=$id");
if(mysqli_num_rows($result)==0){
    die("Surat tidak ditemukan.");
}
$data = mysqli_fetch_assoc($result);

if(isset($_POST['submit'])){
    $no_surat = $_POST['no_surat'];
    $tanggal = $_POST['tanggal'];
    $pengirim = $_POST['pengirim'];
    $perihal = $_POST['perihal'];

    $file_surat = $data['file_surat']; // file lama default
    if(isset($_FILES['file_surat']) && $_FILES['file_surat']['error']==0){
        // Upload file baru
        $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/','_',pathinfo($_FILES['file_surat']['name'], PATHINFO_FILENAME));
        $newname = "uploads/surat_masuk/".$filename."_".time().".".$ext;
        move_uploaded_file($_FILES['file_surat']['tmp_name'], "../".$newname);
        $file_surat = $newname;

        // Hapus file lama jika ada
        if(!empty($data['file_surat']) && file_exists('../'.$data['file_surat'])){
            unlink('../'.$data['file_surat']);
        }
    }

    // Update data ke database
    $sql = "UPDATE surat_masuk SET 
            no_surat='$no_surat',
            tanggal='$tanggal',
            pengirim='$pengirim',
            perihal='$perihal',
            file_surat='$file_surat'
            WHERE id_masuk=$id";
    if(mysqli_query($conn,$sql)){
        header("Location: index.php");
        exit;
    } else {
        echo "Error: ".mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Surat Masuk</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
<h2>Edit Surat Masuk</h2>
<form method="post" enctype="multipart/form-data">
<div class="mb-3">
<label>No Surat</label>
<input type="text" name="no_surat" class="form-control" value="<?= htmlspecialchars($data['no_surat']) ?>" required>
</div>
<div class="mb-3">
<label>Tanggal</label>
<input type="date" name="tanggal" class="form-control" value="<?= $data['tanggal'] ?>" required>
</div>
<div class="mb-3">
<label>Pengirim</label>
<input type="text" name="pengirim" class="form-control" value="<?= htmlspecialchars($data['pengirim']) ?>" required>
</div>
<div class="mb-3">
<label>Perihal</label>
<textarea name="perihal" class="form-control" required><?= htmlspecialchars($data['perihal']) ?></textarea>
</div>
<div class="mb-3">
<label>File Surat (PDF/JPG/PNG)</label><br>
<?php if(!empty($data['file_surat'])): ?>
<a href="lihat_file.php?file=<?= urlencode($data['file_surat']) ?>" target="_blank">Lihat File Saat Ini</a><br>
<?php endif; ?>
<input type="file" name="file_surat" class="form-control mt-2">
<small class="text-muted">Biarkan kosong jika tidak ingin mengganti file</small>
</div>
<button type="submit" name="submit" class="btn btn-success">Simpan Perubahan</button>
<a href="index.php" class="btn btn-secondary">Kembali</a>
</form>
</div>
</body>
</html>

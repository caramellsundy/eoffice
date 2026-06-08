<?php
session_start();
if(!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

include '../config.php';
$role = $_SESSION['role'];

if(isset($_POST['submit'])){
    $no_surat = mysqli_real_escape_string($conn, $_POST['no_surat']);
    $kode_surat = mysqli_real_escape_string($conn, $_POST['kode_surat']);
    $metode_pengiriman = mysqli_real_escape_string($conn, $_POST['metode_pengiriman']);
    $tanggal = $_POST['tanggal'];
    $tanggal_terima = $_POST['tanggal_terima'];
    $pengirim = mysqli_real_escape_string($conn, $_POST['pengirim']);
    $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
    $sifat_surat = isset($_POST['sifat_surat']) ? mysqli_real_escape_string($conn, $_POST['sifat_surat']) : '';
    $jenis_surat = isset($_POST['jenis_surat']) ? mysqli_real_escape_string($conn, $_POST['jenis_surat']) : '';
    $created_at = date('Y-m-d H:i:s');

    $file_surat = NULL;
    if(isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0){
        $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['file_surat']['name'], PATHINFO_FILENAME));
        $newname = "uploads/surat_masuk/" . $filename . "_" . time() . "." . $ext;
        
        if (!is_dir('../uploads/surat_masuk')) {
            mkdir('../uploads/surat_masuk', 0777, true);
        }
        
        move_uploaded_file($_FILES['file_surat']['tmp_name'], "../" . $newname);
        $file_surat = $newname;
    }

    // Cek ketersediaan kolom tambahan di tabel database surat_masuk milikmu
    $checkColumns = mysqli_query($conn, "SHOW COLUMNS FROM `surat_masuk` LIKE 'kode_surat'");
    if(mysqli_num_rows($checkColumns) > 0) {
        // Jika struktur database sudah lengkap dengan kolom baru sesuai rancangan ATR/BPN
        $sql = "INSERT INTO surat_masuk (no_surat, kode_surat, metode_pengiriman, tanggal, tanggal_terima, pengirim, perihal, sifat_surat, jenis_surat, file_surat, created_at)
                VALUES ('$no_surat', '$kode_surat', '$metode_pengiriman', '$tanggal', '$tanggal_terima', '$pengirim', '$perihal', '$sifat_surat', '$jenis_surat', '$file_surat', '$created_at')";
    } else {
        // Fallback otomatis menggunakan struktur database dasar (jika kolom di atas belum kamu alter)
        $sql = "INSERT INTO surat_masuk (no_surat, kode_surat, tanggal, pengirim, perihal, metode_pengiriman, file_surat)
                VALUES ('$no_surat', '$kode_surat', '$tanggal', '$pengirim', '$perihal', '$metode_pengiriman', '$file_surat')";
    }
    
    mysqli_query($conn, $sql);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Surat Masuk - Archivista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #e6f0ff; color: #0d3a8c; }

        /* Sidebar Styling */
        .sidebar { height: 100vh; width: 220px; background: #ffffff; color: #0d3a8c; position: fixed; display: flex; flex-direction: column; transition: 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.05); z-index: 100; }
        .sidebar.collapsed { width: 70px; }
        .sidebar h4 { text-align: center; margin: 20px 0; font-weight: 700; color: #0d3a8c; }
        .sidebar .nav-link { color: #0d3a8c; margin: 5px 10px; border-radius: 8px; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #cce0ff; color: #0d3a8c; box-shadow: 0 0 8px rgba(0,123,255,0.1); }
        .sidebar .nav-link i { margin-right: 10px; }
        .sidebar.collapsed .link-text { display: none; }

        /* Main Content Layout */
        .main-content { margin-left: 220px; padding: 20px; transition: 0.3s; }
        .main-content.collapsed { margin-left: 70px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #b3d1ff; padding-bottom: 10px; }
        .header h2 { font-weight: 700; color: #0d3a8c; }

        /* Desain Panel Formulir Model ATR/BPN */
        .bpn-card { background: #ffffff; border-radius: 8px; border: 1px solid #cce0ff; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 20px; }
        .bpn-card-header { background: #2f70b2; color: #ffffff; padding: 10px 15px; font-weight: 600; font-size: 0.95rem; }
        .bpn-card-body { padding: 25px 20px; }

        /* Form Controls Styling */
        .form-group-bpn { display: flex; align-items: center; margin-bottom: 15px; }
        .form-group-bpn label { width: 180px; font-size: 0.85rem; font-weight: 600; color: #0d3a8c; text-align: right; padding-right: 20px; }
        .form-group-bpn label .text-danger { font-weight: 700; }
        .form-input-container { flex: 1; max-width: 450px; }
        
        input.form-control, select.form-select, textarea.form-control { border-radius: 6px; background: #ffffff; color: #0d3a8c; border: 1px solid #cce0ff; font-size: 0.9rem; padding: 6px 12px; }
        input.form-control:focus, select.form-select:focus, textarea.form-control:focus { border-color: #0b76e0; box-shadow: 0 0 5px rgba(0,123,255,0.2); color: #0d3a8c; }
        
        /* Form Buttons */
        .btn-bpn-submit { background: #00b386; color: white; border: none; padding: 6px 18px; border-radius: 6px; font-size: 0.9rem; font-weight: 600; transition: 0.2s; }
        .btn-bpn-submit:hover { background: #009973; transform: translateY(-1px); }
        .btn-bpn-reset { background: #f3a633; color: white; border: none; padding: 6px 18px; border-radius: 6px; font-size: 0.9rem; font-weight: 600; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-bpn-reset:hover { background: #e0921f; color: white; transform: translateY(-1px); }
        .toggle-btn { cursor: pointer; color: #0d3a8c; }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <h4>Archivista</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="../dashboard.php" class="nav-link"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
            <li class="nav-item"><a href="index.php" class="nav-link active"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
            <li class="nav-item"><a href="../surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> <span class="link-text">Surat Keluar</span></a></li>
            <li class="nav-item"><a href="../disposisi/index.php" class="nav-link"><i data-feather="file-text"></i> <span class="link-text">Disposisi</span></a></li>
            <?php if($role == 'admin'): ?>
                <li class="nav-item"><a href="../users.php" class="nav-link"><i data-feather="users"></i> <span class="link-text">Manajemen User</span></a></li>
            <?php endif; ?>
            <li class="nav-item mt-auto"><a href="../logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <form method="post" enctype="multipart/form-data">
            
            <div class="header">
                <h2>Buat Surat Masuk</h2>
                <div class="d-flex gap-2 align-items-center">
                    <button type="submit" name="submit" class="btn-bpn-submit">Kirim</button>
                    <a href="index.php" class="btn-bpn-reset">Reset</a>
                    <i class="toggle-btn ms-2" data-feather="menu" id="toggleSidebar"></i>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-xl-9">
                    
                    <div class="bpn-card">
                        <div class="bpn-card-header">Pengenal Surat</div>
                        <div class="bpn-card-body">
                            
                            <div class="form-group-bpn">
                                <label>Kode Surat Masuk</label>
                                <div class="form-input-container">
                                    <input type="text" name="kode_surat" class="form-control" placeholder="Masukkan kode internal indeks surat">
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Metode Pengiriman <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <select name="metode_pengiriman" class="form-select" required>
                                        <option value="Loket">Loket</option>
                                        <option value="Email">Email</option>
                                        <option value="Kurir">Kurir / Pos</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Asal Surat / Pengirim <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <input type="text" name="pengirim" class="form-control" placeholder="Contoh: Kantor Wilayah BPN Provinsi" required>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Nomor Surat <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <input type="text" name="no_surat" class="form-control" placeholder="Masukkan nomor resmi dokumen" required>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Sifat Surat <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <select name="sifat_surat" class="form-select">
                                        <option value="Biasa">Biasa</option>
                                        <option value="Penting">Penting</option>
                                        <option value="Sangat Penting">Sangat Penting</option>
                                        <option value="Rahasia">Rahasia</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Jenis Surat <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <select name="jenis_surat" class="form-select">
                                        <option value="Surat Dinas">Surat Dinas</option>
                                        <option value="Surat Undangan">Surat Undangan</option>
                                        <option value="Surat Edaran">Surat Edaran</option>
                                        <option value="Nota Dinas">Nota Dinas</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Tanggal Surat <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <input type="date" name="tanggal" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Tanggal Terima <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <input type="date" name="tanggal_terima" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Perihal <span class="text-danger">*</span></label>
                                <div class="form-input-container">
                                    <textarea name="perihal" class="form-control" rows="3" placeholder="Isi ringkasan perihal surat..." required></textarea>
                                </div>
                            </div>

                            <div class="form-group-bpn">
                                <label>Upload Berkas Dokumen</label>
                                <div class="form-input-container">
                                    <input type="file" name="file_surat" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                    <small class="text-muted mt-1 d-block">Ekstensi dokumen yang didukung: PDF, JPG, PNG</small>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        feather.replace();
        
        // Logika Kolaps Menu Sidebar
        document.getElementById('toggleSidebar').addEventListener('click', ()=>{
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
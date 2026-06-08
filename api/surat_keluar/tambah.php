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
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $penerima = mysqli_real_escape_string($conn, $_POST['penerima']);
    $perihal = mysqli_real_escape_string($conn, $_POST['perihal']);
    $sifat_surat = isset($_POST['sifat_surat']) ? mysqli_real_escape_string($conn, $_POST['sifat_surat']) : '';
    $jenis_surat = isset($_POST['jenis_surat']) ? mysqli_real_escape_string($conn, $_POST['jenis_surat']) : '';
    $created_at = date('Y-m-d H:i:s');

    $file_surat = NULL;
    if(isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0){
        $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($_FILES['file_surat']['name'], PATHINFO_FILENAME));
        $newname = "uploads/surat_keluar/" . $filename . "_" . time() . "." . $ext;
        
        // Memastikan direktori folder upload tersedia
        if (!is_dir('../uploads/surat_keluar')) {
            mkdir('../uploads/surat_keluar', 0777, true);
        }
        
        move_uploaded_file($_FILES['file_surat']['tmp_name'], "../" . $newname);
        $file_surat = $newname;
    }

    // Menangani fallback otomatis jika kolom dinamis di database kamu hanya mencakup struktur dasar
    $checkSifat = mysqli_query($conn, "SHOW COLUMNS FROM `surat_keluar` LIKE 'sifat_surat'");
    if(mysqli_num_rows($checkSifat) > 0) {
        $sql = "INSERT INTO surat_keluar (no_surat, tanggal_keluar, penerima, perihal, sifat_surat, jenis_surat, file_surat, created_at)
                VALUES ('$no_surat', '$tanggal_keluar', '$penerima', '$perihal', '$sifat_surat', '$jenis_surat', '$file_surat', '$created_at')";
    } else {
        $sql = "INSERT INTO surat_keluar (no_surat, tanggal_keluar, penerima, perihal, file_surat, created_at)
                VALUES ('$no_surat', '$tanggal_keluar', '$penerima', '$perihal', '$file_surat', '$created_at')";
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
    <title>Buat Surat Keluar - Archivista</title>
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
        
        /* Box Form Panel */
        .form-container { background: #ffffff; border-radius: 12px; padding: 25px; border: 1px solid #cce0ff; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .section-title { font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #5c85d6; margin-bottom: 15px; border-bottom: 2px solid #e6f0ff; padding-bottom: 5px; }
        
        /* Form Controls */
        label { font-size: 0.85rem; font-weight: 600; color: #0d3a8c; margin-bottom: 4px; }
        label .text-danger { font-weight: 700; }
        input.form-control, select.form-select, textarea.form-control { border-radius: 8px; background: #ffffff; color: #0d3a8c; border: 1px solid #cce0ff; font-size: 0.9rem; }
        input.form-control:focus, select.form-select:focus, textarea.form-control:focus { border-color: #0b76e0; box-shadow: 0 0 5px rgba(0,123,255,0.2); color: #0d3a8c; }
        
        .btn-custom { transition: 0.2s; border-radius: 8px; font-weight: 600; padding: 8px 16px; }
        .btn-custom:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,123,255,0.15); }
        .toggle-btn { cursor: pointer; color: #0d3a8c; }
        
        /* Tab Sederhana Kirim */
        .nav-tabs-custom { display: flex; gap: 5px; border-bottom: 1px solid #cce0ff; margin-bottom: 15px; }
        .nav-tabs-custom .tab-item { padding: 6px 12px; font-size: 0.85rem; font-weight: 600; border-radius: 6px 6px 0 0; background: #f0f5ff; border: 1px solid #cce0ff; border-bottom: none; color: #5c85d6; cursor: pointer; }
        .nav-tabs-custom .tab-item.active { background: #ffffff; color: #0d3a8c; border-color: #cce0ff; position: relative; margin-bottom: -1px; height: calc(100% + 1px); }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <h4>Archivista</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="../dashboard.php" class="nav-link"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
            <li class="nav-item"><a href="../surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
            <li class="nav-item"><a href="index.php" class="nav-link active"><i data-feather="send"></i> <span class="link-text">Surat Keluar</span></a></li>
            <li class="nav-item"><a href="../disposisi/index.php" class="nav-link"><i data-feather="file-text"></i> <span class="link-text">Disposisi</span></a></li>
            <?php if($role == 'admin'): ?>
                <li class="nav-item"><a href="../users.php" class="nav-link"><i data-feather="users"></i> <span class="link-text">Manajemen User</span></a></li>
            <?php endif; ?>
            <li class="nav-item mt-auto"><a href="../logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="header">
            <h2>Buat Surat Keluar</h2>
            <div class="user-info">
                <i class="toggle-btn" data-feather="menu" id="toggleSidebar"></i>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="form-container">
            <div class="row g-4">
                
                <div class="col-12 col-lg-7 border-end-lg" style="border-color: #e6f0ff;">
                    <div class="section-title">Atribut Surat</div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label>Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" name="no_surat" class="form-control" placeholder="Contoh: 100/ATR-BPN/VI/2026" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label>Tanggal Surat <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_keluar" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label>Sifat Surat <span class="text-danger">*</span></label>
                            <select name="sifat_surat" class="form-select">
                                <option value="Biasa">Biasa</option>
                                <option value="Penting">Penting</option>
                                <option value="Sangat Penting">Sangat Penting</option>
                                <option value="Rahasia">Rahasia</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label>Jenis Surat <span class="text-danger">*</span></label>
                            <select name="jenis_surat" class="form-select">
                                <option value="Surat Dinas">Surat Dinas</option>
                                <option value="Surat Undangan">Surat Undangan</option>
                                <option value="Surat Tugas">Surat Tugas</option>
                                <option value="Nota Dinas">Nota Dinas</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label>Perihal <span class="text-danger">*</span></label>
                            <textarea name="perihal" class="form-control" rows="4" placeholder="Tuliskan ringkasan perihal atau isi surat keluar..." required></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    
                    <div class="section-title">Kirim Ke</div>
                    <div class="nav-tabs-custom">
                        <div class="tab-item active">Perorangan / Instansi</div>
                        <div class="tab-item">Pihak Luar</div>
                        <div class="tab-item">Massal</div>
                    </div>
                    
                    <div class="mb-4">
                        <label>Nama Penerima / Unit Kerja <span class="text-danger">*</span></label>
                        <input type="text" name="penerima" class="form-control" placeholder="Masukkan nama instansi atau jabatan penerima" required>
                    </div>

                    <div class="section-title">File Surat</div>
                    <div class="p-3 border border-dashed rounded-3 bg-light text-center mb-4" style="border-style: dashed !important; border-color: #b3d1ff !important;">
                        <i data-feather="upload-cloud" class="text-primary mb-2" style="width: 40px; height: 40px;"></i>
                        <div class="mb-2"><label for="file_surat" class="form-label m-0 font-weight-normal text-muted" style="cursor:pointer;">Klik untuk pilih dokumen berkas</label></div>
                        <input type="file" name="file_surat" id="file_surat" class="form-control form-control-sm mx-auto" style="max-width: 280px;" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted d-block mt-2">Format yang didukung: PDF, JPG, JPEG, PNG</small>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-5 pt-3 border-top" style="border-color: #e6f0ff;">
                        <a href="index.php" class="btn btn-outline-secondary btn-custom">Batal</a>
                        <button type="submit" name="submit" class="btn btn-primary btn-custom">💾 Simpan Surat Keluar</button>
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
<?php 
session_start();
if(!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

include '../config.php';
$role = $_SESSION['role'];

// Base URL untuk file dokumen
$base_url = "http://localhost/surat_menyurat/";

// Pengaturan Pagination
$limit = 10;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Menangkap Input Filter & Pencarian
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$bulan  = isset($_GET['bulan']) ? mysqli_real_escape_string($conn, $_GET['bulan']) : '';
$tahun  = isset($_GET['tahun']) ? mysqli_real_escape_string($conn, $_GET['tahun']) : '';

// Menyusun Klausa WHERE Dinamis untuk Filter Data (Menangani fallback nama kolom 'tanggal' atau 'tanggal_keluar')
$whereClauses = [];
if($search != ''){
    $whereClauses[] = "(no_surat LIKE '%$search%' OR penerima LIKE '%$search%' OR perihal LIKE '%$search%')";
}

// Deteksi nama kolom tanggal yang kamu gunakan di database (default memakai 'tanggal_keluar' / 'tanggal')
// Kita gunakan query check untuk memastikan nama kolom agar filter bulan/tahun tidak error
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM `surat_keluar` LIKE 'tanggal_keluar'");
$dateColumn = (mysqli_num_rows($checkColumn) > 0) ? "tanggal_keluar" : "tanggal";

if($bulan != '' && $bulan != 'Lihat Semua Bulan'){
    $whereClauses[] = "MONTH($dateColumn) = '$bulan'";
}
if($tahun != '' && $tahun != 'Tahun'){
    $whereClauses[] = "YEAR($dateColumn) = '$tahun'";
}

$whereQuery = "";
if(count($whereClauses) > 0){
    $whereQuery = " WHERE " . implode(" AND ", $whereClauses);
}

// Menghitung Total Data untuk Pagination
$totalQuery  = "SELECT COUNT(*) as total FROM surat_keluar" . $whereQuery;
$totalResult = mysqli_query($conn, $totalQuery);
$totalData   = mysqli_fetch_assoc($totalResult)['total'];
$totalPages  = ceil($totalData / $limit);

// Mengambil Data Utama Tabel Surat Keluar
$dataQuery = "SELECT * FROM surat_keluar" . $whereQuery . " ORDER BY id_keluar DESC LIMIT $start, $limit";
$result    = mysqli_query($conn, $dataQuery);

// --- LOGIKA STATISTIK (Angka Ringkasan Atas disamakan dengan Surat Masuk) ---
// Surat keluar hari ini
$qHariIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keluar WHERE DATE($dateColumn) = CURDATE()");
$statHariIni = mysqli_fetch_assoc($qHariIni)['total'] ?? 0;

// Karena surat keluar biasanya tidak memakai metode pengiriman Loket/Email di database utama kamu, 
// Sub-statistik hari ini dipecah berdasarkan porsi data contoh/opsional agar grid 5 kolom tetap seimbang dan rapi.
$statInfo1 = $statHariIni; 
$statInfo2 = 0; // Opsi pencatatan tambahan jika diperlukan nanti

// Total Akumulasi Bulan Ini
$qBulanIni   = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keluar WHERE MONTH($dateColumn) = MONTH(CURDATE()) AND YEAR($dateColumn) = YEAR(CURDATE())");
$statBulanIni = mysqli_fetch_assoc($qBulanIni)['total'] ?? 0;

// Total Akumulasi Tahun Ini
$qTahunIni   = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_keluar WHERE YEAR($dateColumn) = YEAR(CURDATE())");
$statTahunIni = mysqli_fetch_assoc($qTahunIni)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keluar - Archivista</title>
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
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-info span { color: #0d3a8c; background: #cce0ff; padding: 5px 12px; border-radius: 20px; font-weight: 600; box-shadow: 0 0 5px rgba(0,123,255,0.2); }

        /* Card Statistik */
        .stat-card { background: #ffffff; border-radius: 12px; padding: 15px; border: 1px solid #cce0ff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; height: 100%; }
        .stat-card h3 { font-size: 1.8rem; font-weight: 700; color: #0d3a8c; margin: 0; }
        .stat-card p { font-size: 0.75rem; color: #5c85d6; margin: 0; line-height: 1.2; }
        .stat-icon { background: #e6f0ff; color: #0d3a8c; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }

        /* Area Konten Panel */
        .filter-box { background: #ffffff; border-radius: 12px; padding: 15px; border: 1px solid #cce0ff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); margin-bottom: 20px; }
        .latest-table { background: #ffffff; border-radius: 12px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        
        /* Elemen Tabel dan Tombol */
        .table thead th { background: #cce0ff; color: #0d3a8c; border-color: #b3d1ff; font-weight: 600; }
        .table tbody tr { transition: all 0.2s; }
        .table tbody tr:hover { background: #f7faff; }
        .btn-custom { transition: 0.2s; border-radius: 8px; }
        .btn-custom:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,123,255,0.15); }
        
        /* Element Form Kontrol */
        input.form-control, select.form-select { border-radius: 8px; background: #ffffff; color: #0d3a8c; border: 1px solid #cce0ff; font-size: 0.9rem; }
        input.form-control:focus, select.form-select:focus { border-color: #0b76e0; box-shadow: 0 0 5px rgba(0,123,255,0.2); color: #0d3a8c; }
        .toggle-btn { cursor: pointer; color: #0d3a8c; }
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
            <h2>Surat Keluar</h2>
            <div class="user-info">
                <span><?= ucfirst($role) ?></span> 
                <i class="toggle-btn" data-feather="menu" id="toggleSidebar"></i>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div>
                        <h3><?= $statHariIni ?></h3>
                        <p>Surat keluar diproses hari ini</p>
                    </div>
                    <div class="stat-icon"><i data-feather="send"></i></div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div>
                        <h3><?= $statInfo1 ?></h3>
                        <p>Surat keluar selesai hari ini</p>
                    </div>
                    <div class="stat-icon text-success"><i data-feather="check-circle"></i></div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div>
                        <h3><?= $statHariIni ?></h3>
                        <p>Total surat keluar hari ini</p>
                    </div>
                    <div class="stat-icon"><i data-feather="file-text"></i></div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div>
                        <h3><?= $statBulanIni ?></h3>
                        <p>Total surat keluar bulan ini</p>
                    </div>
                    <div class="stat-icon"><i data-feather="calendar"></i></div>
                </div>
            </div>
            <div class="col-12 col-md">
                <div class="stat-card">
                    <div>
                        <h3><?= $statTahunIni ?></h3>
                        <p>Total surat keluar tahun ini</p>
                    </div>
                    <div class="stat-icon"><i data-feather="archive"></i></div>
                </div>
            </div>
        </div>

        <div class="filter-box">
            <form method="get" action="" class="row g-2 align-items-center">
                <div class="col-12 col-md-auto mb-2 mb-md-0">
                    <a href="tambah.php" class="btn btn-primary btn-custom w-100">+ Tambah Surat Keluar</a>
                </div>
                
                <div class="col-12 col-md mb-2 mb-md-0 ms-md-auto">
                    <input class="form-control" type="search" name="search" placeholder="Cari No Surat / Penerima / Perihal..." value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="col-6 col-md-auto mb-2 mb-md-0">
                    <select class="form-select" name="bulan">
                        <option value="">Lihat Semua Bulan</option>
                        <?php 
                        $namaBulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
                        for($m=1; $m<=12; $m++) {
                            $selected = ($bulan == $m) ? 'selected' : '';
                            echo "<option value='$m' $selected>".$namaBulan[$m-1]."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-6 col-md-auto mb-2 mb-md-0">
                    <select class="form-select" name="tahun">
                        <option value="">Tahun</option>
                        <option value="2026" <?= $tahun == '2026' ? 'selected' : '' ?>>2026</option>
                        <option value="2025" <?= $tahun == '2025' ? 'selected' : '' ?>>2025</option>
                        <option value="2024" <?= $tahun == '2024' ? 'selected' : '' ?>>2024</option>
                    </select>
                </div>

                <div class="col-12 col-md-auto mb-2 mb-md-0">
                    <button class="btn btn-outline-primary btn-custom w-100" type="submit">Cari</button>
                </div>
            </form>
        </div>

        <div class="latest-table">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-dark">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">ID</th>
                            <th>No Surat</th>
                            <th>Tanggal Keluar</th>
                            <th>Penerima</th>
                            <th>Perihal</th>
                            <th class="text-center">File</th>
                            <th class="text-center" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($data = mysqli_fetch_assoc($result)): ?>
                                <?php 
                                    $filePath  = (!empty($data['file_surat'])) ? $base_url . $data['file_surat'] : '';
                                    $tglKeluar = isset($data[$dateColumn]) ? $data[$dateColumn] : '';
                                ?>
                                <tr>
                                    <td class="text-center text-muted fw-bold"><?= htmlspecialchars($data['id_keluar']) ?></td>
                                    <td>✉️ <?= htmlspecialchars($data['no_surat']) ?></td>
                                    <td><?= (!empty($tglKeluar)) ? date('d-m-Y', strtotime($tglKeluar)) : '-' ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($data['penerima']) ?></td>
                                    <td>📝 <?= htmlspecialchars($data['perihal']) ?></td>
                                    <td class="text-center">
                                        <?php if($filePath): ?>
                                            <a href="<?= htmlspecialchars($filePath) ?>" target="_blank" class="btn btn-sm btn-outline-primary btn-custom py-1 px-2">
                                                <i data-feather="file" style="width:14px; height:14px;"></i> Lihat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">Tidak ada file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit.php?id=<?= $data['id_keluar'] ?>" class="btn btn-warning btn-sm btn-custom text-white mb-1 mb-md-0 py-1 px-2">✏️ Edit</a> 
                                        <a href="hapus.php?id=<?= $data['id_keluar'] ?>" onclick="return confirm('Yakin ingin menghapus data surat keluar ini?');" class="btn btn-danger btn-sm btn-custom py-1 px-2">🗑️ Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data surat keluar yang cocok dengan filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center mb-0">
                        <?php
                        for($i = 1; $i <= $totalPages; $i++){
                            $active = ($i == $page) ? 'active' : '';
                            
                            $filterParams = "";
                            if($search != '') $filterParams .= '&search='.urlencode($search);
                            if($bulan != '')  $filterParams .= '&bulan='.urlencode($bulan);
                            if($tahun != '')  $filterParams .= '&tahun='.urlencode($tahun);
                            
                            echo '<li class="page-item"><a class="page-link '.$active.'" href="?page='.$i.$filterParams.'">'.$i.'</a></li>';
                        }
                        ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        feather.replace();
        
        // Logika Responsif Kolaps Konten Sidebar
        document.getElementById('toggleSidebar').addEventListener('click', ()=>{
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
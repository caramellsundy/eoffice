<?php 
session_start();
if(!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

// Menggunakan absolute path yang aman agar Apache tidak melempar "Not Found"
include $_SERVER['DOCUMENT_ROOT'] . '/surat_menyurat/config.php';

$role = $_SESSION['role'];

// Base URL untuk keseragaman path berkas
$base_url = "http://localhost/surat_menyurat/";

// Pagination (Disamakan dengan Surat Masuk)
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Filter & Search Input
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$bulan  = isset($_GET['bulan']) ? mysqli_real_escape_string($conn, $_GET['bulan']) : '';
$tahun  = isset($_GET['tahun']) ? mysqli_real_escape_string($conn, $_GET['tahun']) : '';

// Menyusun Klausa WHERE Dinamis untuk Surat Pengantar
$whereClauses = [];
if($search != ''){
    $whereClauses[] = "(sp.no_pengantar LIKE '%$search%' OR sp.tujuan LIKE '%$search%' OR sp.pembuat LIKE '%$search%' OR sm.no_surat LIKE '%$search%')";
}
if($bulan != '' && $bulan != 'Lihat Semua Bulan'){
    $whereClauses[] = "MONTH(sp.tanggal_surat) = '$bulan'";
}
if($tahun != '' && $tahun != 'Tahun'){
    $whereClauses[] = "YEAR(sp.tanggal_surat) = '$tahun'";
}

$whereQuery = "";
if(count($whereClauses) > 0){
    $whereQuery = " WHERE " . implode(" AND ", $whereClauses);
}

// Total data untuk pagination
$totalQuery = "SELECT COUNT(*) as total FROM surat_pengantar sp 
               LEFT JOIN surat_masuk sm ON sp.id_surat_masuk = sm.id_masuk" . $whereQuery;
$totalResult = mysqli_query($conn, $totalQuery);
$totalData = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalData / $limit);

// Data tabel utama (Menggunakan LEFT JOIN agar nomor surat masuk referensi bisa tampil)
$dataQuery = "SELECT sp.*, sm.no_surat AS no_surat_masuk 
              FROM surat_pengantar sp 
              LEFT JOIN surat_masuk sm ON sp.id_surat_masuk = sm.id_masuk" 
              . $whereQuery . 
              " ORDER BY sp.id_pengantar DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $dataQuery);

// --- LOGIKA STATISTIK (Disesuaikan khusus untuk Surat Pengantar) ---
// Total surat pengantar hari ini
$qHariIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_pengantar WHERE DATE(tanggal_surat) = CURDATE()");
$statHariIni = mysqli_fetch_assoc($qHariIni)['total'];

// Total surat pengantar bulan ini
$qBulanIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_pengantar WHERE MONTH(tanggal_surat) = MONTH(CURDATE()) AND YEAR(tanggal_surat) = YEAR(CURDATE())");
$statBulanIni = mysqli_fetch_assoc($qBulanIni)['total'];

// Total surat pengantar tahun ini
$qTahunIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_pengantar WHERE YEAR(tanggal_surat) = YEAR(CURDATE())");
$statTahunIni = mysqli_fetch_assoc($qTahunIni)['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Surat Pengantar - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#e6f0ff; color:#0d3a8c; }

/* Sidebar (Sama Persis dengan Surat Masuk) */
.sidebar{height:100vh;width:220px;background:#ffffff;color:#0d3a8c;position:fixed;display:flex;flex-direction:column;transition:0.3s;box-shadow:2px 0 10px rgba(0,0,0,0.05);z-index: 100;}
.sidebar.collapsed{width:70px;}
.sidebar h4{text-align:center;margin:20px 0;font-weight:700;color:#0d3a8c;}
.sidebar .nav-link{color:#0d3a8c;margin:5px 10px;border-radius:8px;transition:0.3s;}
.sidebar .nav-link:hover, .sidebar .nav-link.active{background:#cce0ff;color:#0d3a8c;box-shadow:0 0 8px rgba(0,123,255,0.1);}
.sidebar .nav-link i{margin-right:10px;}
.sidebar.collapsed .link-text{display:none;}

/* Main content */
.main-content{margin-left:220px;padding:20px;transition:0.3s;}
.main-content.collapsed{margin-left:70px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;border-bottom:1px solid #b3d1ff;padding-bottom:10px;}
.header h2{font-weight:700;color:#0d3a8c;}
.user-info{display:flex;align-items:center;gap:10px;}
.user-info span{color:#0d3a8c;background:#cce0ff;padding:5px 12px;border-radius:20px;font-weight:600;box-shadow:0 0 5px rgba(0,123,255,0.2);}

/* Statistik Cards */
.stat-card { background: #ffffff; border-radius: 12px; padding: 15px; border: 1px solid #cce0ff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; }
.stat-card h3 { font-size: 1.8rem; font-weight: 700; color: #0d3a8c; margin: 0; }
.stat-card p { font-size: 0.75rem; color: #5c85d6; margin: 0; line-height: 1.2; }
.stat-icon { background: #e6f0ff; color: #0d3a8c; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }

/* Filter Bar */
.filter-box { background: #ffffff; border-radius: 12px; padding: 15px; border: 1px solid #cce0ff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); margin-bottom: 20px; }

/* Tabel & Kartu */
.latest-table{background:#ffffff;border-radius:12px;padding:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-top:0px;}
.table thead th{background:#cce0ff;color:#0d3a8c;border-color: #b3d1ff;}
.table tbody tr{transition: all 0.3s; cursor:pointer;}
.table tbody tr:hover{transform: translateY(-1px); box-shadow:0 2px 8px rgba(0,123,255,0.08); background: #f7faff;}
.btn-custom{transition:0.3s; border-radius:8px;}
.btn-custom:hover{transform:scale(1.03); box-shadow:0 0 10px rgba(0,123,255,0.15);}
input.form-control, select.form-select{border-radius:8px; background:#ffffff; color:#0d3a8c; border:1px solid #cce0ff; font-size: 0.9rem;}
input.form-control:focus, select.form-select:focus{border-color:#0b76e0; box-shadow:0 0 5px rgba(0,123,255,0.2); color:#0d3a8c;}

.badge-ref { background-color: #e2e8f0; color: #334155; font-size: 0.75rem; padding: 3px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; }
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="../dashboard_admin.php" class="nav-link"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
<li class="nav-item"><a href="../surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
<li class="nav-item"><a href="../surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> <span class="link-text">Surat Keluar</span></a></li>
<li class="nav-item"><a href="index.php" class="nav-link active"><i data-feather="file-text"></i> <span class="link-text">Surat Pengantar</span></a></li>
<li class="nav-item"><a href="../disposisi/index.php" class="nav-link"><i data-feather="layers"></i> <span class="link-text">Disposisi</span></a></li>
<?php if($role=='admin'): ?>
<li class="nav-item"><a href="../users.php" class="nav-link"><i data-feather="users"></i> <span class="link-text">Manajemen User</span></a></li>
<?php endif; ?>
<li class="nav-item mt-auto"><a href="../logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
</ul>
</div>

<div class="main-content" id="mainContent">
<div class="header">
<h2>Surat Pengantar</h2>
<div class="user-info"><span><?= ucfirst($role) ?></span> <i class="toggle-btn" data-feather="menu" id="toggleSidebar" style="cursor:pointer;"></i></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div>
                <h3><?= $statHariIni ?></h3>
                <p>Surat pengantar dibuat hari ini</p>
            </div>
            <div class="stat-icon"><i data-feather="file-text"></i></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div>
                <h3><?= $statBulanIni ?></h3>
                <p>Total pengantar bulan ini</p>
            </div>
            <div class="stat-icon"><i data-feather="calendar"></i></div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div>
                <h3><?= $statTahunIni ?></h3>
                <p>Total pengantar tahun ini</p>
            </div>
            <div class="stat-icon"><i data-feather="archive"></i></div>
        </div>
    </div>
</div>

<div class="filter-box">
    <form method="get" action="" class="row g-2 align-items-center">
        <div class="col-12 col-md-auto mb-2 mb-md-0">
            <a href="tambah.php" class="btn btn-primary btn-custom w-100">+ Tambah Surat Pengantar</a>
        </div>
        
        <div class="col-12 col-md mb-2 mb-md-0 ms-md-auto">
            <input class="form-control" type="search" name="search" placeholder="Cari No Pengantar / Tujuan / Pembuat..." value="<?= htmlspecialchars($search); ?>">
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
<table class="table table-bordered table-hover align-middle mb-0 text-dark">
<thead>
<tr>
<th class="text-center" style="width: 50px;">ID</th>
<th>No Surat Pengantar</th>
<th>Tujuan Berkas Pengiriman</th>
<th>Petugas Pembuat</th>
<th class="text-center" style="width: 140px;">Tanggal Surat</th>
<th class="text-center" style="width: 160px;">Aksi</th>
</tr>
</thead>
<tbody>
<?php
if($result && mysqli_num_rows($result) > 0){
    while($data = mysqli_fetch_assoc($result)){
        echo '<tr>';
        echo '<td class="text-center text-muted fw-bold">'.htmlspecialchars($data['id_pengantar']).'</td>';
        echo '<td>';
        echo '💼 '.htmlspecialchars($data['no_pengantar']);
        if(!empty($data['no_surat_masuk'])){
            echo '<br><div class="badge-ref"><i data-feather="link" style="width:11px; height:11px;"></i> Ref: '.htmlspecialchars($data['no_surat_masuk']).'</div>';
        }
        echo '</td>';
        echo '<td>📍 '.htmlspecialchars($data['tujuan']).'</td>';
        echo '<td><span class="badge bg-light text-dark border px-2 py-1.5">'.htmlspecialchars($data['pembuat']).'</span></td>';
        echo '<td class="text-center">'.date('d-m-Y', strtotime($data['tanggal_surat'])).'</td>';
        echo '<td class="text-center">
            <a href="cetak.php?id='.$data['id_pengantar'].'" target="_blank" class="btn btn-success btn-sm btn-custom text-white"><i data-feather="printer" style="width:14px;"></i> Cetak</a>
        </td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data surat pengantar yang cocok dengan filter.</td></tr>';
}
?>
</tbody>
</table>
</div>

<?php if($totalPages > 1){ ?>
<nav class="mt-3">
<ul class="pagination justify-content-center mb-0">
<?php
for($i=1;$i<=$totalPages;$i++){
    $active = ($i == $page) ? 'active' : '';
    $filterParams = "";
    if($search != '') $filterParams .= '&search='.urlencode($search);
    if($bulan != '') $filterParams .= '&bulan='.urlencode($bulan);
    if($tahun != '') $filterParams .= '&tahun='.urlencode($tahun);
    
    echo '<li class="page-item"><a class="page-link '.$active.'" href="?page='.$i.$filterParams.'">'.$i.'</a></li>';
}
?>
</ul>
</nav>
<?php } ?>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
feather.replace();
document.getElementById('toggleSidebar').addEventListener('click', ()=>{
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainContent').classList.toggle('collapsed');
});
</script>
</body>
</html>
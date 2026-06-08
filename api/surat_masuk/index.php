<?php 
session_start();
if(!isset($_SESSION['login'])) header("Location: ../login.php");

include '../config.php';
$role = $_SESSION['role'];

// Base URL untuk file
$base_url = "http://localhost/surat_menyurat/";

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Filter & Search Input
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$metode = isset($_GET['metode']) ? mysqli_real_escape_string($conn, $_GET['metode']) : '';
$bulan  = isset($_GET['bulan']) ? mysqli_real_escape_string($conn, $_GET['bulan']) : '';
$tahun  = isset($_GET['tahun']) ? mysqli_real_escape_string($conn, $_GET['tahun']) : '';

// Menyusun Klausa WHERE Dinamis berdasarkan Filter Gambar 1
$whereClauses = [];
if($search != ''){
    $whereClauses[] = "(no_surat LIKE '%$search%' OR pengirim LIKE '%$search%' OR perihal LIKE '%$search%')";
}
if($metode != '' && $metode != 'Metode Pengiriman'){
    $whereClauses[] = "metode_pengiriman = '$metode'";
}
if($bulan != '' && $bulan != 'Lihat Semua Bulan'){
    $whereClauses[] = "MONTH(tanggal) = '$bulan'";
}
if($tahun != '' && $tahun != 'Tahun'){
    $whereClauses[] = "YEAR(tanggal) = '$tahun'";
}

$whereQuery = "";
if(count($whereClauses) > 0){
    $whereQuery = " WHERE " . implode(" AND ", $whereClauses);
}

// Total data untuk pagination
$totalQuery = "SELECT COUNT(*) as total FROM surat_masuk" . $whereQuery;
$totalResult = mysqli_query($conn, $totalQuery);
$totalData = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalData / $limit);

// Data tabel utama
$dataQuery = "SELECT * FROM surat_masuk" . $whereQuery . " ORDER BY id_masuk DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $dataQuery);

// --- LOGIKA STATISTIK (Mengadopsi Angka Ringkasan Gambar 1) ---
// Hari ini loket
$qLoketHariIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_masuk WHERE DATE(tanggal) = CURDATE() AND (metode_pengiriman = 'Loket' OR metode_pengiriman IS NULL)");
$statLoketHariIni = mysqli_fetch_assoc($qLoketHariIni)['total'];

// Hari ini email
$qEmailHariIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_masuk WHERE DATE(tanggal) = CURDATE() AND metode_pengiriman = 'Email'");
$statEmailHariIni = mysqli_fetch_assoc($qEmailHariIni)['total'];

// Total Hari ini
$statTotalHariIni = $statLoketHariIni + $statEmailHariIni;

// Total Bulan Ini
$qBulanIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_masuk WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
$statBulanIni = mysqli_fetch_assoc($qBulanIni)['total'];

// Total Tahun Ini
$qTahunIni = mysqli_query($conn, "SELECT COUNT(*) as total FROM surat_masuk WHERE YEAR(tanggal) = YEAR(CURDATE())");
$statTahunIni = mysqli_fetch_assoc($qTahunIni)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Surat Masuk - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#e6f0ff; color:#0d3a8c; }

/* Sidebar */
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

/* Statistik Cards (Style Archivista) */
.stat-card { background: #ffffff; border-radius: 12px; padding: 15px; border: 1px solid #cce0ff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; }
.stat-card h3 { font-size: 1.8rem; font-weight: 700; color: #0d3a8c; margin: 0; }
.stat-card p { font-size: 0.75rem; color: #5c85d6; margin: 0; line-height: 1.2; }
.stat-icon { background: #e6f0ff; color: #0d3a8c; padding: 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }

/* Filter Bar */
.filter-box { background: #ffffff; border-radius: 12px; padding: 15px; border: 1px solid #cce0ff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); margin-bottom: 20px; }

/* Tabel & kartu */
.latest-table{background:#ffffff;border-radius:12px;padding:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-top:0px;}
.table thead th{background:#cce0ff;color:#0d3a8c;border-color: #b3d1ff;}
.table tbody tr{transition: all 0.3s; cursor:pointer;}
.table tbody tr:hover{transform: translateY(-1px); box-shadow:0 2px 8px rgba(0,123,255,0.08); background: #f7faff;}
.btn-custom{transition:0.3s; border-radius:8px;}
.btn-custom:hover{transform:scale(1.03); box-shadow:0 0 10px rgba(0,123,255,0.15);}
input.form-control, select.form-select{border-radius:8px; background:#ffffff; color:#0d3a8c; border:1px solid #cce0ff; font-size: 0.9rem;}
input.form-control:focus, select.form-select:focus{border-color:#0b76e0; box-shadow:0 0 5px rgba(0,123,255,0.2); color:#0d3a8c;}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="../dashboard_admin.php" class="nav-link"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
<li class="nav-item"><a href="index.php" class="nav-link active"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
<li class="nav-item"><a href="../surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> <span class="link-text">Surat Keluar</span></a></li>
<li class="nav-item"><a href="../disposisi/index.php" class="nav-link"><i data-feather="file-text"></i> <span class="link-text">Disposisi</span></a></li>
<?php if($role=='admin'): ?>
<li class="nav-item"><a href="../users.php" class="nav-link"><i data-feather="users"></i> <span class="link-text">Manajemen User</span></a></li>
<?php endif; ?>
<li class="nav-item mt-auto"><a href="../logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
</ul>
</div>

<div class="main-content" id="mainContent">
<div class="header">
<h2>Surat Masuk</h2>
<div class="user-info"><span><?= ucfirst($role) ?></span> <i class="toggle-btn" data-feather="menu" id="toggleSidebar"></i></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md">
        <div class="stat-card">
            <div>
                <h3><?= $statLoketHariIni ?></h3>
                <p>Surat masuk dari loket hari ini</p>
            </div>
            <div class="stat-icon"><i data-feather="mail"></i></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card">
            <div>
                <h3><?= $statEmailHariIni ?></h3>
                <p>Surat masuk dari email hari ini</p>
            </div>
            <div class="stat-icon" style="font-weight:bold; font-size:1.1rem; width:40px; height:40px;">@</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card">
            <div>
                <h3><?= $statTotalHariIni ?></h3>
                <p>Total surat hari ini email dan loket</p>
            </div>
            <div class="stat-icon"><i data-feather="check-square"></i></div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="stat-card">
            <div>
                <h3><?= $statBulanIni ?></h3>
                <p>Total surat bulan ini email dan loket</p>
            </div>
            <div class="stat-icon"><i data-feather="calendar"></i></div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="stat-card">
            <div>
                <h3><?= $statTahunIni ?></h3>
                <p>Total surat tahun ini email dan loket</p>
            </div>
            <div class="stat-icon"><i data-feather="archive"></i></div>
        </div>
    </div>
</div>

<div class="filter-box">
    <form method="get" action="" class="row g-2 align-items-center">
        <div class="col-12 col-md-auto mb-2 mb-md-0">
            <a href="tambah.php" class="btn btn-primary btn-custom w-100">+ Tambah Surat Masuk</a>
        </div>
        
        <div class="col-12 col-md mb-2 mb-md-0 ms-md-auto">
            <input class="form-control" type="search" name="search" placeholder="Cari No Surat / Pengirim / Perihal..." value="<?= htmlspecialchars($search); ?>">
        </div>
        
        <div class="col-6 col-md-auto mb-2 mb-md-0">
            <select class="form-select" name="metode">
                <option value="">Metode Pengiriman</option>
                <option value="Loket" <?= $metode == 'Loket' ? 'selected' : '' ?>>Loket</option>
                <option value="Email" <?= $metode == 'Email' ? 'selected' : '' ?>>Email</option>
            </select>
        </div>

        <div class="col-6 col-md-auto mb-2 mb-md-0">
            <select class="form-select" name="bulan">
                <option value="">Lihat Semua Bulan</option>
                <?php 
                $namaBulan = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktber","November","Desember"];
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

        <div class="col-6 col-md-auto mb-2 mb-md-0">
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
<th>No Surat</th>
<th>Kode Surat</th>
<th>Tanggal Terima</th>
<th>Pengirim</th>
<th>Perihal</th>
<th>Metode</th>
<th class="text-center">File</th>
<th class="text-center" style="width: 160px;">Aksi</th>
</tr>
</thead>
<tbody>
<?php
if(mysqli_num_rows($result) > 0){
    while($data = mysqli_fetch_assoc($result)){
        $filePath = (!empty($data['file_surat'])) ? $base_url.$data['file_surat'] : '';
        // Cek fallback jika kolom baru database belum dibuat
        $kodeSurat = isset($data['kode_surat']) ? $data['kode_surat'] : '-';
        $metodeKirim = isset($data['metode_pengiriman']) ? $data['metode_pengiriman'] : 'Loket';
        
        echo '<tr>';
        echo '<td class="text-center text-muted fw-bold">'.htmlspecialchars($data['id_masuk']).'</td>';
        echo '<td>✉️ '.htmlspecialchars($data['no_surat']).'</td>';
        echo '<td><code class="text-primary bg-light px-2 py-1 rounded" style="font-size:0.8rem;">'.htmlspecialchars($kodeSurat).'</code></td>';
        echo '<td>'.htmlspecialchars($data['tanggal']).'</td>';
        echo '<td class="fw-semibold">'.htmlspecialchars($data['pengirim']).'</td>';
        echo '<td>📝 '.htmlspecialchars($data['perihal']).'</td>';
        echo '<td><span class="badge bg-light text-dark border px-2 py-1.5">'.htmlspecialchars($metodeKirim).'</span></td>';
        echo '<td class="text-center">';
        if($filePath) echo '<a href="'.htmlspecialchars($filePath).'" target="_blank" class="btn btn-sm btn-primary btn-custom">📄 Lihat</a>';
        else echo '<span class="text-muted small">Tidak ada file</span>';
        echo '</td>';
        echo '<td class="text-center">
            <a href="edit.php?id='.$data['id_masuk'].'" class="btn btn-warning btn-sm btn-custom text-white mb-1 mb-md-0">✏️ Edit</a> 
            <a href="hapus.php?id='.$data['id_masuk'].'" onclick="return confirm(\'Yakin ingin menghapus?\');" class="btn btn-danger btn-sm btn-custom">🗑️ Hapus</a>
        </td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="9" class="text-center text-muted py-4">Belum ada data surat masuk yang cocok dengan filter.</td></tr>';
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
    // Mempertahankan filter saat pindah halaman
    $filterParams = "";
    if($search != '') $filterParams .= '&search='.urlencode($search);
    if($metode != '') $filterParams .= '&metode='.urlencode($metode);
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
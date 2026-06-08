<?php
session_start();
if(!isset($_SESSION['login'])) header("Location: login.php");

include "config.php";
$role = $_SESSION['role'];

// Hitung data
$jm_surat_masuk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_masuk"))['total'];
$jm_surat_keluar = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_keluar"))['total'];
$jm_disposisi = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM disposisi"))['total'];
// Tambahan perhitungan untuk Surat Pengantar
$jm_surat_pengantar = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_pengantar"))['total'];

// Data terbaru (Limit 5)
$latest_surat_masuk = mysqli_query($conn, "SELECT * FROM surat_masuk ORDER BY id_masuk DESC LIMIT 5");
$latest_surat_keluar = mysqli_query($conn, "SELECT * FROM surat_keluar ORDER BY id_keluar DESC LIMIT 5");
$latest_disposisi = mysqli_query($conn, "SELECT d.*, s.no_surat FROM disposisi d LEFT JOIN surat_masuk s ON d.id_masuk=s.id_masuk ORDER BY id_disposisi DESC LIMIT 5");
// Tambahan query untuk menarik Surat Pengantar Terbaru
$latest_surat_pengantar = mysqli_query($conn, "SELECT sp.*, sm.no_surat AS no_surat_masuk FROM surat_pengantar sp LEFT JOIN surat_masuk sm ON sp.id_surat_masuk = sm.id_masuk ORDER BY sp.id_pengantar DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
    font-family:'Inter',sans-serif;
    background:#e6f0ff; /* soft blue background */
    color:#0d3a8c; /* dark blue text */
    overflow-x:hidden;
}
.sidebar {
    height:100vh;
    width:240px;
    background:#ffffff;
    color:#0d3a8c;
    position:fixed;
    display:flex;
    flex-direction:column;
    transition:0.3s;
    box-shadow:2px 0 10px rgba(0,0,0,0.05);
}
.sidebar.collapsed{width:70px;}
.sidebar h4{text-align:center;margin:20px 0;font-weight:700;color:#0d3a8c;}
.sidebar .nav-link{color:#0d3a8c;margin:5px 10px;border-radius:8px;transition:0.3s;}
.sidebar .nav-link:hover, .sidebar .nav-link.active{background:#cce0ff;color:#0d3a8c;box-shadow:0 0 8px rgba(0,123,255,0.1);}
.sidebar .nav-link i{margin-right:10px;}
.sidebar.collapsed .link-text{display:none;}
.main-content{margin-left:240px;padding:20px;transition:0.3s;}
.main-content.collapsed{margin-left:70px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.header h2{font-weight:700;color:#0d3a8c;}
.user-info{display:flex;align-items:center;gap:10px;transition:0.3s;}
.feature-card{
    background:#ffffff;
    padding:25px;
    border-radius:15px;
    text-align:center;
    transition:0.3s;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
.feature-card i{font-size:2.5rem;margin-bottom:10px;color:#0d3a8c;}
.feature-card:hover{transform:translateY(-3px) scale(1.02);box-shadow:0 4px 15px rgba(0,123,255,0.1);cursor:pointer;}
.latest-table{
    border-radius:15px;
    padding:20px;
    margin-top:20px;
    box-shadow:0 2px 12px rgba(0,0,0,0.05);
    background:#ffffff;
}
.latest-table table{
    border-radius:12px;
    overflow:hidden;
}
.latest-table table th, .latest-table table td{
    padding:12px;
    vertical-align:middle;
}
.latest-table table thead{
    background:#cce0ff; /* soft blue header */
}
.latest-table table tbody tr:hover{
    background:#b3d1ff; /* hover soft blue */
}
.table-surat-keluar thead{background:#cce0ff;}
.table-surat-keluar tbody tr:hover{background:#b3d1ff;}
.table-disposisi thead{background:#cce0ff;}
.table-disposisi tbody tr:hover{background:#b3d1ff;}
.table-surat-pengantar thead{background:#cce0ff;}
.table-surat-pengantar tbody tr:hover{background:#b3d1ff;}
.footer{text-align:center;font-size:0.9rem;color:#0d3a8c;margin-top:40px;}
.toggle-btn{cursor:pointer;color:#0d3a8c;}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="dashboard.php" class="nav-link active"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
<li class="nav-item"><a href="surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
<li class="nav-item"><a href="surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> <span class="link-text">Surat Keluar</span></a></li>
<li class="nav-item"><a href="surat_pengantar/index.php" class="nav-link"><i data-feather="link-2"></i> <span class="link-text">Surat Pengantar</span></a></li>
<li class="nav-item"><a href="disposisi/index.php" class="nav-link"><i data-feather="file-text"></i> <span class="link-text">Disposisi</span></a></li>
<?php if($role=='admin'): ?>
<li class="nav-item"><a href="users.php" class="nav-link"><i data-feather="users"></i> <span class="link-text">Manajemen User</span></a></li>
<?php endif; ?>
<li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
</ul>
</div>

<div class="main-content" id="mainContent">
<div class="header">
<h2>Dashboard</h2>
<div class="user-info"><span><?= ucfirst($role) ?></span> <i class="toggle-btn" data-feather="menu" id="toggleSidebar"></i></div>
</div>

<div class="row g-4 mb-4">
<div class="col-md-4">
  <a href="surat_masuk/index.php" class="text-decoration-none">
    <div class="feature-card"><i data-feather="inbox"></i><h5>Surat Masuk & Keluar</h5><p>Digital, cepat, dan aman (Total: <?= $jm_surat_masuk ?>)</p></div>
  </a>
</div>
<div class="col-md-4">
  <a href="tracking.php" class="text-decoration-none">
    <div class="feature-card"><i data-feather="bar-chart-2"></i><h5>Tracking Real-Time</h5><p>Monitor status surat</p></div>
  </a>
</div>
<div class="col-md-4">
  <a href="notifikasi.php" class="text-decoration-none">
    <div class="feature-card"><i data-feather="bell"></i><h5>Notifikasi Otomatis</h5><p>Peringatan untuk setiap update</p></div>
  </a>
</div>
<div class="col-md-4">
  <a href="surat_pengantar/index.php" class="text-decoration-none">
    <div class="feature-card"><i data-feather="link-2"></i><h5>Surat Pengantar</h5><p>Kelola lampiran dokumen (Total: <?= $jm_surat_pengantar ?>)</p></div>
  </a>
</div>
<div class="col-md-4">
  <a href="disposisi/index.php" class="text-decoration-none">
    <div class="feature-card"><i data-feather="file-text"></i><h5>Disposisi Digital</h5><p>Pencarian cepat (Total: <?= $jm_disposisi ?>)</p></div>
  </a>
</div>
<div class="col-md-4">
  <a href="dashboard_interaktif.php" class="text-decoration-none">
    <div class="feature-card"><i data-feather="grid"></i><h5>Dashboard Interaktif</h5><p>Monitoring aktivitas surat</p></div>
  </a>
</div>
</div>

<div class="latest-table">
<h5 class="mb-3">Surat Pengantar Terbaru</h5>
<table class="table table-hover mb-0 table-surat-pengantar">
<thead><tr><th>Nomor Pengantar</th><th>Tujuan</th><th>Penandatangan</th><th>Ref Surat Masuk</th></tr></thead>
<tbody>
<?php if(mysqli_num_rows($latest_surat_pengantar) > 0): ?>
    <?php while($row=mysqli_fetch_assoc($latest_surat_pengantar)): ?>
    <tr>
    <td class="fw-bold"><?= htmlspecialchars($row['no_pengantar']) ?></td>
    <td><?= htmlspecialchars($row['tujuan']) ?></td>
    <td><?= htmlspecialchars($row['pengirim_surat'] ?? '-') ?></td>
    <td><?= !empty($row['no_surat_masuk']) ? htmlspecialchars($row['no_surat_masuk']) : '<span class="text-muted">-</span>' ?></td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data surat pengantar.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div class="latest-table">
<h5 class="mb-3">Surat Masuk Terbaru</h5>
<table class="table table-hover mb-0 table-summary">
<thead><tr><th>No Surat</th><th>Perihal</th><th>Pengirim</th><th>File</th></tr></thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($latest_surat_masuk)): ?>
<tr>
<td><?= htmlspecialchars($row['no_surat']) ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['pengirim']) ?></td>
<td><?php if($row['file_surat']) echo '📄 <a href="'.$row['file_surat'].'" target="_blank">Lihat</a>'; else echo '❌'; ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="latest-table">
<h5 class="mb-3">Surat Keluar Terbaru</h5>
<table class="table table-hover mb-0 table-surat-keluar">
<thead><tr><th>No Surat</th><th>Perihal</th><th>Penerima</th><th>File</th></tr></thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($latest_surat_keluar)): ?>
<tr>
<td><?= htmlspecialchars($row['no_surat']) ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['penerima']) ?></td>
<td><?php if($row['file_surat']) echo '📄 <a href="'.$row['file_surat'].'" target="_blank">Lihat</a>'; else echo '❌'; ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="latest-table p-3"> <h5 class="mb-3">Disposisi Terbaru</h5>
<table class="table table-hover mb-0 table-disposisi">
<thead><tr><th>No Surat</th><th>Tujuan</th><th>Instruksi</th><th>Tanggal</th></tr></thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($latest_disposisi)): ?>
<tr>
<td><?= htmlspecialchars($row['no_surat']) ?></td>
<td><?= htmlspecialchars($row['tujuan']) ?></td>
<td><?= htmlspecialchars($row['instruksi']) ?></td>
<td><?= htmlspecialchars($row['tanggal_disposisi']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="footer">&copy; <?= date('Y'); ?> Archivista - Kementerian ATR/BPN</div>
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
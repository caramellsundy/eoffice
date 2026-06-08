<?php
session_start();
if(!isset($_SESSION['login']) || $_SESSION['role']!='user'){
    header("Location: login.php"); exit;
}
include 'config.php';

// Total surat
$jm_surat_masuk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_masuk"))['total'];
$jm_surat_keluar = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_keluar"))['total'];

// Surat baru (belum dibaca)
$newSuratMasuk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_masuk WHERE is_read=0"))['total'];
$newSuratKeluar = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_keluar WHERE is_read=0"))['total'];

// Surat terbaru
$latestSuratMasuk = mysqli_query($conn,"SELECT * FROM surat_masuk ORDER BY id_masuk DESC LIMIT 5");
$latestSuratKeluar = mysqli_query($conn,"SELECT * FROM surat_keluar ORDER BY id_keluar DESC LIMIT 5");

// Data chart bulanan (dummy)
$months = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
$surat_masuk_month = [5,8,12,10,7,9,14,11,6,15,9,12];
$surat_keluar_month = [3,6,8,7,5,10,12,9,4,11,7,10];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard User - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body, html{font-family:'Inter',sans-serif;margin:0;padding:0;width:100%;height:100%;background:#e6f0ff;color:#000;}
.sidebar{position:fixed;left:0;top:0;width:220px;height:100vh;background:#1a3b5d;color:#fff;padding:20px;display:flex;flex-direction:column;z-index:2;}
.sidebar h4{text-align:center;margin-bottom:30px;color:#a3d2f2;}
.sidebar .nav-link{color:#cfd8dc;margin:5px 0;border-radius:8px;transition:all 0.2s;display:flex;justify-content:space-between;align-items:center;}
.sidebar .nav-link:hover{background:#3b6ea5;color:#fff;transform:scale(1.02);}
.sidebar .nav-link i{margin-right:10px;}
.main-content{margin-left:220px;padding:20px;position:relative;z-index:2;transition:0.3s;}
.header h2{font-weight:600;color:#1a3b5d;text-shadow:0 0 5px #a3d2f2;margin-bottom:20px;}
.card-dashboard{border-radius:20px;position:relative;overflow:hidden;box-shadow:0 0 15px rgba(0,0,0,0.3);opacity:0;transform:translateY(20px);animation:fadeUp 0.8s forwards;margin-bottom:20px;cursor:pointer;}
.card-dashboard:hover{transform:translateY(-6px) scale(1.03);box-shadow:0 0 25px #a3d2f2,0 0 40px rgba(163,210,242,0.5);}
.card-dashboard .card-icon{font-size:3rem;opacity:0.85;transition:0.3s;}
.bg-surats{background:linear-gradient(135deg,#5dade2,#85c1e9);color:#fff;}
.bg-suratk{background:linear-gradient(135deg,#82e0aa,#5dade2);color:#fff;}
.latest-table{padding:15px;margin-top:20px;background:#d0ebff;border-radius:12px;box-shadow:0 0 20px rgba(0,0,0,0.2);}
.table-scroll{overflow-x:auto;overflow-y:auto;max-height:300px;}
.table-scroll table{min-width:600px;}
.table-hover tbody tr:hover{background:#b3d1ff;cursor:pointer;}
.badge-new{background:#e74c3c;color:#fff;font-size:0.75rem;padding:3px 6px;border-radius:12px;}
.chart-container{background:#d0ebff;padding:15px;border-radius:12px;box-shadow:0 0 15px rgba(0,0,0,0.2);margin-top:15px;opacity:0;transform:translateY(20px);animation:fadeUp 0.8s forwards;}
.chart-flex{display:flex;gap:20px;flex-wrap:wrap;}
.chart-flex .chart-container{flex:1;min-width:300px;}
.footer{text-align:center;font-size:0.9rem;color:#1a3b5d;margin-top:40px;opacity:0.6;}
@keyframes fadeUp{0%{opacity:0;transform:translateY(20px);}100%{opacity:1;transform:translateY(0);}}
</style>
</head>
<body>

<div class="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="dashboard_user.php" class="nav-link active"><i data-feather="home"></i> Dashboard <span class="badge bg-danger ms-1"><?= $newSuratMasuk+$newSuratKeluar ?></span></a></li>
<li class="nav-item"><a href="surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> Surat Masuk <span class="badge bg-danger ms-1"><?= $newSuratMasuk ?></span></a></li>
<li class="nav-item"><a href="surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> Surat Keluar <span class="badge bg-danger ms-1"><?= $newSuratKeluar ?></span></a></li>
<li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> Logout</a></li>
</ul>
</div>

<div class="main-content">
<div class="header"><h2>Halo, Anna!</h2></div>

<div class="row g-4">
<div class="col-md-6">
<div class="card card-dashboard bg-surats" onclick="window.location='surat_masuk/index.php'">
<i data-feather="inbox" class="me-3 card-icon"></i>
<div><h5>Surat Masuk</h5><small><?= $jm_surat_masuk ?> Surat</small></div>
</div>
</div>
<div class="col-md-6">
<div class="card card-dashboard bg-suratk" onclick="window.location='surat_keluar/index.php'">
<i data-feather="send" class="me-3 card-icon"></i>
<div><h5>Surat Keluar</h5><small><?= $jm_surat_keluar ?> Surat</small></div>
</div>
</div>
</div>

<!-- Tabel Surat Masuk & Keluar -->
<div class="latest-table">
<h5>Surat Masuk & Keluar Terbaru</h5>
<div class="table-scroll">
<table class="table table-hover mb-0" style="background:#85c1e9;">
<thead style="background:#5dade2;">
<tr><th>No Surat</th><th>Perihal</th><th>Pengirim/Tujuan</th><th>File</th></tr>
</thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($latestSuratMasuk)): ?>
<tr onclick="window.location='surat_masuk/detail.php?id=<?= $row['id_masuk'] ?>'">
<td><?= htmlspecialchars($row['no_surat']) ?> <?= $row['is_read']==0?'<span class="badge-new">New</span>':'' ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['pengirim']) ?></td>
<td><?= $row['file_surat']? '<a href="'.$row['file_surat'].'" target="_blank" class="btn btn-sm btn-primary">Lihat</a>':'-' ?></td>
</tr>
<?php endwhile; ?>
<?php while($row=mysqli_fetch_assoc($latestSuratKeluar)): ?>
<tr onclick="window.location='surat_keluar/detail.php?id=<?= $row['id_keluar'] ?>'">
<td><?= htmlspecialchars($row['no_surat']) ?> <?= $row['is_read']==0?'<span class="badge-new">New</span>':'' ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['tujuan']) ?></td>
<td><?= $row['file_surat']? '<a href="'.$row['file_surat'].'" target="_blank" class="btn btn-sm btn-primary">Lihat</a>':'-' ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- Widget Iklan & Rekomendasi -->
<div class="latest-table">
<h5>Rekomendasi & New Information</h5>
<div class="table-scroll">
<table class="table table-hover mb-0" style="background:#d0ebff;">
<thead style="background:#5dade2; color:#fff;">

</thead>
<tbody>
<tr><td>Produktivitas & Kantor</td><td>Software manajemen proyek, alat kolaborasi tim, platform video conference berbayar.</td><td><a href="#" class="btn btn-sm btn-primary" target="_blank">Cek</a></td></tr>
<tr><td>Keamanan Data</td><td>Layanan cloud storage premium, sertifikasi keamanan, solusi anti-phishing.</td><td><a href="#" class="btn btn-sm btn-primary" target="_blank">Cek</a></td></tr>
<tr><td>Layanan Pelengkap</td><td>Platform Tanda Tangan Digital, software e-office, layanan mengurus pajak tanah.</td><td><a href="#" class="btn btn-sm btn-primary" target="_blank">Cek</a></td></tr>
</tbody>
</table>
</div>
</div>

<!-- Compact Charts (2 side by side) -->
<div class="chart-flex">
<div class="chart-container">
<h6>Distribusi Surat</h6>
<canvas id="doughnutChart" height="200"></canvas>
</div>
<div class="chart-container">
<h6>Tren Bulanan Surat</h6>
<canvas id="lineChart" height="200"></canvas>
</div>
</div>

<div class="footer">&copy; <?= date('Y'); ?> Archivista - Sistem Arsip Digital</div>
</div>

<script>
feather.replace();

// Doughnut Chart
new Chart(document.getElementById('doughnutChart'),{
type:'doughnut',
data:{labels:['Surat Masuk','Surat Keluar'],
datasets:[{data:[<?= $jm_surat_masuk ?>,<?= $jm_surat_keluar ?>], backgroundColor:['#5dade2','#82e0aa'],borderColor:'#fff',borderWidth:2}]},
options:{responsive:true,plugins:{legend:{position:'bottom'}},animation:{duration:1200}}
});

// Line Chart
new Chart(document.getElementById('lineChart'),{
type:'line',
data:{labels:<?= json_encode($months); ?>,
datasets:[
{label:'Surat Masuk',data:<?= json_encode($surat_masuk_month); ?>,borderColor:'#5dade2',backgroundColor:'rgba(93,173,226,0.3)',fill:true,tension:0.4},
{label:'Surat Keluar',data:<?= json_encode($surat_keluar_month); ?>,borderColor:'#82e0aa',backgroundColor:'rgba(130,224,170,0.3)',fill:true,tension:0.4}
]},
options:{responsive:true,plugins:{legend:{position:'bottom'}},animation:{duration:1200}}
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

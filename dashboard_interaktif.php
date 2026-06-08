<?php 
session_start();
if(!isset($_SESSION['login'])) header("Location: login.php");

include "config.php";
$role = $_SESSION['role'];

// Hitung data
$jm_surat_masuk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_masuk"))['total'];
$jm_surat_keluar = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_keluar"))['total'];
$jm_disposisi = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM disposisi"))['total'];

// Aktivitas terbaru
$latest_activities = mysqli_query($conn, "
    SELECT 'Masuk' AS type, no_surat AS no, perihal, pengirim AS pihak, tanggal AS date FROM surat_masuk
    UNION ALL
    SELECT 'Keluar', no_surat, perihal, penerima AS pihak, tanggal_keluar AS date FROM surat_keluar
    UNION ALL
    SELECT 'Disposisi', id_disposisi, instruksi AS perihal, tujuan AS pihak, tanggal_disposisi AS date FROM disposisi
    ORDER BY date DESC
    LIMIT 20
");

// Chart bulanan (dummy data)
$months = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
$surat_masuk_month = [5,8,12,10,7,9,14,11,6,15,9,12];
$surat_keluar_month = [3,6,8,7,5,10,12,9,4,11,7,10];
$disposisi_month = [2,4,5,6,3,5,7,6,4,8,6,5];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Interaktif - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{
    font-family:'Inter',sans-serif;
    background:#e6f0ff; /* soft biru */
    color:#0f1a2b;
    overflow-x:hidden;
}
.sidebar{
    height:100vh;
    width:240px;
    background:#cce0ff;
    color:#0f1a2b;
    position:fixed;
    display:flex;
    flex-direction:column;
    box-shadow:2px 0 20px rgba(0,0,0,0.1);
}
.sidebar h4{
    text-align:center;
    margin:20px 0;
    font-weight:700;
    color:#0f1a2b;
}
.sidebar .nav-link{
    color:#0f1a2b;
    margin:5px 10px;
    border-radius:8px;
    transition:0.3s;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active{
    background:#a3c9ff;
    color:#0f1a2b;
    box-shadow:0 0 15px rgba(0,123,255,0.3);
}
.sidebar .nav-link i{margin-right:10px;}
.main-content{margin-left:240px;padding:20px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.header h2{font-weight:700;color:#0f1a2b;}
.card-dashboard{
    border-radius:20px;
    padding:20px;
    box-shadow:0 0 20px rgba(0,123,255,0.2);
    transition:0.3s;
    cursor:pointer;
    position:relative;
    background:#d9eaff;
}
.card-dashboard:hover{
    transform:scale(1.05);
    box-shadow:0 0 25px #7ebfff,0 0 40px rgba(126,191,255,0.5);
}
.card-dashboard .card-icon{
    font-size:2.5rem;
    opacity:0.85;
    transition:0.3s;
}
.card-badge{
    position:absolute;
    top:15px;
    right:15px;
    background:#7ebfff;
    color:#0f1a2b;
    font-weight:700;
    padding:5px 12px;
    border-radius:50%;
    animation:countUp 2s;
}
.latest-table{
    background:#cce0ff;
    border-radius:12px;
    padding:15px;
    box-shadow:0 0 20px rgba(0,123,255,0.2);
}
.chart-container{
    background:#cce0ff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 0 20px rgba(0,123,255,0.2);
    margin-bottom:20px;
}
.footer{
    text-align:center;
    font-size:0.9rem;
    color:#0f1a2b;
    margin-top:40px;
    opacity:0.7;
}
@keyframes countUp{0%{transform:scale(0)}100%{transform:scale(1)}}
.filter-select{max-width:200px;margin-bottom:15px;}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="dashboard_interaktif.php" class="nav-link active"><i data-feather="home"></i> Dashboard</a></li>
<li class="nav-item"><a href="surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> Surat Masuk</a></li>
<li class="nav-item"><a href="surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> Surat Keluar</a></li>
<li class="nav-item"><a href="disposisi/index.php" class="nav-link"><i data-feather="file-text"></i> Disposisi</a></li>
<li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> Logout</a></li>
</ul>
</div>

<!-- Main Content -->
<div class="main-content">
<div class="header">
<h2>Dashboard Interaktif</h2>
<div class="user-info"><?= ucfirst($role) ?></div>
</div>

<!-- Cards Summary -->
<div class="row g-4 mb-4">
<div class="col-md-4">
<div class="card-dashboard" onclick="location.href='surat_masuk/index.php'">
<i data-feather="inbox" class="card-icon"></i>
<h5 class="mt-2">Surat Masuk</h5>
<p><span class="card-badge"><?= $jm_surat_masuk ?></span> Surat</p>
</div>
</div>
<div class="col-md-4">
<div class="card-dashboard" onclick="location.href='surat_keluar/index.php'">
<i data-feather="send" class="card-icon"></i>
<h5 class="mt-2">Surat Keluar</h5>
<p><span class="card-badge"><?= $jm_surat_keluar ?></span> Surat</p>
</div>
</div>
<div class="col-md-4">
<div class="card-dashboard" onclick="location.href='disposisi/index.php'">
<i data-feather="file-text" class="card-icon"></i>
<h5 class="mt-2">Disposisi</h5>
<p><span class="card-badge"><?= $jm_disposisi ?></span> Dokumen</p>
</div>
</div>
</div>

<!-- Chart Line -->
<div class="chart-container">
<h5 class="mb-3">Tren Bulanan Aktivitas Surat</h5>
<canvas id="lineChart"></canvas>
</div>

<!-- Filter -->
<select id="filterType" class="form-select filter-select">
<option value="">Semua Jenis</option>
<option value="Masuk">Surat Masuk</option>
<option value="Keluar">Surat Keluar</option>
<option value="Disposisi">Disposisi</option>
</select>

<!-- Table Latest Activities -->
<div class="latest-table">
<h5 class="mb-3">Aktivitas Terbaru</h5>
<table class="table table-hover mb-0" id="activityTable">
<thead>
<tr>
<th>Jenis</th>
<th>No Surat / ID</th>
<th>Perihal / Instruksi</th>
<th>Pihak</th>
<th>Tanggal</th>
</tr>
</thead>
<tbody>
<?php while($row = mysqli_fetch_assoc($latest_activities)): ?>
<tr>
<td><?= htmlspecialchars($row['type']) ?></td>
<td><?= htmlspecialchars($row['no']) ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['pihak']) ?></td>
<td><?= htmlspecialchars($row['date']) ?></td>
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

// Chart Line Bulanan soft biru
new Chart(document.getElementById('lineChart').getContext('2d'),{
type:'line',
data:{
labels:<?= json_encode($months); ?>,
datasets:[
{label:'Surat Masuk', data:<?= json_encode($surat_masuk_month); ?>, borderColor:'#4da6ff', backgroundColor:'rgba(77,166,255,0.3)', fill:true, tension:0.4},
{label:'Surat Keluar', data:<?= json_encode($surat_keluar_month); ?>, borderColor:'#3399ff', backgroundColor:'rgba(51,153,255,0.3)', fill:true, tension:0.4},
{label:'Disposisi', data:<?= json_encode($disposisi_month); ?>, borderColor:'#66b3ff', backgroundColor:'rgba(102,179,255,0.3)', fill:true, tension:0.4}
]
},
options:{responsive:true, plugins:{legend:{position:'bottom'}}, animation:{duration:1500}}
});

// Filter tabel aktivitas
document.getElementById('filterType').addEventListener('change', function(){
    const type = this.value.toLowerCase();
    const rows = document.querySelectorAll('#activityTable tbody tr');
    rows.forEach(row => {
        const rowType = row.cells[0].textContent.toLowerCase();
        row.style.display = (type === '' || rowType === type) ? '' : 'none';
    });
});
</script>
</body>
</html>

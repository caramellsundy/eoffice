<?php 
session_start();
if(!isset($_SESSION['login']) || $_SESSION['role'] != 'staff'){
    header("Location: login.php");
    exit;
}
include 'config.php';

// Ambil jumlah data
$jm_surat_masuk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM surat_masuk"))['total'];
$jm_disposisi = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM disposisi"))['total'];

// Surat terbaru
$latestSurat = mysqli_query($conn, "SELECT * FROM surat_masuk ORDER BY id_masuk DESC LIMIT 5");
$latestDisposisi = mysqli_query($conn, "SELECT d.*, s.no_surat FROM disposisi d LEFT JOIN surat_masuk s ON d.id_masuk=s.id_masuk ORDER BY id_disposisi DESC LIMIT 5");

// Data chart bulanan (dummy)
$months = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Agu","Sep","Okt","Nov","Des"];
$surat_masuk_month = [5,8,12,10,7,9,14,11,6,15,9,12];
$surat_disposisi_month = [2,4,6,3,5,7,5,6,4,5,3,4];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Staff - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body, html{
    font-family:'Inter',sans-serif;
    margin:0; padding:0;
    width:100%; height:100%;
    background: #cce0ff; /* soft blue background */
    color:#0d3a8c;
    overflow-x:hidden;
}
.sidebar{
    position:fixed;left:0;top:0;width:220px;height:100vh;
    background:#4a90e2; /* soft blue sidebar */
    color:#fff;padding:20px;
    display:flex;flex-direction:column;transition:0.3s;z-index:2;
}
.sidebar.collapsed{width:70px;}
.sidebar h4{text-align:center;margin-bottom:30px;color:#fff;text-shadow:0 0 6px #a3d2f2;}
.sidebar .nav-link{color:#e0f0ff;margin:5px 0;border-radius:8px;transition:all 0.2s;}
.sidebar .nav-link:hover{background:#357ad7;color:#fff;transform:scale(1.02);}
.sidebar .nav-link i{margin-right:10px;}
.sidebar.collapsed .link-text{display:none;}

.main-content{margin-left:220px;padding:20px;position:relative;z-index:2;transition:0.3s;}
.main-content.collapsed{margin-left:70px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.header h2{font-weight:600;color:#0d3a8c;}
.user-info{display:flex;align-items:center;gap:10px;}

.card-dashboard{
    border-radius:20px;position:relative;overflow:hidden;
    box-shadow:0 0 15px rgba(0,0,0,0.1);
    transition: all 0.3s;
    cursor:pointer;
}
.card-dashboard:hover{
    transform:translateY(-5px) scale(1.02);
    box-shadow:0 0 20px #357ad7,0 0 40px rgba(53,122,215,0.3);
}
.card-dashboard .card-icon{font-size:3rem;opacity:0.85;transition:0.3s;}
.bg-surats{background:linear-gradient(135deg,#5dade2,#85c1e9);color:#fff;}
.bg-dispos{background:linear-gradient(135deg,#82e0aa,#5dade2);color:#fff;}

.latest-table{
    background:#e6f0ff;border-radius:12px;padding:15px;margin-top:20px;
    box-shadow:0 0 15px rgba(0,0,0,0.1);
}
.latest-table table th, .latest-table table td{
    text-align:left; vertical-align:middle; padding:10px;
}
.latest-table table td.file-status{text-align:right;}
.latest-table table tbody tr:hover{background:#d0e4ff;}

.chart-container{
    background:#e6f0ff;padding:20px;border-radius:12px;
    box-shadow:0 0 15px rgba(0,0,0,0.1);margin-top:20px;
}
.footer{text-align:center;font-size:0.9rem;color:#0d3a8c;margin-top:40px;opacity:0.8;}
.toggle-btn{cursor:pointer;color:#0d3a8c;transition:0.3s;}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="dashboard_staff.php" class="nav-link active"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
<li class="nav-item"><a href="surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
<li class="nav-item"><a href="disposisi/index.php" class="nav-link"><i data-feather="file-text"></i> <span class="link-text">Disposisi</span></a></li>
<li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
</ul>
</div>

<div class="main-content" id="mainContent">
<div class="header">
<h2>Selamat Datang, Alya!</h2>
<div class="user-info"><i class="toggle-btn" data-feather="menu" id="toggleSidebar"></i></div>
</div>

<!-- Card Dashboard dengan klik -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card card-dashboard bg-surats p-3" onclick="window.location='surat_masuk/index.php'">
            <div class="d-flex align-items-center">
                <i data-feather="inbox" class="me-3 card-icon"></i>
                <div>
                    <h5>Surat Masuk</h5>
                    <small><?= $jm_surat_masuk ?> Surat</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-dashboard bg-dispos p-3" onclick="window.location='disposisi/index.php'">
            <div class="d-flex align-items-center">
                <i data-feather="file-text" class="me-3 card-icon"></i>
                <div>
                    <h5>Disposisi</h5>
                    <small><?= $jm_disposisi ?> Surat</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Surat Masuk -->
<div class="latest-table">
<h5>Surat Masuk Terbaru</h5>
<table class="table table-hover mb-0">
<thead><tr><th>No Surat</th><th>Perihal</th><th>Pengirim</th><th>File</th></tr></thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($latestSurat)): ?>
<tr>
<td><?= htmlspecialchars($row['no_surat']) ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['pengirim']) ?></td>
<td class="file-status">
<?php if($row['file_surat']): ?>
<a href="<?= $row['file_surat'] ?>" target="_blank" class="btn btn-sm btn-primary">Lihat</a>
<?php else: ?>
<span class="text-muted">Tidak ada file</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- Tabel Disposisi -->
<div class="latest-table">
<h5>Disposisi Terbaru</h5>
<table class="table table-hover mb-0">
<thead><tr><th>No Surat</th><th>Tujuan</th><th>Instruksi</th><th>Tanggal</th></tr></thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($latestDisposisi)): ?>
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

<!-- Chart -->
<div class="row">
<div class="col-md-6">
<div class="chart-container">
<h5>Tren Bulanan Surat Masuk & Disposisi</h5>
<canvas id="lineChart"></canvas>
</div>
</div>
<div class="col-md-6">
<div class="chart-container">
<h5>Distribusi Surat</h5>
<canvas id="doughnutChart"></canvas>
</div>
</div>
</div>

<div class="footer">&copy; <?= date('Y'); ?> Archivista - Sistem Arsip Digital</div>
</div>

<script>
feather.replace();
document.getElementById('toggleSidebar').addEventListener('click', ()=>{
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.getElementById('mainContent').classList.toggle('collapsed');
});

// Line Chart
new Chart(document.getElementById('lineChart').getContext('2d'),{
type:'line',
data:{
    labels:<?= json_encode($months); ?>,
    datasets:[
        {
            label:'Surat Masuk',
            data:<?= json_encode($surat_masuk_month); ?>,
            borderColor:'#5dade2',
            backgroundColor:'rgba(93,173,226,0.3)',
            fill:true,
            tension:0.4,
            pointHoverRadius:8,
            pointHoverBackgroundColor:'#85c1e9'
        },
        {
            label:'Disposisi',
            data:<?= json_encode($surat_disposisi_month); ?>,
            borderColor:'#82e0aa',
            backgroundColor:'rgba(130,224,170,0.3)',
            fill:true,
            tension:0.4,
            pointHoverRadius:8,
            pointHoverBackgroundColor:'#a3d2f2'
        }
    ]
},
options:{
    responsive:true,
    plugins:{legend:{position:'bottom'}},
    animation:{duration:1500},
    hover:{mode:'nearest', intersect:true}
}
});

// Doughnut Chart
new Chart(document.getElementById('doughnutChart').getContext('2d'),{
type:'doughnut',
data:{
    labels:['Surat Masuk','Disposisi'],
    datasets:[{
        data:[<?= $jm_surat_masuk ?>,<?= $jm_disposisi ?>],
        backgroundColor:['#5dade2','#82e0aa'],
        borderColor:'#4a90e2',
        borderWidth:2,
        hoverOffset:10
    }]
},
options:{responsive:true,plugins:{legend:{position:'bottom'}},animation:{duration:1500}}
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

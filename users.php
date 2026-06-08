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
body, html{font-family:'Inter',sans-serif;margin:0;padding:0;width:100%;height:100%;background:#0f0f1a;color:#fff;}
.sidebar{position:fixed;left:0;top:0;width:220px;height:100vh;background:#111127;color:#fff;padding:20px;display:flex;flex-direction:column;z-index:2;}
.sidebar h4{text-align:center;margin-bottom:30px;color:#00f6ff;text-shadow:0 0 10px #00f6ff;}
.sidebar .nav-link{color:#cfd8dc;margin:5px 0;border-radius:8px;transition:all 0.2s;display:flex;justify-content:space-between;align-items:center;}
.sidebar .nav-link:hover{background:#1a1a40;color:#00f6ff;transform:scale(1.02);}
.sidebar .nav-link i{margin-right:10px;}
.main-content{margin-left:220px;padding:20px;position:relative;z-index:2;transition:0.3s;}
.header h2{font-weight:600;color:#00f6ff;text-shadow:0 0 5px #00f6ff;margin-bottom:20px;}
.card-dashboard{border-radius:20px;position:relative;overflow:hidden;box-shadow:0 0 15px rgba(0,0,0,0.3);opacity:0;transform:translateY(20px);animation:fadeUp 0.8s forwards;margin-bottom:20px;}
.card-dashboard:hover{transform:translateY(-6px) scale(1.03);box-shadow:0 0 25px #00f6ff,0 0 40px rgba(0,246,255,0.5);}
.card-dashboard .card-icon{font-size:3rem;opacity:0.85;transition:0.3s;}
.bg-surats{background:linear-gradient(135deg,#6c5ce7,#a29bfe);color:#fff;}
.bg-suratk{background:linear-gradient(135deg,#00b894,#55efc4);color:#fff;}
.latest-table{background:rgba(27,27,47,0.85);border-radius:12px;padding:15px;margin-top:20px;box-shadow:0 0 20px rgba(0,246,255,0.2);}
.latest-table table th, .latest-table table td{text-align:left; vertical-align:middle; padding:10px;}
.latest-table table td.file-status{text-align:right;}
.latest-table table tr{opacity:0; transform:translateY(20px); animation:fadeUpRow 0.8s forwards; position:relative;}
.latest-table table tr .badge-new{position:absolute;top:50%;right:10px;transform:translateY(-50%);background:#e74c3c;color:#fff;font-size:0.75rem;padding:3px 6px;border-radius:12px;}
.chart-container{background:rgba(27,27,47,0.85);padding:20px;border-radius:12px;box-shadow:0 0 20px rgba(0,246,255,0.2);margin-top:20px;opacity:0; transform:translateY(20px); animation:fadeUp 0.8s forwards;}
.chart-flex{display:flex;gap:20px;flex-wrap:wrap;}
.chart-flex .chart-container{flex:1;}
.footer{text-align:center;font-size:0.9rem;color:#00f6ff;margin-top:40px;opacity:0.6;}
@keyframes fadeUp{0%{opacity:0;transform:translateY(20px);}100%{opacity:1;transform:translateY(0);} }
@keyframes fadeUpRow{0%{opacity:0;transform:translateY(20px);}100%{opacity:1;transform:translateY(0);} }
</style>
</head>
<body>

<div class="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item">
    <a href="dashboard_user.php" class="nav-link active">
        <i data-feather="home"></i> Dashboard
        <span class="badge bg-danger ms-1" id="notif-dashboard"><?= $newSuratMasuk+$newSuratKeluar ?></span>
    </a>
</li>
<li class="nav-item">
    <a href="surat_masuk/index.php" class="nav-link">
        <i data-feather="inbox"></i> Surat Masuk
        <span class="badge bg-danger ms-1" id="notif-masuk"><?= $newSuratMasuk ?></span>
    </a>
</li>
<li class="nav-item">
    <a href="surat_keluar/index.php" class="nav-link">
        <i data-feather="send"></i> Surat Keluar
        <span class="badge bg-danger ms-1" id="notif-keluar"><?= $newSuratKeluar ?></span>
    </a>
</li>
<li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> Logout</a></li>
</ul>
</div>

<div class="main-content">
<div class="header"><h2>Halo, <?= $_SESSION['username'] ?></h2></div>

<div class="row g-4">
<div class="col-md-6">
<div class="card card-dashboard bg-surats p-3 position-relative">
<span class="position-absolute top-0 end-0 badge bg-danger rounded-circle" style="transform:translate(50%,-50%)" id="card-masuk"><?= $newSuratMasuk ?></span>
<div class="d-flex align-items-center">
<i data-feather="inbox" class="me-3 card-icon"></i>
<div><h5>Surat Masuk</h5><small><?= $jm_surat_masuk ?> Surat</small></div>
</div>
</div>
</div>

<div class="col-md-6">
<div class="card card-dashboard bg-suratk p-3 position-relative">
<span class="position-absolute top-0 end-0 badge bg-danger rounded-circle" style="transform:translate(50%,-50%)" id="card-keluar"><?= $newSuratKeluar ?></span>
<div class="d-flex align-items-center">
<i data-feather="send" class="me-3 card-icon"></i>
<div><h5>Surat Keluar</h5><small><?= $jm_surat_keluar ?> Surat</small></div>
</div>
</div>
</div>
</div>

<div class="row g-4">
<div class="col-md-6">
<div class="latest-table">
<h5>Surat Masuk Terbaru</h5>
<table class="table table-hover mb-0">
<thead><tr><th>No Surat</th><th>Perihal</th><th>Pengirim</th><th>File</th></tr></thead>
<tbody id="tbody-masuk">
<?php while($row=mysqli_fetch_assoc($latestSuratMasuk)): ?>
<tr>
<td><?= htmlspecialchars($row['no_surat']) ?> <?= $row['is_read']==0?'<span class="badge-new">New</span>':'' ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['pengirim']) ?></td>
<td class="file-status">
<?php if($row['file_surat']): ?><a href="<?= $row['file_surat'] ?>" target="_blank" class="btn btn-sm btn-primary">Lihat</a><?php else: ?><span class="text-muted">Tidak ada file</span><?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<div class="col-md-6">
<div class="latest-table">
<h5>Surat Keluar Terbaru</h5>
<table class="table table-hover mb-0">
<thead><tr><th>No Surat</th><th>Perihal</th><th>Tujuan</th><th>File</th></tr></thead>
<tbody id="tbody-keluar">
<?php while($row=mysqli_fetch_assoc($latestSuratKeluar)): ?>
<tr>
<td><?= htmlspecialchars($row['no_surat']) ?> <?= $row['is_read']==0?'<span class="badge-new">New</span>':'' ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['tujuan']) ?></td>
<td class="file-status">
<?php if($row['file_surat']): ?><a href="<?= $row['file_surat'] ?>" target="_blank" class="btn btn-sm btn-primary">Lihat</a><?php else: ?><span class="text-muted">Tidak ada file</span><?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>

<div class="chart-flex">
<div class="chart-container">
<h5>Distribusi Surat</h5>
<canvas id="doughnutChart"></canvas>
</div>
<div class="chart-container">
<h5>Tren Bulanan Surat</h5>
<canvas id="lineChart"></canvas>
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
datasets:[{data:[<?= $jm_surat_masuk ?>,<?= $jm_surat_keluar ?>],
backgroundColor:['#6c5ce7','#00b894'],borderColor:'#111',borderWidth:2}]},
options:{responsive:true,plugins:{legend:{position:'bottom'}},animation:{duration:1500}}
});

// Line Chart Tren Bulanan
new Chart(document.getElementById('lineChart'),{
type:'line',
data:{
labels:<?= json_encode($months); ?>,
datasets:[
{label:'Surat Masuk',data:<?= json_encode($surat_masuk_month); ?>,borderColor:'#6c5ce7',backgroundColor:'rgba(108,92,231,0.3)',fill:true,tension:0.4},
{label:'Surat Keluar',data:<?= json_encode($surat_keluar_month); ?>,borderColor:'#00b894',backgroundColor:'rgba(0,184,148,0.3)',fill:true,tension:0.4}
]
},
options:{responsive:true,plugins:{legend:{position:'bottom'},tooltip:{mode:'index',intersect:false}},animation:{duration:1500}}
});

// Update notif otomatis
function updateNotif(){
    $.getJSON('ajax_notif.php',function(data){
        $('#notif-dashboard').text(data.masuk+data.keluar>0?data.masuk+data.keluar:'');
        $('#notif-masuk').text(data.masuk>0?data.masuk:'');
        $('#notif-keluar').text(data.keluar>0?data.keluar:'');
        $('#card-masuk').text(data.masuk>0?data.masuk:'');
        $('#card-keluar').text(data.keluar>0?data.keluar:'');

        let tbodyMasuk = '';
        data.latestMasuk.forEach(row=>{
            tbodyMasuk += `<tr>
<td>${row.no_surat} ${row.is_read==0?'<span class="badge-new">New</span>':''}</td>
<td>${row.perihal}</td>
<td>${row.pengirim}</td>
<td class="file-status">${row.file_surat?'<a href="'+row.file_surat+'" target="_blank" class="btn btn-sm btn-primary">Lihat</a>':'<span class="text-muted">Tidak ada file</span>'}</td>
</tr>`;
        });
        $('#tbody-masuk').html(tbodyMasuk);

        let tbodyKeluar = '';
        data.latestKeluar.forEach(row=>{
            tbodyKeluar += `<tr>
<td>${row.no_surat} ${row.is_read==0?'<span class="badge-new">New</span>':''}</td>
<td>${row.perihal}</td>
<td>${row.tujuan}</td>
<td class="file-status">${row.file_surat?'<a href="'+row.file_surat+'" target="_blank" class="btn btn-sm btn-primary">Lihat</a>':'<span class="text-muted">Tidak ada file</span>'}</td>
</tr>`;
        });
        $('#tbody-keluar').html(tbodyKeluar);
    });
}
setInterval(updateNotif,10000);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
if(!isset($_SESSION['login'])) header("Location: login.php");
include "config.php";
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifikasi Real-Time - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;background:#f0f4ff;color:#0f1a2b;overflow-x:hidden;}
.sidebar{height:100vh;width:240px;background:#d0e4ff;color:#0f1a2b;position:fixed;display:flex;flex-direction:column;box-shadow:2px 0 20px rgba(0,0,0,0.1);}
.sidebar h4{text-align:center;margin:20px 0;font-weight:700;color:#0f1a2b;}
.sidebar .nav-link{color:#0f1a2b;margin:5px 10px;border-radius:8px;transition:0.3s;}
.sidebar .nav-link:hover,.sidebar .nav-link.active{background:#a9c9ff;color:#0f1a2b;}
.sidebar .nav-link i{margin-right:10px;}
.main-content{margin-left:240px;padding:20px;}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.header h2{font-weight:700;color:#0f1a2b;}
.user-info{font-weight:600;color:#0f1a2b;}
.badge-notif{background:#7ed6ff;color:#0f1a2b;font-weight:700;padding:5px 10px;border-radius:50%;margin-left:5px;}
.card-notif{background:#e4f0ff;padding:15px;margin-bottom:15px;border-radius:12px;box-shadow:0 0 20px rgba(126,214,255,0.2);opacity:0;transform:translateY(20px);animation:fadeUp 0.6s forwards;cursor:pointer;transition:0.3s;}
.card-notif:hover{transform:translateY(-5px);box-shadow:0 0 25px #7ed6ff,0 0 40px rgba(126,214,255,0.5);}
.card-notif .type{font-weight:700;color:#0f1a2b;}
.card-notif .info{opacity:0.8;color:#0f1a2b;}
.card-notif .date{font-size:0.85rem;color:#0f1a2b;opacity:0.6;}
.footer{text-align:center;font-size:0.9rem;color:#0f1a2b;margin-top:40px;opacity:0.7;}
@keyframes fadeUp{0%{opacity:0;transform:translateY(20px);}100%{opacity:1;transform:translateY(0);}}
</style>
</head>
<body>

<div class="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="dashboard.php" class="nav-link"><i data-feather="home"></i> Dashboard</a></li>
<li class="nav-item"><a href="surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> Surat Masuk</a></li>
<li class="nav-item"><a href="surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> Surat Keluar</a></li>
<li class="nav-item"><a href="disposisi/index.php" class="nav-link"><i data-feather="file-text"></i> Disposisi</a></li>
<li class="nav-item"><a href="notifikasi_realtime.php" class="nav-link active"><i data-feather="bell"></i> Notifikasi <span id="notifBadge" class="badge-notif">0</span></a></li>
<li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> Logout</a></li>
</ul>
</div>

<div class="main-content">
<div class="header">
<h2>Notifikasi Real-Time</h2>
<div class="user-info"><?= ucfirst($role) ?></div>
</div>

<div id="notifContainer"></div>

<div class="footer">&copy; <?= date('Y'); ?> Archivista - Kementerian ATR/BPN</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
feather.replace();

// Fetch notifikasi real-time
function fetchNotifikasi(){
    fetch('ajax_notifikasi.php')
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('notifContainer');
        container.innerHTML = '';
        data.forEach((item, idx) => {
            const card = document.createElement('div');
            card.className = 'card-notif';
            card.style.animationDelay = (idx*0.1)+'s';
            card.innerHTML = `<div class="type">${item.type}</div>
                              <div class="info">${item.title} - ${item.info}</div>
                              <div class="date">${item.date}</div>`;
            card.onclick = ()=>{
                fetch('ajax_mark_read.php?id='+item.id);
                card.style.display='none';
            };
            container.appendChild(card);
        });
        document.getElementById('notifBadge').textContent = data.length;
    });
}

// Jalankan fetch setiap 5 detik
fetchNotifikasi();
setInterval(fetchNotifikasi,5000);
</script>

</body>
</html>

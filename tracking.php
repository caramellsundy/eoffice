<?php
session_start();
if(!isset($_SESSION['login'])) header("Location: login.php");

include "config.php";
$role = $_SESSION['role'];

$status_filter = $_GET['status'] ?? '';
$pengirim_filter = $_GET['pengirim'] ?? '';
$tanggal_filter = $_GET['tanggal'] ?? '';

$where = [];
if($status_filter == 'pending') $where[] = "d.tanggal_disposisi IS NULL";
elseif($status_filter == 'complete') $where[] = "d.tanggal_disposisi IS NOT NULL";
if($pengirim_filter) $where[] = "s.pengirim LIKE '%".mysqli_real_escape_string($conn,$pengirim_filter)."%'"; 
if($tanggal_filter) $where[] = "DATE(d.tanggal_disposisi)='".mysqli_real_escape_string($conn,$tanggal_filter)."'";

$where_sql = $where ? "WHERE ".implode(' AND ', $where) : "";

$tracking = mysqli_query($conn, "
SELECT s.no_surat, s.perihal, s.pengirim, d.tujuan, d.instruksi, d.tanggal_disposisi
FROM surat_masuk s
LEFT JOIN disposisi d ON s.id_masuk = d.id_masuk
{$where_sql}
ORDER BY d.tanggal_disposisi DESC, s.id_masuk DESC
LIMIT 100
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tracking Real-Time - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
    font-family:'Inter',sans-serif; 
    background:#f0f4ff; /* Soft biru background */
    color:#0f1a2b;
}

/* Sidebar */
.sidebar {
    height:100vh;
    width:220px;
    background:#d0e4ff; /* Soft biru */
    color:#0f1a2b;
    position:fixed;
    display:flex;
    flex-direction:column;
    transition:0.3s;
    box-shadow:0 0 15px rgba(0,0,0,0.1);
}
.sidebar.collapsed{width:70px;}
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
.sidebar .nav-link:hover,
.sidebar .nav-link.active{
    background:#a9c9ff;
    color:#0f1a2b;
}
.sidebar .nav-link i{margin-right:10px;}
.sidebar.collapsed .link-text{display:none;}

/* Main content */
.main-content{
    margin-left:220px;
    padding:20px;
    transition:0.3s;
}
.main-content.collapsed{margin-left:70px;}
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}
.header h2{
    font-weight:700;
    color:#0f1a2b;
}
.user-info{
    font-weight:600;
    color:#0f1a2b;
}

/* Filter */
.filter-section{
    margin-bottom:20px;
    padding:15px;
    background:#dce9ff;
    border-radius:12px;
    box-shadow:0 0 10px rgba(0,0,0,0.05);
}
.filter-section label{
    font-weight:600;
    color:#0f1a2b;
}

/* Table */
.table-tracking{
    background:#e4f0ff; /* Soft biru */
    border-radius:12px;
    padding:15px;
    box-shadow:0 0 15px rgba(0,0,0,0.05);
}
.table-tracking th, .table-tracking td{
    color:#0f1a2b;
}
.table-tracking th{
    border-bottom:2px solid #a9c9ff;
}
.table-tracking td{
    border-bottom:1px solid rgba(0,0,0,0.05);
}
.status-badge{
    padding:5px 12px;
    border-radius:12px;
    font-weight:600;
    display:inline-block;
}
.status-pending{background:#ffe39f;color:#0f1a2b;}
.status-complete{background:#7ed6ff;color:#0f1a2b;}

.footer{
    text-align:center;
    font-size:0.9rem;
    color:#0f1a2b;
    margin-top:40px;
    opacity:0.7;
}

/* Inputs */
input.form-control, select.form-select{
    border-radius:10px; 
    border:1px solid #a9c9ff;
    background:#ffffff;
    color:#0f1a2b;
}
input.form-control:focus, select.form-select:focus{
    border-color:#7ed6ff;
    box-shadow:0 0 5px rgba(126,214,255,0.5);
    background:#ffffff;
    color:#0f1a2b;
}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="dashboard.php" class="nav-link"><i data-feather="home"></i> Dashboard</a></li>
<li class="nav-item"><a href="surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> Surat Masuk</a></li>
<li class="nav-item"><a href="surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> Surat Keluar</a></li>
<li class="nav-item"><a href="tracking.php" class="nav-link active"><i data-feather="bar-chart-2"></i> Tracking Real-Time</a></li>
<li class="nav-item mt-auto"><a href="logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> Logout</a></li>
</ul>
</div>

<div class="main-content">
<div class="header">
<h2>Tracking Real-Time Surat</h2>
<div class="user-info"><?= ucfirst($role) ?></div>
</div>

<div class="filter-section">
<form method="get" class="row g-3">
<div class="col-md-3">
<label>Status</label>
<select class="form-select" name="status">
<option value="">Semua</option>
<option value="pending" <?= $status_filter=='pending'?'selected':''; ?>>Pending</option>
<option value="complete" <?= $status_filter=='complete'?'selected':''; ?>>Selesai</option>
</select>
</div>
<div class="col-md-4">
<label>Pengirim</label>
<input type="text" name="pengirim" class="form-control" placeholder="Nama Pengirim" value="<?= htmlspecialchars($pengirim_filter) ?>">
</div>
<div class="col-md-3">
<label>Tanggal Disposisi</label>
<input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal_filter) ?>">
</div>
<div class="col-md-2 d-flex align-items-end">
<button type="submit" class="btn btn-primary w-100">Filter</button>
</div>
</form>
</div>

<div class="table-tracking">
<table class="table table-hover mb-0">
<thead>
<tr>
<th>No Surat</th>
<th>Perihal</th>
<th>Pengirim</th>
<th>Tujuan</th>
<th>Instruksi</th>
<th>Tanggal Disposisi</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($tracking)): ?>
<tr>
<td><?= htmlspecialchars($row['no_surat']) ?></td>
<td><?= htmlspecialchars($row['perihal']) ?></td>
<td><?= htmlspecialchars($row['pengirim']) ?></td>
<td><?= htmlspecialchars($row['tujuan'] ?? '-') ?></td>
<td><?= htmlspecialchars($row['instruksi'] ?? '-') ?></td>
<td><?= htmlspecialchars($row['tanggal_disposisi'] ?? '-') ?></td>
<td>
<?php if($row['tanggal_disposisi']) echo '<span class="status-badge status-complete">Selesai</span>';
      else echo '<span class="status-badge status-pending">Pending</span>'; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="footer">&copy; <?= date('Y'); ?> Archivista - Kementerian ATR/BPN</div>
</div>

<script>
feather.replace();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

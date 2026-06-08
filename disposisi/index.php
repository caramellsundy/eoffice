<?php
session_start();
if(!isset($_SESSION['login'])) header("Location: ../login.php");

include '../config.php';
$role = $_SESSION['role'];
$base_url = "http://localhost/surat_menyurat/";

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Total data
$totalQuery = "SELECT COUNT(*) as total FROM disposisi";
if($search != ''){
    $totalQuery .= " WHERE tujuan LIKE '%$search%' OR instruksi LIKE '%$search%'";
}
$totalResult = mysqli_query($conn, $totalQuery);
$totalData = mysqli_fetch_assoc($totalResult)['total'];
$totalPages = ceil($totalData / $limit);

// Data tabel
$dataQuery = "SELECT d.*, m.no_surat FROM disposisi d 
              LEFT JOIN surat_masuk m ON d.id_masuk = m.id_masuk";
if($search != ''){
    $dataQuery .= " WHERE d.tujuan LIKE '%$search%' OR d.instruksi LIKE '%$search%' OR m.no_surat LIKE '%$search%'";
}
$dataQuery .= " ORDER BY d.id_disposisi DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $dataQuery);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Disposisi - Archivista</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family:'Inter',sans-serif; background:#e6f0ff; color:#0d3a8c; }

/* Sidebar */
.sidebar{
    height:100vh;
    width:220px;
    background:#ffffff;
    color:#0d3a8c;
    position:fixed;
    display:flex;
    flex-direction:column;
    transition:0.3s;
    box-shadow:2px 0 10px rgba(0,0,0,0.05);
}
.sidebar.collapsed{width:70px;}
.sidebar h4{
    text-align:center;
    margin:20px 0;
    font-weight:700;
    color:#0d3a8c;
}
.sidebar .nav-link{
    color:#0d3a8c;
    margin:5px 10px;
    border-radius:8px;
    transition:0.3s;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active{
    background:#cce0ff;
    color:#0d3a8c;
    box-shadow:0 0 5px rgba(0,123,255,0.2);
}
.sidebar .nav-link i{margin-right:10px;}
.sidebar.collapsed .link-text{display:none;}

/* Main content */
.main-content{margin-left:220px;padding:20px;transition:0.3s;}
.main-content.collapsed{margin-left:70px;}
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
    border-bottom:1px solid #b3d1ff;
    padding-bottom:10px;
}
.header h2{
    font-weight:700;
    color:#0d3a8c;
}
.user-info{
    display:flex;
    align-items:center;
    gap:10px;
}
.user-info span{
    color:#0d3a8c;
    background:#cce0ff;
    padding:5px 12px;
    border-radius:20px;
    font-weight:600;
    box-shadow:0 0 5px rgba(0,123,255,0.2);
}

/* Table & tombol */
.latest-table{
    background:#ffffff;
    border-radius:12px;
    padding:15px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
    margin-top:20px;
}
.table thead th{
    background:#cce0ff;
    color:#0d3a8c;
    position:sticky;
    top:0;
}
.table tbody tr{
    transition: all 0.3s;
    cursor:pointer;
}
.table tbody tr:hover{
    transform: translateY(-2px);
    box-shadow:0 0 8px rgba(0,123,255,0.1);
}
.btn-custom{transition:0.3s; border-radius:8px;}
.btn-custom:hover{transform:scale(1.05); box-shadow:0 0 8px rgba(0,123,255,0.2);}
input.form-control{
    border-radius:10px;
    background:#ffffff;
    color:#0d3a8c;
    border:1px solid #cce0ff;
}
input.form-control:focus{
    border-color:#0b76e0;
    box-shadow:0 0 5px rgba(0,123,255,0.2);
}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
<h4>Archivista</h4>
<ul class="nav flex-column">
<li class="nav-item"><a href="../dashboard_admin.php" class="nav-link"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
<li class="nav-item"><a href="../surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
<li class="nav-item"><a href="../surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> <span class="link-text">Surat Keluar</span></a></li>
<li class="nav-item"><a href="index.php" class="nav-link active"><i data-feather="file-text"></i> <span class="link-text">Disposisi</span></a></li>
<?php if($role=='admin'): ?>
<li class="nav-item"><a href="../users.php" class="nav-link"><i data-feather="users"></i> <span class="link-text">Manajemen User</span></a></li>
<?php endif; ?>
<li class="nav-item mt-auto"><a href="../logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
</ul>
</div>

<div class="main-content" id="mainContent">
<div class="header">
<h2>Disposisi</h2>
<div class="user-info"><span><?= ucfirst($role) ?></span> <i class="toggle-btn" data-feather="menu" id="toggleSidebar"></i></div>
</div>

<div class="d-flex justify-content-between mb-3">
<a href="tambah.php" class="btn btn-primary btn-custom">Tambah Disposisi</a>
<form class="d-flex" method="get" action="">
<input class="form-control me-2" type="search" name="search" placeholder="Cari No Surat / Tujuan / Instruksi" value="<?= htmlspecialchars($search); ?>">
<button class="btn btn-outline-primary btn-custom" type="submit">Cari</button>
</form>
</div>

<div class="latest-table">
<div class="table-responsive">
<table class="table table-bordered table-hover align-middle mb-0 text-dark">
<thead>
<tr>
<th>ID</th>
<th>No Surat</th>
<th>Tujuan</th>
<th>Instruksi</th>
<th>Tanggal Disposisi</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php
if(mysqli_num_rows($result) > 0){
    while($data = mysqli_fetch_assoc($result)){
        echo '<tr>';
        echo '<td>'.htmlspecialchars($data['id_disposisi']).'</td>';
        echo '<td>✉️ '.htmlspecialchars($data['no_surat']).'</td>';
        echo '<td>👤 '.htmlspecialchars($data['tujuan']).'</td>';
        echo '<td>📝 '.htmlspecialchars($data['instruksi']).'</td>';
        echo '<td>'.htmlspecialchars($data['tanggal_disposisi']).'</td>';
        echo '<td>
            ✏️ <a href="edit.php?id='.$data['id_disposisi'].'" class="btn btn-warning btn-sm btn-custom">Edit</a> 
            🗑️ <a href="hapus.php?id='.$data['id_disposisi'].'" onclick="return confirm(\'Yakin ingin menghapus?\');" class="btn btn-danger btn-sm btn-custom">Hapus</a>
        </td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center text-muted">Belum ada data disposisi.</td></tr>';
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
    $searchParam = ($search != '') ? '&search='.urlencode($search) : '';
    echo '<li class="page-item"><a class="page-link '.$active.'" href="?page='.$i.$searchParam.'">'.$i.'</a></li>';
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

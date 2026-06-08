<?php
session_start();
include 'config.php';
$error = '';

if(isset($_POST['login'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role     = mysqli_real_escape_string($conn, $_POST['role']);

    $query = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND role='$role'");
    if(mysqli_num_rows($query) == 1){
        $user = mysqli_fetch_assoc($query);
        if(password_verify($password, $user['password']) || $password==$user['password']){
            $_SESSION['login'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if($user['role']=='admin') header("Location: dashboard_admin.php");
            elseif($user['role']=='pegawai') header("Location: dashboard_pegawai.php");
            else header("Location: dashboard_user.php");
            exit;
        } else $error = "Password salah!";
    } else $error = "Username atau role tidak sesuai!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Archivista - Sistem Surat Digital</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body, html {
    margin:0; padding:0;
    font-family:'Inter', sans-serif;
    background: linear-gradient(135deg,#6c5ce7,#00b894);
    height:100vh;
    overflow:hidden;
    display:flex;
    justify-content:center;
    align-items:center;
}

/* Info Layout */
.container-info {
    width:90%;
    max-width:1000px;
    padding:40px;
    color:#fff;
    text-align:left;
    transform:translateX(-50px);
    opacity:0;
    transition: all 1s ease-out;
}
.container-info.show {
    transform:translateX(0);
    opacity:1;
}
.container-info h1 { font-size:2.8rem; font-weight:700; margin-bottom:15px; }
.container-info p { font-size:1.1rem; line-height:1.4; margin-bottom:6px; }
.table-custom { color:#111; background:#fff; border-radius:12px; overflow:hidden; margin-top:20px; width:100%; }
.table-custom th { background:#6c5ce7; color:#fff; font-weight:600; text-align:center; }
.table-custom td { padding:10px; text-align:center; }

/* Button Login Sekarang */
.btn-login-now {
    margin-top:20px;
    background:#fff; color:#6c5ce7;
    font-weight:600; padding:12px 30px;
    border-radius:10px; display:inline-block; cursor:pointer;
    transition: all 0.3s ease;
}
.btn-login-now:hover {
    transform:scale(1.05);
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
}

/* Overlay */
#overlay {
    position:fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(6px);
    display:flex;
    justify-content:center;
    align-items:center;
    opacity:0;
    visibility:hidden;
    transition: all 0.5s ease;
    z-index:999;
}

/* Card Login */
.card-login {
    background: #fff;
    padding:40px;
    border-radius:20px;
    width:100%;
    max-width:400px;
    box-shadow:0 25px 60px rgba(0,0,0,0.25);
    transform: translateY(50px);
    opacity:0;
    transition: all 0.8s ease-out;
}
.card-login.show {
    transform:translateY(0);
    opacity:1;
}
.card-login h3 { font-weight:700; text-align:center; margin-bottom:25px; color:#111; }
input.form-control, select.form-select {
    border-radius:12px; transition:0.3s;
}
input.form-control:focus, select.form-select:focus {
    box-shadow:0 0 12px rgba(0,255,234,0.6);
    outline:none;
}
.btn-login { 
    width:100%;
    background: linear-gradient(135deg,#6c5ce7,#00b894);
    color:#fff; font-weight:700; padding:12px;
    border:none; border-radius:12px; transition: all 0.3s;
    margin-top:10px;
}
.btn-login:hover {
    transform:scale(1.05) translateY(-3px);
    box-shadow:0 12px 25px rgba(7,145,199,0.3);
}

/* Animate table rows */
.table-custom tbody tr {
    opacity:0;
    transform: translateY(20px);
    animation: fadeSlide 0.8s forwards;
}
.table-custom tbody tr:nth-child(1) { animation-delay:0.2s; }
.table-custom tbody tr:nth-child(2) { animation-delay:0.4s; }
.table-custom tbody tr:nth-child(3) { animation-delay:0.6s; }
.table-custom tbody tr:nth-child(4) { animation-delay:0.8s; }
.table-custom tbody tr:nth-child(5) { animation-delay:1s; }

@keyframes fadeSlide {
    to { opacity:1; transform:translateY(0); }
}
</style>
</head>
<body>

<div class="container-info" id="infoWrapper">
    <h1>Archivista</h1>
    <p>Sistem Surat Digital Modern dan Profesional.</p>
    <p>Mengelola semua surat dan dokumen organisasi dengan cepat, aman, dan efisien.</p>
    <p>Dilengkapi notifikasi otomatis,Surat digital, dan tracking real-time.</p>

    <table class="table table-custom mt-3">
        <thead>
            <tr><th>Fitur</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            <tr><td>Surat Masuk & Keluar</td><td>Digital, cepat, dan aman</td></tr>
            <tr><td>Tracking Real-Time</td><td>Monitor status surat</td></tr>
            <tr><td>Notifikasi Otomatis</td><td>Peringatan untuk setiap update</td></tr>
            <tr><td>Disposisi Digital</td><td>Pencarian cepat dan mudah</td></tr>
            <tr><td>Dashboard Interaktif</td><td>Monitoring aktivitas surat</td></tr>
        </tbody>
    </table>

    <span class="btn-login-now" id="btnLoginNow">Login Sekarang</span>
</div>

<!-- Overlay Login -->
<div id="overlay">
    <div class="card-login" id="cardLogin">
        <h3>Login Archivista</h3>
        <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <div class="mb-4">
                <label>Masuk sebagai</label>
                <select class="form-select" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="pegawai">Pegawai</option>
                    <option value="umum">Umum</option>
                </select>
            </div>
            <button type="submit" name="login" class="btn btn-login">Masuk</button>
        </form>
    </div>
</div>

<script>
// Animasi info muncul
window.addEventListener('load', ()=>{
    document.getElementById('infoWrapper').classList.add('show');
});

// Tombol Login Sekarang
document.getElementById('btnLoginNow').addEventListener('click', ()=>{
    const overlay = document.getElementById('overlay');
    overlay.style.visibility = 'visible';
    overlay.style.opacity = 1;

    setTimeout(()=>{
        document.getElementById('cardLogin').classList.add('show');
    }, 100);
});
</script>

</body>
</html>

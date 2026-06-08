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
            elseif($user['role']=='staff') header("Location: dashboard_staff.php");
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
<title>Archivista - Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body, html {
    margin:0; padding:0;
    font-family:'Inter', sans-serif;
    background: linear-gradient(135deg, #6c5ce7, #00b894);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    overflow:hidden;
}

/* Card Login */
.card-login {
    background: rgba(255,255,255,0.95);
    padding: 40px;
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    opacity:0;
    transform: translateY(50px);
    transition: all 1s ease-out;
}
.card-login.show {
    opacity:1;
    transform: translateY(0);
}
.card-login h3 {
    font-weight:700;
    margin-bottom:25px;
    color:#111;
    text-align:center;
}
input.form-control, select.form-select {
    border-radius:12px;
    transition:0.3s;
}
input.form-control:focus, select.form-select:focus {
    box-shadow:0 0 12px rgba(0,255,234,0.6);
    outline:none;
}
.btn-login { 
    width:100%;
    background: linear-gradient(135deg,#6c5ce7,#00b894); 
    color:#fff; 
    font-weight:700; 
    padding:12px; 
    border:none; 
    border-radius:12px; 
    transition:0.3s; 
    margin-top:10px;
}
.btn-login:hover {
    transform:scale(1.05);
    box-shadow:0 12px 25px rgba(7,145,199,0.3);
}

/* Alert Error */
.alert {
    font-size:0.95rem;
    padding:8px 12px;
    border-radius:10px;
}

/* Splash Loading */
#splash {
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.85);
    color:#fff;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:2rem;
    font-weight:700;
    z-index:999;
    display:none;
    flex-direction:column;
    gap:15px;
}
#splash span {
    font-size:1rem;
    font-weight:400;
    color:#fff;
}
</style>
</head>
<body>

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
                <option value="staff">Staff</option>
                <option value="user">User</option>
            </select>
        </div>
        <button type="submit" name="login" class="btn btn-login">Masuk</button>
    </form>
</div>

<!-- Splash -->
<div id="splash">
    Sedang Memuat...
    <span>Mohon tunggu beberapa detik...</span>
</div>

<script>
// Animasi card login
window.addEventListener('load', ()=>{
    document.getElementById('cardLogin').classList.add('show');
});

// Jika login diarahkan dari tombol info
if(window.location.search.includes('from=info')){
    const splash = document.getElementById('splash');
    splash.style.display = 'flex';
    setTimeout(()=>{ splash.style.display = 'none'; }, 2000);
}
</script>

</body>
</html>

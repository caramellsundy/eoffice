<?php
session_start();
$redirect = "login.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Archivista - Sistem Arsip Digital</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body, html {
    margin:0; padding:0;
    width:100%; height:100%;
    font-family:'Inter', sans-serif;
    overflow:hidden;
    background: #0f2027;
    display:flex; justify-content:center; align-items:center; flex-direction:column;
    color:white;
}

/* Canvas overlay */
#particles {
    position:absolute;
    width:100%;
    height:100%;
    top:0; left:0;
    z-index:0;
}

/* Logo & subtitle */
.splash-logo {
    position:relative;
    z-index:1;
    font-size: 4rem;
    font-weight:700;
    letter-spacing:2px;
    animation: fadeInDown 1s ease forwards;
}
.splash-subtitle {
    position:relative;
    z-index:1;
    font-size:1.3rem;
    color:#00ffea;
    margin-top:10px;
    animation: fadeInUp 1s ease forwards;
    animation-delay:0.5s;
}

/* Loader */
.loader-container {
    position:relative;
    z-index:1;
    width: 80%;
    max-width:400px;
    height:10px;
    background: rgba(255,255,255,0.2);
    border-radius:5px;
    margin-top:50px;
    overflow:hidden;
}
.loader-bar {
    width:0; height:100%;
    background: #00ffea;
    animation: loading 3s linear forwards;
}

/* Animations */
@keyframes fadeInDown { 0% {opacity:0; transform:translateY(-30px);} 100%{opacity:1; transform:translateY(0);} }
@keyframes fadeInUp { 0% {opacity:0; transform:translateY(30px);} 100%{opacity:1; transform:translateY(0);} }
@keyframes loading { 0%{width:0;} 100%{width:100%;} }
</style>
</head>
<body>

<canvas id="particles"></canvas>

<div class="splash-logo">Archivista</div>
<div class="splash-subtitle">Sistem Arsip Digital & Inovatif</div>
<div class="loader-container">
    <div class="loader-bar"></div>
</div>

<script>
// ===== Canvas Particles =====
const canvas = document.getElementById('particles');
const ctx = canvas.getContext('2d');
let width = canvas.width = window.innerWidth;
let height = canvas.height = window.innerHeight;

let particles = [];
const particleCount = 80;

class Particle {
    constructor(){
        this.x = Math.random()*width;
        this.y = Math.random()*height;
        this.radius = Math.random()*3 + 1;
        this.speedX = (Math.random()-0.5)*0.5;
        this.speedY = (Math.random()-0.5)*0.5;
    }
    draw(){
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.radius, 0, Math.PI*2);
        ctx.fillStyle = 'rgba(0,255,234,0.7)';
        ctx.fill();
    }
    update(){
        this.x += this.speedX;
        this.y += this.speedY;
        if(this.x < 0 || this.x > width) this.speedX*=-1;
        if(this.y < 0 || this.y > height) this.speedY*=-1;
    }
}

for(let i=0;i<particleCount;i++){
    particles.push(new Particle());
}

function connectParticles(){
    for(let a=0;a<particles.length;a++){
        for(let b=a;b<particles.length;b++){
            let dx = particles[a].x - particles[b].x;
            let dy = particles[a].y - particles[b].y;
            let dist = Math.sqrt(dx*dx + dy*dy);
            if(dist < 120){
                ctx.beginPath();
                ctx.strokeStyle = 'rgba(0,255,234,'+(1-dist/120)+')';
                ctx.lineWidth = 1;
                ctx.moveTo(particles[a].x, particles[a].y);
                ctx.lineTo(particles[b].x, particles[b].y);
                ctx.stroke();
            }
        }
    }
}

function animate(){
    ctx.clearRect(0,0,width,height);
    particles.forEach(p => {p.update(); p.draw();});
    connectParticles();
    requestAnimationFrame(animate);
}

animate();

window.addEventListener('resize', ()=>{
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
});

// Redirect after 3 seconds
setTimeout(()=>{ window.location.href = "<?= $redirect ?>"; }, 3000);
</script>

</body>
</html>

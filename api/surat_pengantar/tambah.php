<?php 
session_start();
if(!isset($_SESSION['login'])) {
    header("Location: ../login.php");
    exit;
}

// Menggunakan path absolute yang stabil agar Apache tidak melempar Not Found
include $_SERVER['DOCUMENT_ROOT'] . '/surat_menyurat/config.php';
$role = $_SESSION['role'];

// Logika pemrosesan simpan surat pengantar jika formulir disubmit
if(isset($_POST['submit'])){
    $no_pengantar = mysqli_real_escape_string($conn, $_POST['no_pengantar']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $pengirim_surat = mysqli_real_escape_string($conn, $_POST['pengirim_surat']);
    $tanggal_surat = $_POST['tanggal_surat'];
    // Jika kosong akan di-set NULL di database
    $id_surat_masuk = !empty($_POST['id_surat_masuk']) ? mysqli_real_escape_string($conn, $_POST['id_surat_masuk']) : "NULL";
    $created_at = date('Y-m-d H:i:s');
    $pembuat = $_SESSION['username'] ?? 'Petugas';

    // Memastikan skema kolom tersedia di database
    $checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM `surat_pengantar` LIKE 'pengirim_surat'");
    if(mysqli_num_rows($checkColumn) > 0) {
        $id_masuk_val = ($id_surat_masuk === "NULL") ? "NULL" : "'$id_surat_masuk'";
        $sql = "INSERT INTO surat_pengantar (no_pengantar, tujuan, pembuat, tanggal_surat, pengirim_surat, id_surat_masuk, created_at)
                VALUES ('$no_pengantar', '$tujuan', '$pembuat', '$tanggal_surat', '$pengirim_surat', $id_masuk_val, '$created_at')";
    } else {
        $sql = "INSERT INTO surat_pengantar (no_pengantar, tujuan, pembuat, tanggal_surat, created_at)
                VALUES ('$no_pengantar', '$tujuan', '$pembuat', '$tanggal_surat', '$created_at')";
    }
            
    if(mysqli_query($conn, $sql)){
        header("Location: index.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// Logika AJAX untuk mencari data Surat Masuk secara real-time
if(isset($_GET['action']) && $_GET['action'] == 'search_surat') {
    $tanggal_cari = mysqli_real_escape_string($conn, $_GET['tanggal']);
    $nomor_cari = mysqli_real_escape_string($conn, $_GET['nomor_surat']);
    
    $where = "WHERE 1=1";
    if(!empty($tanggal_cari)) {
        $where .= " AND (tanggal_terima = '$tanggal_cari' OR tanggal = '$tanggal_cari')";
    }
    if(!empty($nomor_cari)) {
        $where .= " AND no_surat LIKE '%$nomor_cari%'";
    }
    
    $query_masuk = "SELECT id_masuk as id, no_surat, pengirim, tanggal, perihal FROM surat_masuk $where ORDER BY id_masuk DESC LIMIT 5";
    $res_masuk = mysqli_query($conn, $query_masuk);
    
    $data_surat = [];
    if($res_masuk) {
        while($row = mysqli_fetch_assoc($res_masuk)) {
            $data_surat[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($data_surat);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Surat Pengantar - Archivista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #e6f0ff; color: #0d3a8c; }

        /* Sidebar Styling (Sama Persis dengan Surat Keluar) */
        .sidebar { height: 100vh; width: 220px; background: #ffffff; color: #0d3a8c; position: fixed; display: flex; flex-direction: column; transition: 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.05); z-index: 100; }
        .sidebar.collapsed { width: 70px; }
        .sidebar h4 { text-align: center; margin: 20px 0; font-weight: 700; color: #0d3a8c; }
        .sidebar .nav-link { color: #0d3a8c; margin: 5px 10px; border-radius: 8px; transition: 0.3s; text-decoration: none; padding: 10px; display: block; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #cce0ff; color: #0d3a8c; box-shadow: 0 0 8px rgba(0,123,255,0.1); font-weight: 600; }
        .sidebar .nav-link i { margin-right: 10px; }
        .sidebar.collapsed .link-text { display: none; }

        /* Main Content Layout */
        .main-content { margin-left: 220px; padding: 20px; transition: 0.3s; }
        .main-content.collapsed { margin-left: 70px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #b3d1ff; padding-bottom: 10px; }
        .header h2 { font-weight: 700; color: #0d3a8c; }
        
        /* Box Form Panel */
        .form-container { background: #ffffff; border-radius: 12px; padding: 25px; border: 1px solid #cce0ff; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .section-title { font-size: 0.95rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #5c85d6; margin-bottom: 15px; border-bottom: 2px solid #e6f0ff; padding-bottom: 5px; }
        
        /* Form Controls */
        label { font-size: 0.85rem; font-weight: 600; color: #0d3a8c; margin-bottom: 4px; }
        label .text-danger { font-weight: 700; }
        input.form-control, select.form-select, textarea.form-control { border-radius: 8px; background: #ffffff; color: #0d3a8c; border: 1px solid #cce0ff; font-size: 0.9rem; }
        input.form-control:focus, select.form-select:focus, textarea.form-control:focus { border-color: #0b76e0; box-shadow: 0 0 5px rgba(0,123,255,0.2); color: #0d3a8c; }
        
        .btn-custom { transition: 0.2s; border-radius: 8px; font-weight: 600; padding: 8px 16px; }
        .btn-custom:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,123,255,0.15); }
        .toggle-btn { cursor: pointer; color: #0d3a8c; }
        
        /* Tab Sederhana Custom */
        .nav-tabs-custom { display: flex; gap: 5px; border-bottom: 1px solid #cce0ff; margin-bottom: 15px; }
        .nav-tabs-custom .tab-item { padding: 6px 12px; font-size: 0.85rem; font-weight: 600; border-radius: 6px 6px 0 0; background: #f0f5ff; border: 1px solid #cce0ff; border-bottom: none; color: #5c85d6; cursor: pointer; border: 1px solid #cce0ff; }
        .nav-tabs-custom .tab-item.active { background: #ffffff; color: #0d3a8c; border-color: #cce0ff; position: relative; margin-bottom: -1px; }

        /* Hasil Tabel AJAX */
        .table thead th { background: #cce0ff; color: #0d3a8c; border-color: #b3d1ff; font-size: 0.85rem; }
        .table tbody tr { transition: 0.2s; }
        .table tbody tr:hover { background: #f7faff; }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <h4>Archivista</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="../dashboard.php" class="nav-link"><i data-feather="home"></i> <span class="link-text">Dashboard</span></a></li>
            <li class="nav-item"><a href="../surat_masuk/index.php" class="nav-link"><i data-feather="inbox"></i> <span class="link-text">Surat Masuk</span></a></li>
            <li class="nav-item"><a href="../surat_keluar/index.php" class="nav-link"><i data-feather="send"></i> <span class="link-text">Surat Keluar</span></a></li>
            <li class="nav-item"><a href="index.php" class="nav-link active"><i data-feather="file-text"></i> <span class="link-text">Surat Pengantar</span></a></li>
            <li class="nav-item"><a href="../disposisi/index.php" class="nav-link"><i data-feather="layers"></i> <span class="link-text">Disposisi</span></a></li>
            <?php if($role == 'admin'): ?>
                <li class="nav-item"><a href="../users.php" class="nav-link"><i data-feather="users"></i> <span class="link-text">Manajemen User</span></a></li>
            <?php endif; ?>
            <li class="nav-item mt-auto"><a href="../logout.php" class="nav-link text-danger"><i data-feather="log-out"></i> <span class="link-text">Logout</span></a></li>
        </ul>
    </div>

    <div class="main-content" id="mainContent">
        <div class="header">
            <h2>Buat Surat Pengantar</h2>
            <div class="user-info">
                <i class="toggle-btn" data-feather="menu" id="toggleSidebar"></i>
            </div>
        </div>

        <form method="post" class="form-container" id="formPengantar">
            <div class="row g-4">
                
                <div class="col-12 col-lg-6 border-end-lg" style="border-color: #e6f0ff;">
                    <div class="section-title">Atribut Surat Pengantar</div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label>Nomor Surat Pengantar <span class="text-danger">*</span></label>
                            <input type="text" name="no_pengantar" class="form-control" placeholder="Masukkan nomor surat pengantar" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label>Tanggal Surat <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_surat" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label>Penandatangan Surat <span class="text-danger">*</span></label>
                            <input type="text" name="pengirim_surat" id="pengirim_surat" class="form-control" placeholder="Nama penandatangan..." required>
                        </div>

                        <div class="col-12">
                            <label>Tujuan Pengantar <span class="text-danger">*</span></label>
                            <select name="tujuan" class="form-select" required>
                                <option value="">-- Pilih Tujuan --</option>
                                <option value="Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional">Menteri Agraria dan Tata Ruang/Kepala Badan Pertanahan Nasional</option>
                                <option value="Direktorat Jenderal Penetapan Hak dan Pendaftaran Tanah">Direktorat Jenderal Penetapan Hak dan Pendaftaran Tanah</option>
                                <option value="Sekretariat Jenderal">Sekretariat Jenderal</option>
                                <option value="Biro Hubungan Masyarakat dan Protokol">Biro Hubungan Masyarakat dan Protokol</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="id_surat_masuk" id="id_surat_masuk">

                    <div class="d-flex gap-2 justify-content-start mt-5 pt-3 border-top" style="border-color: #e6f0ff;">
                        <button type="submit" name="submit" class="btn btn-primary btn-custom">💾 Simpan dan Cetak</button>
                        <a href="index.php" class="btn btn-outline-secondary btn-custom">Batal</a>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="section-title">Referensi Hubungkan Surat Masuk</div>
                    
                    <div class="nav-tabs-custom">
                        <div class="tab-item active" id="tabTanggal">Tanggal Terima</div>
                        <div class="tab-item" id="tabNomor">Nomor Surat</div>
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col" id="containerCariTanggal">
                            <label>Tanggal Masuk :</label>
                            <input type="date" id="cari_tanggal" class="form-control">
                        </div>
                        <div class="col d-none" id="containerCariNomor">
                            <label>Cari Nomor Surat :</label>
                            <input type="text" id="cari_nomor" class="form-control" placeholder="Ketik nomor surat...">
                        </div>
                        <div class="col-auto">
                            <button type="button" id="btnCariSurat" class="btn btn-outline-primary btn-custom"><i data-feather="search" style="width:16px;"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded bg-light" style="max-height: 220px; overflow-y:auto;">
                        <table class="table table-bordered table-hover align-middle mb-0" style="font-size:0.85rem;" id="tableHasilCari">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">Pilih</th>
                                    <th>Nomor Surat</th>
                                    <th>Asal Pengirim</th>
                                    <th>Perihal</th>
                                </tr>
                            </thead>
                            <tbody id="listSuratMasuk">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Data kosong. Tentukan filter lalu cari.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="small text-muted" id="statusPilih">Belum ada referensi surat terpilih.</span>
                        <button type="button" id="btnGunakanSurat" class="btn btn-success btn-custom btn-sm d-none">Gunakan Surat</button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        feather.replace();

        // Logika Sidebar Menu
        document.getElementById('toggleSidebar').addEventListener('click', ()=>{
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainContent').classList.toggle('collapsed');
        });

        // Kontrol Filter Tab Referensi
        const tabTanggal = document.getElementById('tabTanggal');
        const tabNomor = document.getElementById('tabNomor');
        const containerCariTanggal = document.getElementById('containerCariTanggal');
        const containerCariNomor = document.getElementById('containerCariNomor');

        tabTanggal.addEventListener('click', () => {
            tabTanggal.classList.add('active');
            tabNomor.classList.remove('active');
            containerCariTanggal.classList.remove('d-none');
            containerCariNomor.classList.add('d-none');
            document.getElementById('cari_nomor').value = '';
        });

        tabNomor.addEventListener('click', () => {
            tabNomor.classList.add('active');
            tabTanggal.classList.remove('active');
            containerCariNomor.classList.remove('d-none');
            containerCariTanggal.classList.add('d-none');
            document.getElementById('cari_tanggal').value = '';
        });

        // Event Handler AJAX Pencarian Surat Masuk
        document.getElementById('btnCariSurat').addEventListener('click', function() {
            const tanggal = document.getElementById('cari_tanggal').value;
            const nomor = document.getElementById('cari_nomor').value;
            const tbody = document.getElementById('listSuratMasuk');
            
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Mencari data...</td></tr>';
            
            fetch(`tambah.php?action=search_surat&tanggal=${tanggal}&nomor_surat=${nomor}`)
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';
                    if(data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada surat masuk yang cocok.</td></tr>';
                        document.getElementById('btnGunakanSurat').classList.add('d-none');
                        return;
                    }
                    
                    data.forEach(surat => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="text-center"><input type="radio" name="check_surat" value="${surat.id}" data-nosurat="${surat.no_surat}" data-pengirim="${surat.pengirim}"></td>
                            <td><strong>${surat.no_surat}</strong></td>
                            <td>${surat.pengirim}</td>
                            <td>${surat.perihal}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                    document.getElementById('btnGunakanSurat').classList.remove('d-none');
                })
                .catch(err => {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Gagal memuat data referensi.</td></tr>';
                });
        });

        // Mengunci Referensi Surat Terpilih ke Form Atribut Utama
        document.getElementById('btnGunakanSurat').addEventListener('click', function() {
            const terpilih = document.querySelector('input[name="check_surat"]:checked');
            if(!terpilih) {
                alert('Silakan pilih salah satu surat terlebih dahulu!');
                return;
            }
            
            document.getElementById('id_surat_masuk').value = terpilih.value;
            document.getElementById('pengirim_surat').value = terpilih.getAttribute('data-pengirim');
            document.getElementById('statusPilih').innerHTML = `✔️ Terkunci pada No Surat: <strong>${terpilih.getAttribute('data-nosurat')}</strong>`;
            
            // Memberikan style highlight sukses sementara
            document.getElementById('statusPilih').classList.add('text-success');
        });
    </script>
</body>
</html>
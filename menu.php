<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT p.id, a.nama as nama_auditorium, p.tanggal, p.waktu_mulai, p.waktu_selesai 
        FROM peminjaman p 
        INNER JOIN auditorium a ON p.id_auditorium = a.id 
        WHERE p.id_pengguna = ?
        ORDER BY p.tanggal DESC, p.waktu_mulai DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Dashboard Pengguna</h2>
    <h3>Riwayat Peminjaman</h3>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Auditorium</th>
                    <th>Tanggal</th>
                    <th>Jam Mulai</th>
                    <th>Jam Selesai</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_auditorium']); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                        <td><?php echo date('H:i', strtotime($row['waktu_mulai'])); ?></td>
                        <td><?php echo date('H:i', strtotime($row['waktu_selesai'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($result->num_rows == 0): ?>
        <div class="alert alert-info">
            Belum ada riwayat peminjaman.
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="peminjaman.php" class="btn btn-primary">Ajukan Peminjaman Baru</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>


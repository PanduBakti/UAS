<?php
include 'includes/db.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables for error/success messages
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitize and validate inputs
        $id_auditorium = isset($_POST['id_auditorium']) ? (int)$_POST['id_auditorium'] : 0;
        $tanggal = $_POST['tanggal'] ?? '';
        $waktu_mulai = $_POST['jam_mulai'] ?? '';
        $waktu_selesai = $_POST['jam_selesai'] ?? '';
        $keperluan = $_POST['keperluan'] ?? '';
        $id_pengguna = (int)$_SESSION['user_id'];

        // Validate inputs
        if ($id_auditorium <= 0) {
            throw new Exception("Pilih auditorium yang valid!");
        }

        // Verify auditorium exists
        $check_audit = "SELECT id FROM auditorium WHERE id = ?";
        $stmt = $conn->prepare($check_audit);
        $stmt->bind_param("i", $id_auditorium);
        $stmt->execute();
        if (!$stmt->get_result()->num_rows) {
            throw new Exception("Auditorium tidak ditemukan!");
        }
        $stmt->close();

        // Validate date and time
        $current_date = date('Y-m-d');
        if ($tanggal < $current_date) {
            throw new Exception("Tanggal peminjaman tidak boleh kurang dari hari ini!");
        }

        if ($waktu_mulai >= $waktu_selesai) {
            throw new Exception("Waktu selesai harus lebih besar dari waktu mulai!");
        }
        
        // Check for time conflicts
        $check_sql = "SELECT * FROM peminjaman 
                     WHERE id_auditorium = ? 
                     AND tanggal = ? 
                     AND status != 'declined'
                     AND ((waktu_mulai BETWEEN ? AND ?) 
                          OR (waktu_selesai BETWEEN ? AND ?)
                          OR (waktu_mulai <= ? AND waktu_selesai >= ?))";
                   
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("isssssss", 
            $id_auditorium, 
            $tanggal, 
            $waktu_mulai, 
            $waktu_selesai,
            $waktu_mulai, 
            $waktu_selesai,
            $waktu_mulai,
            $waktu_selesai
        );
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Maaf, jadwal yang dipilih sudah dibooking!");
        }
        $stmt->close();

        // Begin transaction
        $conn->begin_transaction();

        // Insert peminjaman
        $sql = "INSERT INTO peminjaman (id_pengguna, id_auditorium, tanggal, waktu_mulai, waktu_selesai, keperluan, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissss", 
            $id_pengguna, 
            $id_auditorium, 
            $tanggal, 
            $waktu_mulai, 
            $waktu_selesai,
            $keperluan
        );

        if (!$stmt->execute()) {
            throw new Exception("Error saat menyimpan peminjaman: " . $stmt->error);
        }

        $conn->commit();
        $message = "Peminjaman berhasil diajukan!";
        $stmt->close();

    } catch (Exception $e) {
        if ($conn->connect_errno != 0) $conn->rollback();
        $error = $e->getMessage();
    }
}

// Get list of auditoriums
$auditoriums = [];
try {
    $query = "SELECT * FROM auditorium ORDER BY nama";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $auditoriums[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Error saat mengambil data auditorium: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Auditorium</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <h2>Ajukan Peminjaman Auditorium</h2>
    <form method="POST" class="mt-4" id="peminjamanForm">
        <div class="form-group">
            
            <div class="form-group">
    <label>Auditorium</label>
    <select name="id_auditorium" class="form-control" required>
        <option value="">Pilih Auditorium</option>
        <option value="1">Auditorium BTI</option>
        <option value="2">Auditorium FK (Pondok Labu)</option>
        <option value="3">Auditorium MERCE Kedokteran (Limo)</option>
        <option value="4">Auditorium FISIP</option>
        <option value="5">Auditorium FT lt 8</option>
    </select>
</div>
        </div>
        
        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" name="tanggal" class="form-control" 
                   min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Jam Mulai</label>
            <input type="time" name="jam_mulai" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label>Jam Selesai</label>
            <input type="time" name="jam_selesai" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Keperluan</label>
            <textarea name="keperluan" class="form-control" rows="3" required></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
        <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // Form validation
    $('#peminjamanForm').on('submit', function(e) {
        var startTime = $('input[name="jam_mulai"]').val();
        var endTime = $('input[name="jam_selesai"]').val();
        
        if (startTime >= endTime) {
            e.preventDefault();
            alert('Jam selesai harus lebih besar dari jam mulai!');
            return false;
        }

        var selectedDate = new Date($('input[name="tanggal"]').val());
        var today = new Date();
        today.setHours(0,0,0,0);
        
        if (selectedDate < today) {
            e.preventDefault();
            alert('Tanggal tidak boleh kurang dari hari ini!');
            return false;
        }
    });
});
</script>

</body>
</html>
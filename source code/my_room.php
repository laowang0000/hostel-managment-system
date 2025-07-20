<?php
session_start();

// Check if user is not logged in or not a student
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student") {
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Clear any potential deallocation notification session variables
unset($_SESSION['deallocation_msg'], $_SESSION['notification'], $_SESSION['deallocation_message']);

// Fetch student's allocated room details
$sql = "SELECT ra.room_number, r.room_type, r.ac_status, ra.check_in_date, r.floor_number 
        FROM room_allocations ra 
        JOIN rooms r ON ra.room_number = r.room_number 
        WHERE ra.student_id = ? AND ra.status = 'active'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$room = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Set theme based on user role
if (!isset($_SESSION['theme'])) {
    if ($_SESSION['role'] === 'admin') {
        $_SESSION['theme'] = 'dark';
    } elseif ($_SESSION['role'] === 'student') {
        $_SESSION['theme'] = 'light';
    } elseif ($_SESSION['role'] === 'staff') {
        $_SESSION['theme'] = 'green';
    } else {
        $_SESSION['theme'] = 'light';
    }
}

// Handle room application submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_room'])) {
    $room_number = trim($_POST['room_number']);
    // Check if already applied for for room and pending
    $sql = "SELECT * FROM room_applications WHERE student_id = ? AND room_number = ? AND status = 'pending'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $_SESSION["id"], $room_number);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_fetch_assoc($result)) {
        $application_msg = '<div class="alert alert-warning">You have already applied for this room and it is pending.</div>';
    } else {
        // Insert application
        $sql = "INSERT INTO room_applications (student_id, room_number) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "is", $_SESSION["id"], $room_number);
        if (mysqli_stmt_execute($stmt)) {
            $application_msg = '<div class="alert alert-success">Application successfully submitted successfully!</div>';
        } else {
            $application_msg = '<div class="alert alert-danger">Failed to submit application.</div>';
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Room - - Hostel Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font: 14px sans-serif; background-color: #f8f9fa; }
        .wrapper { width: 100%; padding: 20px; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #495057; }
        .content { padding: 20px; }
        .card { margin-bottom: 20px; }
        .theme-light { background-color: #f8f9fa; color: #222; }
        .theme-dark { background-color: #222; color: #ffffff !important; }
        .theme-blue { background-color: #007bff; color: #fff; }
        .theme-pinkspec { background-color: #ffc0cb; color: #222; }
        .theme-green { background-color: #d4edda; color: #155724; }
        .theme-dark .card, .theme-dark .sidebar { background-color: #333 !important; color: #ffffff !important; }
        .theme-dark .table, .theme-dark .table th, .theme-dark .table td,
        .theme-dark .table thead th, .theme-dark .table tbody td { color: #ffffff !important; }
        .theme-dark .content, .theme-dark h2, .theme-dark .card-title, .theme-dark .card-text, 
        .theme-dark .alert, .theme-dark label, .theme-dark .badge { color: #ffffff !important; }
        .theme-dark .sidebar a { color: #ffffff !important; }
        .theme-blue .card, .theme-blue .sidebar { background-color: #339cff !important; color: #fff !important; }
        .theme-pink .card, .theme-pink .sidebar { background-color: #ffe4ec !important; color: #222 !important; }
        .theme-green .card, .theme-green .sidebar { background-color: #e2f7e1 !important; color: #155724 !important; }
        .theme-pink .sidebar, .theme-pink .sidebar a { color: #222 !important; }
        .theme-green .sidebar, .theme-green .sidebar a { color: #222 !important; }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($_SESSION['theme']); ?>">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h3 class="text-center mb-4">Hostel MS System</h3>
                <nav>
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="my_room.php"><i class="fas fa-door-open"></i> My Room</a>
                    <a href="pay_fees.php"><i class="fas fa-money-bill"></i> Fees</a>
                    <a href="submit_maintenance.php"><i class="fas fa-tools"></i> Maintenance Request</a>
                    <a href="submit_complaint.php"><i class="fas fa-exclamation-circle"></i> Complaint</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <div class="d-flex justify-content-between-align-items-center mb-4">
                    <h2>My Room Details</h2>
                    <span class="badge badge-primary"><?php echo ucfirst($_SESSION["role"]); ?></span>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Allocated Room</h5>
                        <?php if ($room): ?>
                            <p><strong>Room Number:</strong> <?php echo htmlspecialchars($room['room_number']); ?></p>
                            <p><strong>Floor Number:</strong> <?php echo htmlspecialchars($room['floor_number']); ?></p>
                            <p><strong>Room Type:</strong> <?php echo ucfirst(htmlspecialchars($room['room_type'])); ?></p>
                            <p><strong>AC Status:</strong> <?php echo htmlspecialchars($room['ac_status']); ?></p>
                            <p><strong>Check-in Date:</strong> <?php echo htmlspecialchars($room['check_in_date']); ?></p>
                        <?php else: ?>
                            <p class="text-muted">No room allocated yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Room Application Section for Students Without a Room -->
                <?php if (!$room): ?>
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Apply for a Room</h5>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="form-group">
                                <label>Available Rooms</label>
                                <select name="room_number" class="form-control" required>
                                    <?php
                                    $sql = "SELECT room_number, room_type, ac_status, floor_number FROM rooms WHERE status = 'available'";
                                    $result = mysqli_query($conn, $sql);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row['room_number']) . '">' . htmlspecialchars($row['room_number']) . ' (Floor ' . htmlspecialchars($row['floor_number']) . ', ' . ucfirst($row['room_type']) . ', ' . htmlspecialchars($row['ac_status']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" name="apply_room" class="btn btn-success">Apply</button>
                        </form>
                        <?php if (isset($application_msg)) echo '<div class="mt-2">' . $application_msg . '</div>'; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
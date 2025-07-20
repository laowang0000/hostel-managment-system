<?php
session_start();

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// After session_start() and database connection, add:
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

// Process cleaning schedule creation
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_schedule"])){
    $room_number = trim($_POST["room_number"]);
    $staff_id = trim($_POST["staff_id"]);
    $cleaning_date = trim($_POST["cleaning_date"]);
    $notes = trim($_POST["notes"]);
    
    // Validate staff_id exists
    $sql = "SELECT id FROM staff WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt) == 0){
            $error_msg = "Invalid staff selected.";
        }
        mysqli_stmt_close($stmt);
    }
    
    if(!isset($error_msg)){
        $sql = "INSERT INTO cleaning_schedule (room_number, staff_id, cleaning_date, status, notes) VALUES (?, ?, ?, 'assigned', ?)";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "siss", $room_number, $staff_id, $cleaning_date, $notes);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Schedule created successfully!";
            } else{
                $error_msg = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cleaning Schedule - Hostel Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font: 14px sans-serif; background-color: #f8f9fa; }
        .wrapper { width: 100%; padding: 20px; }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content { padding: 20px; }
        .card { margin-bottom: 20px; }
        .theme-light { background-color: #f8f9fa; color: #222; }
        .theme-dark { background-color: #222; color: #ffffff !important; }
        .theme-blue { background-color: #007bff; color: #fff; }
        .theme-pink { background-color: #ffc0cb; color: #222; }
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
                <h3 class="text-center mb-4">Hostel MS</h3>
                <nav>
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="room_management.php"><i class="fas fa-door-open"></i> Room Management</a>
                    <a href="fee_management.php"><i class="fas fa-money-bill"></i> Fee Management</a>
                    <a href="cleaning_schedule.php"><i class="fas fa-broom"></i> Cleaning Schedule</a>
                    <a href="maintenance_requests.php"><i class="fas fa-tools"></i> Maintenance</a>
                    <a href="complaints.php"><i class="fas fa-exclamation-circle"></i> Complaints</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2>Cleaning Schedule</h2>
                
                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>

                <!-- Create Schedule Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Create Cleaning Schedule</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Check if staff exist
                        $sql = "SELECT COUNT(*) as total FROM staff";
                        $result = mysqli_query($conn, $sql);
                        $row = mysqli_fetch_assoc($result);
                        if($row['total'] == 0){
                            echo '<div class="alert alert-warning">No staff members available. Please register staff users first.</div>';
                        } else {
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Room Number</label>
                                        <select name="room_number" class="form-control" required>
                                            <?php
                                            $sql = "SELECT room_number, floor_number FROM rooms WHERE status != 'maintenance'";
                                            $result = mysqli_query($conn, $sql);
                                            while($row = mysqli_fetch_assoc($result)){
                                                echo "<option value='" . htmlspecialchars($row['room_number']) . "'>" . htmlspecialchars($row['room_number']) . " (Floor " . htmlspecialchars($row['floor_number']) . ")</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Staff Member</label>
                                        <select name="staff_id" class="form-control" required>
                                            <?php
                                            $sql = "SELECT id, name FROM staff";
                                            $result = mysqli_query($conn, $sql);
                                            while($row = mysqli_fetch_assoc($result)){
                                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Cleaning Date</label>
                                        <input type="date" name="cleaning_date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <input type="text" name="notes" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="create_schedule" class="btn btn-primary">Create Schedule</button>
                        </form>
                        <?php } ?>
                    </div>
                </div>

                <!-- Cleaning Task List -->
                <div class="card">
                    <div class="card-header">
                        <h4>Cleaning Task List</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Floor Number</th>
                                    <th>Staff Member</th>
                                    <th>Cleaning Date</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT cs.*, s.name as staff_name, r.floor_number 
                                       FROM cleaning_schedule cs 
                                       JOIN staff s ON cs.staff_id = s.id 
                                       JOIN rooms r ON cs.room_number = r.room_number 
                                       ORDER BY cs.cleaning_date DESC";
                                $result = mysqli_query($conn, $sql);
                                while($row = mysqli_fetch_assoc($result)){
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['floor_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['staff_name']) . "</td>";
                                    echo "<td>" . $row['cleaning_date'] . "</td>";
                                    echo "<td>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
session_start();

// Check if the user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Process task assignment
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["assign_task"])){
    $request_id = trim($_POST["request_id"]);
    $assigned_to = trim($_POST["assigned_to"]);
    
    $sql = "UPDATE maintenance_requests SET status = 'assigned', assigned_to = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ii", $assigned_to, $request_id);
        if(mysqli_stmt_execute($stmt)){
            $success_msg = "Task assigned successfully!";
        } else{
            $error_msg = "Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Requests - Hostel Management System</title>
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
                <h2>Maintenance Requests</h2>

                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                <?php if(isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>

                <!-- Active Request List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Active Maintenance Requests</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Floor Number</th>
                                    <th>Student</th>
                                    <th>Issue Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT mr.*, u.username as student_name, s.name as staff_name, r.floor_number 
                                       FROM maintenance_requests mr 
                                       JOIN users u ON mr.student_id = u.id 
                                       LEFT JOIN staff s ON mr.assigned_to = s.id 
                                       JOIN rooms r ON mr.room_number = r.room_number 
                                       WHERE mr.status != 'resolved'
                                       ORDER BY mr.created_at DESC";
                                $result = mysqli_query($conn, $sql);
                                while($row = mysqli_fetch_assoc($result)){
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['floor_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                    echo "<td>" . ucfirst($row['issue_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . ucfirst($row['status']) . "</td>";
                                    echo "<td>" . ($row['staff_name'] ? htmlspecialchars($row['staff_name']) : 'Not Assigned') . "</td>";
                                    echo "<td>" . $row['created_at'] . "</td>";
                                    echo "<td>";
                                    echo "<form method='post' style='display: inline;'>";
                                    echo "<input type='hidden' name='request_id' value='" . $row['id'] . "'>";
                                    echo "<select name='assigned_to' class='form-control form-control-sm' style='display: inline-block; width: auto;'>";
                                    echo "<option value=''>Select Staff</option>";
                                    $staff_sql = "SELECT id, name FROM staff";
                                    $staff_result = mysqli_query($conn, $staff_sql);
                                    while($staff_row = mysqli_fetch_assoc($staff_result)){
                                        echo "<option value='" . $staff_row['id'] . "'>" . htmlspecialchars($staff_row['name']) . "</option>";
                                    }
                                    echo "</select> ";
                                    echo "<button type='submit' name='assign_task' class='btn btn-primary btn-sm'>Assign</button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Resolved Request List -->
                <div class="card">
                    <div class="card-header">
                        <h4>Resolved Maintenance Requests</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Floor Number</th>
                                    <th>Student</th>
                                    <th>Issue Type</th>
                                    <th>Description</th>
                                    <th>Assigned To</th>
                                    <th>Created At</th>
                                    <th>Resolved At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT mr.*, u.username as student_name, s.name as staff_name, r.floor_number 
                                       FROM maintenance_requests mr 
                                       JOIN users u ON mr.student_id = u.id 
                                       LEFT JOIN staff s ON mr.assigned_to = s.id 
                                       JOIN rooms r ON mr.room_number = r.room_number 
                                       WHERE mr.status = 'resolved'
                                       ORDER BY mr.resolved_at DESC";
                                $result = mysqli_query($conn, $sql);
                                while($row = mysqli_fetch_assoc($result)){
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['floor_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                    echo "<td>" . ucfirst($row['issue_type']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . ($row['staff_name'] ? htmlspecialchars($row['staff_name']) : 'Not Assigned') . "</td>";
                                    echo "<td>" . $row['created_at'] . "</td>";
                                    echo "<td>" . $row['resolved_at'] . "</td>";
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
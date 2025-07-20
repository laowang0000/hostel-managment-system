<?php
session_start();

// Check if the user is logged in and is a student
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student"){
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

// Process maintenance request submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $room_number = trim($_POST["room_number"]);
    $issue_type = trim($_POST["issue_type"]);
    $description = trim($_POST["description"]);
    
    $sql = "INSERT INTO maintenance_requests (room_number, student_id, issue_type, description, status) 
            VALUES (?, ?, ?, ?, 'pending')";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "siss", $room_number, $_SESSION["id"], $issue_type, $description);
        if(mysqli_stmt_execute($stmt)){
            header("location: submit_maintenance.php?success=1");
            exit();
        } else{
            $error_msg = "Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Maintenance Request - Hostel Management System</title>
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
                    <a href="my_room.php"><i class="fas fa-door-open"></i> My Room</a>
                    <a href="pay_fees.php"><i class="fas fa-money-bill"></i> Pay Fees</a>
                    <a href="submit_maintenance.php"><i class="fas fa-tools"></i> Maintenance Request</a>
                    <a href="submit_complaint.php"><i class="fas fa-exclamation-circle"></i> Submit Complaint</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <h2>Submit Maintenance Request</h2>
                
                <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="alert alert-success">Maintenance request submitted successfully!</div>
                <?php endif; ?>
                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <!-- Submit Request Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>New Maintenance Request</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label>Room Number</label>
                                <select name="room_number" class="form-control" required>
                                    <?php
                                    $sql = "SELECT room_number FROM room_allocations WHERE student_id = ? AND status = 'active'";
                                    if($stmt = mysqli_prepare($conn, $sql)){
                                        mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if($row = mysqli_fetch_assoc($result)){
                                            echo "<option value='" . htmlspecialchars($row['room_number']) . "'>" . htmlspecialchars($row['room_number']) . "</option>";
                                        }
                                        mysqli_stmt_close($stmt);
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Issue Type</label>
                                <select name="issue_type" class="form-control" required>
                                    <option value="electrical">Electrical</option>
                                    <option value="plumbing">Plumbing</option>
                                    <option value="furniture">Furniture</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="4" required 
                                          placeholder="Please describe the issue in detail..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </form>
                    </div>
                </div>

                <!-- My Requests List -->
                <div class="card">
                    <div class="card-header">
                        <h4>My Maintenance Requests</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Issue Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Created At</th>
                                    <th>Resolved At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT mr.*, s.name as staff_name 
                                       FROM maintenance_requests mr 
                                       LEFT JOIN staff s ON mr.assigned_to = s.id 
                                       WHERE mr.student_id = ? 
                                       ORDER BY mr.created_at DESC";
                                if($stmt = mysqli_prepare($conn, $sql)){
                                    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)){
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                                        echo "<td>" . ucfirst(htmlspecialchars($row['issue_type'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                        echo "<td>" . ucfirst(htmlspecialchars($row['status'])) . "</td>";
                                        echo "<td>" . ($row['staff_name'] ? htmlspecialchars($row['staff_name']) : 'Not Assigned') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                        echo "<td>" . ($row['resolved_at'] ? htmlspecialchars($row['resolved_at']) : 'Not Resolved') . "</td>";
                                        echo "</tr>";
                                    }
                                    mysqli_stmt_close($stmt);
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
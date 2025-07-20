<?php
if (!isset($_SESSION)) session_start();

// Initialize deallocation_msg to avoid undefined variable warning
$deallocation_msg = '';

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: start.php');
    exit;
}

require_once "config/database.php";

// Fetch due fees for students
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === "student") {
    $sql = "SELECT fee_type, amount, due_date FROM fees WHERE student_id = ? AND status = 'unpaid' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY due_date ASC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $due_fees = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $due_fees[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    // Check if the room was recently deallocated
    $sql_check_deallocated = "SELECT ra.room_number, ra.check_out_date 
                             FROM room_allocations ra 
                             WHERE ra.student_id = ? AND ra.status = 'completed' 
                             ORDER BY ra.check_out_date DESC LIMIT 1";
    if ($stmt_check = mysqli_prepare($conn, $sql_check_deallocated)) {
        mysqli_stmt_bind_param($stmt_check, "i", $_SESSION["id"]);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        if ($deallocated = mysqli_fetch_assoc($result_check)) {
            $deallocation_msg = '<div class="alert alert-info">Your room ' . htmlspecialchars($deallocated['room_number']) . ' was deallocated on ' . htmlspecialchars($deallocated['check_out_date']) . '. Please apply for a new room.</div>';
        }
        mysqli_stmt_close($stmt_check);
    }
}

// Set default theme to light if not set
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

// Handle theme change
if (isset($_POST['theme_select'])) {
    $theme = $_POST['theme_select'];
    if (in_array($theme, ['light', 'dark', 'blue', 'pink', 'green'])) {
        $_SESSION['theme'] = $theme;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Hostel Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font: 14px sans-serif;
            background-color: #f8f9fa;
        }
        .wrapper {
            width: 100%;
            padding: 20px;
        }
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
        .content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .theme-light { background-color: #f8f9fa; color: #222; }
        .theme-dark { background-color: #222; color: #ffffff !important; }
        .theme-blue { background-color: #007bff; color: #fff; }
        .theme-pink { background-color: #ffc0cb; color: #222; }
        .theme-green { background-color: #d4edda; color: #155724; }
        .theme-dark .card, .theme-dark .sidebar { background-color: #333 !important; color: #ffffff !important; }
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
                    <?php if ($_SESSION["role"] == "admin"): ?>
                        <a href="room_management.php"><i class="fas fa-door-open"></i> Room Management</a>
                        <a href="fee_management.php"><i class="fas fa-money-bill"></i> Fee Management</a>
                        <a href="cleaning_schedule.php"><i class="fas fa-broom"></i> Cleaning Schedule</a>
                        <a href="maintenance_requests.php"><i class="fas fa-tools"></i> Maintenance</a>
                        <a href="complaints.php"><i class="fas fa-exclamation-circle"></i> Complaints</a>
                    <?php elseif ($_SESSION["role"] == "staff"): ?>
                        <a href="cleaning_task.php"><i class="fas fa-broom"></i> My Cleaning Tasks</a>
                        <a href="maintenance_task.php"><i class="fas fa-tools"></i> My Maintenance Tasks</a>
                    <?php else: ?>
                        <a href="my_room.php"><i class="fas fa-door-open"></i> My Room</a>
                        <a href="pay_fees.php"><i class="fas fa-money-bill"></i> Pay Fees</a>
                        <a href="submit_maintenance.php"><i class="fas fa-tools"></i> Maintenance Request</a>
                        <a href="submit_complaint.php"><i class="fas fa-exclamation-circle"></i> Submit Complaint</a>
                    <?php endif; ?>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
                    <span class="badge badge-primary"><?php echo ucfirst($_SESSION["role"]); ?></span>
                </div>

                <?php if (isset($due_fees) && count($due_fees) > 0): ?>
                    <div class="alert alert-warning">
                        <strong>Payment Reminder:</strong> You have unpaid fees due soon:<br>
                        <ul>
                            <?php foreach ($due_fees as $fee): ?>
                                <li><?php echo ucfirst(htmlspecialchars($fee['fee_type'])); ?> - ₹<?php echo number_format($fee['amount'], 2); ?> (Due: <?php echo htmlspecialchars($fee['due_date']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                        Please pay before the due date to avoid penalties.
                    </div>
                <?php endif; ?>
                <?php if ($deallocation_msg): ?>
                    <?php echo $deallocation_msg; ?>
                <?php endif; ?>

                <div class="row">
                    <?php if ($_SESSION["role"] == "admin"): ?>
                        <!-- Admin Dashboard Cards -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Rooms</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $sql = "SELECT COUNT(*) as total FROM rooms";
                                        $result = mysqli_query($conn, $sql);
                                        $row = mysqli_fetch_assoc($result);
                                        echo $row['total'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Total Students</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $sql = "SELECT COUNT(*) as total FROM users WHERE role='student'";
                                        $result = mysqli_query($conn, $sql);
                                        $row = mysqli_fetch_assoc($result);
                                        echo $row['total'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Requests</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $sql = "SELECT COUNT(*) as total FROM maintenance_requests WHERE status='pending'";
                                        $result = mysqli_query($conn, $sql);
                                        $row = mysqli_fetch_assoc($result);
                                        echo $row['total'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($_SESSION["role"] == "staff"): ?>
                        <!-- Staff Dashboard Cards -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Cleaning Tasks</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $sql = "SELECT COUNT(*) as total FROM cleaning_schedule cs 
                                               WHERE cs.staff_id = (SELECT staff_id FROM users WHERE id = ?) 
                                               AND cs.status != 'completed'";
                                        if ($stmt = mysqli_prepare($conn, $sql)) {
                                            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                            mysqli_stmt_execute($stmt);
                                            $result = mysqli_stmt_get_result($stmt);
                                            $row = mysqli_fetch_assoc($result);
                                            echo $row['total'] ?? 0;
                                            mysqli_stmt_close($stmt);
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Maintenance Tasks</h5>
                                    <p class="card-text display-4">
                                        <?php
                                        $sql = "SELECT COUNT(*) as total FROM maintenance_requests mr 
                                               WHERE mr.assigned_to = (SELECT staff_id FROM users WHERE id = ?) 
                                               AND mr.status != 'resolved'";
                                        if ($stmt = mysqli_prepare($conn, $sql)) {
                                            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                            mysqli_stmt_execute($stmt);
                                            $result = mysqli_stmt_get_result($stmt);
                                            $row = mysqli_fetch_assoc($result);
                                            echo $row['total'] ?? 0;
                                            mysqli_stmt_close($stmt);
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Student Dashboard Cards -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">My Room</h5>
                                    <p class="card-text">
                                        <?php
                                        $sql = "SELECT room_number FROM room_allocations WHERE student_id = ? AND status = 'active'";
                                        if ($stmt = mysqli_prepare($conn, $sql)) {
                                            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                            mysqli_stmt_execute($stmt);
                                            $result = mysqli_stmt_get_result($stmt);
                                            if ($row = mysqli_fetch_assoc($result)) {
                                                echo "Room: " . htmlspecialchars($row['room_number']);
                                            } else {
                                                echo "No room allocated";
                                            }
                                            mysqli_stmt_close($stmt);
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Due Fees</h5>
                                    <p class="card-text">
                                        <?php
                                        $sql = "SELECT SUM(amount) as total FROM fees WHERE student_id = ? AND status='unpaid'";
                                        if ($stmt = mysqli_prepare($conn, $sql)) {
                                            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                            mysqli_stmt_execute($stmt);
                                            $result = mysqli_stmt_get_result($stmt);
                                            if ($row = mysqli_fetch_assoc($result)) {
                                                echo "₹" . number_format($row['total'] ?? 0, 2);
                                            } else {
                                                echo "No dues";
                                            }
                                            mysqli_stmt_close($stmt);
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Active Requests</h5>
                                    <p class="card-text">
                                        <?php
                                        $sql = "SELECT COUNT(*) as total FROM maintenance_requests WHERE student_id = ? AND status='pending'";
                                        if ($stmt = mysqli_prepare($conn, $sql)) {
                                            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                            mysqli_stmt_execute($stmt);
                                            $result = mysqli_stmt_get_result($stmt);
                                            $row = mysqli_fetch_assoc($result);
                                            echo $row['total'] ?? 0;
                                            mysqli_stmt_close($stmt);
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Theme Selector -->
                <form method="post" class="form-inline mb-3" style="float:right;">
                    <label for="theme_select" class="mr-2">Theme:</label>
                    <select name="theme_select" id="theme_select" class="form-control" onchange="this.form.submit()">
                        <option value="light" <?php if ($_SESSION['theme'] === 'light') echo 'selected'; ?>>Light</option>
                        <option value="dark" <?php if ($_SESSION['theme'] === 'dark') echo 'selected'; ?>>Dark</option>
                        <option value="blue" <?php if ($_SESSION['theme'] === 'blue') echo 'selected'; ?>>Blue</option>
                        <option value="pink" <?php if ($_SESSION['theme'] === 'pink') echo 'selected'; ?>>Pink</option>
                        <option value="green" <?php if ($_SESSION['theme'] === 'green') echo 'selected'; ?>>Green</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
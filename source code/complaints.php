<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: login.php");
    exit;
}

require_once "config/database.php";

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

// Process complaint status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_status"])) {
    $complaint_id = (int)trim($_POST["complaint_id"]);
    $status = trim($_POST["status"]);
    $admin_remarks = trim($_POST["admin_remarks"]);
    
    // Validate inputs
    if ($complaint_id <= 0 || !in_array($status, ['pending', 'in_progress', 'resolved'])) {
        $error_msg = "Invalid input data.";
    } else {
        $sql = "UPDATE complaints SET status = ?, admin_remarks = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $status, $admin_remarks, $complaint_id);
            if (mysqli_stmt_execute($stmt)) {
                header("location: complaints.php");
                exit();
            } else {
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
    <title>Complaints - Hostel Management System</title>
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

        /* Modal theme styles */
        .theme-light .modal-content { background-color: #ffffff; color: #222; }
        .theme-dark .modal-content { background-color: #333; color: #ffffff; }
        .theme-blue .modal-content { background-color: #339cff; color: #fff; }
        .theme-pink .modal-content { background-color: #ffe4ec; color: #222; }
        .theme-green .modal-content { background-color: #e2f7e1; color: #155724; }
        .modal-content .modal-header { border-bottom: 1px solid rgba(0, 0, 0, 0.1); }
        .theme-dark .modal-header, .theme-blue .modal-header { border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .modal-content .modal-title { font-weight: bold; }
        .theme-dark .modal-title, .theme-blue .modal-title, .theme-dark label, .theme-blue label { color: #fff; }
        .theme-pink .modal-title, .theme-green .modal-title, .theme-pink label, .theme-green label { color: #222; }
        .modal-content .close { color: #000; text-shadow: none; opacity: 0.8; }
        .theme-dark .close, .theme-blue .close { color: #fff; }
        .theme-pink .close, .theme-green .close { color: #222; }
        .modal-content .form-control { background-color: #fff; color: #222; border: 1px solid #ced4da; }
        .theme-dark .form-control { background-color: #444; color: #fff; border: 1px solid #555; }
        .theme-blue .form-control { background-color: #e6f0ff; color: #222; border: 1px solid #99c2ff; }
        .theme-pink .form-control { background-color: #fff5f7; color: #222; border: 1px solid #ffccd5; }
        .theme-green .form-control { background-color: #f5faf5; color: #155724; border: 1px solid #c3e6cb; }
        .modal-content .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .theme-dark .btn-primary { background-color: #0056b3; border-color: #0056b3; }
        .theme-blue .btn-primary { background-color: #004d99; border-color: #004d99; }
        .theme-pink .btn-primary { background-color: #ff8099; border-color: #ff8099; }
        .theme-green .btn-primary { background-color: #28a745; border-color: #28a745; }
        .modal-content .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .theme-dark .btn-secondary { background-color: #5a6268; border-color: #5a6268; }
        .theme-blue .btn-secondary { background-color: #668cff; border-color: #668cff; }
        .theme-pink .btn-secondary { background-color: #ff99b3; border-color: #ff99b3; }
        .theme-green .btn-secondary { background-color: #6c757d; border-color: #6c757d; }
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
                <h2>Complaints Management</h2>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <!-- Complaints List -->
                <div class="card">
                    <div class="card-header">
                        <h4>Complaints List</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Complaint Type</th>
                                    <th>Description</th>
                                    <th>Anonymous</th>
                                    <th>Status</th>
                                    <th>Admin Remarks</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT c.*, u.username as student_name 
                                       FROM complaints c 
                                       JOIN users u ON c.student_id = u.id 
                                       ORDER BY c.created_at DESC";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . ($row['is_anonymous'] ? 'Anonymous' : htmlspecialchars($row['student_name'])) . "</td>";
                                    echo "<td>" . ucfirst(str_replace('_', ' ', htmlspecialchars($row['complaint_type']))) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td>" . ($row['is_anonymous'] ? 'Yes' : 'No') . "</td>";
                                    echo "<td>" . ucfirst(htmlspecialchars($row['status'])) . "</td>";
                                    echo "<td>" . (empty($row['admin_remarks']) ? 'No remarks' : htmlspecialchars($row['admin_remarks'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                                    echo "<td>";
                                    if ($row['status'] != 'resolved') {
                                        echo "<button type='button' class='btn btn-primary btn-sm' 
                                              onclick='showUpdateModal(" . $row['id'] . ")'>Update Status</button>";
                                    }
                                    echo "</td>";
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content theme-<?php echo htmlspecialchars($_SESSION['theme']); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Update Complaint Status</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="updateForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="complaint_id" id="modal_complaint_id">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Admin Remarks</label>
                            <textarea name="admin_remarks" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                            <button type="reset" class="btn btn-secondary ml-2">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function showUpdateModal(complaintId) {
            document.getElementById('modal_complaint_id').value = complaintId;
            $('#updateModal').modal('show');
        }
    </script>
</body>
</html>
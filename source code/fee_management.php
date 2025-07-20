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

// Process fee creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create_fee"])) {
    $student_id = (int)trim($_POST["student_id"]);
    $amount = floatval(trim($_POST["amount"]));
    $fee_type = trim($_POST["fee_type"]);
    $due_date = trim($_POST["due_date"]);
    
    $error_msg = '';
    
    // Validate inputs
    if ($student_id <= 0) {
        $error_msg = "Invalid student selected.";
    } elseif ($amount <= 0) {
        $error_msg = "Amount must be greater than zero.";
    } elseif (!in_array($fee_type, ['room_rent', 'maintenance', 'other'])) {
        $error_msg = "Invalid fee type selected.";
    } elseif (empty($due_date)) {
        $error_msg = "Due date is required.";
    }
    
    // Insert fee
    if (empty($error_msg)) {
        $sql = "INSERT INTO fees (student_id, amount, fee_type, due_date) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "idss", $student_id, $amount, $fee_type, $due_date);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Fee created successfully!";
            } else {
                $error_msg = "Error creating fee.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Database query preparation failed.";
        }
    }
}

// Process payment approval
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["approve_payment"])) {
    $fee_id = (int)trim($_POST["fee_id"]);
    $payment_id = (int)trim($_POST["payment_id"]);
    
    $error_msg = '';
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update fee_payments status to success
        $sql = "UPDATE fee_payments SET status = 'success' WHERE id = ? AND fee_id = ? AND status = 'pending'";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $payment_id, $fee_id);
            if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) == 0) {
                throw new Exception("No pending payment found or already processed.");
            }
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Failed to prepare payment update query.");
        }
        
        // Update fees status to paid
        $sql = "UPDATE fees SET status = 'paid' WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $fee_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception("Failed to prepare fee status update query.");
        }
        
        // Commit transaction
        mysqli_commit($conn);
        $success_msg = "Payment approved successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error_msg = "Failed to approve payment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Management - Hostel Management System</title>
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
        .theme-dark .modal-title, .theme-blue .modal-title { color: #fff; }
        .theme-pink .modal-title, .theme-green .modal-title { color: #222; }
        .modal-content .close { color: #000; text-shadow: none; opacity: 0.8; }
        .theme-dark .close, .theme-blue .close { color: #fff; }
        .theme-pink .close, .theme-green .close { color: #222; }
        .theme-dark .modal-body .table, .theme-dark .modal-body .table th, .theme-dark .modal-body .table td,
        .theme-dark .modal-body .table thead th, .theme-dark .modal-body .table tbody td { color: #ffffff !important; }
        .theme-blue .modal-body .table { background-color: #e6f0ff; color: #222; }
        .theme-pink .modal-body .table { background-color: #fff5f7; color: #222; }
        .theme-green .modal-body .table { background-color: #f5faf5; color: #155724; }
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
                <h2>Fee Management</h2>
                
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>

                <!-- Create Fee Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Create New Fee</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Student</label>
                                        <select name="student_id" class="form-control" required>
                                            <option value="">Select Student</option>
                                            <?php
                                            $sql = "SELECT id, username FROM users WHERE role = 'student'";
                                            $result = mysqli_query($conn, $sql);
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['username']) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Fee Type</label>
                                        <select name="fee_type" class="form-control" required>
                                            <option value="room_rent">Room Rent</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Due Date</label>
                                        <input type="date" name="due_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="create_fee" class="btn btn-primary">Create Fee</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Fee List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Fee List</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Amount</th>
                                    <th>Fee Type</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT f.*, u.username, 
                                       (SELECT COUNT(*) FROM fee_payments fp WHERE fp.fee_id = f.id AND fp.status = 'pending') as pending_payments 
                                       FROM fees f 
                                       JOIN users u ON f.student_id = u.id 
                                       ORDER BY f.due_date DESC";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td>₹" . number_format($row['amount'], 2) . "</td>";
                                    echo "<td>" . ucfirst(str_replace('_', ' ', $row['fee_type'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                                    echo "<td>" . ucfirst(htmlspecialchars($row['status'])) . "</td>";
                                    echo "<td>";
                                    echo "<a href='#paymentHistoryModal' data-toggle='modal' 
                                           data-fee-id='" . $row['id'] . "' 
                                           class='btn btn-info btn-sm view-history'>View History</a>";
                                    if ($row['status'] == 'unpaid' && $row['pending_payments'] > 0) {
                                        $sql_payment = "SELECT id FROM fee_payments WHERE fee_id = ? AND status = 'pending' LIMIT 1";
                                        if ($stmt_payment = mysqli_prepare($conn, $sql_payment)) {
                                            mysqli_stmt_bind_param($stmt_payment, "i", $row['id']);
                                            mysqli_stmt_execute($stmt_payment);
                                            $result_payment = mysqli_stmt_get_result($stmt_payment);
                                            if ($payment = mysqli_fetch_assoc($result_payment)) {
                                                echo "<form action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' method='post' style='display:inline;'>";
                                                echo "<input type='hidden' name='fee_id' value='" . $row['id'] . "'>";
                                                echo "<input type='hidden' name='payment_id' value='" . $payment['id'] . "'>";
                                                echo "<button type='submit' name='approve_payment' class='btn btn-success btn-sm ml-1'>Approve Payment</button>";
                                                echo "</form>";
                                            }
                                            mysqli_stmt_close($stmt_payment);
                                        }
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

    <!-- Payment History Modal -->
    <div class="modal fade" id="paymentHistoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content theme-<?php echo htmlspecialchars($_SESSION['theme']); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Payment History</5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistoryBody">
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $('.view-history').click(function() {
            var feeId = $(this).data('fee-id');
            $.ajax({
                url: 'get_payment_history.php',
                method: 'POST',
                data: { fee_id: feeId },
                success: function(response) {
                    $('#paymentHistoryBody').html(response);
                },
                error: function() {
                    $('#paymentHistoryBody').html('<tr><td colspan="5">Failed to load payment history.</td></tr>');
                }
            });
        });
    </script>
</body>
</html>
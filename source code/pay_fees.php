<?php
session_start();

// Check if the user is logged in and is a student
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "student" || !isset($_SESSION["id"])) {
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

// Process payment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_payment"])) {
    $fee_id = trim($_POST["fee_id"]);
    $amount = floatval(trim($_POST["amount"]));
    $payment_method = trim($_POST["payment_method"]);
    $transaction_id = trim($_POST["transaction_id"]);
    
    $error_msg = '';
    
    // Validate amount
    if ($amount <= 0) {
        $error_msg = "Invalid payment amount.";
    }
    
    // Validate payment method
    $allowed_methods = ['cash', 'online_transfer', 'e_wallet', 'credit_debit_card'];
    if (!in_array($payment_method, $allowed_methods)) {
        $error_msg = "Invalid payment method selected.";
    }
    
    // Validate transaction ID (required for non-cash methods)
    if ($payment_method !== 'cash' && empty($transaction_id)) {
        $error_msg = "Transaction ID is required for this payment method.";
    }
    
    // Insert payment submission into fee_payments
    if (empty($error_msg)) {
        $sql = "INSERT INTO fee_payments (fee_id, amount, payment_method, transaction_id, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "idss", $fee_id, $amount, $payment_method, $transaction_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Payment method submitted successfully! Awaiting admin approval.";
            } else {
                $error_msg = "Failed to submit payment details.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Database query preparation failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Fees - Hostel Management System</title>
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
                <h2>Pay Fees</h2>
                
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>

                <!-- Unpaid Fees List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Unpaid Fees</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT id, amount, fee_type, due_date 
                                       FROM fees 
                                       WHERE student_id = ? AND status = 'unpaid'";
                                if ($stmt = mysqli_prepare($conn, $sql)) {
                                    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td>" . ucfirst(str_replace('_', ' ', htmlspecialchars($row['fee_type']))) . "</td>";
                                            echo "<td>₹" . number_format($row['amount'], 2) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                                            echo "<td>";
                                            echo "<button type='button' class='btn btn-primary btn-sm' 
                                                  onclick='showPaymentModal(" . $row['id'] . ", " . $row['amount'] . ")'>Pay Now</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4'>No unpaid fees found.</td></tr>";
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

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <input type="hidden" name="fee_id" id="modal_fee_id">
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" id="modal_amount" class="form-control" step="0.01" readonly>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-control" required>
                                <option value="cash">Cash</option>
                                <option value="online_transfer">Online Transfer</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="credit_debit_card">Credit/Debit Card</option>
                            </select>
                        </div>
                        <div class="form-group" id="transaction_id_group" style="display:none;">
                            <label>Transaction ID</label>
                            <input type="text" name="transaction_id" id="transaction_id" class="form-control">
                        </div>
                        <button type="submit" name="submit_payment" class="btn btn-primary">Submit Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function showPaymentModal(feeId, amount) {
            document.getElementById('modal_fee_id').value = feeId;
            document.getElementById('modal_amount').value = amount.toFixed(2);
            $('#paymentModal').modal('show');
        }

        $(document).ready(function(){
            $('#payment_method').change(function(){
                if($(this).val() !== 'cash') {
                    $('#transaction_id_group').show();
                    $('#transaction_id').prop('required', true);
                } else {
                    $('#transaction_id_group').hide();
                    $('#transaction_id').prop('required', false);
                }
            });
        });
    </script>
</body>
</html>
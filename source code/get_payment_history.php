<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    exit; // Silently exit for AJAX
}

require_once "config/database.php";

if (isset($_POST['fee_id'])) {
    $fee_id = (int)$_POST['fee_id'];
    $sql = "SELECT payment_date, amount, payment_method, transaction_id, status 
            FROM fee_payments WHERE fee_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $fee_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr style='color: " . ($_SESSION['theme'] === 'dark' ? '#ffffff' : 'inherit') . ";'>";
                echo "<td>" . htmlspecialchars($row['payment_date']) . "</td>";
                echo "<td>₹" . number_format($row['amount'], 2) . "</td>";
                echo "<td>" . ucfirst(str_replace('_', ' ', htmlspecialchars($row['payment_method']))) . "</td>";
                echo "<td>" . (empty($row['transaction_id']) ? '-' : htmlspecialchars($row['transaction_id'])) . "</td>";
                echo "<td>" . ucfirst(htmlspecialchars($row['status'])) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr style='color: " . ($_SESSION['theme'] === 'dark' ? '#ffffff' : 'inherit') . ";'>";
            echo "<td colspan='5' class='text-center'>No payment records found.</td>";
            echo "</tr>";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
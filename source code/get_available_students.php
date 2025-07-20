<?php
session_start();

// Check if the user is logged in and is admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['room_number'])) {
    $room_number = trim($_POST['room_number']);
    
    // Fetch students not allocated to any room
    $sql = "SELECT id, username 
            FROM users 
            WHERE role = 'student' 
            AND id NOT IN (SELECT student_id FROM room_allocations WHERE status = 'active')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = [
            'id' => $row['id'],
            'username' => htmlspecialchars($row['username'])
        ];
    }
    mysqli_stmt_close($stmt);
    
    header('Content-Type: application/json');
    echo json_encode($students);
}
?>
<?php
// Redirect to dashboard if already logged in
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Hostel Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #007bff 0%, #6dd5ed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .welcome-box {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.2);
            padding: 40px 30px 30px 30px;
            max-width: 500px;
            text-align: center;
        }
        .welcome-box h1 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 20px;
        }
        .welcome-box p {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 30px;
        }
        .welcome-box .btn-primary {
            font-size: 1.1rem;
            padding: 10px 30px;
            border-radius: 25px;
            box-shadow: 0 2px 8px rgba(0,123,255,0.15);
        }
        .welcome-icon {
            font-size: 3rem;
            color: #339cff;
            margin-bottom: 15px;
            animation: bounce 1.5s infinite alternate;
        }
        @keyframes bounce {
            0% { transform: translateY(0); }
            100% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="welcome-box">
        <div class="welcome-icon">
            <i class="fas fa-building"></i>
        </div>
        <h1>Welcome!</h1>
        <p>
            <strong>Hostel Management System</strong><br>
            Streamline your hostel experience.<br>
            <span style="color:#007bff;">Students</span>, <span style="color:#28a745;">Staff</span>, and <span style="color:#343a40;">Admins</span> can manage rooms, fees, maintenance, and more—all in one place.
        </p>
        <table class="table table-bordered mt-4" style="background:#fff;">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Programme</th>
                    <th>Lab Section</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>1</td><td>1211110319</td><td>KEE SHEE HAU</td><td>AI</td><td>1C</td></tr>
                <tr><td>2</td><td>1211108357</td><td>ONG QI REN</td><td>AI</td><td>1C</td></tr>
                <tr><td>3</td><td>1211108626</td><td>LIM CHIN CHEN</td><td>AI</td><td>1C</td></tr>
                <tr><td>4</td><td>1211108213</td><td>SOO TECK SHEN</td><td>AI</td><td>1C</td></tr>
            </tbody>
        </table>
        <a href="login.php" class="btn btn-primary">Login to Continue</a>
        <div class="mt-4" style="font-size:0.95rem;color:#888;">
            <i class="fas fa-info-circle"></i> Secure &amp; User-Friendly | 2024
        </div>
    </div>
</body>
</html> 
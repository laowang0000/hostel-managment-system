<?php
require_once "config/database.php";

$username = $password = $confirm_password = $role = $staff_name = "";
$username_err = $password_err = $confirm_password_err = $role_err = $staff_name_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = trim($_POST["username"]);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate role
    if (empty(trim($_POST["role"]))) {
        $role_err = "Please select a role.";     
    } elseif (!in_array(trim($_POST["role"]), ['admin', 'student', 'staff'])) {
        $role_err = "Invalid role selected.";
    } else {
        $role = trim($_POST["role"]);
    }
    
    // Validate staff name if role is staff
    if ($role == 'staff') {
        if (empty(trim($_POST["staff_name"]))) {
            $staff_name_err = "Please enter a staff name.";
        } else {
            $staff_name = trim($_POST["staff_name"]);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err) && empty($staff_name_err)) {
        // Begin transaction for atomicity
        mysqli_begin_transaction($conn);
        
        try {
            $staff_id = NULL;
            if ($role == 'staff') {
                // Insert into staff table
                $sql = "INSERT INTO staff (name) VALUES (?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "s", $staff_name);
                    if (mysqli_stmt_execute($stmt)) {
                        $staff_id = mysqli_insert_id($conn);
                    } else {
                        throw new Exception("Failed to insert staff.");
                    }
                    mysqli_stmt_close($stmt);
                }
            }
            
            // Insert into users table
            $sql = "INSERT INTO users (username, password, role, staff_id) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssi", $param_username, $param_password, $param_role, $staff_id);
                
                $param_username = $username;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_role = $role;
                
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_commit($conn);
                    header("location: login.php");
                } else {
                    throw new Exception("Failed to insert user.");
                }
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "Oops! Something went wrong: " . htmlspecialchars($e->getMessage());
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - Hostel Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 360px; padding: 20px; margin: 0 auto; margin-top: 50px; }
        #staff_name_field { display: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Sign Up</h2>
        <p>Please fill this form to create an account.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role" class="form-control <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Select Role</option>
                    <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="student" <?php echo ($role == 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="staff" <?php echo ($role == 'staff') ? 'selected' : ''; ?>>Staff</option>
                </select>
                <span class="invalid-feedback"><?php echo $role_err; ?></span>
            </div>
            <div class="form-group" id="staff_name_field">
                <label>Staff Name</label>
                <input type="text" name="staff_name" class="form-control <?php echo (!empty($staff_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($staff_name); ?>">
                <span class="invalid-feedback"><?php echo $staff_name_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($password); ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($confirm_password); ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
            </div>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>
    <script>
        document.getElementById('role').addEventListener('change', function() {
            var staffField = document.getElementById('staff_name_field');
            if (this.value === 'staff') {
                staffField.style.display = 'block';
            } else {
                staffField.style.display = 'none';
            }
        });
    </script>
</body>
</html>
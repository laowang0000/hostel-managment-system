<?php
session_start();

// Check if the user is logged in and is admin
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

// Handle room creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_room'])) {
    $room_number = trim($_POST['room_number']);
    $room_type = trim($_POST['room_type']);
    $ac_status = trim($_POST['ac_status']);
    $floor_number = (int)$_POST['floor_number'];
    
    // Validate inputs
    if (empty($room_number) || !in_array($room_type, ['single', 'double']) || !in_array($ac_status, ['AC', 'Non-AC']) || $floor_number < 0) {
        $error_msg = "Invalid input data.";
    } else {
        // Check if room number already exists
        $sql = "SELECT room_number FROM rooms WHERE room_number = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $room_number);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error_msg = "Room number already exists.";
        } else {
            $sql = "INSERT INTO rooms (room_number, room_type, ac_status, floor_number, status) VALUES (?, ?, ?, ?, 'available')";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssi", $room_number, $room_type, $ac_status, $floor_number);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "Room created successfully!";
                } else {
                    $error_msg = "Error creating room.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Handle room allocation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['allocate_room'])) {
    $room_number = trim($_POST['room_number']);
    $student_id = (int)$_POST['student_id'];
    $check_in_date = trim($_POST['check_in_date']);
    
    // Validate inputs
    if (empty($room_number) || $student_id <= 0 || empty($check_in_date)) {
        $error_msg = "Invalid input data.";
    } else {
        // Check room type and current allocations
        $sql = "SELECT room_type, status FROM rooms WHERE room_number = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $room_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $room = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($room) {
            // Check if student is already allocated to any room
            $sql = "SELECT id FROM room_allocations WHERE student_id = ? AND status = 'active'";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $student_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_msg = "Student is already allocated to another room.";
            } else {
                $sql = "SELECT COUNT(*) as count FROM room_allocations WHERE room_number = ? AND status = 'active'";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $room_number);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $allocations = mysqli_fetch_assoc($result)['count'];
                mysqli_stmt_close($stmt);
                
                $max_occupants = ($room['room_type'] === 'double') ? 2 : 1;
                if ($allocations < $max_occupants) {
                    $sql = "INSERT INTO room_allocations (room_number, student_id, check_in_date) VALUES (?, ?, ?)";
                    if ($stmt = mysqli_prepare($conn, $sql)) {
                        mysqli_stmt_bind_param($stmt, "sis", $room_number, $student_id, $check_in_date);
                        if (mysqli_stmt_execute($stmt)) {
                            // Update room status
                            $allocations++;
                            $new_status = ($allocations >= $max_occupants) ? 'occupied' : 'available';
                            $sql_update = "UPDATE rooms SET status = ? WHERE room_number = ?";
                            $stmt_update = mysqli_prepare($conn, $sql_update);
                            mysqli_stmt_bind_param($stmt_update, "ss", $new_status, $room_number);
                            mysqli_stmt_execute($stmt_update);
                            mysqli_stmt_close($stmt_update);
                            $success_msg = "Room allocated successfully!";
                        } else {
                            $error_msg = "Error allocating room.";
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $error_msg = "Room is already fully occupied.";
                }
            }
        } else {
            $error_msg = "Invalid room number.";
        }
    }
}

// Handle room deallocation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deallocate_room'])) {
    $allocation_id = (int)$_POST['allocation_id'];
    
    // Validate inputs
    if ($allocation_id <= 0) {
        $error_msg = "Invalid allocation ID.";
    } else {
        // Get room details
        $sql = "SELECT ra.room_number, r.room_type FROM room_allocations ra JOIN rooms r ON ra.room_number = r.room_number WHERE ra.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $allocation_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $room_info = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($room_info) {
            $sql = "UPDATE room_allocations SET check_out_date = CURDATE(), status = 'completed' WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $allocation_id);
                if (mysqli_stmt_execute($stmt)) {
                    // Update room status
                    $sql_count = "SELECT COUNT(*) as count FROM room_allocations WHERE room_number = ? AND status = 'active'";
                    $stmt_count = mysqli_prepare($conn, $sql_count);
                    mysqli_stmt_bind_param($stmt_count, "s", $room_info['room_number']);
                    mysqli_stmt_execute($stmt_count);
                    $result = mysqli_stmt_get_result($stmt_count);
                    $allocations = mysqli_fetch_assoc($result)['count'];
                    mysqli_stmt_close($stmt_count);
                    
                    $max_occupants = ($room_info['room_type'] === 'double') ? 2 : 1;
                    $new_status = ($allocations > 0) ? ($allocations >= $max_occupants ? 'occupied' : 'available') : 'available';
                    
                    $sql_update = "UPDATE rooms SET status = ? WHERE room_number = ?";
                    $stmt_update = mysqli_prepare($conn, $sql_update);
                    mysqli_stmt_bind_param($stmt_update, "ss", $new_status, $room_info['room_number']);
                    mysqli_stmt_execute($stmt_update);
                    mysqli_stmt_close($stmt_update);
                    $success_msg = "Room deallocated successfully!";
                } else {
                    $error_msg = "Error deallocating room.";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $error_msg = "Invalid allocation ID.";
        }
    }
}

// Handle room application approval/denial
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_application'])) {
    $application_id = (int)$_POST['application_id'];
    $decision = trim($_POST['decision']);
    $admin_remarks = trim($_POST['admin_remarks']);
    $decision_date = date('Y-m-d H:i:s');

    // Validate inputs
    if ($application_id <= 0 || !in_array($decision, ['approved', 'denied'])) {
        $error_msg = "Invalid input data.";
    } else {
        // Get application details
        $sql = "SELECT ra.*, r.room_type FROM room_applications ra JOIN rooms r ON ra.room_number = r.room_number WHERE ra.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $application = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($application && $application['status'] == 'pending') {
            if ($decision == 'approved') {
                // Check if student is already allocated
                $sql = "SELECT id FROM room_allocations WHERE student_id = ? AND status = 'active'";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $application['student_id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) {
                    $error_msg = "Student is already allocated to another room.";
                } else {
                    // Check room capacity
                    $sql = "SELECT COUNT(*) as count FROM room_allocations WHERE room_number = ? AND status = 'active'";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $application['room_number']);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $allocations = mysqli_fetch_assoc($result)['count'];
                    mysqli_stmt_close($stmt);
                    
                    $max_occupants = ($application['room_type'] === 'double') ? 2 : 1;
                    if ($allocations < $max_occupants) {
                        // Allocate the room
                        $sql = "INSERT INTO room_allocations (room_number, student_id, check_in_date) VALUES (?, ?, CURDATE())";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "si", $application['room_number'], $application['student_id']);
                        if (mysqli_stmt_execute($stmt)) {
                            // Update room status
                            $allocations++;
                            $new_status = ($allocations >= $max_occupants) ? 'occupied' : 'available';
                            $sql_update = "UPDATE rooms SET status = ? WHERE room_number = ?";
                            $stmt_update = mysqli_prepare($conn, $sql_update);
                            mysqli_stmt_bind_param($stmt_update, "ss", $new_status, $application['room_number']);
                            mysqli_stmt_execute($stmt_update);
                            mysqli_stmt_close($stmt_update);
                            // Update application status
                            $sql_app = "UPDATE room_applications SET status='approved', admin_remarks=?, decision_date=? WHERE id=?";
                            $stmt_app = mysqli_prepare($conn, $sql_app);
                            mysqli_stmt_bind_param($stmt_app, "ssi", $admin_remarks, $decision_date, $application_id);
                            mysqli_stmt_execute($stmt_app);
                            mysqli_stmt_close($stmt_app);
                            $success_msg = "Application approved and room allocated.";
                        } else {
                            $error_msg = "Failed to allocate room.";
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        $error_msg = "Room is already fully occupied.";
                    }
                }
            } else {
                // Deny application
                $sql_app = "UPDATE room_applications SET status='denied', admin_remarks=?, decision_date=? WHERE id=?";
                $stmt_app = mysqli_prepare($conn, $sql_app);
                mysqli_stmt_bind_param($stmt_app, "ssi", $admin_remarks, $decision_date, $application_id);
                mysqli_stmt_execute($stmt_app);
                mysqli_stmt_close($stmt_app);
                $success_msg = "Application denied.";
            }
        } else {
            $error_msg = "Invalid or non-pending application.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Management - Hostel Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font: 14px sans-serif; background-color: #f8f9fa; }
        .wrapper { width: 100%; padding: 20px; }
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #495057; }
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
        .theme-green .form-control { background-color: #fff !important; color: #222 !important; }
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
                <h2>Room Management</h2>
                
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <!-- Create Room Form -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Create New Room</h5>
                        <form method="post">
                            <div class="form-group">
                                <label>Room Number</label>
                                <input type="text" name="room_number" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Room Type</label>
                                <select name="room_type" class="form-control" required>
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>AC Status</label>
                                <select name="ac_status" class="form-control" required>
                                    <option value="AC">AC</option>
                                    <option value="Non-AC">Non-AC</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Floor Number</label>
                                <input type="number" name="floor_number" class="form-control" required min="0">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="create_room" class="btn btn-primary">Create Room</button>
                                <button type="reset" class="btn btn-secondary ml-2">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Allocate Room Form -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Allocate Room</h5>
                        <form method="post" id="allocateRoomForm">
                            <div class="form-group">
                                <label>Room Number</label>
                                <select name="room_number" id="room_number" class="form-control" required>
                                    <option value="">Select Room</option>
                                    <?php
                                    $sql = "SELECT r.room_number, r.room_type, COUNT(ra.id) as occupant_count 
                                            FROM rooms r 
                                            LEFT JOIN room_allocations ra ON r.room_number = ra.room_number AND ra.status = 'active'
                                            GROUP BY r.room_number, r.room_type
                                            HAVING occupant_count < CASE WHEN r.room_type = 'double' THEN 2 ELSE 1 END";
                                    $result = mysqli_query($conn, $sql);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . htmlspecialchars($row['room_number']) . "'>" . htmlspecialchars($row['room_number']) . " (" . ucfirst($row['room_type']) . ", " . $row['occupant_count'] . "/" . ($row['room_type'] === 'double' ? 2 : 1) . ")</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Student</label>
                                <select name="student_id" id="student_id" class="form-control" required>
                                    <option value="">Select Student</option>
                                    <?php
                                    $sql = "SELECT id, username FROM users WHERE role='student' AND id NOT IN (SELECT student_id FROM room_allocations WHERE status = 'active')";
                                    $result = mysqli_query($conn, $sql);
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['username']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Check-in Date</label>
                                <input type="date" name="check_in_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="allocate_room" class="btn btn-primary">Allocate Room</button>
                                <button type="reset" class="btn btn-secondary ml-2">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Room Status Table -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Room Status</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Type</th>
                                    <th>AC Status</th>
                                    <th>Floor</th>
                                    <th>Status</th>
                                    <th>Occupants</th>
                                    <th>Current Student(s)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT r.*, GROUP_CONCAT(u.username SEPARATOR ', ') as usernames, 
                                        GROUP_CONCAT(ra.id) as allocation_ids, COUNT(ra.id) as occupant_count,
                                        CASE WHEN r.room_type = 'double' THEN 2 ELSE 1 END as max_occupants
                                        FROM rooms r 
                                        LEFT JOIN room_allocations ra ON r.room_number = ra.room_number AND ra.status = 'active'
                                        LEFT JOIN users u ON ra.student_id = u.id
                                        GROUP BY r.room_number";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                                    echo "<td>" . ucfirst(htmlspecialchars($row['room_type'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ac_status']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['floor_number']) . "</td>";
                                    echo "<td>" . ucfirst(htmlspecialchars($row['status'])) . "</td>";
                                    echo "<td>" . $row['occupant_count'] . "/" . $row['max_occupants'] . "</td>";
                                    echo "<td>" . ($row['usernames'] ? htmlspecialchars($row['usernames']) : '-') . "</td>";
                                    echo "<td>";
                                    if ($row['allocation_ids']) {
                                        $alloc_ids = explode(',', $row['allocation_ids']);
                                        foreach ($alloc_ids as $alloc_id) {
                                            echo "<form method='post' style='display:inline; margin-right:10px;'>
                                                    <input type='hidden' name='allocation_id' value='" . htmlspecialchars($alloc_id) . "'>
                                                    <button type='submit' name='deallocate_room' class='btn btn-sm btn-danger'>Deallocate</button>
                                                  </form>";
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

                <!-- Room Applications Table -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Pending Room Applications</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Application ID</th>
                                    <th>Student</th>
                                    <th>Room Number</th>
                                    <th>Application Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT ra.*, u.username FROM room_applications ra JOIN users u ON ra.student_id = u.id WHERE ra.status = 'pending' ORDER BY ra.application_date ASC";
                                $result = mysqli_query($conn, $sql);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['application_date']) . "</td>";
                                    echo "<td>" . ucfirst(htmlspecialchars($row['status'])) . "</td>";
                                    echo "<td>";
                                    echo '<form method="post" style="display:inline;">';
                                    echo '<input type="hidden" name="application_id" value="' . htmlspecialchars($row['id']) . '">';
                                    echo '<input type="text" name="admin_remarks" class="form-control mb-1" placeholder="Remarks (optional)">';
                                    echo '<button type="submit" name="process_application" value="approve" class="btn btn-sm btn-success mr-1" onclick="this.form.decision.value=\'approved\';">Approve</button>';
                                    echo '<button type="submit" name="process_application" value="deny" class="btn btn-sm btn-danger" onclick="this.form.decision.value=\'denied\';">Deny</button>';
                                    echo '<input type="hidden" name="decision" value="">';
                                    echo '</form>';
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

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#room_number').change(function() {
                var roomNumber = $(this).val();
                if (roomNumber) {
                    $.ajax({
                        url: 'get_available_students.php',
                        type: 'POST',
                        data: { room_number: roomNumber },
                        dataType: 'json',
                        success: function(data) {
                            var studentSelect = $('#student_id');
                            studentSelect.empty();
                            studentSelect.append('<option value="">Select Student</option>');
                            $.each(data, function(index, student) {
                                studentSelect.append('<option value="' + student.id + '">' + student.username + '</option>');
                            });
                        },
                        error: function() {
                            alert('Failed to fetch available students.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
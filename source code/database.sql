-- Create database
CREATE DATABASE IF NOT EXISTS hostel_management;
USE hostel_management;

-- Staff table (created first to avoid foreign key issues)
CREATE TABLE IF NOT EXISTS staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'staff') NOT NULL,
    staff_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    room_number VARCHAR(10) PRIMARY KEY,
    room_type ENUM('single', 'double') NOT NULL,
    ac_status ENUM('AC', 'Non-AC') NOT NULL,
    floor_number INT NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room allocations table
CREATE TABLE IF NOT EXISTS room_allocations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10),
    student_id INT,
    check_in_date DATE NOT NULL,
    check_out_date DATE,
    status ENUM('active', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_number) REFERENCES rooms(room_number),
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Fees table
CREATE TABLE IF NOT EXISTS fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    amount DECIMAL(10,2) NOT NULL,
    fee_type ENUM('room_rent', 'maintenance', 'other') NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('paid', 'unpaid') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Fee payments table
CREATE TABLE IF NOT EXISTS fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fee_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('cash', 'online transfer', 'e_wallet', 'credit_debit_card', 'other') NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    FOREIGN KEY (fee_id) REFERENCES fees(id)
);

-- Cleaning schedule table
CREATE TABLE IF NOT EXISTS cleaning_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10),
    staff_id INT,
    cleaning_date DATE NOT NULL,
    status ENUM('assigned', 'completed') DEFAULT 'assigned',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_number) REFERENCES rooms(room_number),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Maintenance requests table
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(10),
    student_id INT,
    issue_type ENUM('electrical', 'plumbing', 'furniture', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'assigned', 'in_progress', 'resolved') DEFAULT 'pending',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (room_number) REFERENCES rooms(room_number),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES staff(id)
);

-- Complaints table
CREATE TABLE IF NOT EXISTS complaints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    complaint_type ENUM('noise', 'food', 'behavior', 'other') NOT NULL,
    description TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES users(id)
);

-- Room applications table
CREATE TABLE IF NOT EXISTS room_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'denied') DEFAULT 'pending',
    admin_remarks TEXT,
    decision_date TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (room_number) REFERENCES rooms(room_number)
);

INSERT INTO rooms (room_number, room_type, ac_status, floor_number, status) VALUES
('101', 'single', 'AC', 1, 'available'),
('102', 'double', 'Non-AC', 1, 'available'),
('201', 'single', 'AC', 2, 'available'),
('202', 'double', 'AC', 2, 'occupied'),
('301', 'single', 'Non-AC', 3, 'available'),
('302', 'double', 'AC', 3, 'available'),
('401', 'single', 'AC', 4, 'available'),
('402', 'double', 'Non-AC', 4, 'available'),
('501', 'single', 'AC', 5, 'available'),
('502', 'double', 'AC', 5, 'available');
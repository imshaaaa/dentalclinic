CREATE DATABASE IF NOT EXISTS primecaredental;
USE primecaredental;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(100) UNIQUE NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'dentist', 'secretary', 'patient') NOT NULL DEFAULT 'patient',
    contact_number VARCHAR(11) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(120) NULL,
    description TEXT NULL,
    duration INT NOT NULL,
    buffer INT DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    daily_limit INT NULL,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE staff_details (
    user_id INT PRIMARY KEY,
    assigned_service_id INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_code VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT NULL,
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('Pending','Accepted','Approved','Rejected','Cancelled','Completed') DEFAULT 'Pending',
    patient_name VARCHAR(150) NOT NULL,
    patient_email VARCHAR(150) NOT NULL,
    patient_contact VARCHAR(11) NOT NULL,
    service_name VARCHAR(150) NOT NULL,
    service_fee DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

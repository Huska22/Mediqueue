<?php
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS mediqueue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db('mediqueue');

$conn->query("CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('receptionist','clinician','admin') NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS patients (
  patient_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  date_of_birth DATE NULL,
  gender ENUM('male','female','other') NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS appointments (
  appointment_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  scheduled_at DATETIME NOT NULL,
  status ENUM('booked','rescheduled','cancelled','completed','no_show') DEFAULT 'booked',
  created_by INT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
  FOREIGN KEY (created_by) REFERENCES users(user_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS queue_tickets (
  ticket_id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  queue_no VARCHAR(10) NOT NULL,
  source ENUM('walkin','appointment') NOT NULL,
  appointment_id INT NULL,
  status ENUM('waiting','serving','completed','cancelled') DEFAULT 'waiting',
  arrival_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  service_point VARCHAR(50) DEFAULT 'OPD',
  FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
  FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS consultation_events (
  consultation_id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  clinician_id INT NOT NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  status ENUM('serving','completed') DEFAULT 'serving',
  FOREIGN KEY (ticket_id) REFERENCES queue_tickets(ticket_id),
  FOREIGN KEY (clinician_id) REFERENCES users(user_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS audit_events (
  event_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  ticket_id INT NULL,
  event_type VARCHAR(50) NOT NULL,
  event_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (ticket_id) REFERENCES queue_tickets(ticket_id)
)");

$check = $conn->query("SELECT COUNT(*) as c FROM users");
$row = $check->fetch_assoc();
if ($row['c'] == 0) {
    $r = password_hash('reception123', PASSWORD_DEFAULT);
    $c = password_hash('clinician123', PASSWORD_DEFAULT);
    $a = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, email, password_hash, role) VALUES 
        ('Reception One', 'reception@mediqueue.test', '$r', 'receptionist'),
        ('Dr. Clinician', 'clinician@mediqueue.test', '$c', 'clinician'),
        ('Admin User', 'admin@mediqueue.test', '$a', 'admin')
    ");
}

$check = $conn->query("SELECT COUNT(*) as c FROM patients");
$row = $check->fetch_assoc();
if ($row['c'] == 0) {
    $conn->query("INSERT INTO patients (name, phone, date_of_birth, gender) VALUES 
        ('John Mwangi', '0712345678', '1990-05-15', 'male'),
        ('Mary Kamau', '0723456789', '1985-11-22', 'female'),
        ('Peter Njoroge', '0734567890', '1978-03-10', 'male'),
        ('Grace Wanjiku', '0745678901', '1995-08-30', 'female')
    ");
}

echo "Database setup complete!<br>";
echo "Database: mediqueue<br>";
echo "Users created: 3<br>";
echo "Patients created: 4<br><br>";
echo "<strong>Login credentials:</strong><br>";
echo "Receptionist: reception@mediqueue.test / reception123<br>";
echo "Clinician: clinician@mediqueue.test / clinician123<br>";
echo "Admin: admin@mediqueue.test / admin123<br>";
?>
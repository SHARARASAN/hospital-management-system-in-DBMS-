-- Create the database
CREATE DATABASE IF NOT EXISTS hmisphp;
USE hmisphp;

-- Departments table
CREATE TABLE IF NOT EXISTS departments (
    dept_id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Pharmaceutical categories
CREATE TABLE IF NOT EXISTS pharmaceutical_categories (
    cat_id INT AUTO_INCREMENT PRIMARY KEY,
    cat_name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Doctors table
CREATE TABLE IF NOT EXISTS his_docs (
    doc_id INT AUTO_INCREMENT PRIMARY KEY,
    doc_fname VARCHAR(50) NOT NULL,
    doc_lname VARCHAR(50) NOT NULL,
    doc_email VARCHAR(100) NOT NULL UNIQUE,
    doc_phone VARCHAR(20),
    dept_id INT NOT NULL,
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id)
);

-- Patients table with embedded appointment history
CREATE TABLE IF NOT EXISTS his_patients (
    pat_id INT AUTO_INCREMENT PRIMARY KEY,
    pat_fname VARCHAR(50) NOT NULL,
    pat_lname VARCHAR(50) NOT NULL,
    pat_age INT NOT NULL,
    pat_addr VARCHAR(255) NOT NULL,
    pat_phone VARCHAR(20),
    pat_email VARCHAR(100),
    pat_history JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pharmaceuticals table
CREATE TABLE IF NOT EXISTS his_pharmaceuticals (
    phar_id INT AUTO_INCREMENT PRIMARY KEY,
    phar_name VARCHAR(100) NOT NULL,
    phar_bcode VARCHAR(50) NOT NULL UNIQUE,
    phar_qty INT NOT NULL,
    phar_price DECIMAL(10,2) NOT NULL,
    cat_id INT NOT NULL,
    supplier VARCHAR(100),
    expiry_date DATE,
    FOREIGN KEY (cat_id) REFERENCES pharmaceutical_categories(cat_id)
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    msg_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100),
    message TEXT NOT NULL,
    status ENUM('Unread', 'Read', 'Responded') DEFAULT 'Unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO departments (dept_name, description) VALUES
('Cardiology', 'Heart and cardiovascular system'),
('Neurology', 'Nervous system disorders'),
('Orthopedics', 'Musculoskeletal system');

INSERT INTO pharmaceutical_categories (cat_name, description) VALUES
('Pain Relief', 'Medications for pain management'),
('Antibiotics', 'Anti-bacterial medications'),
('Antihistamines', 'For allergies and allergic reactions');

INSERT INTO his_docs (doc_fname, doc_lname, doc_email, doc_phone, dept_id) VALUES
('Robert', 'Williams', 'r.williams@hospital.com', '555-0201', 1),
('Sarah', 'Miller', 's.miller@hospital.com', '555-0202', 2),
('David', 'Brown', 'd.brown@hospital.com', '555-0203', 3);

INSERT INTO his_patients (pat_fname, pat_lname, pat_age, pat_addr, pat_phone, pat_email, pat_history) VALUES
('John', 'Doe', 30, '123 Main St, Cityville', '555-0101', 'john.doe@email.com', 
 '{"visits": [
    {"date": "2023-10-15", "time": "10:00:00", "doctor": "Robert Williams", "reason": "Routine checkup", "status": "Completed"},
    {"date": "2023-11-15", "time": "14:30:00", "doctor": "Sarah Miller", "reason": "Follow-up", "status": "Scheduled"}
 ]}'),

('Jane', 'Smith', 25, '456 Elm St, Townsville', '555-0102', 'jane.smith@email.com', 
 '{"visits": [
    {"date": "2023-10-16", "time": "11:00:00", "doctor": "Sarah Miller", "reason": "Headache evaluation", "status": "Completed"}
 ]}'),

('Alice', 'Johnson', 40, '789 Oak St, Villagetown', '555-0103', 'alice.johnson@email.com', 
 '{"visits": [
    {"date": "2023-10-17", "time": "09:30:00", "doctor": "David Brown", "reason": "Knee pain consultation", "status": "Completed"},
    {"date": "2023-11-01", "time": "15:45:00", "doctor": "Robert Williams", "reason": "Physical therapy", "status": "Completed"}
 ]}');

INSERT INTO his_pharmaceuticals (phar_name, phar_bcode, phar_qty, phar_price, cat_id, supplier, expiry_date) VALUES
('Paracetamol', 'PCM-500', 100, 5.99, 1, 'MediCorp', '2025-12-31'),
('Amoxicillin', 'AMX-250', 50, 12.50, 2, 'PharmaPlus', '2024-06-30'),
('Ibuprofen', 'IBP-200', 75, 7.25, 1, 'HealthSupplies', '2025-09-30');

INSERT INTO contact_messages (name, email, phone, subject, message) VALUES
('Michael Brown', 'm.brown@email.com', '555-0301', 'Appointment Query', 'How do I reschedule my appointment?'),
('Emily Davis', 'e.davis@email.com', '555-0302', 'Feedback', 'The service was excellent, thank you!');
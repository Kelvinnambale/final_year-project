-- Create database
CREATE DATABASE IF NOT EXISTS calyd_db;
USE calyd_db;
-- Set SQL mode
SET SQL_MODE = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION";

-- Create users table
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'client', 'employee') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (username)
);

-- Create clients table
CREATE TABLE clients (
    client_id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    second_name VARCHAR(50) NOT NULL,
    surname VARCHAR(50) NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    national_id VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    county VARCHAR(100) NOT NULL,
    sub_county VARCHAR(100) NOT NULL,
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_id),
    UNIQUE KEY (national_id),
    UNIQUE KEY (email),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create employees table
CREATE TABLE employees (
    employee_id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    second_name VARCHAR(50) NOT NULL,
    surname VARCHAR(50) NOT NULL,
    id_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    kra_pin VARCHAR(20) NOT NULL,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (employee_id),
    UNIQUE KEY (id_number),
    UNIQUE KEY (email),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create tasks table
CREATE TABLE tasks (
    task_id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11),
    client_id INT(11),
    task_type ENUM('tax_filing', 'audit', 'accounting') NOT NULL,
    due_date DATE NOT NULL,
    priority ENUM('high', 'medium', 'low') NOT NULL,
    notes TEXT,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id),
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE SET NULL
);

-- Create user_sessions table
CREATE TABLE user_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create enquiries table
CREATE TABLE enquiries (
    enquiry_id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11),
    employee_id INT(11),
    type ENUM('general', 'support', 'billing', 'feature', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'responded') DEFAULT 'pending',
    submitted_by ENUM('client', 'employee') NOT NULL DEFAULT 'client',
    PRIMARY KEY (enquiry_id),
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE SET NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL
);

-- Create feedback table
CREATE TABLE feedback (
    feedback_id INT(11) NOT NULL AUTO_INCREMENT,
    enquiry_id INT(11) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_admin_reply TINYINT(1) NOT NULL,
    recipient_type ENUM('client', 'employee') NOT NULL,
    recipient_id INT(11) NOT NULL,
    employee_id INT(10) NOT NULL,
    PRIMARY KEY (feedback_id),
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(enquiry_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
);

-- Add indexes for improved query performance
CREATE INDEX idx_tasks_employee ON tasks(employee_id);
CREATE INDEX idx_tasks_client ON tasks(client_id);
CREATE INDEX idx_enquiries_client ON enquiries(client_id);
CREATE INDEX idx_enquiries_employee ON enquiries(employee_id);
CREATE INDEX idx_feedback_enquiry ON feedback(enquiry_id);
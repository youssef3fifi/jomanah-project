-- Pharmacy Management System Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS pharmacy_db;
USE pharmacy_db;

-- Users table for admin access
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pharmacist', 'cashier') DEFAULT 'cashier',
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medicines table
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    expiry_date DATE,
    supplier VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_stock (stock_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_phone (phone),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card', 'insurance') DEFAULT 'cash',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_sale_date (sale_date),
    INDEX idx_payment_method (payment_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sale items table
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    INDEX idx_sale (sale_id),
    INDEX idx_medicine (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, role, email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@pharmacy.com')
ON DUPLICATE KEY UPDATE username=username;

-- Insert sample medicines
INSERT INTO medicines (name, category, price, stock_quantity, expiry_date, supplier, description) VALUES
('Paracetamol 500mg', 'Pain Relief', 5.99, 500, '2026-12-31', 'PharmaCorp', 'Pain and fever relief'),
('Amoxicillin 250mg', 'Antibiotics', 12.50, 300, '2026-06-30', 'MediSupply', 'Antibiotic for bacterial infections'),
('Ibuprofen 400mg', 'Pain Relief', 7.99, 450, '2026-09-15', 'PharmaCorp', 'Anti-inflammatory pain reliever'),
('Omeprazole 20mg', 'Digestive', 15.99, 200, '2026-03-20', 'HealthPharma', 'Reduces stomach acid'),
('Cetirizine 10mg', 'Antihistamines', 8.50, 350, '2026-11-10', 'AllergyMeds', 'Allergy relief'),
('Metformin 500mg', 'Diabetes', 18.99, 250, '2026-08-25', 'DiabetesCare', 'Type 2 diabetes treatment'),
('Aspirin 75mg', 'Cardiovascular', 6.50, 400, '2026-10-05', 'CardioMed', 'Blood thinner, heart health'),
('Vitamin D3 1000IU', 'Vitamins', 10.99, 600, '2027-01-15', 'VitaLife', 'Vitamin D supplement'),
('Cough Syrup', 'Respiratory', 9.99, 150, '2025-12-20', 'RespiraCare', 'Relieves cough symptoms'),
('Eye Drops', 'Ophthalmology', 11.50, 180, '2026-07-30', 'EyeCare Plus', 'Dry eye relief')
ON DUPLICATE KEY UPDATE name=name;

-- Insert sample customers
INSERT INTO customers (name, phone, email, address) VALUES
('John Smith', '+1234567890', 'john.smith@email.com', '123 Main St, City, State'),
('Sarah Johnson', '+1234567891', 'sarah.j@email.com', '456 Oak Ave, City, State'),
('Michael Brown', '+1234567892', 'michael.b@email.com', '789 Pine Rd, City, State'),
('Emily Davis', '+1234567893', 'emily.d@email.com', '321 Elm St, City, State'),
('David Wilson', '+1234567894', 'david.w@email.com', '654 Maple Dr, City, State')
ON DUPLICATE KEY UPDATE name=name;

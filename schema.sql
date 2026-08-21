CREATE DATABASE IF NOT EXISTS bank_db;
USE bank_db;

-- 1. Users table for authentication and profile management
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Accounts table storing balances and account numbers
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Transactions table tracking all monetary operations
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'transfer') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    recipient_account VARCHAR(20) NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- 4. Default System Administrator Seed (Password: Admin@1234)
INSERT INTO users (name, email, password, role) 
VALUES (
    'System Administrator', 
    'admin@bank.com', 
    '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFjKGtW0y9D3.B28YQkKqJzK9YJp6H6S', 
    'admin'
)
ON DUPLICATE KEY UPDATE name = VALUES(name);
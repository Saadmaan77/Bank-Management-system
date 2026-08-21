# Bank-Management-system
Markdown
# Bank Management System (BMS)

A lightweight, secure core banking web application built with native **PHP**, **MySQL**, and modern **CSS**. Designed with strict transactional integrity (ACID compliance), role-based access control, and complete administrative management capabilities.

---

## Key Features

### Customer Portal
* **Self-Service Onboarding:** User registration with automatic bank account number generation and optional initial deposit processing.
* **Account Dashboard:** Real-time balance display and recent transaction ledger.
* **Fund Transfers:** Atomic peer-to-peer transfers using database row-level locking (`FOR UPDATE`) to eliminate race conditions.
* **Security & Auth:** Secure password hashing using PHP's native `password_hash()` (Bcrypt) and parameterized SQL queries via `PDO`.

### Admin Control Center
* **Liquidity & Growth Metrics:** Global analytics dashboard displaying total vault liquidity, total active users, registered accounts, and processed transaction volume.
* **Account Moderation:** Authorize, activate, or suspend customer accounts instantly.
* **Manual Balance Adjustments:** Direct debit/credit functionality allowing admins to deposit or withdraw funds on behalf of customers.
* **User Management:** Complete record overview and cascade account deletion.

---

## Tech Stack

* **Backend:** PHP 8.x (PDO MySQL)
* **Database:** MySQL / MariaDB (InnoDB engine for foreign key & transaction support)
* **Frontend:** Semantic HTML5, Vanilla CSS3 (Flexbox/Grid, Responsive)
* **Server Environment:** Apache (XAMPP / WampServer / LAMP)

---

## Database Architecture

The relational schema ensures full referential integrity with foreign key cascading:

```text
users (1) ───< accounts (1) ───< transactions (N)
•	users: Authentication records, profile details, and system roles (customer, admin).
•	accounts: Account numbers, balance ledgers, and operational statuses (active, suspended).
•	transactions: Complete audit trail of deposits, withdrawals, and inter-account transfers.
Installation & Setup
1.	Clone the repository:
Bash
git clone [https://github.com/yourusername/bank-management-system.git](https://github.com/yourusername/bank-management-system.git)
2.	Move to web root:
Move the project directory into your local server root (e.g., htdocs/bank_system for XAMPP).
3.	Database Setup:
o	Start Apache and MySQL in your XAMPP Control Panel.
o	Open phpMyAdmin (http://localhost/phpmyadmin).
o	Create a new database named bank_db.
o	Import the schema.sql file provided in the repository root.
4.	Configure Database Connection:
Check config/db.php and verify your local credentials:
PHP
$host = 'localhost';
$dbname = 'bank_db';
$username = 'root';
$password = ''; // Default XAMPP password is empty
5.	Run the Application:
Open your browser and navigate to:
Plaintext
http://localhost/bank_system/login.php
Default Credentials
Role	Email	Password
Administrator	admin@bank.com	Admin@1234
Customer	(Register via the UI)	(Set during registration)
Project Structure
Plaintext
bank_system/
├── config/
│   └── db.php               # PDO database connection configuration
├── css/
│   ├── admin.css            # Admin dashboard styling
│   └── style.css            # Base styles, variables, and customer UI
├── admin_dashboard.php      # Admin control center & transaction overrides
├── dashboard.php            # Customer balance & activity dashboard
├── login.php                # Authentication gateway & role-based redirection
├── logout.php               # Secure session termination
├── register.php             # New customer registration & account provisioner
├── schema.sql               # Database definition & admin seed
└── transfer.php             # ACID-compliant fund transfer engine

This project is open-source.

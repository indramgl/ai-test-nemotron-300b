-- Database Schema for Personal Finance SaaS
-- MySQL 8.0+ Compatible

-- Drop existing tables if they exist (for clean import)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS goals;
DROP TABLE IF EXISTS goal_transactions;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS account_types;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS user_subscriptions;

SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    base_currency CHAR(3) DEFAULT 'IDR',
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscription plans
CREATE TABLE subscription_plans (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2),
    max_accounts INT DEFAULT 3,
    max_categories INT DEFAULT 10,
    allow_recurring BOOLEAN DEFAULT FALSE,
    allow_export BOOLEAN DEFAULT FALSE,
    advanced_analytics BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User subscriptions
CREATE TABLE user_subscriptions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    plan_id CHAR(36) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    payment_method VARCHAR(50),
    next_billing_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id),
    INDEX idx_user_id (user_id),
    INDEX idx_plan_id (plan_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Account types
CREATE TABLE account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50),
    color VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accounts
CREATE TABLE accounts (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    account_type_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    currency CHAR(3) DEFAULT 'IDR',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_type_id) REFERENCES account_types(id),
    INDEX idx_user_id (user_id),
    INDEX idx_account_type (account_type_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories (hierarchical: parent-child)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('INCOME', 'EXPENSE', 'TRANSFER') NOT NULL,
    parent_id INT NULL,
    icon VARCHAR(50),
    color VARCHAR(7),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_parent_id (parent_id),
    INDEX idx_active (is_active),
    UNIQUE KEY uk_name_type_parent (name, type, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions
CREATE TABLE transactions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    account_id CHAR(36) NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('INCOME', 'EXPENSE', 'TRANSFER') NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern VARCHAR(50), -- e.g., 'monthly', 'weekly', 'yearly'
    recurrence_end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_user_id (user_id),
    INDEX idx_account_id (account_id),
    INDEX idx_category_id (category_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_type (type),
    INDEX idx_is_recurring (is_recurring)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Budgets
CREATE TABLE budgets (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    period ENUM('monthly', 'weekly', 'yearly') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE,
    alert_threshold_80 BOOLEAN DEFAULT TRUE,
    alert_threshold_100 BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_period (period),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Financial Goals
CREATE TABLE goals (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    name VARCHAR(200) NOT NULL,
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0.00,
    target_date DATE,
    icon VARCHAR(50),
    color VARCHAR(7),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_active (is_active),
    INDEX idx_target_date (target_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goal transactions (deposits/withdrawals to goals)
CREATE TABLE goal_transactions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    goal_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('DEPOSIT', 'WITHDRAWAL') NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_goal_id (goal_id),
    INDEX idx_user_id (user_id),
    INDEX idx_transaction_date (transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default subscription plans
INSERT INTO subscription_plans (name, description, price_monthly, price_yearly, max_accounts, max_categories, allow_recurring, allow_export, advanced_analytics) VALUES
('Free', 'Basic personal finance features', 0.00, 0.00, 3, 10, FALSE, FALSE, FALSE),
('Pro', 'Advanced features with unlimited accounts and analytics', 4.99, 49.99, NULL, NULL, TRUE, TRUE, TRUE);

-- Insert default account types
INSERT INTO account_types (name, icon, color) VALUES
('Cash', 'wallet', '#28a745'),
('Bank', 'bank', '#007bff'),
('E-Wallet', 'credit-card', '#ffc107'),
('Investasi', 'chart-line', '#dc3545');

-- Insert default categories
-- Parent categories
INSERT INTO categories (name, type) VALUES
('Pemasukan', 'INCOME'),
('Pengeluaran', 'EXPENSE'),
('Transfer', 'TRANSFER');

-- Sub-categories for Pemasukan
INSERT INTO categories (name, type, parent_id) SELECT
    'Gaji', 'INCOME', c.id FROM categories c WHERE c.name = 'Pemasukan' AND c.type = 'INCOME' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Bonus', 'INCOME', c.id FROM categories c WHERE c.name = 'Pemasukan' AND c.type = 'INCOME' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Investasi', 'INCOME', c.id FROM categories c WHERE c.name = 'Pemasukan' AND c.type = 'INCOME' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Lain-lain', 'INCOME', c.id FROM categories c WHERE c.name = 'Pemasukan' AND c.type = 'INCOME' LIMIT 1;

-- Sub-categories for Pengeluaran
INSERT INTO categories (name, type, parent_id) SELECT
    'Makanan & Minuman', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Transportasi', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Belanja', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Hiburan', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Kesehatan', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Tagihan', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Pendidikan', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
INSERT INTO categories (name, type, parent_id) SELECT
    'Lain-lain', 'EXPENSE', c.id FROM categories c WHERE c.name = 'Pengeluaran' AND c.type = 'EXPENSE' LIMIT 1;
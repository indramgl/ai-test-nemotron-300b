-- Database Schema for Personal Finance SaaS (SQLite Compatible)
-- For development/testing without MySQL

-- Drop existing tables if they exist (for clean import)
DROP TABLE IF EXISTS goal_transactions;
DROP TABLE IF EXISTS goals;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS account_types;
DROP TABLE IF EXISTS user_subscriptions;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id TEXT PRIMARY KEY,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    base_currency TEXT DEFAULT 'IDR',
    first_name TEXT,
    last_name TEXT,
    phone TEXT,
    is_active INTEGER DEFAULT 1,
    email_verified INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);

-- Subscription plans
CREATE TABLE subscription_plans (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT,
    price_monthly REAL NOT NULL,
    price_yearly REAL,
    max_accounts INTEGER DEFAULT 3,
    max_categories INTEGER DEFAULT 10,
    allow_recurring INTEGER DEFAULT 0,
    allow_export INTEGER DEFAULT 0,
    advanced_analytics INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User subscriptions
CREATE TABLE user_subscriptions (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    plan_id TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    is_active INTEGER DEFAULT 1,
    payment_method TEXT,
    next_billing_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

CREATE INDEX idx_user_subscriptions_user_id ON user_subscriptions(user_id);
CREATE INDEX idx_user_subscriptions_plan_id ON user_subscriptions(plan_id);
CREATE INDEX idx_user_subscriptions_active ON user_subscriptions(is_active);

-- Account types
CREATE TABLE account_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    icon TEXT,
    color TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Accounts
CREATE TABLE accounts (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    account_type_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    balance REAL DEFAULT 0.00,
    currency TEXT DEFAULT 'IDR',
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_type_id) REFERENCES account_types(id)
);

CREATE INDEX idx_accounts_user_id ON accounts(user_id);
CREATE INDEX idx_accounts_account_type ON accounts(account_type_id);
CREATE INDEX idx_accounts_active ON accounts(is_active);

-- Categories (hierarchical: parent-child)
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('INCOME', 'EXPENSE', 'TRANSFER')),
    parent_id INTEGER,
    icon TEXT,
    color TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_categories_type ON categories(type);
CREATE INDEX idx_categories_parent_id ON categories(parent_id);
CREATE INDEX idx_categories_active ON categories(is_active);
CREATE UNIQUE INDEX uk_categories_name_type_parent ON categories(name, type, parent_id);

-- Transactions
CREATE TABLE transactions (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    account_id TEXT NOT NULL,
    category_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('INCOME', 'EXPENSE', 'TRANSFER')),
    description TEXT,
    transaction_date DATE NOT NULL,
    is_recurring INTEGER DEFAULT 0,
    recurrence_pattern TEXT,
    recurrence_end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_account_id ON transactions(account_id);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);
CREATE INDEX idx_transactions_transaction_date ON transactions(transaction_date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_is_recurring ON transactions(is_recurring);

-- Budgets
CREATE TABLE budgets (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    category_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    period TEXT DEFAULT 'monthly' CHECK (period IN ('monthly', 'weekly', 'yearly')),
    start_date DATE NOT NULL,
    end_date DATE,
    alert_threshold_80 INTEGER DEFAULT 1,
    alert_threshold_100 INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE INDEX idx_budgets_user_id ON budgets(user_id);
CREATE INDEX idx_budgets_category_id ON budgets(category_id);
CREATE INDEX idx_budgets_period ON budgets(period);
CREATE INDEX idx_budgets_active ON budgets(is_active);

-- Financial Goals
CREATE TABLE goals (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    name TEXT NOT NULL,
    target_amount REAL NOT NULL,
    current_amount REAL DEFAULT 0.00,
    target_date DATE,
    icon TEXT,
    color TEXT,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_goals_user_id ON goals(user_id);
CREATE INDEX idx_goals_active ON goals(is_active);
CREATE INDEX idx_goals_target_date ON goals(target_date);

-- Goal transactions (deposits/withdrawals to goals)
CREATE TABLE goal_transactions (
    id TEXT PRIMARY KEY,
    goal_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    amount REAL NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('DEPOSIT', 'WITHDRAWAL')),
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_goal_transactions_goal_id ON goal_transactions(goal_id);
CREATE INDEX idx_goal_transactions_user_id ON goal_transactions(user_id);
CREATE INDEX idx_goal_transactions_transaction_date ON goal_transactions(transaction_date);

-- Trigger to update updated_at timestamp for users
CREATE TRIGGER update_users_timestamp 
AFTER UPDATE ON users
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to update updated_at timestamp for subscription_plans
CREATE TRIGGER update_subscription_plans_timestamp 
AFTER UPDATE ON subscription_plans
BEGIN
    UPDATE subscription_plans SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to update updated_at timestamp for user_subscriptions
CREATE TRIGGER update_user_subscriptions_timestamp 
AFTER UPDATE ON user_subscriptions
BEGIN
    UPDATE user_subscriptions SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to update updated_at timestamp for accounts
CREATE TRIGGER update_accounts_timestamp 
AFTER UPDATE ON accounts
BEGIN
    UPDATE accounts SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to update updated_at timestamp for categories
CREATE TRIGGER update_categories_timestamp 
AFTER UPDATE ON categories
BEGIN
    UPDATE categories SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to update updated_at timestamp for transactions
CREATE TRIGGER update_transactions_timestamp 
AFTER UPDATE ON transactions
BEGIN
    UPDATE transactions SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to update updated_at timestamp for budgets
CREATE TRIGGER update_budgets_timestamp 
AFTER UPDATE ON budgets
BEGIN
    UPDATE budgets SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to update updated_at timestamp for goals
CREATE TRIGGER update_goals_timestamp 
AFTER UPDATE ON goals
BEGIN
    UPDATE goals SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Insert default subscription plans
INSERT INTO subscription_plans (id, name, description, price_monthly, price_yearly, max_accounts, max_categories, allow_recurring, allow_export, advanced_analytics) VALUES
(lower(hex(randomblob(16))), 'Free', 'Basic personal finance features', 0.00, 0.00, 3, 10, 0, 0, 0),
(lower(hex(randomblob(16))), 'Pro', 'Advanced features with unlimited accounts and analytics', 4.99, 49.99, NULL, NULL, 1, 1, 1);

-- Insert default account types
INSERT INTO account_types (name, icon, color) VALUES
('Cash', 'wallet', '#28a745'),
('Bank', 'bank', '#007bff'),
('E-Wallet', 'credit-card', '#ffc107'),
('Investasi', 'chart-line', '#dc3545');

-- Insert default categories - Parent categories
INSERT INTO categories (name, type) VALUES
('Pemasukan', 'INCOME'),
('Pengeluaran', 'EXPENSE'),
('Transfer', 'TRANSFER');

-- Sub-categories for Pemasukan
-- We'll use a simpler approach for SQLite - just insert with known parent IDs
-- Parent IDs will be 1, 2, 3 for Pemasukan, Pengeluaran, Transfer
INSERT INTO categories (name, type, parent_id) VALUES
('Gaji', 'INCOME', 1),
('Bonus', 'INCOME', 1),
('Investasi', 'INCOME', 1),
('Lain-lain', 'INCOME', 1);

-- Sub-categories for Pengeluaran
INSERT INTO categories (name, type, parent_id) VALUES
('Makanan & Minuman', 'EXPENSE', 2),
('Transportasi', 'EXPENSE', 2),
('Belanja', 'EXPENSE', 2),
('Hiburan', 'EXPENSE', 2),
('Kesehatan', 'EXPENSE', 2),
('Tagihan', 'EXPENSE', 2),
('Pendidikan', 'EXPENSE', 2),
('Lain-lain', 'EXPENSE', 2);
<?php
/**
 * Database Import Script for SQLite (Development/Testing)
 * Creates the database schema and seed data for the Personal Finance SaaS
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Check if we should use SQLite (for development without MySQL)
$useSqlite = !extension_loaded('pdo_mysql') || 
             ($_ENV['DB_DRIVER'] ?? 'mysql') === 'sqlite';

if ($useSqlite) {
    echo "Using SQLite for development\n";
    importSqlite();
} else {
    echo "Using MySQL\n";
    importMysql();
}

/**
 * Import using SQLite
 */
function importSqlite() {
    $dbPath = __DIR__ . '/../storage/app.db';
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    try {
        $pdo = new PDO("sqlite:$dbPath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        
        echo "Connected to SQLite database\n";
        
        // Read and execute schema - execute line by line
        $schema = file_get_contents(__DIR__ . '/schema.sqlite.sql');
        $lines = explode("\n", $schema);
        $currentStatement = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '--')) {
                continue;
            }
            
            $currentStatement .= " $line";
            
            // If statement ends with semicolon, execute it
            if (str_ends_with($line, ';')) {
                $stmt = trim($currentStatement);
                $currentStatement = '';
                
                if (!empty($stmt)) {
                    try {
                        $pdo->exec($stmt);
                        echo "Executed: " . substr($stmt, 0, 60) . "...\n";
                    } catch (PDOException $e) {
                        echo "Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        
        // Seed initial data
        seedData($pdo);
        
        echo "SQLite database import completed successfully!\n";
        echo "Database file: $dbPath\n";
        
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Import using MySQL (original function)
 */
function importMysql() {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? 3306;
    $dbname = $_ENV['DB_NAME'] ?? 'personal_finance';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        echo "Connected to MySQL server\n";

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database '$dbname' created or already exists\n";

        $pdo->exec("USE `$dbname`");

        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !str_starts_with($statement, '--')) {
                try {
                    $pdo->exec($statement);
                    echo "Executed: " . substr($statement, 0, 50) . "...\n";
                } catch (PDOException $e) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }

        seedData($pdo);
        echo "Database import completed successfully!\n";

    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Seed initial data
 */
function seedData(PDO $pdo) {
    echo "Seeding initial data...\n";
    
    // Check tables exist
    $tables = ['subscription_plans', 'account_types', 'categories'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT 1 FROM $table LIMIT 1");
        $stmt->execute();
        if (!$stmt->fetch()) {
            echo "ERROR: Table $table does not exist!\n";
            return;
        }
    }
    
    // Insert default subscription plans
    $plans = [
        ['name' => 'Free', 'description' => 'Basic personal finance features', 'price_monthly' => 0.00, 'price_yearly' => 0.00, 'max_accounts' => 3, 'max_categories' => 10, 'allow_recurring' => 0, 'allow_export' => 0, 'advanced_analytics' => 0],
        ['name' => 'Pro', 'description' => 'Advanced features with unlimited accounts and analytics', 'price_monthly' => 4.99, 'price_yearly' => 49.99, 'max_accounts' => null, 'max_categories' => null, 'allow_recurring' => 1, 'allow_export' => 1, 'advanced_analytics' => 1],
    ];
    
    foreach ($plans as $plan) {
        $placeholders = [];
        $values = [];
        foreach ($plan as $key => $value) {
            $placeholders[] = "$key = :$key";
            $values[":$key"] = $value;
        }
        
        $checkStmt = $pdo->prepare("SELECT id FROM subscription_plans WHERE name = :name LIMIT 1");
        $checkStmt->execute(['name' => $plan['name']]);
        if (!$checkStmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO subscription_plans SET " . implode(', ', $placeholders));
            $stmt->execute($values);
            echo "Inserted plan: {$plan['name']}\n";
        }
    }
    
    // Insert default account types
    $accountTypes = [
        ['name' => 'Cash', 'icon' => 'wallet', 'color' => '#28a745'],
        ['name' => 'Bank', 'icon' => 'bank', 'color' => '#007bff'],
        ['name' => 'E-Wallet', 'icon' => 'credit-card', 'color' => '#ffc107'],
        ['name' => 'Investasi', 'icon' => 'chart-line', 'color' => '#dc3545'],
    ];
    
    foreach ($accountTypes as $type) {
        $checkStmt = $pdo->prepare("SELECT id FROM account_types WHERE name = :name LIMIT 1");
        $checkStmt->execute(['name' => $type['name']]);
        if (!$checkStmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO account_types (name, icon, color) VALUES (:name, :icon, :color)");
            $stmt->execute($type);
            echo "Inserted account type: {$type['name']}\n";
        }
    }
    
    // Insert default categories - Parent categories
    $parentCategories = [
        ['name' => 'Pemasukan', 'type' => 'INCOME'],
        ['name' => 'Pengeluaran', 'type' => 'EXPENSE'],
        ['name' => 'Transfer', 'type' => 'TRANSFER'],
    ];
    
    foreach ($parentCategories as $cat) {
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name AND type = :type AND parent_id IS NULL LIMIT 1");
        $checkStmt->execute($cat);
        if (!$checkStmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (:name, :type)");
            $stmt->execute($cat);
            echo "Inserted parent category: {$cat['name']}\n";
        }
    }
    
    // Sub-categories for Pemasukan
    $incomeSubCategories = ['Gaji', 'Bonus', 'Investasi', 'Lain-lain'];
    $parentStmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Pemasukan' AND type = 'INCOME' LIMIT 1");
    $parentStmt->execute();
    $parent = $parentStmt->fetch();
    
    if ($parent) {
        foreach ($incomeSubCategories as $name) {
            $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name AND type = 'INCOME' AND parent_id = :parent_id LIMIT 1");
            $checkStmt->execute(['name' => $name, 'parent_id' => $parent['id']]);
            if (!$checkStmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, type, parent_id) VALUES (:name, 'INCOME', :parent_id)");
                $stmt->execute(['name' => $name, 'parent_id' => $parent['id']]);
                echo "Inserted sub-category: $name\n";
            }
        }
    }
    
    // Sub-categories for Pengeluaran
    $expenseSubCategories = [
        'Makanan & Minuman', 'Transportasi', 'Belanja', 'Hiburan',
        'Kesehatan', 'Tagihan', 'Pendidikan', 'Lain-lain'
    ];
    $parentStmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Pengeluaran' AND type = 'EXPENSE' LIMIT 1");
    $parentStmt->execute();
    $parent = $parentStmt->fetch();
    
    if ($parent) {
        foreach ($expenseSubCategories as $name) {
            $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE name = :name AND type = 'EXPENSE' AND parent_id = :parent_id LIMIT 1");
            $checkStmt->execute(['name' => $name, 'parent_id' => $parent['id']]);
            if (!$checkStmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, type, parent_id) VALUES (:name, 'EXPENSE', :parent_id)");
                $stmt->execute(['name' => $name, 'parent_id' => $parent['id']]);
                echo "Inserted sub-category: $name\n";
            }
        }
    }
    
    echo "Initial data seeded successfully\n";
}
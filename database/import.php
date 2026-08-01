<?php
/**
 * Database Import Script
 * Creates the database schema and seed data for the Personal Finance SaaS
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/schema.sql';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Database connection parameters
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? 3306;
$dbname = $_ENV['DB_NAME'] ?? 'personal_finance';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    // Connect to MySQL server (without selecting database first)
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Connected to MySQL server\n";

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbname' created or already exists\n";

    // Select the database
    $pdo->exec("USE `$dbname`");

    // Create tables
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Some statements might fail if tables already exist, we'll continue
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }

    // Seed initial data
    seedData($pdo);

    echo "Database import completed successfully!\n";

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Seed initial data
 */
function seedData(PDO $pdo) {
    echo "Seeding initial data...\n";
    
    // Insert default categories
    $categories = [
        // Parent categories
        ['name' => 'Pemasukan', 'type' => 'INCOME', 'parent_id' => null],
        ['name' => 'Pengeluaran', 'type' => 'EXPENSE', 'parent_id' => null],
        ['name' => 'Transfer', 'type' => 'TRANSFER', 'parent_id' => null],
        
        // Sub-categories for Pemasukan
        ['name' => 'Gaji', 'type' => 'INCOME', 'parent_name' => 'Pemasukan'],
        ['name' => 'Bonus', 'type' => 'INCOME', 'parent_name' => 'Pemasukan'],
        ['name' => 'Investasi', 'type' => 'INCOME', 'parent_name' => 'Pemasukan'],
        ['name' => 'Lain-lain', 'type' => 'INCOME', 'parent_name' => 'Pemasukan'],
        
        // Sub-categories for Pengeluaran
        ['name' => 'Makanan & Minuman', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
        ['name' => 'Transportasi', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
        ['name' => 'Belanja', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
        ['name' => 'Hiburan', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
        ['name' => 'Kesehatan', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
        ['name' => 'Tagihan', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
        ['name' => 'Pendidikan', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
        ['name' => 'Lain-lain', 'type' => 'EXPENSE', 'parent_name' => 'Pengeluaran'],
    ];
    
    // First insert parent categories
    foreach ($categories as $category) {
        if ($category['parent_id'] === null) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (:name, :type)");
            $stmt->execute([
                'name' => $category['name'],
                'type' => $category['type']
            ]);
        }
    }
    
    // Then insert sub-categories with parent_id
    foreach ($categories as $category) {
        if ($category['parent_name'] !== null) {
            // Get parent category ID
            $parentStmt = $pdo->prepare("SELECT id FROM categories WHERE name = :parent_name AND type = :type LIMIT 1");
            $parentStmt->execute([
                'parent_name' => $category['parent_name'],
                'type' => $category['type']
            ]);
            $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($parent) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, type, parent_id) VALUES (:name, :type, :parent_id)");
                $stmt->execute([
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'parent_id' => $parent['id']
                ]);
            }
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
        // Check if account type already exists
        $checkStmt = $pdo->prepare("SELECT id FROM account_types WHERE name = :name LIMIT 1");
        $checkStmt->execute(['name' => $type['name']]);
        if (!$checkStmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO account_types (name, icon, color) VALUES (:name, :icon, :color)");
            $stmt->execute([
                'name' => $type['name'],
                'icon' => $type['icon'],
                'color' => $type['color']
            ]);
        }
    }
    
    echo "Initial data seeded successfully\n";
}
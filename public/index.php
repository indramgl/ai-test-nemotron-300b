<?php
/**
 * Front controller for the Personal Finance SaaS application
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Start session
session_start();

// Initialize configuration
$config = \App\Core\Config::getInstance();

// Initialize database
$db = \App\Core\Database::getInstance();

// Simple router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove trailing slashes (but keep root as '/')
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

// Define routes
$routes = [
    // Auth routes
    ['GET', '/api/auth/login', 'App\Http\Controllers\AuthController', 'showLoginForm'],
    ['POST', '/api/auth/login', 'App\Http\Controllers\AuthController', 'login'],
    ['POST', '/api/auth/logout', 'App\Http\Controllers\AuthController', 'logout'],
    ['GET', '/api/auth/register', 'App\Http\Controllers\AuthController', 'showRegisterForm'],
    ['POST', '/api/auth/register', 'App\Http\Controllers\AuthController', 'register'],

    // Account routes
    ['GET', '/api/accounts', 'App\Http\Controllers\AccountController', 'index'],
    ['POST', '/api/accounts', 'App\Http\Controllers\AccountController', 'store'],
    ['GET', '/api/accounts/{id}', 'App\Http\Controllers\AccountController', 'show'],
    ['PUT', '/api/accounts/{id}', 'App\Http\Controllers\AccountController', 'update'],
    ['DELETE', '/api/accounts/{id}', 'App\Http\Controllers\AccountController', 'destroy'],

    // Transaction routes
    ['GET', '/api/transactions', 'App\Http\Controllers\TransactionController', 'index'],
    ['POST', '/api/transactions', 'App\Http\Controllers\TransactionController', 'store'],
    ['GET', '/api/transactions/{id}', 'App\Http\Controllers\TransactionController', 'show'],
    ['PUT', '/api/transactions/{id}', 'App\Http\Controllers\TransactionController', 'update'],
    ['DELETE', '/api/transactions/{id}', 'App\Http\Controllers\TransactionController', 'destroy'],
    ['GET', '/api/transactions/summary', 'App\Http\Controllers\TransactionController', 'summary'],
    ['GET', '/api/transactions/monthly', 'App\Http\Controllers\TransactionController', 'monthlySummary'],

    // Budget routes
    ['GET', '/api/budgets', 'App\Http\Controllers\BudgetController', 'index'],
    ['POST', '/api/budgets', 'App\Http\Controllers\BudgetController', 'store'],
    ['GET', '/api/budgets/{id}', 'App\Http\Controllers\BudgetController', 'show'],
    ['PUT', '/api/budgets/{id}', 'App\Http\Controllers\BudgetController', 'update'],
    ['DELETE', '/api/budgets/{id}', 'App\Http\Controllers\BudgetController', 'destroy'],

    // Goal routes
    ['GET', '/api/goals', 'App\Http\Controllers\GoalController', 'index'],
    ['POST', '/api/goals', 'App\Http\Controllers\GoalController', 'store'],
    ['GET', '/api/goals/{id}', 'App\Http\Controllers\GoalController', 'show'],
    ['PUT', '/api/goals/{id}', 'App\Http\Controllers\GoalController', 'update'],
    ['DELETE', '/api/goals/{id}', 'App\Http\Controllers\GoalController', 'destroy'],
    ['POST', '/api/goals/{id}/deposit', 'App\Http\Controllers\GoalController', 'deposit'],
    ['POST', '/api/goals/{id}/withdraw', 'App\Http\Controllers\GoalController', 'withdraw'],

    // Report routes
    ['GET', '/api/reports/summary', 'App\Http\Controllers\ReportController', 'summary'],
    ['GET', '/api/reports/cashflow', 'App\Http\Controllers\ReportController', 'cashflow'],
    ['GET', '/api/reports/networth', 'App\Http\Controllers\ReportController', 'networth'],

    // Profile routes
    ['GET', '/api/profile', 'App\\Http\\Controllers\\ProfileController', 'index'],
    ['PUT', '/api/profile', 'App\\Http\\Controllers\\ProfileController', 'update'],
    ['POST', '/api/profile/password', 'App\\Http\\Controllers\\ProfileController', 'changePassword'],

    // Subscription routes
    ['GET', '/api/subscription', 'App\\Http\\Controllers\\SubscriptionController', 'index'],
    ['POST', '/api/subscription/upgrade', 'App\\Http\\Controllers\\SubscriptionController', 'upgrade'],

    // Dashboard routes
    ['GET', '/api/dashboard', 'App\Http\Controllers\DashboardController', 'index'],

    // Frontend routes (serving HTML)
        ['GET', '/', 'App\\Http\\Controllers\\HomeController', 'index'],
        ['GET', '/login', 'App\\Http\\Controllers\\AuthController', 'showLoginForm'],
        ['GET', '/register', 'App\\Http\\Controllers\\AuthController', 'showRegisterForm'],
        ['GET', '/dashboard', 'App\\Http\\Controllers\\DashboardController', 'indexPage'],
        ['GET', '/accounts', 'App\\Http\\Controllers\\AccountController', 'indexPage'],
        ['GET', '/transactions', 'App\\Http\\Controllers\\TransactionController', 'indexPage'],
        ['GET', '/budgets', 'App\\Http\\Controllers\\BudgetController', 'indexPage'],
        ['GET', '/goals', 'App\\Http\\Controllers\\GoalController', 'indexPage'],
        ['GET', '/reports', 'App\\Http\\Controllers\\ReportController', 'indexPage'],
        ['GET', '/profile', 'App\\Http\\Controllers\\ProfileController', 'indexPage'],
        ['GET', '/subscription', 'App\\Http\\Controllers\\SubscriptionController', 'indexPage'],

// Try to match the route
$found = false;
foreach ($routes as $route) {
    list($routeMethod, $routePattern, $controller, $action) = $route;

    // Convert placeholders to regex
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^\/]+)', $routePattern);
    $pattern = '#^' . $pattern . '$#';

    if ($method === $routeMethod && preg_match($pattern, $uri, $matches)) {
        // Remove the first element (full match) and shift offsets
        array_shift($matches);

        // Call the controller method
        $controllerInstance = new $controller();
        call_user_func_array([$controllerInstance, $action], $matches);
        $found = true;
        break;
    }
}

if (!$found) {
    // Serve static files if they exist
    $filePath = __DIR__ . $uri;
    if ($uri !== '/' && file_exists($filePath) && is_file($filePath)) {
        // Serve the file
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'json' => 'application/json',
        ];

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $contentType);
        readfile($filePath);
        exit;
    }

    // If not found, show 404
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
?>
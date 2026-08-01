<?php
namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Config;

class AuthController extends Controller
{
    private $auth;
    private $config;

    public function __construct()
    {
        $this->auth = new \App\Core\Auth();
        $this->config = \App\Core\Config::getInstance();
    }

    public function showLoginForm()
    {
        // Return login view (HTML)
        $this->render('auth/login');
    }

    public function showRegisterForm()
    {
        // Return register view (HTML)
        $this->render('auth/register');
    }

    public function login()
    {
        // Handle POST request for login
        $_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->jsonResponse(['error' => 'Email and password are required'], 400);
            return;
        }

        try {
            $result = $this->auth->login($email, $password);
            
            // Set token in cookie or return in response
            setcookie(
                'token', 
                $result['token'], 
                [
                    'expires' => time() + ($this->config->get('jwt.ttl', 60) * 60),
                    'path' => '/',
                    'secure' => $_SERVER['HTTPS'] ?? false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
            
            // Don't return the token in the JSON response for security
            unset($result['token']);
            
            $this->jsonResponse([
                'message' => 'Login successful',
                'user' => $result['user']
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 401);
        }
    }

    public function logout()
    {
        // Clear the cookie
        setcookie('token', '', time() - 3600, '/');
        
        $this->jsonResponse(['message' => 'Logged out successfully']);
    }

    public function register()
    {
        // Handle POST request for registration
        $_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $data = [
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'base_currency' => $_POST['base_currency'] ?? 'IDR'
        ];

        // Basic validation
        if (empty($data['email']) || empty($data['password'])) {
            $this->jsonResponse(['error' => 'Email and password are required'], 400);
            return;
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'Invalid email format'], 400);
            return;
        }

        if (strlen($data['password']) < 6) {
            $this->jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
            return;
        }

        try {
            $userId = $this->auth->register($data);
            
            $this->jsonResponse([
                'message' => 'Registration successful',
                'user_id' => $userId
            ], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // Helper methods
    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function render($view, $data = [])
    {
        // Extract data to variables
        extract($data);
        
        // Set content type
        header('Content-Type: text/html; charset=utf-8');
        
        // Include the view file
        $viewFile = __DIR__ . "/../../views/{$view}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            // Fallback to a simple message
            echo "<h1>View not found: {$view}</h1>";
        }
        exit;
    }
}

// Base Controller class
class Controller
{
    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function render($view, $data = [])
    {
        // Extract data to variables
        extract($data);
        
        // Set content type
        header('Content-Type: text/html; charset=utf-8');
        
        // Include the view file
        $viewFile = __DIR__ . "/../../views/{$view}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            // Fallback to a simple message
            echo "<h1>View not found: {$view}</h1>";
        }
        exit;
    }
}
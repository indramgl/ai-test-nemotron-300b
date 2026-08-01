<?php
namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    public static function requireAuth()
    {
        $token = $_COOKIE['token'] ?? '';
        
        if (empty($token)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (empty($token)) {
            return [
                'authenticated' => false,
                'error' => 'Unauthorized',
                'status_code' => 401
            ];
        }

        $auth = new Auth();
        try {
            $decoded = $auth->validateToken($token);
            return [
                'authenticated' => true,
                'user_id' => $decoded['uid'],
                'email' => $decoded['email']
            ];
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'error' => 'Invalid token',
                'status_code' => 401
            ];
        }
    }

    public static function handle(callable $next)
    {
        $result = self::requireAuth();
        
        if (!$result['authenticated']) {
            header('Content-Type: application/json');
            http_response_code($result['status_code']);
            echo json_encode(['error' => $result['error']]);
            exit;
        }

        // Store user_id in session for controllers to use
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['user_email'] = $result['email'];

        return $next($result['user_id']);
    }

    public static function optionalAuth()
    {
        $token = $_COOKIE['token'] ?? '';
        
        if (empty($token)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (empty($token)) {
            return [
                'authenticated' => false,
                'user_id' => null
            ];
        }

        $auth = new Auth();
        try {
            $decoded = $auth->validateToken($token);
            return [
                'authenticated' => true,
                'user_id' => $decoded['uid'],
                'email' => $decoded['email']
            ];
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'user_id' => null
            ];
        }
    }
}
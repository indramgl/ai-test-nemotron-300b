<?php
namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        // Check if user is already logged in
        $token = $_COOKIE['token'] ?? '';
        $isLoggedIn = false;
        
        if (!empty($token)) {
            $auth = new \App\Core\Auth();
            try {
                $decoded = $auth->validateToken($token);
                $isLoggedIn = true;
            } catch (\Exception $e) {
                // Token invalid, treat as not logged in
            }
        }
        
        if ($isLoggedIn) {
            // Redirect to dashboard
            header('Location: /dashboard');
            exit;
        }
        
        $this->render('home/index');
    }
}
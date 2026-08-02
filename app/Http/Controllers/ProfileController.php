<?php
namespace App\Http\Controllers;

use App\Models\User;

class ProfileController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function index()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $user = $this->userModel->findById($userId);
        if (!$user) {
            $this->jsonResponse(['error' => 'User not found'], 404);
            return;
        }
        
        // Get subscription info
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT sp.name as plan_name, us.start_date, us.end_date, us.is_active
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = :user_id AND us.is_active = 1
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $subscription = $stmt->fetch();
        
        $this->jsonResponse([
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'phone' => $user['phone'],
                'base_currency' => $user['base_currency'],
                'created_at' => $user['created_at']
            ],
            'subscription' => $subscription ?: [
                'plan_name' => 'Free',
                'is_active' => true
            ]
        ]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('profile/index');
    }

    public function update()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $allowedFields = ['first_name', 'last_name', 'phone', 'base_currency'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            $this->jsonResponse(['error' => 'No data to update'], 400);
            return;
        }
        
        $result = $this->userModel->update($userId, $updateData);
        if (!$result) {
            $this->jsonResponse(['error' => 'Failed to update profile'], 400);
            return;
        }
        
        $user = $this->userModel->findById($userId);
        $this->jsonResponse(['user' => $user]);
    }

    public function changePassword()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_new_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->jsonResponse(['error' => 'All password fields are required'], 400);
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->jsonResponse(['error' => 'New passwords do not match'], 400);
            return;
        }
        
        if (strlen($newPassword) < 6) {
            $this->jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
            return;
        }
        
        // Get current user to verify current password
        $user = $this->userModel->findById($userId);
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $this->jsonResponse(['error' => 'Current password is incorrect'], 400);
            return;
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $this->userModel->updatePassword($userId, $newPasswordHash);
        
        if (!$result) {
            $this->jsonResponse(['error' => 'Failed to update password'], 400);
            return;
        }
        
        $this->jsonResponse(['message' => 'Password changed successfully']);
    }

    // Helper methods
    private function requireAuth()
    {
        $token = $_COOKIE['token'] ?? '';
        if (empty($token)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (empty($token)) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            exit;
        }

        $auth = new \App\Core\Auth();
        try {
            $decoded = $auth->validateToken($token);
            $_SESSION['user_id'] = $decoded['uid'];
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Invalid token'], 401);
            exit;
        }
    }

    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? '';
    }
}
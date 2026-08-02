<?php
namespace App\Http\Controllers;

use App\Models\User;

class SubscriptionController extends Controller
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
        
        // Get current subscription
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT sp.*, us.start_date, us.end_date, us.is_active, us.next_billing_date
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = :user_id AND us.is_active = 1
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $currentSubscription = $stmt->fetch();
        
        // Get all plans
        $stmt = $db->prepare("SELECT * FROM subscription_plans ORDER BY price_monthly ASC");
        $stmt->execute();
        $plans = $stmt->fetchAll();
        
        $this->jsonResponse([
            'current_subscription' => $currentSubscription,
            'plans' => $plans
        ]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('subscription/index');
    }

    public function upgrade()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $planId = $data['plan_id'] ?? '';
        $billingCycle = $data['billing_cycle'] ?? 'monthly'; // monthly or yearly
        
        if (empty($planId)) {
            $this->jsonResponse(['error' => 'Plan ID is required'], 400);
            return;
        }
        
        // Get plan details
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $planId]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            $this->jsonResponse(['error' => 'Plan not found'], 404);
            return;
        }
        
        // In a real implementation, this would integrate with a payment gateway
        // For now, we'll simulate a successful upgrade
        $db->beginTransaction();
        
        try {
            // Deactivate current subscription
            $stmt = $db->prepare("
                UPDATE user_subscriptions 
                SET is_active = 0 
                WHERE user_id = :user_id AND is_active = 1
            ");
            $stmt->execute(['user_id' => $userId]);
            
            // Create new subscription
            $subscriptionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $startDate = date('Y-m-d');
            $endDate = $billingCycle === 'yearly' ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d', strtotime('+1 month'));
            $nextBillingDate = $endDate;
            
            $stmt = $db->prepare("
                INSERT INTO user_subscriptions (id, user_id, plan_id, start_date, end_date, is_active, next_billing_date)
                VALUES (:id, :user_id, :plan_id, :start_date, :end_date, 1, :next_billing_date)
            ");
            
            $stmt->execute([
                'id' => $subscriptionId,
                'user_id' => $userId,
                'plan_id' => $planId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_billing_date' => $nextBillingDate
            ]);
            
            $db->commit();
            
            $this->jsonResponse([
                'message' => 'Subscription upgraded successfully',
                'subscription' => [
                    'id' => $subscriptionId,
                    'plan_name' => $plan['name'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'billing_cycle' => $billingCycle
                ]
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
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
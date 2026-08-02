<?php
namespace App\Http\Controllers;

use App\Models\Account;

class AccountController extends Controller
{
    private $accountModel;

    public function __construct()
    {
        $this->accountModel = new Account();
    }

    public function index()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $accounts = $this->accountModel->findByUserId($userId);
        $this->jsonResponse(['accounts' => $accounts]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('accounts/index');
    }

    public function store()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $accountTypeId = $data['account_type_id'] ?? '';
        $name = $data['name'] ?? '';
        $initialBalance = $data['initial_balance'] ?? 0.00;
        $currency = $data['currency'] ?? 'IDR';

        if (empty($accountTypeId) || empty($name)) {
            $this->jsonResponse(['error' => 'Account type and name are required'], 400);
            return;
        }

        // Check Free tier account limit (max 3 accounts)
        $userSubscription = $this->checkUserSubscription($userId);
        if ($userSubscription && $userSubscription['plan_name'] === 'Free') {
            $currentAccounts = $this->accountModel->findByUserId($userId);
            if (count($currentAccounts) >= 3) {
                $this->jsonResponse(['error' => 'Free tier limit reached. Maximum 3 accounts allowed. Upgrade to Pro for unlimited accounts.'], 403);
                return;
            }
        }

        try {
            $accountId = $this->accountModel->create($userId, $accountTypeId, $name, $initialBalance, $currency);
            $account = $this->accountModel->findById($accountId, $userId);
            $this->jsonResponse(['account' => $account], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function checkUserSubscription($userId)
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT sp.name as plan_name 
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = :user_id AND us.is_active = 1
            ORDER BY us.start_date DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    public function show($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $account = $this->accountModel->findById($id, $userId);
        if (!$account) {
            $this->jsonResponse(['error' => 'Account not found'], 404);
            return;
        }
        
        $this->jsonResponse(['account' => $account]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        try {
            $result = $this->accountModel->update($id, $userId, $data);
            if (!$result) {
                $this->jsonResponse(['error' => 'Account not found or no changes made'], 404);
                return;
            }
            
            $account = $this->accountModel->findById($id, $userId);
            $this->jsonResponse(['account' => $account]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $result = $this->accountModel->delete($id, $userId);
        if (!$result) {
            $this->jsonResponse(['error' => 'Account not found'], 404);
            return;
        }
        
        $this->jsonResponse(['message' => 'Account deleted successfully']);
    }

    // Helper methods
    private function requireAuth()
    {
        $token = $_COOKIE['token'] ?? '';
        if (empty($token)) {
            // Try to get from Authorization header
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
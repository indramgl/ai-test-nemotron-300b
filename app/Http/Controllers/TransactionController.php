<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;

class TransactionController extends Controller
{
    private $transactionModel;
    private $categoryModel;
    private $accountModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->categoryModel = new Category();
        $this->accountModel = new Account();
    }

    public function index()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        // Parse query parameters
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $filters = [];
        if (!empty($_GET['account_id'])) $filters['account_id'] = $_GET['account_id'];
        if (!empty($_GET['category_id'])) $filters['category_id'] = $_GET['category_id'];
        if (!empty($_GET['type'])) $filters['type'] = $_GET['type'];
        if (!empty($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
        if (!empty($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];

        $transactions = $this->transactionModel->findByUserId($userId, $limit, $offset, $filters);
        
        // Also get categories and accounts for dropdowns
        $categories = $this->categoryModel->getHierarchicalCategories();
        $accounts = $this->accountModel->findByUserId($userId);
        
        $this->jsonResponse([
            'transactions' => $transactions,
            'categories' => $categories,
            'accounts' => $accounts
        ]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('transactions/index');
    }

    public function store()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $accountId = $data['account_id'] ?? '';
        $categoryId = $data['category_id'] ?? '';
        $amount = $data['amount'] ?? 0;
        $type = $data['type'] ?? '';
        $description = $data['description'] ?? '';
        $transactionDate = $data['transaction_date'] ?? date('Y-m-d');
        $isRecurring = $data['is_recurring'] ?? false;
        $recurrencePattern = $data['recurrence_pattern'] ?? null;
        $recurrenceEndDate = $data['recurrence_end_date'] ?? null;

        if (empty($accountId) || empty($categoryId) || empty($amount) || empty($type)) {
            $this->jsonResponse(['error' => 'Account, category, amount, and type are required'], 400);
            return;
        }

        if (!in_array($type, ['INCOME', 'EXPENSE', 'TRANSFER'])) {
            $this->jsonResponse(['error' => 'Invalid transaction type'], 400);
            return;
        }

        // For TRANSFER, we need a destination account
        $toAccountId = $data['to_account_id'] ?? null;
        if ($type === 'TRANSFER' && empty($toAccountId)) {
            $this->jsonResponse(['error' => 'Destination account is required for transfers'], 400);
            return;
        }

        // Check if recurring transactions are allowed for user's subscription tier
        if ($isRecurring) {
            $userSubscription = $this->checkUserSubscription($userId);
            if ($userSubscription && $userSubscription['plan_name'] === 'Free') {
                $this->jsonResponse(['error' => 'Recurring transactions are only available for Pro tier. Upgrade to Pro to use this feature.'], 403);
                return;
            }
        }

        try {
            $transactionId = $this->transactionModel->create(
                $userId, 
                $accountId, 
                $categoryId, 
                $amount, 
                $type, 
                $description, 
                $transactionDate, 
                $isRecurring, 
                $recurrencePattern, 
                $recurrenceEndDate,
                $toAccountId  // Pass to_account_id as last parameter for TRANSFER
            );

            $transaction = $this->transactionModel->findById($transactionId, $userId);
            $this->jsonResponse(['transaction' => $transaction], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $transaction = $this->transactionModel->findById($id, $userId);
        if (!$transaction) {
            $this->jsonResponse(['error' => 'Transaction not found'], 404);
            return;
        }
        
        $this->jsonResponse(['transaction' => $transaction]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        try {
            $result = $this->transactionModel->update($id, $userId, $data);
            if (!$result) {
                $this->jsonResponse(['error' => 'Transaction not found or no changes made'], 404);
                return;
            }
            
            $transaction = $this->transactionModel->findById($id, $userId);
            $this->jsonResponse(['transaction' => $transaction]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $result = $this->transactionModel->delete($id, $userId);
        if (!$result) {
            $this->jsonResponse(['error' => 'Transaction not found'], 404);
            return;
        }
        
        $this->jsonResponse(['message' => 'Transaction deleted successfully']);
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

    public function summary()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        $summary = $this->transactionModel->getSummaryByCategory($userId, $startDate, $endDate);
        $this->jsonResponse(['summary' => $summary]);
    }

    public function monthlySummary()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
        
        $summary = $this->transactionModel->getMonthlySummary($userId, $year, $month);
        $this->jsonResponse(['summary' => $summary]);
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
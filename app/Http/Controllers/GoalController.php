<?php
namespace App\Http\Controllers;

use App\Models\Goal;

class GoalController extends Controller
{
    private $goalModel;

    public function __construct()
    {
        $this->goalModel = new Goal();
    }

    public function index()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $goals = $this->goalModel->findByUserId($userId);
        
        // Add progress for each goal
        $goalsWithProgress = [];
        foreach ($goals as $goal) {
            $progress = $this->goalModel->getProgress($goal['id'], $userId);
            $goalsWithProgress[] = $progress;
        }
        
        $this->jsonResponse(['goals' => $goalsWithProgress]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('goals/index');
    }

    public function store()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $name = $data['name'] ?? '';
        $targetAmount = $data['target_amount'] ?? 0;
        $targetDate = $data['target_date'] ?? null;
        $icon = $data['icon'] ?? null;
        $color = $data['color'] ?? null;
        $description = $data['description'] ?? null;

        if (empty($name) || empty($targetAmount)) {
            $this->jsonResponse(['error' => 'Name and target amount are required'], 400);
            return;
        }

        try {
            $goalId = $this->goalModel->create($userId, $name, $targetAmount, $targetDate, $icon, $color, $description);
            $goal = $this->goalModel->findById($goalId, $userId);
            $this->jsonResponse(['goal' => $goal], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $goal = $this->goalModel->findById($id, $userId);
        if (!$goal) {
            $this->jsonResponse(['error' => 'Goal not found'], 404);
            return;
        }
        
        $progress = $this->goalModel->getProgress($id, $userId);
        $transactions = $this->goalModel->getTransactions($id, $userId);
        
        $this->jsonResponse(['goal' => $progress, 'transactions' => $transactions]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        try {
            $result = $this->goalModel->update($id, $userId, $data);
            if (!$result) {
                $this->jsonResponse(['error' => 'Goal not found or no changes made'], 404);
                return;
            }
            
            $progress = $this->goalModel->getProgress($id, $userId);
            $this->jsonResponse(['goal' => $progress]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $result = $this->goalModel->delete($id, $userId);
        if (!$result) {
            $this->jsonResponse(['error' => 'Goal not found'], 404);
            return;
        }
        
        $this->jsonResponse(['message' => 'Goal deleted successfully']);
    }

    public function deposit($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $amount = $data['amount'] ?? 0;
        $description = $data['description'] ?? '';
        $transactionDate = $data['transaction_date'] ?? null;

        if (empty($amount) || $amount <= 0) {
            $this->jsonResponse(['error' => 'Amount must be greater than 0'], 400);
            return;
        }

        try {
            $txnId = $this->goalModel->deposit($id, $userId, $amount, $description, $transactionDate);
            $progress = $this->goalModel->getProgress($id, $userId);
            $this->jsonResponse(['goal' => $progress, 'transaction_id' => $txnId]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function withdraw($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $amount = $data['amount'] ?? 0;
        $description = $data['description'] ?? '';
        $transactionDate = $data['transaction_date'] ?? null;

        if (empty($amount) || $amount <= 0) {
            $this->jsonResponse(['error' => 'Amount must be greater than 0'], 400);
            return;
        }

        try {
            $txnId = $this->goalModel->withdraw($id, $userId, $amount, $description, $transactionDate);
            $progress = $this->goalModel->getProgress($id, $userId);
            $this->jsonResponse(['goal' => $progress, 'transaction_id' => $txnId]);
        } catch (\Exception $e) {
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
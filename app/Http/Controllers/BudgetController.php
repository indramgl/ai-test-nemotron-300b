<?php
namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;

class BudgetController extends Controller
{
    private $budgetModel;
    private $categoryModel;

    public function __construct()
    {
        $this->budgetModel = new Budget();
        $this->categoryModel = new Category();
    }

    public function index()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $budgets = $this->budgetModel->findByUserId($userId);
        
        // Get budget usage/alerts
        $usage = $this->budgetModel->getBudgetUsage($userId);
        $alerts = $this->budgetModel->checkBudgetAlerts($userId);
        
        // Get expense categories for dropdown
        $categories = $this->categoryModel->findByUserId($userId, 'EXPENSE');
        
        $this->jsonResponse([
            'budgets' => $budgets,
            'usage' => $usage,
            'alerts' => $alerts,
            'categories' => $categories
        ]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('budgets/index');
    }

    public function store()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $categoryId = $data['category_id'] ?? '';
        $amount = $data['amount'] ?? 0;
        $period = $data['period'] ?? 'monthly';
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;

        if (empty($categoryId) || empty($amount)) {
            $this->jsonResponse(['error' => 'Category and amount are required'], 400);
            return;
        }

        if (!in_array($period, ['monthly', 'weekly', 'yearly'])) {
            $this->jsonResponse(['error' => 'Invalid period. Must be monthly, weekly, or yearly'], 400);
            return;
        }

        try {
            $budgetId = $this->budgetModel->create($userId, $categoryId, $amount, $period, $startDate, $endDate);
            $budget = $this->budgetModel->findById($budgetId, $userId);
            $this->jsonResponse(['budget' => $budget], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $budget = $this->budgetModel->findById($id, $userId);
        if (!$budget) {
            $this->jsonResponse(['error' => 'Budget not found'], 404);
            return;
        }
        
        $usage = $this->budgetModel->getBudgetUsage($userId, $id);
        $this->jsonResponse(['budget' => $budget, 'usage' => $usage]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        // Validate period if provided
        if (isset($data['period']) && !in_array($data['period'], ['monthly', 'weekly', 'yearly'])) {
            $this->jsonResponse(['error' => 'Invalid period. Must be monthly, weekly, or yearly'], 400);
            return;
        }

        try {
            $result = $this->budgetModel->update($id, $userId, $data);
            if (!$result) {
                $this->jsonResponse(['error' => 'Budget not found or no changes made'], 404);
                return;
            }
            
            $budget = $this->budgetModel->findById($id, $userId);
            $usage = $this->budgetModel->getBudgetUsage($userId, $id);
            $this->jsonResponse(['budget' => $budget, 'usage' => $usage]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $result = $this->budgetModel->delete($id, $userId);
        if (!$result) {
            $this->jsonResponse(['error' => 'Budget not found'], 404);
            return;
        }
        
        $this->jsonResponse(['message' => 'Budget deleted successfully']);
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
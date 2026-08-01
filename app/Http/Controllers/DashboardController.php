<?php
namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Goal;

class DashboardController extends Controller
{
    private $accountModel;
    private $transactionModel;
    private $budgetModel;
    private $goalModel;

    public function __construct()
    {
        $this->accountModel = new Account();
        $this->transactionModel = new Transaction();
        $this->budgetModel = new Budget();
        $this->goalModel = new Goal();
    }

    public function index()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        // Get account balances
        $accounts = $this->accountModel->findByUserId($userId);
        $totalBalance = 0;
        foreach ($accounts as $account) {
            $totalBalance += (float)$account['balance'];
        }
        
        // Get monthly summary
        $year = date('Y');
        $month = date('n');
        $monthlySummary = $this->transactionModel->getMonthlySummary($userId, $year, $month);
        
        // Get recent transactions (last 10)
        $recentTransactions = $this->transactionModel->findByUserId($userId, 10, 0);
        
        // Get budget alerts
        $budgetAlerts = $this->budgetModel->checkBudgetAlerts($userId);
        
        // Get active goals with progress
        $goals = $this->goalModel->findByUserId($userId);
        $goalsProgress = [];
        foreach ($goals as $goal) {
            $progress = $this->goalModel->getProgress($goal['id'], $userId);
            if ($progress) {
                $goalsProgress[] = $progress;
            }
        }
        
        // Sort goals by target date or completion status
        usort($goalsProgress, function($a, $b) {
            // Completed goals last
            if ($a['is_completed'] && !$b['is_completed']) return 1;
            if (!$a['is_completed'] && $b['is_completed']) return -1;
            // Then by target date
            if ($a['goal']['target_date'] && $b['goal']['target_date']) {
                return strtotime($a['goal']['target_date']) - strtotime($b['goal']['target_date']);
            }
            return 0;
        });
        
        $this->jsonResponse([
            'total_balance' => $totalBalance,
            'monthly_income' => $monthlySummary['total_income'] ?? 0,
            'monthly_expense' => $monthlySummary['total_expense'] ?? 0,
            'monthly_net' => $monthlySummary['net_amount'] ?? 0,
            'accounts' => $accounts,
            'recent_transactions' => $recentTransactions,
            'budget_alerts' => $budgetAlerts,
            'goals' => $goalsProgress
        ]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('dashboard/index');
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
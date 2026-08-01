<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Goal;

class ReportController extends Controller
{
    private $transactionModel;
    private $accountModel;
    private $goalModel;

    public function __construct()
    {
        $this->transactionModel = new Transaction();
        $this->accountModel = new Account();
        $this->goalModel = new Goal();
    }

    public function summary()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Get category summary
        $categorySummary = $this->transactionModel->getSummaryByCategory($userId, $startDate, $endDate);
        
        // Get monthly summary
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
        $monthlySummary = $this->transactionModel->getMonthlySummary($userId, $year, $month);
        
        // Get account balances
        $accounts = $this->accountModel->findByUserId($userId);
        $totalAssets = 0;
        foreach ($accounts as $account) {
            $totalAssets += (float)$account['balance'];
        }
        
        $this->jsonResponse([
            'category_summary' => $categorySummary,
            'monthly_summary' => $monthlySummary,
            'total_assets' => $totalAssets,
            'accounts' => $accounts
        ]);
    }

    public function cashflow()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        $months = isset($_GET['months']) ? (int)$_GET['months'] : 12;
        
        $cashflowData = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('n', strtotime("-{$i} months"));
            $yearForMonth = date('Y', strtotime("-{$i} months"));
            
            $summary = $this->transactionModel->getMonthlySummary($userId, $yearForMonth, $month);
            
            $cashflowData[] = [
                'month' => sprintf('%04d-%02d', $yearForMonth, $month),
                'income' => $summary['total_income'] ?? 0,
                'expense' => $summary['total_expense'] ?? 0,
                'net' => $summary['net_amount'] ?? 0
            ];
        }
        
        $this->jsonResponse(['cashflow' => $cashflowData]);
    }

    public function networth()
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        
        // Get all accounts with balances
        $accounts = $this->accountModel->findByUserId($userId);
        
        $totalAssets = 0;
        $totalLiabilities = 0; // Could be extended with loan accounts
        
        $accountBreakdown = [];
        foreach ($accounts as $account) {
            $balance = (float)$account['balance'];
            $accountBreakdown[] = [
                'id' => $account['id'],
                'name' => $account['name'],
                'type' => $account['account_type_name'],
                'balance' => $balance,
                'currency' => $account['currency']
            ];
            
            if ($balance >= 0) {
                $totalAssets += $balance;
            } else {
                $totalLiabilities += abs($balance);
            }
        }
        
        // Get goals progress
        $goals = $this->goalModel->findByUserId($userId);
        $goalsProgress = [];
        foreach ($goals as $goal) {
            $target = (float)$goal['target_amount'];
            $current = (float)$goal['current_amount'];
            $percentage = $target > 0 ? ($current / $target) * 100 : 0;
            
            $goalsProgress[] = [
                'id' => $goal['id'],
                'name' => $goal['name'],
                'target_amount' => $target,
                'current_amount' => $current,
                'percentage' => round($percentage, 1),
                'target_date' => $goal['target_date']
            ];
        }
        
        $netWorth = $totalAssets - $totalLiabilities;
        
        $this->jsonResponse([
            'net_worth' => $netWorth,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'accounts' => $accountBreakdown,
            'goals' => $goalsProgress
        ]);
    }

    public function indexPage()
    {
        $this->requireAuth();
        $this->render('reports/index');
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
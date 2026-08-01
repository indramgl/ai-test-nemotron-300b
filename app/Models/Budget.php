<?php
namespace App\Models;

use App\Core\Database;

class Budget
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id, $userId = null)
    {
        $sql = "SELECT b.*, c.name as category_name, c.type as category_type 
                FROM budgets b 
                JOIN categories c ON b.category_id = c.id 
                WHERE b.id = :id";
        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND b.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function findByUserId($userId, $activeOnly = true)
    {
        $sql = "SELECT b.*, c.name as category_name, c.type as category_type 
                FROM budgets b 
                JOIN categories c ON b.category_id = c.id 
                WHERE b.user_id = :user_id";
        $params = ['user_id' => $userId];

        if ($activeOnly) {
            $sql .= " AND b.is_active = TRUE";
        }

        $sql .= " ORDER BY b.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($userId, $categoryId, $amount, $period = 'monthly', $startDate = null, $endDate = null)
    {
        if ($startDate === null) {
            }

            $startDate = date('Y-m-d');
        }

        $this->db->beginTransaction();

        try {
            // Verify category belongs to user (if needed) and is an expense category
            $categoryCheck = $this->db->prepare("
                SELECT c.id FROM categories c 
                WHERE c.id = :category_id AND c.type = 'EXPENSE' AND c.is_active = TRUE
            ");
            $categoryCheck->execute(['category_id' => $categoryId]);
            
            if (!$categoryCheck->fetch()) {
                throw new \Exception('Invalid or inactive expense category');
            }

            $stmt = $this->db->prepare("
                INSERT INTO budgets (id, user_id, category_id, amount, period, start_date, end_date)
                VALUES (:id, :user_id, :category_id, :amount, :period, :start_date, :end_date)
            ");

            $stmt->execute([
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'user_id' => $userId,
                'category_id' => $categoryId,
                'amount' => $amount,
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $this->db->commit();
            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $userId, $data)
    {
        $allowedFields = ['category_id', 'amount', 'period', 'start_date', 'end_date', 'alert_threshold_80', 'alert_threshold_100', 'is_active'];
        $updates = [];
        $params = ['id' => $id, 'user_id' => $userId];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE budgets SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id");
        return $stmt->execute($params);
    }

    public function delete($id, $userId)
    {
        $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function getBudgetUsage($userId, $budgetId = null)
    {
        if ($budgetId !== null) {
            // Get usage for specific budget
            $sql = "SELECT b.*, c.name as category_name,
                           SUM(CASE WHEN t.type = 'EXPENSE' THEN t.amount ELSE 0 END) as spent_amount
                    FROM budgets b
                    JOIN categories c ON b.category_id = c.id
                    LEFT JOIN transactions t ON t.category_id = c.id 
                        AND t.user_id = :user_id
                        AND t.transaction_date >= :start_date
                        AND t.transaction_date <= :end_date
                    WHERE b.id = :budget_id AND b.user_id = :user_id
                    GROUP BY b.id, b.user_id, b.category_id, b.amount, b.period, b.start_date, b.end_date, 
                             b.alert_threshold_80, b.alert_threshold_100, b.is_active, c.name";
            
            $budget = $this->findById($budgetId, $userId);
            if (!$budget) {
                return null;
            }

            $startDate = $budget['start_date'];
            $endDate = $budget['end_date'];

            // Adjust date range based on period
            if ($budget['period'] === 'monthly') {
                $startDate = date('Y-m-01', strtotime($startDate));
                $endDate = date('Y-m-t', strtotime($startDate)); // Last day of month
            } elseif ($budget['period'] === 'weekly') {
                // Start of week (Monday) to end of week (Sunday)
                $startDate = date('Y-m-d', strtotime('monday this week', strtotime($startDate)));
                $endDate = date('Y-m-d', strtotime('sunday this week', strtotime($startDate)));
            } elseif ($budget['period'] === 'yearly') {
                $startDate = date('Y-01-01', strtotime($startDate));
                $endDate = date('Y-12-31', strtotime($startDate));
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'budget_id' => $budgetId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            return $stmt->fetch();
        } else {
            // Get usage for all budgets
            $budgets = $this->findByUserId($userId);
            $usage = [];

            foreach ($budgets as $budget) {
                $budgetUsage = $this->getBudgetUsage($userId, $budget['id']);
                if ($budgetUsage) {
                    $usage[] = $budgetUsage;
                }
            }

            return $usage;
        }
    }

    public function checkBudgetAlerts($userId)
    {
        $alerts = [];
        $budgets = $this->findByUserId($userId);

        foreach ($budgets as $budget) {
            $usage = $this->getBudgetUsage($userId, $budget['id']);
            
            if ($usage) {
                $spent = $usage['spent_amount'] ?? 0;
                $budgetAmount = (float)$budget['amount'];
                
                if ($budgetAmount > 0) {
                    $percentage = ($spent / $budgetAmount) * 100;
                    
                    // Check 80% threshold
                    if ($percentage >= 80 && $budget['alert_threshold_80'] && $percentage < 100) {
                        $alerts[] = [
                            'type' => 'warning',
                            'message' => "Anda telah menggunakan " . number_format($percentage, 1) "% dari anggaran untuk kategori {$usage['category_name']}",
                            'budget_id' => $budget['id'],
                            'category_name' => $usage['category_name'],
                            'percentage' => $percentage,
                            'amount_spent' => $spent,
                            'budget_amount' => $budgetAmount
                        ];
                    }
                    
                    // Check 100% threshold
                    if ($percentage >= 100 && $budget['alert_threshold_100']) {
                        $alerts[] = [
                            'type' => 'danger',
                            'message' => "Anda telah melebihi anggaran untuk kategori {$usage['category_name']} sebesar " . number_format($percentage - 100, 1) "%",
                            'budget_id' => $budget['id'],
                            'category_name' => $usage['category_name'],
                            'percentage' => $percentage,
                            'amount_spent' => $spent,
                            'budget_amount' => $budgetAmount
                        ];
                    }
                }
            }
        }

        return $alerts;
    }
}
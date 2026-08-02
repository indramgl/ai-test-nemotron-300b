<?php
namespace App\Models;

use App\Core\Database;

class Transaction
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id, $userId = null)
    {
        $sql = "SELECT t.*, a.name as account_name, c.name as category_name, c.type as category_type 
                FROM transactions t 
                JOIN accounts a ON t.account_id = a.id 
                JOIN categories c ON t.category_id = c.id 
                WHERE t.id = :id";
        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND t.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function findByUserId($userId, $limit = null, $offset = null, $filters = [])
    {
        $sql = "SELECT t.*, a.name as account_name, c.name as category_name, c.type as category_type 
                FROM transactions t 
                JOIN accounts a ON t.account_id = a.id 
                JOIN categories c ON t.category_id = c.id 
                WHERE t.user_id = :user_id";
        $params = ['user_id' => $userId];

        // Apply filters
        if (!empty($filters)) {
            if (!empty($filters['account_id'])) {
                $sql .= " AND t.account_id = :account_id";
                $params['account_id'] = $filters['account_id'];
            }
            
            if (!empty($filters['category_id'])) {
                $sql .= " AND t.category_id = :category_id";
                $params['category_id'] = $filters['category_id'];
            }
            
            if (!empty($filters['type'])) {
                $sql .= " AND t.type = :type";
                $params['type'] = $filters['type'];
            }
            
            if (!empty($filters['start_date'])) {
                $sql .= " AND t.transaction_date >= :start_date";
                $params['start_date'] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $sql .= " AND t.transaction_date <= :end_date";
                $params['end_date'] = $filters['end_date'];
            }
        }

        $sql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int)$limit;
            
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
                $params['offset'] = (int)$offset;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($userId, $accountId, $categoryId, $amount, $type, $description = '', $transactionDate = null, $isRecurring = false, $recurrencePattern = null, $recurrenceEndDate = null, $toAccountId = null)
    {
        if ($transactionDate === null) {
            $transactionDate = date('Y-m-d');
        }

        $this->db->beginTransaction();

        try {
            $transactionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            
            $stmt = $this->db->prepare("
                INSERT INTO transactions (id, user_id, account_id, category_id, amount, type, description, transaction_date, is_recurring, recurrence_pattern, recurrence_end_date)
                VALUES (:id, :user_id, :account_id, :category_id, :amount, :type, :description, :transaction_date, :is_recurring, :recurrence_pattern, :recurrence_end_date)
            ");

            $stmt->execute([
                'id' => $transactionId,
                'user_id' => $userId,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'amount' => $amount,
                'type' => $type,
                'description' => $description,
                'transaction_date' => $transactionDate,
                'is_recurring' => $isRecurring ? 1 : 0,
                'recurrence_pattern' => $recurrencePattern,
                'recurrence_end_date' => $recurrenceEndDate
            ]);

            // Update account balance - skip for TRANSFER as it's handled below
            if ($type !== 'TRANSFER') {
                $this->adjustAccountBalance($accountId, $amount, $type === 'INCOME');
            } else {
                // For TRANSFER, handle transfer between accounts
                if ($toAccountId) {
                    $this->transferBetweenAccounts($accountId, $toAccountId, $amount);
                }
            }

            $this->db->commit();
            return $transactionId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $userId, $data)
    {
        $allowedFields = ['account_id', 'category_id', 'amount', 'description', 'transaction_date', 'is_recurring', 'recurrence_pattern', 'recurrence_end_date'];
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

        // If amount or account changed, we need to adjust balances
        $balanceAdjustmentNeeded = 
            array_key_exists('amount', $data) || 
            array_key_exists('account_id', $data);

        if ($balanceAdjustmentNeeded) {
            $this->db->beginTransaction();

            try {
                // Get original transaction to reverse its effect
                $originalTransaction = $this->findById($id, $userId);
                if (!$originalTransaction) {
                    throw new \Exception('Transaction not found');
                }

                // Reverse original transaction effect
                $this->adjustAccountBalance(
                    $originalTransaction['account_id'],
                    $originalTransaction['amount'],
                    $originalTransaction['type'] === 'INCOME' ? false : true
                );

                // Apply new transaction effect
                $newAmount = $data['amount'] ?? $originalTransaction['amount'];
                $newAccountId = $data['account_id'] ?? $originalTransaction['account_id'];
                $newType = $data['type'] ?? $originalTransaction['type'];

                $this->adjustAccountBalance(
                    $newAccountId,
                    $newAmount,
                    $newType === 'INCOME'
                );

                // Update transaction
                $stmt = $this->db->prepare("UPDATE transactions SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id");
                $stmt->execute($params);

                $this->db->commit();
                return true;
            } catch (\Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
        } else {
            // Simple update without balance adjustment
            $stmt = $this->db->prepare("UPDATE transactions SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id");
            return $stmt->execute($params);
        }
    }

    public function delete($id, $userId)
    {
        $this->db->beginTransaction();

        try {
            // Get transaction to reverse its effect
            $transaction = $this->findById($id, $userId);
            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }

            // Reverse transaction effect on account balance
            $this->adjustAccountBalance(
                $transaction['account_id'],
                $transaction['amount'],
                $transaction['type'] === 'INCOME' ? false : true
            );

            // Delete transaction
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $id, 'user_id' => $userId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getSummaryByCategory($userId, $startDate = null, $endDate = null)
    {
        $sql = "SELECT c.name as category_name, c.type as category_type, c.color, 
                       SUM(t.amount) as total_amount,
                       COUNT(t.id) as transaction_count
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id";
        $params = ['user_id' => $userId];

        if ($startDate !== null) {
            $sql .= " AND t.transaction_date >= :start_date";
            $params['start_date'] = $startDate;
        }
        
        if ($endDate !== null) {
            $sql .= " AND t.transaction_date <= :end_date";
            $params['end_date'] = $endDate;
        }

        $sql .= " GROUP BY c.id, c.name, c.type, c.color
                  ORDER BY c.type, total_amount DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getMonthlySummary($userId, $year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = sprintf('%04d-%02d-31', $year, $month);
        
        $sql = "SELECT 
                       SUM(CASE WHEN t.type = 'INCOME' THEN t.amount ELSE 0 END) as total_income,
                       SUM(CASE WHEN t.type = 'EXPENSE' THEN t.amount ELSE 0 END) as total_expense,
                       SUM(CASE WHEN t.type = 'INCOME' THEN t.amount ELSE -t.amount END) as net_amount
                FROM transactions t
                WHERE t.user_id = :user_id
                  AND t.transaction_date BETWEEN :start_date AND :end_date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $stmt->fetch();
    }

    private function adjustAccountBalance($accountId, $amount, $isIncome)
    {
        if ($isIncome) {
            $stmt = $this->db->prepare("UPDATE accounts SET balance = balance + :amount WHERE id = :id");
        } else {
            $stmt = $this->db->prepare("UPDATE accounts SET balance = balance - :amount WHERE id = :id");
        }

        $stmt->execute(['amount' => $amount, 'id' => $accountId]);
        return true;
    }

    public function getRecurringTransactions($userId, $date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        $sql = "SELECT t.*, a.name as account_name, c.name as category_name 
                FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = :user_id
                  AND t.is_recurring = TRUE
                  AND (
                    (t.recurrence_pattern = 'daily') OR
                    (t.recurrence_pattern = 'weekly' AND WEEKDAY(t.transaction_date) = WEEKDAY(:date)) OR
                    (t.recurrence_pattern = 'monthly' AND DAY(t.transaction_date) = DAY(:date)) OR
                    (t.recurrence_pattern = 'yearly' AND MONTH(t.transaction_date) = MONTH(:date) AND DAY(t.transaction_date) = DAY(:date))
                  )
                  AND (t.recurrence_end_date IS NULL OR t.recurrence_end_date >= :date)
                  AND t.transaction_date <= :date
                ORDER BY t.transaction_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'date' => $date
        ]);

        return $stmt->fetchAll();
    }

    private function transferBetweenAccounts($fromAccountId, $toAccountId, $amount)
    {
        // Check if source account has sufficient funds
        $stmt = $this->db->prepare("SELECT balance FROM accounts WHERE id = :id");
        $stmt->execute(['id' => $fromAccountId]);
        $fromAccount = $stmt->fetch();
        
        if (!$fromAccount || (float)$fromAccount['balance'] < (float)$amount) {
            throw new \Exception('Insufficient funds');
        }

        // Deduct from source account
        $stmt = $this->db->prepare("UPDATE accounts SET balance = balance - :amount WHERE id = :id");
        $stmt->execute(['amount' => $amount, 'id' => $fromAccountId]);

        // Add to destination account
        $stmt = $this->db->prepare("UPDATE accounts SET balance = balance + :amount WHERE id = :id");
        $stmt->execute(['amount' => $amount, 'id' => $toAccountId]);

        return true;
    }
}
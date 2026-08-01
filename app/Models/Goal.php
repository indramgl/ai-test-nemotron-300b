<?php
namespace App\Models;

use App\Core\Database;

class Goal
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id, $userId = null)
    {
        $sql = "SELECT * FROM goals WHERE id = :id";
        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function findByUserId($userId, $activeOnly = true)
    {
        $sql = "SELECT * FROM goals WHERE user_id = :user_id";
        $params = ['user_id' => $userId];

        if ($activeOnly) {
            $sql .= " AND is_active = TRUE";
        }

        $sql .= " ORDER BY target_date ASC, created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($userId, $name, $targetAmount, $targetDate = null, $icon = null, $color = null, $description = null)
    {
        $this->db->beginTransaction();

        try {
            $goalId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            
            $stmt = $this->db->prepare("
                INSERT INTO goals (id, user_id, name, target_amount, target_date, icon, color, description)
                VALUES (:id, :user_id, :name, :target_amount, :target_date, :icon, :color, :description)
            ");

            $stmt->execute([
                'id' => $goalId,
                'user_id' => $userId,
                'name' => $name,
                'target_amount' => $targetAmount,
                'target_date' => $targetDate,
                'icon' => $icon,
                'color' => $color,
                'description' => $description
            ]);

            $this->db->commit();
            return $goalId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $userId, $data)
    {
        $allowedFields = ['name', 'target_amount', 'target_date', 'icon', 'color', 'description', 'is_active'];
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

        $stmt = $this->db->prepare("UPDATE goals SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id");
        return $stmt->execute($params);
    }

    public function delete($id, $userId)
    {
        // Soft delete
        $stmt = $this->db->prepare("UPDATE goals SET is_active = FALSE WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function deposit($goalId, $userId, $amount, $description = '', $transactionDate = null)
    {
        if ($transactionDate === null) {
            $transactionDate = date('Y-m-d');
        }

        $this->db->beginTransaction();

        try {
            // Verify goal belongs to user
            $goal = $this->findById($goalId, $userId);
            if (!$goal) {
                throw new \Exception('Goal not found');
            }

            // Create goal transaction
            $txnId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $stmt = $this->db->prepare("
                INSERT INTO goal_transactions (id, goal_id, user_id, amount, type, description, transaction_date)
                VALUES (:id, :goal_id, :user_id, :amount, 'DEPOSIT', :description, :transaction_date)
            ");

            $stmt->execute([
                'id' => $txnId,
                'goal_id' => $goalId,
                'user_id' => $userId,
                'amount' => $amount,
                'description' => $description,
                'transaction_date' => $transactionDate
            ]);

            // Update goal current amount
            $stmt = $this->db->prepare("UPDATE goals SET current_amount = current_amount + :amount WHERE id = :id");
            $stmt->execute(['amount' => $amount, 'id' => $goalId]);

            $this->db->commit();
            return $txnId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function withdraw($goalId, $userId, $amount, $description = '', $transactionDate = null)
    {
        if ($transactionDate === null) {
            $transactionDate = date('Y-m-d');
        }

        $this->db->beginTransaction();

        try {
            // Verify goal belongs to user
            $goal = $this->findById($goalId, $userId);
            if (!$goal) {
                throw new \Exception('Goal not found');
            }

            // Check if enough balance
            if ((float)$goal['current_amount'] < (float)$amount) {
                throw new \Exception('Insufficient balance in goal');
            }

            // Create goal transaction
            $txnId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $stmt = $this->db->prepare("
                INSERT INTO goal_transactions (id, goal_id, user_id, amount, type, description, transaction_date)
                VALUES (:id, :goal_id, :user_id, :amount, 'WITHDRAWAL', :description, :transaction_date)
            ");

            $stmt->execute([
                'id' => $txnId,
                'goal_id' => $goalId,
                'user_id' => $userId,
                'amount' => $amount,
                'description' => $description,
                'transaction_date' => $transactionDate
            ]);

            // Update goal current amount
            $stmt = $this->db->prepare("UPDATE goals SET current_amount = current_amount - :amount WHERE id = :id");
            $stmt->execute(['amount' => $amount, 'id' => $goalId]);

            $this->db->commit();
            return $txnId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getTransactions($goalId, $userId = null)
    {
        $sql = "SELECT * FROM goal_transactions WHERE goal_id = :goal_id";
        $params = ['goal_id' => $goalId];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $sql .= " ORDER BY transaction_date DESC, created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getProgress($goalId, $userId = null)
    {
        $goal = $this->findById($goalId, $userId);
        if (!$goal) {
            return null;
        }

        $targetAmount = (float)$goal['target_amount'];
        $currentAmount = (float)$goal['current_amount'];
        $percentage = $targetAmount > 0 ? ($currentAmount / $targetAmount) * 100 : 0;

        $daysRemaining = null;
        if ($goal['target_date']) {
            $today = new \DateTime();
            $targetDate = new \DateTime($goal['target_date']);
            $interval = $today->diff($targetDate);
            $daysRemaining = $interval->days;
            if ($targetDate < $today) {
                $daysRemaining = -$daysRemaining;
            }
        }

        $monthlyNeeded = null;
        if ($targetAmount > $currentAmount && $daysRemaining !== null && $daysRemaining > 0) {
            $monthsRemaining = $daysRemaining / 30;
            $monthlyNeeded = ($targetAmount - $currentAmount) / $monthsRemaining;
        }

        return [
            'goal' => $goal,
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'percentage' => round($percentage, 1),
            'days_remaining' => $daysRemaining,
            'monthly_needed' => $monthlyNeeded ? round($monthlyNeeded, 2) : null,
            'is_completed' => $currentAmount >= $targetAmount
        ];
    }
}
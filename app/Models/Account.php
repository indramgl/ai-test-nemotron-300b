<?php
namespace App\Models;

use App\Core\Database;

class Account
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id, $userId = null)
    {
        $sql = "SELECT a.*, at.name as account_type_name, at.icon, at.color 
                FROM accounts a 
                JOIN account_types at ON a.account_type_id = at.id 
                WHERE a.id = :id";
        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= " AND a.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function findByUserId($userId, $activeOnly = true)
    {
        $sql = "SELECT a.*, at.name as account_type_name, at.icon, at.color 
                FROM accounts a 
                JOIN account_types at ON a.account_type_id = at.id 
                WHERE a.user_id = :user_id";
        $params = ['user_id' => $userId];

        if ($activeOnly) {
            $sql .= " AND a.is_active = TRUE";
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($userId, $accountTypeId, $name, $initialBalance = 0.00, $currency = 'IDR')
    {
        $this->db->beginTransaction();

        try {
            $accountId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            
            $stmt = $this->db->prepare("
                INSERT INTO accounts (id, user_id, account_type_id, name, balance, currency)
                VALUES (:id, :user_id, :account_type_id, :name, :balance, :currency)
            ");

            $stmt->execute([
                'id' => $accountId,
                'user_id' => $userId,
                'account_type_id' => $accountTypeId,
                'name' => $name,
                'balance' => $initialBalance,
                'currency' => $currency
            ]);

            $this->db->commit();
            return $accountId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update($id, $userId, $data)
    {
        $allowedFields = ['name', 'balance', 'currency', 'is_active'];
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

        $stmt = $this->db->prepare("UPDATE accounts SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id");
        return $stmt->execute($params);
    }

    public function delete($id, $userId)
    {
        // Instead of deleting, we'll soft delete by setting is_active to FALSE
        $stmt = $this->db->prepare("UPDATE accounts SET is_active = FALSE WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function getBalance($accountId, $userId = null)
    {
        $sql = "SELECT balance FROM accounts WHERE id = :id";
        $params = ['id' => $accountId];

        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result ? $result['balance'] : 0.00;
    }

    public function updateBalance($accountId, $amount, $isIncome = true)
    {
        $this->db->beginTransaction();

        try {
            if ($isIncome) {
                $stmt = $this->db->prepare("UPDATE accounts SET balance = balance + :amount WHERE id = :id");
            } else {
                $stmt = $this->db->prepare("UPDATE accounts SET balance = balance - :amount WHERE id = :id");
            }

            $stmt->execute([
                'amount' => $amount,
                'id' => $accountId
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function transfer($fromAccountId, $toAccountId, $amount, $userId)
    {
        $this->db->beginTransaction();

        try {
            // Check if both accounts belong to the user
            $fromAccount = $this->findById($fromAccountId, $userId);
            $toAccount = $this->findById($toAccountId, $userId);

            if (!$fromAccount || !$toAccount) {
                throw new \Exception('One or both accounts not found or do not belong to user');
            }

            if ((float)$fromAccount['balance'] < (float)$amount) {
                throw new \Exception('Insufficient funds');
            }

            // Deduct from source account
            $stmt = $this->db->prepare("UPDATE accounts SET balance = balance - :amount WHERE id = :id");
            $stmt->execute(['amount' => $amount, 'id' => $fromAccountId]);

            // Add to destination account
            $stmt = $this->db->prepare("UPDATE accounts SET balance = balance + :amount WHERE id = :id");
            $stmt->execute(['amount' => $amount, 'id' => $toAccountId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
<?php
namespace App\Models;

use App\Core\Database;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (id, email, password_hash, base_currency, first_name, last_name, phone)
            VALUES (:id, :email, :password_hash, :base_currency, :first_name, :last_name, :phone)
        ");

        $stmt->execute([
            'id' => $data['id'] ?? \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'base_currency' => $data['base_currency'] ?? 'IDR',
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $allowedFields = ['first_name', 'last_name', 'phone', 'base_currency'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id");
        return $stmt->execute($params);
    }

    public function updatePassword($id, $passwordHash)
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
        return $stmt->execute(['password_hash' => $passwordHash, 'id' => $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function verifyEmail($id)
    {
        $stmt = $this->db->prepare("UPDATE users SET email_verified = TRUE WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
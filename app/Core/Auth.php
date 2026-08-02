<?php
namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    private $db;
    private $config;

    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance()->getConnection();
        $this->config = \App\Core\Config::getInstance();
    }

    public function register(array $userData)
    {
        // Check if user already exists
        $existingUser = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $existingUser->execute(['email' => $userData['email']]);
        
        if ($existingUser->fetch()) {
            throw new \Exception('User with this email already exists');
        }

        // Hash password
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

        // Create user
        $userId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        
        $stmt = $this->db->prepare("
            INSERT INTO users (id, email, password_hash, base_currency, first_name, last_name, phone)
            VALUES (:id, :email, :password_hash, :base_currency, :first_name, :last_name, :phone)
        ");

        $stmt->execute([
            'id' => $userId,
            'email' => $userData['email'],
            'password_hash' => $passwordHash,
            'base_currency' => $userData['base_currency'] ?? 'IDR',
            'first_name' => $userData['first_name'] ?? null,
            'last_name' => $userData['last_name'] ?? null,
            'phone' => $userData['phone'] ?? null,
        ]);

        // Create default accounts for new user
        $this->createDefaultAccounts($userId);

        // Assign free plan by default
        $this->assignDefaultSubscription($userId);

        return $userId;
    }

    public function login(string $email, string $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new \Exception('Invalid email or password');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new \Exception('Invalid email or password');
        }

        // Generate JWT token
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + ($this->config->get('jwt.ttl', 60) * 60), // Valid for 60 minutes
            'uid' => $user['id'],
            'email' => $user['email']
        ];

        $jwt = JWT::encode(
            $payload, 
            $this->config->get('jwt.secret', 'your-secret-key'), 
            'HS256'
        );

        return [
            'token' => $jwt,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'base_currency' => $user['base_currency'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ];
    }

    public function validateToken(string $token)
    {
        try {
            $decoded = JWT::decode(
                $token, 
                new Key($this->config->get('jwt.secret', 'your-secret-key'), 'HS256')
            );
            
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    private function createDefaultAccounts(string $userId)
    {
        // Get default account types
        $stmt = $this->db->prepare("SELECT id, name FROM account_types");
        $stmt->execute();
        $accountTypes = $stmt->fetchAll();

        // Create one account of each type with zero balance
        foreach ($accountTypes as $type) {
            $accountId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            
            $stmt = $this->db->prepare("
                INSERT INTO accounts (id, user_id, account_type_id, name, balance, currency)
                VALUES (:id, :user_id, :account_type_id, :name, :balance, :currency)
            ");

            $stmt->execute([
                'id' => $accountId,
                'user_id' => $userId,
                'account_type_id' => $type['id'],
                'name' => 'My ' . $type['name'],
                'balance' => 0.00,
                'currency' => 'IDR' // Default currency, will be overridden by user's base currency
            ]);
        }
    }

    private function assignDefaultSubscription(string $userId)
    {
        // Get free plan
        $stmt = $this->db->prepare("SELECT id FROM subscription_plans WHERE name = 'Free' LIMIT 1");
        $stmt->execute();
        $plan = $stmt->fetch();

        if ($plan) {
            $subscriptionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            
            $stmt = $this->db->prepare("
                INSERT INTO user_subscriptions (id, user_id, plan_id, start_date, is_active)
                VALUES (:id, :user_id, :plan_id, :start_date, :is_active)
            ");

            $stmt->execute([
                'id' => $subscriptionId,
                'user_id' => $userId,
                'plan_id' => $plan['id'],
                'start_date' => date('Y-m-d'),
                'is_active' => true
            ]);
        }
    }

    public function logout(string $token)
    {
        // In a more advanced implementation, we would blacklist the token
        // For now, we'll just return success as JWT is stateless
        return true;
    }
}
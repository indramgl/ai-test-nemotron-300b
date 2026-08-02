<?php
namespace App\Core;

use Dotenv\Dotenv;

class Config
{
    private static $instance = null;
    private $settings = [];

    private function __construct()
    {
        $this->loadEnvironment();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnvironment()
    {
        // Use Dotenv library to load .env file into $_ENV
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        // Also parse the file directly for nested keys support
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $parsed = parse_ini_file($envFile, true);
            $this->settings = array_merge($this->settings, $parsed);
        }

        // Override with actual $_ENV if available (from Dotenv library)
        foreach ($_ENV as $key => $value) {
            $this->settings[$key] = $value;
            
            // Also map flat keys to nested format (e.g., DB_DRIVER -> database.driver)
            $this->mapFlatKeyToNested($key, $value);
        }
    }

    private function mapFlatKeyToNested($key, $value)
    {
        $mapping = [
            'DB_DRIVER' => 'database.driver',
            'DB_HOST' => 'database.host',
            'DB_PORT' => 'database.port',
            'DB_NAME' => 'database.name',
            'DB_USER' => 'database.username',
            'DB_PASS' => 'database.password',
            'DB_CHARSET' => 'database.charset',
            'APP_NAME' => 'app.name',
            'APP_ENV' => 'app.env',
            'APP_KEY' => 'app.key',
            'APP_DEBUG' => 'app.debug',
            'APP_URL' => 'app.url',
            'JWT_SECRET' => 'jwt.secret',
            'JWT_TTL' => 'jwt.ttl',
        ];

        if (isset($mapping[$key])) {
            $this->setNested($mapping[$key], $value);
        }
    }

    private function setNested($key, $value)
    {
        $keys = explode('.', $key);
        $current = &$this->settings;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
                break;
            }
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
    }

    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $current = $this->settings;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return $default;
            }
        }

        return $current;
    }

    public function set($key, $value)
    {
        $this->setNested($key, $value);
    }
}
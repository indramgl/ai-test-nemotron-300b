<?php
namespace App\Core;

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
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $dotenv = parse_ini_file($envFile, true);
            $this->settings = array_merge($this->settings, $dotenv);
        }

        // Override with actual $_ENV if available (from Dotenv library)
        foreach ($_ENV as $key => $value) {
            $this->settings[$key] = $value;
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
        $keys = explode('.', $key);
        $current = &$this->settings;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
                break;
            }
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
    }
}
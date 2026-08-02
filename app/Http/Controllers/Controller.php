<?php
namespace App\Http\Controllers;

class Controller
{
    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function render($view, $data = [])
    {
        // Extract data to variables
        extract($data);
        
        // Set content type
        header('Content-Type: text/html; charset=utf-8');
        
        // Include the view file
        $viewFile = __DIR__ . "/../../views/{$view}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            // Fallback to a simple message
            echo "<h1>View not found: {$view}</h1>";
        }
        exit;
    }
}
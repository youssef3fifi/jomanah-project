<?php
/**
 * Router for PHP Built-in Server
 * Emulates Apache .htaccess URL rewriting
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Route API requests to appropriate files
if (preg_match('/^\/api\/medicines(\/\d+)?/', $uri)) {
    chdir(__DIR__ . '/api');
    require __DIR__ . '/api/medicines.php';
    return true;
}

if (preg_match('/^\/api\/customers(\/\d+)?/', $uri)) {
    chdir(__DIR__ . '/api');
    require __DIR__ . '/api/customers.php';
    return true;
}

if (preg_match('/^\/api\/sales(\/\d+)?/', $uri)) {
    chdir(__DIR__ . '/api');
    require __DIR__ . '/api/sales.php';
    return true;
}

if (preg_match('/^\/api\/reports\/(inventory|sales|expiring|dashboard)/', $uri)) {
    chdir(__DIR__ . '/api');
    require __DIR__ . '/api/reports.php';
    return true;
}

// Default behavior for static files and index
return false;

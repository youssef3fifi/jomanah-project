<?php
/**
 * Helper Functions
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate required fields
 */
function validateRequiredFields($data, $requiredFields) {
    $errors = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }
    return $errors;
}

/**
 * Send JSON response
 */
function sendResponse($statusCode, $success, $message, $data = null) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

/**
 * Send error response
 */
function sendError($statusCode, $message) {
    sendResponse($statusCode, false, $message);
}

/**
 * Send success response
 */
function sendSuccess($message, $data = null) {
    sendResponse(200, true, $message, $data);
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get JSON input
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate numeric
 */
function validateNumeric($value) {
    return is_numeric($value) && $value >= 0;
}

/**
 * Get pagination parameters
 */
function getPaginationParams() {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

/**
 * Build pagination response
 */
function buildPaginationResponse($data, $total, $page, $limit) {
    return [
        'items' => $data,
        'pagination' => [
            'current_page' => $page,
            'total_items' => $total,
            'items_per_page' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ];
}

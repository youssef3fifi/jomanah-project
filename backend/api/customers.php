<?php
/**
 * Customers API Endpoint
 * Handles CRUD operations for customers
 */

require_once '../config/cors.php';
require_once '../config/storage.php';
require_once '../includes/functions.php';

try {
    $method = getRequestMethod();
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Extract ID from URL if present
    preg_match('/\/customers\/(\d+)/', $requestUri, $matches);
    $id = isset($matches[1]) ? intval($matches[1]) : null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single customer with purchase history
                getCustomer($id);
            } else {
                // Get all customers
                getCustomers();
            }
            break;
            
        case 'POST':
            // Create new customer
            createCustomer();
            break;
            
        case 'PUT':
            // Update customer
            if ($id) {
                updateCustomer($id);
            } else {
                sendError(400, "Customer ID is required");
            }
            break;
            
        case 'DELETE':
            // Delete customer
            if ($id) {
                deleteCustomer($id);
            } else {
                sendError(400, "Customer ID is required");
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
    
} catch (Exception $e) {
    sendError(500, $e->getMessage());
}

/**
 * Get all customers
 */
function getCustomers() {
    $pagination = getPaginationParams();
    $customers = $_SESSION['customers'];
    
    // Apply filters
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = strtolower($_GET['search']);
        $customers = array_filter($customers, function($customer) use ($search) {
            return stripos($customer['name'], $search) !== false ||
                   stripos($customer['phone'], $search) !== false ||
                   (isset($customer['email']) && stripos($customer['email'], $search) !== false);
        });
    }
    
    // Add purchase statistics
    foreach ($customers as &$customer) {
        $purchases = array_filter($_SESSION['sales'], function($sale) use ($customer) {
            return isset($sale['customer_id']) && $sale['customer_id'] == $customer['id'];
        });
        $customer['total_purchases'] = count($purchases);
        $customer['total_spent'] = array_reduce($purchases, function($sum, $sale) {
            return $sum + $sale['total_amount'];
        }, 0);
    }
    
    // Sort by name
    usort($customers, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // Count total
    $total = count($customers);
    
    // Apply pagination
    $customers = array_slice($customers, $pagination['offset'], $pagination['limit']);
    
    $response = buildPaginationResponse($customers, $total, $pagination['page'], $pagination['limit']);
    sendSuccess("Customers retrieved successfully", $response);
}

/**
 * Get single customer with purchase history
 */
function getCustomer($id) {
    $key = findById($_SESSION['customers'], $id);
    
    if ($key === false) {
        sendError(404, "Customer not found");
    }
    
    $customer = $_SESSION['customers'][$key];
    
    // Get purchase statistics
    $purchases = array_filter($_SESSION['sales'], function($sale) use ($id) {
        return isset($sale['customer_id']) && $sale['customer_id'] == $id;
    });
    
    $customer['total_purchases'] = count($purchases);
    $customer['total_spent'] = array_reduce($purchases, function($sum, $sale) {
        return $sum + $sale['total_amount'];
    }, 0);
    
    // Get purchase history (last 10)
    $history = array_map(function($sale) {
        return [
            'id' => $sale['id'],
            'total_amount' => $sale['total_amount'],
            'payment_method' => $sale['payment_method'],
            'sale_date' => $sale['sale_date']
        ];
    }, $purchases);
    
    // Sort by sale_date descending and limit to 10
    usort($history, function($a, $b) {
        return strcmp($b['sale_date'], $a['sale_date']);
    });
    $customer['purchase_history'] = array_slice($history, 0, 10);
    
    sendSuccess("Customer retrieved successfully", $customer);
}

/**
 * Create new customer
 */
function createCustomer() {
    $data = getJsonInput();
    
    // Validate required fields
    $requiredFields = ['name', 'phone'];
    $errors = validateRequiredFields($data, $requiredFields);
    
    if (!empty($errors)) {
        sendError(400, implode(', ', $errors));
    }
    
    // Validate email if provided
    if (isset($data['email']) && !empty($data['email']) && !validateEmail($data['email'])) {
        sendError(400, "Invalid email format");
    }
    
    // Check for duplicate phone
    foreach ($_SESSION['customers'] as $customer) {
        if ($customer['phone'] === $data['phone']) {
            sendError(400, "Customer with this phone number already exists");
        }
    }
    
    // Create new customer
    $newCustomer = [
        'id' => getNextId('customer'),
        'name' => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'] ?? null,
        'address' => $data['address'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $_SESSION['customers'][] = $newCustomer;
    sendSuccess("Customer created successfully", $newCustomer);
}

/**
 * Update customer
 */
function updateCustomer($id) {
    $data = getJsonInput();
    
    // Check if customer exists
    $key = findById($_SESSION['customers'], $id);
    if ($key === false) {
        sendError(404, "Customer not found");
    }
    
    // Validate email if provided
    if (isset($data['email']) && !empty($data['email']) && !validateEmail($data['email'])) {
        sendError(400, "Invalid email format");
    }
    
    // Check for duplicate phone if updating phone
    if (isset($data['phone'])) {
        foreach ($_SESSION['customers'] as $customer) {
            if ($customer['phone'] === $data['phone'] && $customer['id'] != $id) {
                sendError(400, "Customer with this phone number already exists");
            }
        }
    }
    
    // Update allowed fields
    $allowedFields = ['name', 'phone', 'email', 'address'];
    $updated = false;
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $_SESSION['customers'][$key][$field] = $data[$field];
            $updated = true;
        }
    }
    
    if (!$updated) {
        sendError(400, "No fields to update");
    }
    
    sendSuccess("Customer updated successfully", ['id' => $id]);
}

/**
 * Delete customer
 */
function deleteCustomer($id) {
    // Check if customer exists
    $key = findById($_SESSION['customers'], $id);
    if ($key === false) {
        sendError(404, "Customer not found");
    }
    
    // Check if customer has sales
    $hasSales = false;
    foreach ($_SESSION['sales'] as $sale) {
        if (isset($sale['customer_id']) && $sale['customer_id'] == $id) {
            $hasSales = true;
            break;
        }
    }
    
    if ($hasSales) {
        sendError(400, "Cannot delete customer with existing sales records");
    }
    
    // Delete customer
    array_splice($_SESSION['customers'], $key, 1);
    sendSuccess("Customer deleted successfully");
}

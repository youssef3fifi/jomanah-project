<?php
/**
 * Customers API Endpoint
 * Handles CRUD operations for customers
 */

require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = getRequestMethod();
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Extract ID from URL if present
    preg_match('/\/customers\/(\d+)/', $requestUri, $matches);
    $id = isset($matches[1]) ? intval($matches[1]) : null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single customer with purchase history
                getCustomer($db, $id);
            } else {
                // Get all customers
                getCustomers($db);
            }
            break;
            
        case 'POST':
            // Create new customer
            createCustomer($db);
            break;
            
        case 'PUT':
            // Update customer
            if ($id) {
                updateCustomer($db, $id);
            } else {
                sendError(400, "Customer ID is required");
            }
            break;
            
        case 'DELETE':
            // Delete customer
            if ($id) {
                deleteCustomer($db, $id);
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
function getCustomers($db) {
    $pagination = getPaginationParams();
    
    // Build query with filters
    $where = [];
    $params = [];
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $where[] = "(name LIKE :search OR phone LIKE :search OR email LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM customers $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];
    
    // Get data with pagination
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM sales WHERE customer_id = c.id) as total_purchases,
              (SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE customer_id = c.id) as total_spent
              FROM customers c $whereClause 
              ORDER BY c.name ASC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $customers = $stmt->fetchAll();
    
    $response = buildPaginationResponse($customers, $total, $pagination['page'], $pagination['limit']);
    sendSuccess("Customers retrieved successfully", $response);
}

/**
 * Get single customer with purchase history
 */
function getCustomer($db, $id) {
    // Get customer details
    $query = "SELECT c.*, 
              (SELECT COUNT(*) FROM sales WHERE customer_id = c.id) as total_purchases,
              (SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE customer_id = c.id) as total_spent
              FROM customers c WHERE c.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $customer = $stmt->fetch();
    
    if (!$customer) {
        sendError(404, "Customer not found");
    }
    
    // Get purchase history
    $historyQuery = "SELECT s.id, s.total_amount, s.payment_method, s.sale_date
                     FROM sales s
                     WHERE s.customer_id = :id
                     ORDER BY s.sale_date DESC
                     LIMIT 10";
    
    $historyStmt = $db->prepare($historyQuery);
    $historyStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $historyStmt->execute();
    
    $customer['purchase_history'] = $historyStmt->fetchAll();
    
    sendSuccess("Customer retrieved successfully", $customer);
}

/**
 * Create new customer
 */
function createCustomer($db) {
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
    $checkQuery = "SELECT id FROM customers WHERE phone = :phone";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':phone', $data['phone']);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        sendError(400, "Customer with this phone number already exists");
    }
    
    // Insert customer
    $query = "INSERT INTO customers (name, phone, email, address) 
              VALUES (:name, :phone, :email, :address)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':phone', $data['phone']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':address', $data['address']);
    
    if ($stmt->execute()) {
        $data['id'] = $db->lastInsertId();
        sendSuccess("Customer created successfully", $data);
    } else {
        sendError(500, "Failed to create customer");
    }
}

/**
 * Update customer
 */
function updateCustomer($db, $id) {
    $data = getJsonInput();
    
    // Check if customer exists
    $checkQuery = "SELECT id FROM customers WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        sendError(404, "Customer not found");
    }
    
    // Validate email if provided
    if (isset($data['email']) && !empty($data['email']) && !validateEmail($data['email'])) {
        sendError(400, "Invalid email format");
    }
    
    // Check for duplicate phone if updating phone
    if (isset($data['phone'])) {
        $phoneQuery = "SELECT id FROM customers WHERE phone = :phone AND id != :id";
        $phoneStmt = $db->prepare($phoneQuery);
        $phoneStmt->bindParam(':phone', $data['phone']);
        $phoneStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $phoneStmt->execute();
        
        if ($phoneStmt->fetch()) {
            sendError(400, "Customer with this phone number already exists");
        }
    }
    
    // Build update query
    $fields = [];
    $params = [':id' => $id];
    
    $allowedFields = ['name', 'phone', 'email', 'address'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        sendError(400, "No fields to update");
    }
    
    $query = "UPDATE customers SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        sendSuccess("Customer updated successfully", ['id' => $id]);
    } else {
        sendError(500, "Failed to update customer");
    }
}

/**
 * Delete customer
 */
function deleteCustomer($db, $id) {
    // Check if customer exists
    $checkQuery = "SELECT id FROM customers WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        sendError(404, "Customer not found");
    }
    
    // Check if customer has sales
    $salesQuery = "SELECT COUNT(*) as count FROM sales WHERE customer_id = :id";
    $salesStmt = $db->prepare($salesQuery);
    $salesStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $salesStmt->execute();
    $salesCount = $salesStmt->fetch()['count'];
    
    if ($salesCount > 0) {
        sendError(400, "Cannot delete customer with existing sales records");
    }
    
    // Delete customer
    $query = "DELETE FROM customers WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        sendSuccess("Customer deleted successfully");
    } else {
        sendError(500, "Failed to delete customer");
    }
}

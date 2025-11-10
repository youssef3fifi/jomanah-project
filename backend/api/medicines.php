<?php
/**
 * Medicines API Endpoint
 * Handles CRUD operations for medicines
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
    preg_match('/\/medicines\/(\d+)/', $requestUri, $matches);
    $id = isset($matches[1]) ? intval($matches[1]) : null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single medicine
                getMedicine($db, $id);
            } else {
                // Get all medicines with optional filters
                getMedicines($db);
            }
            break;
            
        case 'POST':
            // Create new medicine
            createMedicine($db);
            break;
            
        case 'PUT':
            // Update medicine
            if ($id) {
                updateMedicine($db, $id);
            } else {
                sendError(400, "Medicine ID is required");
            }
            break;
            
        case 'DELETE':
            // Delete medicine
            if ($id) {
                deleteMedicine($db, $id);
            } else {
                sendError(400, "Medicine ID is required");
            }
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
    
} catch (Exception $e) {
    sendError(500, $e->getMessage());
}

/**
 * Get all medicines
 */
function getMedicines($db) {
    $pagination = getPaginationParams();
    
    // Build query with filters
    $where = [];
    $params = [];
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $where[] = "(name LIKE :search OR category LIKE :search OR supplier LIKE :search)";
        $params[':search'] = '%' . $_GET['search'] . '%';
    }
    
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $where[] = "category = :category";
        $params[':category'] = $_GET['category'];
    }
    
    if (isset($_GET['low_stock']) && $_GET['low_stock'] == 'true') {
        $where[] = "stock_quantity < 50";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM medicines $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];
    
    // Get data with pagination
    $query = "SELECT * FROM medicines $whereClause ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $medicines = $stmt->fetchAll();
    
    $response = buildPaginationResponse($medicines, $total, $pagination['page'], $pagination['limit']);
    sendSuccess("Medicines retrieved successfully", $response);
}

/**
 * Get single medicine
 */
function getMedicine($db, $id) {
    $query = "SELECT * FROM medicines WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $medicine = $stmt->fetch();
    
    if ($medicine) {
        sendSuccess("Medicine retrieved successfully", $medicine);
    } else {
        sendError(404, "Medicine not found");
    }
}

/**
 * Create new medicine
 */
function createMedicine($db) {
    $data = getJsonInput();
    
    // Validate required fields
    $requiredFields = ['name', 'price', 'stock_quantity'];
    $errors = validateRequiredFields($data, $requiredFields);
    
    if (!empty($errors)) {
        sendError(400, implode(', ', $errors));
    }
    
    // Validate data types
    if (!validateNumeric($data['price'])) {
        sendError(400, "Price must be a valid number");
    }
    
    if (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0) {
        sendError(400, "Stock quantity must be a valid non-negative number");
    }
    
    if (isset($data['expiry_date']) && !empty($data['expiry_date']) && !validateDate($data['expiry_date'])) {
        sendError(400, "Expiry date must be in YYYY-MM-DD format");
    }
    
    // Insert medicine
    $query = "INSERT INTO medicines (name, category, price, stock_quantity, expiry_date, supplier, description) 
              VALUES (:name, :category, :price, :stock_quantity, :expiry_date, :supplier, :description)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':category', $data['category']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':stock_quantity', $data['stock_quantity']);
    $stmt->bindParam(':expiry_date', $data['expiry_date']);
    $stmt->bindParam(':supplier', $data['supplier']);
    $stmt->bindParam(':description', $data['description']);
    
    if ($stmt->execute()) {
        $data['id'] = $db->lastInsertId();
        sendSuccess("Medicine created successfully", $data);
    } else {
        sendError(500, "Failed to create medicine");
    }
}

/**
 * Update medicine
 */
function updateMedicine($db, $id) {
    $data = getJsonInput();
    
    // Check if medicine exists
    $checkQuery = "SELECT id FROM medicines WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        sendError(404, "Medicine not found");
    }
    
    // Validate data types if provided
    if (isset($data['price']) && !validateNumeric($data['price'])) {
        sendError(400, "Price must be a valid number");
    }
    
    if (isset($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
        sendError(400, "Stock quantity must be a valid non-negative number");
    }
    
    if (isset($data['expiry_date']) && !empty($data['expiry_date']) && !validateDate($data['expiry_date'])) {
        sendError(400, "Expiry date must be in YYYY-MM-DD format");
    }
    
    // Build update query
    $fields = [];
    $params = [':id' => $id];
    
    $allowedFields = ['name', 'category', 'price', 'stock_quantity', 'expiry_date', 'supplier', 'description'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        sendError(400, "No fields to update");
    }
    
    $query = "UPDATE medicines SET " . implode(', ', $fields) . " WHERE id = :id";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        sendSuccess("Medicine updated successfully", ['id' => $id]);
    } else {
        sendError(500, "Failed to update medicine");
    }
}

/**
 * Delete medicine
 */
function deleteMedicine($db, $id) {
    // Check if medicine exists
    $checkQuery = "SELECT id FROM medicines WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        sendError(404, "Medicine not found");
    }
    
    // Check if medicine is used in any sales
    $salesQuery = "SELECT COUNT(*) as count FROM sale_items WHERE medicine_id = :id";
    $salesStmt = $db->prepare($salesQuery);
    $salesStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $salesStmt->execute();
    $salesCount = $salesStmt->fetch()['count'];
    
    if ($salesCount > 0) {
        sendError(400, "Cannot delete medicine that has sales records");
    }
    
    // Delete medicine
    $query = "DELETE FROM medicines WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        sendSuccess("Medicine deleted successfully");
    } else {
        sendError(500, "Failed to delete medicine");
    }
}

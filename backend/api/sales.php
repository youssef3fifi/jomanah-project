<?php
/**
 * Sales API Endpoint
 * Handles sales transactions
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
    preg_match('/\/sales\/(\d+)/', $requestUri, $matches);
    $id = isset($matches[1]) ? intval($matches[1]) : null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single sale with details
                getSale($db, $id);
            } else {
                // Get all sales with filters
                getSales($db);
            }
            break;
            
        case 'POST':
            // Create new sale
            createSale($db);
            break;
            
        default:
            sendError(405, "Method not allowed");
    }
    
} catch (Exception $e) {
    sendError(500, $e->getMessage());
}

/**
 * Get all sales
 */
function getSales($db) {
    $pagination = getPaginationParams();
    
    // Build query with filters
    $where = [];
    $params = [];
    
    if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
        $where[] = "s.customer_id = :customer_id";
        $params[':customer_id'] = $_GET['customer_id'];
    }
    
    if (isset($_GET['payment_method']) && !empty($_GET['payment_method'])) {
        $where[] = "s.payment_method = :payment_method";
        $params[':payment_method'] = $_GET['payment_method'];
    }
    
    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
        $where[] = "DATE(s.sale_date) >= :date_from";
        $params[':date_from'] = $_GET['date_from'];
    }
    
    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
        $where[] = "DATE(s.sale_date) <= :date_to";
        $params[':date_to'] = $_GET['date_to'];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total
    $countQuery = "SELECT COUNT(*) as total FROM sales s $whereClause";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];
    
    // Get data with pagination
    $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone
              FROM sales s
              LEFT JOIN customers c ON s.customer_id = c.id
              $whereClause
              ORDER BY s.sale_date DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    
    $sales = $stmt->fetchAll();
    
    $response = buildPaginationResponse($sales, $total, $pagination['page'], $pagination['limit']);
    sendSuccess("Sales retrieved successfully", $response);
}

/**
 * Get single sale with items
 */
function getSale($db, $id) {
    // Get sale details
    $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email
              FROM sales s
              LEFT JOIN customers c ON s.customer_id = c.id
              WHERE s.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $sale = $stmt->fetch();
    
    if (!$sale) {
        sendError(404, "Sale not found");
    }
    
    // Get sale items
    $itemsQuery = "SELECT si.*, m.name as medicine_name, m.category
                   FROM sale_items si
                   JOIN medicines m ON si.medicine_id = m.id
                   WHERE si.sale_id = :id";
    
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $itemsStmt->execute();
    
    $sale['items'] = $itemsStmt->fetchAll();
    
    sendSuccess("Sale retrieved successfully", $sale);
}

/**
 * Create new sale
 */
function createSale($db) {
    $data = getJsonInput();
    
    // Validate required fields
    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        sendError(400, "Sale items are required");
    }
    
    if (!isset($data['payment_method']) || empty($data['payment_method'])) {
        sendError(400, "Payment method is required");
    }
    
    if (!in_array($data['payment_method'], ['cash', 'card', 'insurance'])) {
        sendError(400, "Invalid payment method");
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        $totalAmount = 0;
        $saleItems = [];
        
        // Validate and prepare sale items
        foreach ($data['items'] as $item) {
            if (!isset($item['medicine_id']) || !isset($item['quantity'])) {
                throw new Exception("Each item must have medicine_id and quantity");
            }
            
            if (!is_numeric($item['quantity']) || $item['quantity'] <= 0) {
                throw new Exception("Quantity must be a positive number");
            }
            
            // Get medicine details and check stock
            $medicineQuery = "SELECT id, name, price, stock_quantity FROM medicines WHERE id = :id";
            $medicineStmt = $db->prepare($medicineQuery);
            $medicineStmt->bindParam(':id', $item['medicine_id'], PDO::PARAM_INT);
            $medicineStmt->execute();
            $medicine = $medicineStmt->fetch();
            
            if (!$medicine) {
                throw new Exception("Medicine with ID {$item['medicine_id']} not found");
            }
            
            if ($medicine['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for {$medicine['name']}. Available: {$medicine['stock_quantity']}");
            }
            
            $subtotal = $medicine['price'] * $item['quantity'];
            $totalAmount += $subtotal;
            
            $saleItems[] = [
                'medicine_id' => $item['medicine_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $medicine['price'],
                'subtotal' => $subtotal
            ];
        }
        
        // Insert sale
        $saleQuery = "INSERT INTO sales (customer_id, total_amount, payment_method) 
                      VALUES (:customer_id, :total_amount, :payment_method)";
        
        $saleStmt = $db->prepare($saleQuery);
        $customerId = isset($data['customer_id']) && !empty($data['customer_id']) ? $data['customer_id'] : null;
        $saleStmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
        $saleStmt->bindParam(':total_amount', $totalAmount);
        $saleStmt->bindParam(':payment_method', $data['payment_method']);
        $saleStmt->execute();
        
        $saleId = $db->lastInsertId();
        
        // Insert sale items and update stock
        foreach ($saleItems as $item) {
            // Insert sale item
            $itemQuery = "INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal)
                         VALUES (:sale_id, :medicine_id, :quantity, :unit_price, :subtotal)";
            
            $itemStmt = $db->prepare($itemQuery);
            $itemStmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
            $itemStmt->bindParam(':medicine_id', $item['medicine_id'], PDO::PARAM_INT);
            $itemStmt->bindParam(':quantity', $item['quantity']);
            $itemStmt->bindParam(':unit_price', $item['unit_price']);
            $itemStmt->bindParam(':subtotal', $item['subtotal']);
            $itemStmt->execute();
            
            // Update stock
            $updateStockQuery = "UPDATE medicines SET stock_quantity = stock_quantity - :quantity 
                                WHERE id = :medicine_id";
            $updateStockStmt = $db->prepare($updateStockQuery);
            $updateStockStmt->bindParam(':quantity', $item['quantity']);
            $updateStockStmt->bindParam(':medicine_id', $item['medicine_id'], PDO::PARAM_INT);
            $updateStockStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        $result = [
            'id' => $saleId,
            'total_amount' => $totalAmount,
            'payment_method' => $data['payment_method'],
            'items_count' => count($saleItems)
        ];
        
        sendSuccess("Sale created successfully", $result);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        sendError(400, $e->getMessage());
    }
}

<?php
/**
 * Sales API Endpoint
 * Handles sales transactions
 */

require_once '../config/cors.php';
require_once '../config/storage.php';
require_once '../includes/functions.php';

try {
    $method = getRequestMethod();
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Extract ID from URL if present
    preg_match('/\/sales\/(\d+)/', $requestUri, $matches);
    $id = isset($matches[1]) ? intval($matches[1]) : null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single sale with details
                getSale($id);
            } else {
                // Get all sales with filters
                getSales();
            }
            break;
            
        case 'POST':
            // Create new sale
            createSale();
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
function getSales() {
    $pagination = getPaginationParams();
    $sales = $_SESSION['sales'];
    
    // Apply filters
    if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
        $customerId = intval($_GET['customer_id']);
        $sales = array_filter($sales, function($sale) use ($customerId) {
            return isset($sale['customer_id']) && $sale['customer_id'] == $customerId;
        });
    }
    
    if (isset($_GET['payment_method']) && !empty($_GET['payment_method'])) {
        $paymentMethod = $_GET['payment_method'];
        $sales = array_filter($sales, function($sale) use ($paymentMethod) {
            return strcasecmp($sale['payment_method'], $paymentMethod) === 0;
        });
    }
    
    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
        $dateFrom = $_GET['date_from'];
        $sales = array_filter($sales, function($sale) use ($dateFrom) {
            return substr($sale['sale_date'], 0, 10) >= $dateFrom;
        });
    }
    
    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
        $dateTo = $_GET['date_to'];
        $sales = array_filter($sales, function($sale) use ($dateTo) {
            return substr($sale['sale_date'], 0, 10) <= $dateTo;
        });
    }
    
    // Add customer info
    foreach ($sales as &$sale) {
        if (isset($sale['customer_id'])) {
            $customerKey = findById($_SESSION['customers'], $sale['customer_id']);
            if ($customerKey !== false) {
                $sale['customer_phone'] = $_SESSION['customers'][$customerKey]['phone'];
            }
        }
    }
    
    // Sort by sale_date descending
    usort($sales, function($a, $b) {
        return strcmp($b['sale_date'], $a['sale_date']);
    });
    
    // Count total
    $total = count($sales);
    
    // Apply pagination
    $sales = array_slice($sales, $pagination['offset'], $pagination['limit']);
    
    $response = buildPaginationResponse($sales, $total, $pagination['page'], $pagination['limit']);
    sendSuccess("Sales retrieved successfully", $response);
}

/**
 * Get single sale with items
 */
function getSale($id) {
    $key = findById($_SESSION['sales'], $id);
    
    if ($key === false) {
        sendError(404, "Sale not found");
    }
    
    $sale = $_SESSION['sales'][$key];
    
    // Add customer details if customer_id is set
    if (isset($sale['customer_id'])) {
        $customerKey = findById($_SESSION['customers'], $sale['customer_id']);
        if ($customerKey !== false) {
            $customer = $_SESSION['customers'][$customerKey];
            $sale['customer_phone'] = $customer['phone'];
            $sale['customer_email'] = $customer['email'] ?? null;
        }
    }
    
    // Add medicine details to items
    if (isset($sale['items'])) {
        foreach ($sale['items'] as &$item) {
            $medicineKey = findById($_SESSION['medicines'], $item['medicine_id']);
            if ($medicineKey !== false) {
                $item['category'] = $_SESSION['medicines'][$medicineKey]['category'];
            }
        }
    }
    
    sendSuccess("Sale retrieved successfully", $sale);
}

/**
 * Create new sale
 */
function createSale() {
    $data = getJsonInput();
    
    // Validate required fields
    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        sendError(400, "Sale items are required");
    }
    
    if (!isset($data['payment_method']) || empty($data['payment_method'])) {
        sendError(400, "Payment method is required");
    }
    
    $paymentMethod = strtolower($data['payment_method']);
    if (!in_array($paymentMethod, ['cash', 'card', 'insurance', 'credit card'])) {
        sendError(400, "Invalid payment method");
    }
    
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
            $medicineKey = findById($_SESSION['medicines'], $item['medicine_id']);
            
            if ($medicineKey === false) {
                throw new Exception("Medicine with ID {$item['medicine_id']} not found");
            }
            
            $medicine = $_SESSION['medicines'][$medicineKey];
            
            if ($medicine['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for {$medicine['name']}. Available: {$medicine['stock_quantity']}");
            }
            
            $subtotal = $medicine['price'] * $item['quantity'];
            $totalAmount += $subtotal;
            
            $saleItems[] = [
                'medicine_id' => $item['medicine_id'],
                'medicine_name' => $medicine['name'],
                'quantity' => intval($item['quantity']),
                'unit_price' => floatval($medicine['price']),
                'subtotal' => floatval($subtotal)
            ];
        }
        
        // Get customer name if customer_id is provided
        $customerName = null;
        $customerId = isset($data['customer_id']) && !empty($data['customer_id']) ? intval($data['customer_id']) : null;
        
        if ($customerId) {
            $customerKey = findById($_SESSION['customers'], $customerId);
            if ($customerKey !== false) {
                $customerName = $_SESSION['customers'][$customerKey]['name'];
            }
        }
        
        // Create new sale
        $newSale = [
            'id' => getNextId('sale'),
            'customer_id' => $customerId,
            'customer_name' => $customerName,
            'total_amount' => floatval($totalAmount),
            'payment_method' => ucwords($paymentMethod),
            'sale_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'items' => $saleItems
        ];
        
        // Update stock for each item
        foreach ($saleItems as $item) {
            $medicineKey = findById($_SESSION['medicines'], $item['medicine_id']);
            $_SESSION['medicines'][$medicineKey]['stock_quantity'] -= $item['quantity'];
        }
        
        // Add sale to session
        $_SESSION['sales'][] = $newSale;
        
        $result = [
            'id' => $newSale['id'],
            'total_amount' => $newSale['total_amount'],
            'payment_method' => $newSale['payment_method'],
            'items_count' => count($saleItems)
        ];
        
        sendSuccess("Sale created successfully", $result);
        
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }
}

<?php
/**
 * Medicines API Endpoint
 * Handles CRUD operations for medicines
 */

require_once '../config/cors.php';
require_once '../config/storage.php';
require_once '../includes/functions.php';

try {
    $method = getRequestMethod();
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // Extract ID from URL if present
    preg_match('/\/medicines\/(\d+)/', $requestUri, $matches);
    $id = isset($matches[1]) ? intval($matches[1]) : null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single medicine
                getMedicine($id);
            } else {
                // Get all medicines with optional filters
                getMedicines();
            }
            break;
            
        case 'POST':
            // Create new medicine
            createMedicine();
            break;
            
        case 'PUT':
            // Update medicine
            if ($id) {
                updateMedicine($id);
            } else {
                sendError(400, "Medicine ID is required");
            }
            break;
            
        case 'DELETE':
            // Delete medicine
            if ($id) {
                deleteMedicine($id);
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
function getMedicines() {
    $pagination = getPaginationParams();
    $medicines = $_SESSION['medicines'];
    
    // Apply filters
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = strtolower($_GET['search']);
        $medicines = array_filter($medicines, function($medicine) use ($search) {
            return stripos($medicine['name'], $search) !== false ||
                   stripos($medicine['category'], $search) !== false ||
                   stripos($medicine['supplier'], $search) !== false;
        });
    }
    
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category = $_GET['category'];
        $medicines = array_filter($medicines, function($medicine) use ($category) {
            return $medicine['category'] === $category;
        });
    }
    
    if (isset($_GET['low_stock']) && $_GET['low_stock'] == 'true') {
        $medicines = array_filter($medicines, function($medicine) {
            return $medicine['stock_quantity'] < 50;
        });
    }
    
    // Sort by name
    usort($medicines, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // Count total
    $total = count($medicines);
    
    // Apply pagination
    $medicines = array_slice($medicines, $pagination['offset'], $pagination['limit']);
    
    $response = buildPaginationResponse($medicines, $total, $pagination['page'], $pagination['limit']);
    sendSuccess("Medicines retrieved successfully", $response);
}

/**
 * Get single medicine
 */
function getMedicine($id) {
    $key = findById($_SESSION['medicines'], $id);
    
    if ($key !== false) {
        sendSuccess("Medicine retrieved successfully", $_SESSION['medicines'][$key]);
    } else {
        sendError(404, "Medicine not found");
    }
}

/**
 * Create new medicine
 */
function createMedicine() {
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
    
    // Create new medicine
    $newMedicine = [
        'id' => getNextId('medicine'),
        'name' => $data['name'],
        'category' => $data['category'] ?? null,
        'price' => floatval($data['price']),
        'stock_quantity' => intval($data['stock_quantity']),
        'expiry_date' => $data['expiry_date'] ?? null,
        'supplier' => $data['supplier'] ?? null,
        'description' => $data['description'] ?? null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $_SESSION['medicines'][] = $newMedicine;
    sendSuccess("Medicine created successfully", $newMedicine);
}

/**
 * Update medicine
 */
function updateMedicine($id) {
    $data = getJsonInput();
    
    // Check if medicine exists
    $key = findById($_SESSION['medicines'], $id);
    if ($key === false) {
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
    
    // Update allowed fields
    $allowedFields = ['name', 'category', 'price', 'stock_quantity', 'expiry_date', 'supplier', 'description'];
    $updated = false;
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            if ($field === 'price') {
                $_SESSION['medicines'][$key][$field] = floatval($data[$field]);
            } elseif ($field === 'stock_quantity') {
                $_SESSION['medicines'][$key][$field] = intval($data[$field]);
            } else {
                $_SESSION['medicines'][$key][$field] = $data[$field];
            }
            $updated = true;
        }
    }
    
    if (!$updated) {
        sendError(400, "No fields to update");
    }
    
    $_SESSION['medicines'][$key]['updated_at'] = date('Y-m-d H:i:s');
    sendSuccess("Medicine updated successfully", ['id' => $id]);
}

/**
 * Delete medicine
 */
function deleteMedicine($id) {
    // Check if medicine exists
    $key = findById($_SESSION['medicines'], $id);
    if ($key === false) {
        sendError(404, "Medicine not found");
    }
    
    // Check if medicine is used in any sales
    foreach ($_SESSION['sales'] as $sale) {
        foreach ($sale['items'] as $item) {
            if ($item['medicine_id'] == $id) {
                sendError(400, "Cannot delete medicine that has sales records");
            }
        }
    }
    
    // Delete medicine
    array_splice($_SESSION['medicines'], $key, 1);
    sendSuccess("Medicine deleted successfully");
}

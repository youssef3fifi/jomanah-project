<?php
/**
 * Reports API Endpoint
 * Generates various reports for the pharmacy
 */

require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = getRequestMethod();
    $requestUri = $_SERVER['REQUEST_URI'];
    
    if ($method !== 'GET') {
        sendError(405, "Method not allowed");
    }
    
    // Determine report type from URL
    if (strpos($requestUri, '/reports/inventory') !== false) {
        getInventoryReport($db);
    } elseif (strpos($requestUri, '/reports/sales') !== false) {
        getSalesReport($db);
    } elseif (strpos($requestUri, '/reports/expiring') !== false) {
        getExpiringMedicinesReport($db);
    } elseif (strpos($requestUri, '/reports/dashboard') !== false) {
        getDashboardStats($db);
    } else {
        sendError(404, "Report type not found");
    }
    
} catch (Exception $e) {
    sendError(500, $e->getMessage());
}

/**
 * Get inventory report
 */
function getInventoryReport($db) {
    $query = "SELECT 
                COUNT(*) as total_medicines,
                SUM(stock_quantity) as total_stock,
                COUNT(CASE WHEN stock_quantity < 50 THEN 1 END) as low_stock_count,
                COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock_count,
                COUNT(CASE WHEN expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon_count
              FROM medicines";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $summary = $stmt->fetch();
    
    // Get low stock medicines
    $lowStockQuery = "SELECT id, name, category, stock_quantity, price
                      FROM medicines
                      WHERE stock_quantity < 50
                      ORDER BY stock_quantity ASC
                      LIMIT 10";
    
    $lowStockStmt = $db->prepare($lowStockQuery);
    $lowStockStmt->execute();
    $lowStock = $lowStockStmt->fetchAll();
    
    // Get category-wise inventory
    $categoryQuery = "SELECT 
                        category,
                        COUNT(*) as medicine_count,
                        SUM(stock_quantity) as total_stock,
                        SUM(stock_quantity * price) as total_value
                      FROM medicines
                      WHERE category IS NOT NULL
                      GROUP BY category
                      ORDER BY total_value DESC";
    
    $categoryStmt = $db->prepare($categoryQuery);
    $categoryStmt->execute();
    $byCategory = $categoryStmt->fetchAll();
    
    $report = [
        'summary' => $summary,
        'low_stock_items' => $lowStock,
        'by_category' => $byCategory
    ];
    
    sendSuccess("Inventory report generated successfully", $report);
}

/**
 * Get sales report
 */
function getSalesReport($db) {
    // Date filters
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    // Sales summary
    $summaryQuery = "SELECT 
                        COUNT(*) as total_sales,
                        SUM(total_amount) as total_revenue,
                        AVG(total_amount) as average_sale,
                        COUNT(DISTINCT customer_id) as unique_customers
                     FROM sales
                     WHERE DATE(sale_date) BETWEEN :date_from AND :date_to";
    
    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->bindParam(':date_from', $dateFrom);
    $summaryStmt->bindParam(':date_to', $dateTo);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetch();
    
    // Sales by payment method
    $paymentQuery = "SELECT 
                        payment_method,
                        COUNT(*) as count,
                        SUM(total_amount) as total
                     FROM sales
                     WHERE DATE(sale_date) BETWEEN :date_from AND :date_to
                     GROUP BY payment_method";
    
    $paymentStmt = $db->prepare($paymentQuery);
    $paymentStmt->bindParam(':date_from', $dateFrom);
    $paymentStmt->bindParam(':date_to', $dateTo);
    $paymentStmt->execute();
    $byPayment = $paymentStmt->fetchAll();
    
    // Daily sales trend
    $trendQuery = "SELECT 
                      DATE(sale_date) as date,
                      COUNT(*) as sales_count,
                      SUM(total_amount) as revenue
                   FROM sales
                   WHERE DATE(sale_date) BETWEEN :date_from AND :date_to
                   GROUP BY DATE(sale_date)
                   ORDER BY date DESC";
    
    $trendStmt = $db->prepare($trendQuery);
    $trendStmt->bindParam(':date_from', $dateFrom);
    $trendStmt->bindParam(':date_to', $dateTo);
    $trendStmt->execute();
    $trend = $trendStmt->fetchAll();
    
    // Top selling medicines
    $topMedicinesQuery = "SELECT 
                            m.id,
                            m.name,
                            m.category,
                            SUM(si.quantity) as total_quantity_sold,
                            SUM(si.subtotal) as total_revenue
                          FROM sale_items si
                          JOIN medicines m ON si.medicine_id = m.id
                          JOIN sales s ON si.sale_id = s.id
                          WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
                          GROUP BY m.id
                          ORDER BY total_revenue DESC
                          LIMIT 10";
    
    $topMedicinesStmt = $db->prepare($topMedicinesQuery);
    $topMedicinesStmt->bindParam(':date_from', $dateFrom);
    $topMedicinesStmt->bindParam(':date_to', $dateTo);
    $topMedicinesStmt->execute();
    $topMedicines = $topMedicinesStmt->fetchAll();
    
    $report = [
        'summary' => $summary,
        'by_payment_method' => $byPayment,
        'daily_trend' => $trend,
        'top_medicines' => $topMedicines,
        'date_range' => [
            'from' => $dateFrom,
            'to' => $dateTo
        ]
    ];
    
    sendSuccess("Sales report generated successfully", $report);
}

/**
 * Get expiring medicines report
 */
function getExpiringMedicinesReport($db) {
    $days = isset($_GET['days']) ? intval($_GET['days']) : 90;
    
    $query = "SELECT 
                id, 
                name, 
                category, 
                price, 
                stock_quantity, 
                expiry_date,
                DATEDIFF(expiry_date, CURDATE()) as days_until_expiry
              FROM medicines
              WHERE expiry_date IS NOT NULL 
                AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
              ORDER BY expiry_date ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    
    $medicines = $stmt->fetchAll();
    
    // Categorize by urgency
    $expired = [];
    $expiringSoon = []; // 0-30 days
    $expiringLater = []; // 31-90 days
    
    foreach ($medicines as $medicine) {
        if ($medicine['days_until_expiry'] < 0) {
            $expired[] = $medicine;
        } elseif ($medicine['days_until_expiry'] <= 30) {
            $expiringSoon[] = $medicine;
        } else {
            $expiringLater[] = $medicine;
        }
    }
    
    $report = [
        'summary' => [
            'expired_count' => count($expired),
            'expiring_soon_count' => count($expiringSoon),
            'expiring_later_count' => count($expiringLater),
            'total_count' => count($medicines)
        ],
        'expired' => $expired,
        'expiring_soon' => $expiringSoon,
        'expiring_later' => $expiringLater
    ];
    
    sendSuccess("Expiring medicines report generated successfully", $report);
}

/**
 * Get dashboard statistics
 */
function getDashboardStats($db) {
    // Today's sales
    $todayQuery = "SELECT 
                    COUNT(*) as sales_count,
                    COALESCE(SUM(total_amount), 0) as revenue
                   FROM sales
                   WHERE DATE(sale_date) = CURDATE()";
    
    $todayStmt = $db->prepare($todayQuery);
    $todayStmt->execute();
    $today = $todayStmt->fetch();
    
    // This month's sales
    $monthQuery = "SELECT 
                    COUNT(*) as sales_count,
                    COALESCE(SUM(total_amount), 0) as revenue
                   FROM sales
                   WHERE MONTH(sale_date) = MONTH(CURDATE()) 
                     AND YEAR(sale_date) = YEAR(CURDATE())";
    
    $monthStmt = $db->prepare($monthQuery);
    $monthStmt->execute();
    $month = $monthStmt->fetch();
    
    // Inventory stats
    $inventoryQuery = "SELECT 
                        COUNT(*) as total_medicines,
                        SUM(stock_quantity) as total_stock,
                        COUNT(CASE WHEN stock_quantity < 50 THEN 1 END) as low_stock_count,
                        COUNT(CASE WHEN expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon_count
                       FROM medicines";
    
    $inventoryStmt = $db->prepare($inventoryQuery);
    $inventoryStmt->execute();
    $inventory = $inventoryStmt->fetch();
    
    // Customer count
    $customerQuery = "SELECT COUNT(*) as total_customers FROM customers";
    $customerStmt = $db->prepare($customerQuery);
    $customerStmt->execute();
    $customers = $customerStmt->fetch();
    
    // Recent sales
    $recentQuery = "SELECT s.id, s.total_amount, s.payment_method, s.sale_date, c.name as customer_name
                    FROM sales s
                    LEFT JOIN customers c ON s.customer_id = c.id
                    ORDER BY s.sale_date DESC
                    LIMIT 5";
    
    $recentStmt = $db->prepare($recentQuery);
    $recentStmt->execute();
    $recentSales = $recentStmt->fetchAll();
    
    $stats = [
        'today' => $today,
        'this_month' => $month,
        'inventory' => $inventory,
        'total_customers' => $customers['total_customers'],
        'recent_sales' => $recentSales
    ];
    
    sendSuccess("Dashboard statistics retrieved successfully", $stats);
}

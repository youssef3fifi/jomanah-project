<?php
/**
 * Reports API Endpoint
 * Generates various reports for the pharmacy
 */

require_once '../config/cors.php';
require_once '../config/storage.php';
require_once '../includes/functions.php';

try {
    $method = getRequestMethod();
    $requestUri = $_SERVER['REQUEST_URI'];
    
    if ($method !== 'GET') {
        sendError(405, "Method not allowed");
    }
    
    // Determine report type from URL
    if (strpos($requestUri, '/reports/inventory') !== false) {
        getInventoryReport();
    } elseif (strpos($requestUri, '/reports/sales') !== false) {
        getSalesReport();
    } elseif (strpos($requestUri, '/reports/expiring') !== false) {
        getExpiringMedicinesReport();
    } elseif (strpos($requestUri, '/reports/dashboard') !== false) {
        getDashboardStats();
    } else {
        sendError(404, "Report type not found");
    }
    
} catch (Exception $e) {
    sendError(500, $e->getMessage());
}

/**
 * Get inventory report
 */
function getInventoryReport() {
    $medicines = $_SESSION['medicines'];
    $today = date('Y-m-d');
    $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));
    
    // Calculate summary
    $summary = [
        'total_medicines' => count($medicines),
        'total_stock' => array_reduce($medicines, function($sum, $m) {
            return $sum + $m['stock_quantity'];
        }, 0),
        'low_stock_count' => count(array_filter($medicines, function($m) {
            return $m['stock_quantity'] < 50;
        })),
        'out_of_stock_count' => count(array_filter($medicines, function($m) {
            return $m['stock_quantity'] == 0;
        })),
        'expiring_soon_count' => count(array_filter($medicines, function($m) use ($today, $thirtyDaysLater) {
            return $m['expiry_date'] && $m['expiry_date'] <= $thirtyDaysLater && $m['expiry_date'] >= $today;
        }))
    ];
    
    // Get low stock medicines
    $lowStock = array_filter($medicines, function($m) {
        return $m['stock_quantity'] < 50;
    });
    usort($lowStock, function($a, $b) {
        return $a['stock_quantity'] - $b['stock_quantity'];
    });
    $lowStock = array_slice($lowStock, 0, 10);
    $lowStock = array_map(function($m) {
        return [
            'id' => $m['id'],
            'name' => $m['name'],
            'category' => $m['category'],
            'stock_quantity' => $m['stock_quantity'],
            'price' => $m['price']
        ];
    }, $lowStock);
    
    // Get category-wise inventory
    $byCategory = [];
    foreach ($medicines as $medicine) {
        $cat = $medicine['category'] ?? 'Uncategorized';
        if (!isset($byCategory[$cat])) {
            $byCategory[$cat] = [
                'category' => $cat,
                'medicine_count' => 0,
                'total_stock' => 0,
                'total_value' => 0
            ];
        }
        $byCategory[$cat]['medicine_count']++;
        $byCategory[$cat]['total_stock'] += $medicine['stock_quantity'];
        $byCategory[$cat]['total_value'] += $medicine['stock_quantity'] * $medicine['price'];
    }
    $byCategory = array_values($byCategory);
    usort($byCategory, function($a, $b) {
        return $b['total_value'] <=> $a['total_value'];
    });
    
    $report = [
        'summary' => $summary,
        'low_stock_items' => array_values($lowStock),
        'by_category' => $byCategory
    ];
    
    sendSuccess("Inventory report generated successfully", $report);
}

/**
 * Get sales report
 */
function getSalesReport() {
    // Date filters
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    
    // Filter sales by date range
    $filteredSales = array_filter($_SESSION['sales'], function($sale) use ($dateFrom, $dateTo) {
        $saleDate = substr($sale['sale_date'], 0, 10);
        return $saleDate >= $dateFrom && $saleDate <= $dateTo;
    });
    
    // Sales summary
    $totalRevenue = array_reduce($filteredSales, function($sum, $sale) {
        return $sum + $sale['total_amount'];
    }, 0);
    
    $uniqueCustomers = array_unique(array_filter(array_map(function($sale) {
        return $sale['customer_id'] ?? null;
    }, $filteredSales)));
    
    $summary = [
        'total_sales' => count($filteredSales),
        'total_revenue' => $totalRevenue,
        'average_sale' => count($filteredSales) > 0 ? $totalRevenue / count($filteredSales) : 0,
        'unique_customers' => count($uniqueCustomers)
    ];
    
    // Sales by payment method
    $byPayment = [];
    foreach ($filteredSales as $sale) {
        $method = $sale['payment_method'];
        if (!isset($byPayment[$method])) {
            $byPayment[$method] = ['payment_method' => $method, 'count' => 0, 'total' => 0];
        }
        $byPayment[$method]['count']++;
        $byPayment[$method]['total'] += $sale['total_amount'];
    }
    $byPayment = array_values($byPayment);
    
    // Daily sales trend
    $trend = [];
    foreach ($filteredSales as $sale) {
        $date = substr($sale['sale_date'], 0, 10);
        if (!isset($trend[$date])) {
            $trend[$date] = ['date' => $date, 'sales_count' => 0, 'revenue' => 0];
        }
        $trend[$date]['sales_count']++;
        $trend[$date]['revenue'] += $sale['total_amount'];
    }
    $trend = array_values($trend);
    usort($trend, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    // Top selling medicines
    $medicineStats = [];
    foreach ($filteredSales as $sale) {
        if (isset($sale['items'])) {
            foreach ($sale['items'] as $item) {
                $medId = $item['medicine_id'];
                if (!isset($medicineStats[$medId])) {
                    $medicineStats[$medId] = [
                        'id' => $medId,
                        'name' => $item['medicine_name'],
                        'category' => null,
                        'total_quantity_sold' => 0,
                        'total_revenue' => 0
                    ];
                    // Get category from medicines
                    $medKey = findById($_SESSION['medicines'], $medId);
                    if ($medKey !== false) {
                        $medicineStats[$medId]['category'] = $_SESSION['medicines'][$medKey]['category'];
                    }
                }
                $medicineStats[$medId]['total_quantity_sold'] += $item['quantity'];
                $medicineStats[$medId]['total_revenue'] += $item['subtotal'];
            }
        }
    }
    $topMedicines = array_values($medicineStats);
    usort($topMedicines, function($a, $b) {
        return $b['total_revenue'] <=> $a['total_revenue'];
    });
    $topMedicines = array_slice($topMedicines, 0, 10);
    
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
function getExpiringMedicinesReport() {
    $days = isset($_GET['days']) ? intval($_GET['days']) : 90;
    $today = new DateTime();
    $futureDate = new DateTime("+$days days");
    
    // Filter medicines with expiry dates within the range
    $medicines = array_filter($_SESSION['medicines'], function($medicine) use ($today, $futureDate) {
        if (!$medicine['expiry_date']) {
            return false;
        }
        $expiryDate = new DateTime($medicine['expiry_date']);
        return $expiryDate <= $futureDate;
    });
    
    // Calculate days until expiry and categorize
    $expired = [];
    $expiringSoon = []; // 0-30 days
    $expiringLater = []; // 31-90 days
    
    foreach ($medicines as $medicine) {
        $expiryDate = new DateTime($medicine['expiry_date']);
        $daysUntilExpiry = $today->diff($expiryDate)->days;
        if ($expiryDate < $today) {
            $daysUntilExpiry = -$daysUntilExpiry;
        }
        
        $medicineData = [
            'id' => $medicine['id'],
            'name' => $medicine['name'],
            'category' => $medicine['category'],
            'price' => $medicine['price'],
            'stock_quantity' => $medicine['stock_quantity'],
            'expiry_date' => $medicine['expiry_date'],
            'days_until_expiry' => $daysUntilExpiry
        ];
        
        if ($daysUntilExpiry < 0) {
            $expired[] = $medicineData;
        } elseif ($daysUntilExpiry <= 30) {
            $expiringSoon[] = $medicineData;
        } else {
            $expiringLater[] = $medicineData;
        }
    }
    
    // Sort by expiry date
    $sortByDate = function($a, $b) {
        return strcmp($a['expiry_date'], $b['expiry_date']);
    };
    usort($expired, $sortByDate);
    usort($expiringSoon, $sortByDate);
    usort($expiringLater, $sortByDate);
    
    $report = [
        'summary' => [
            'expired_count' => count($expired),
            'expiring_soon_count' => count($expiringSoon),
            'expiring_later_count' => count($expiringLater),
            'total_count' => count($expired) + count($expiringSoon) + count($expiringLater)
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
function getDashboardStats() {
    $today = date('Y-m-d');
    $currentMonth = date('Y-m');
    $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));
    
    // Today's sales
    $todaySales = array_filter($_SESSION['sales'], function($sale) use ($today) {
        return substr($sale['sale_date'], 0, 10) === $today;
    });
    $todayRevenue = array_reduce($todaySales, function($sum, $sale) {
        return $sum + $sale['total_amount'];
    }, 0);
    
    // This month's sales
    $monthSales = array_filter($_SESSION['sales'], function($sale) use ($currentMonth) {
        return substr($sale['sale_date'], 0, 7) === $currentMonth;
    });
    $monthRevenue = array_reduce($monthSales, function($sum, $sale) {
        return $sum + $sale['total_amount'];
    }, 0);
    
    // Inventory stats
    $medicines = $_SESSION['medicines'];
    $inventory = [
        'total_medicines' => count($medicines),
        'total_stock' => array_reduce($medicines, function($sum, $m) {
            return $sum + $m['stock_quantity'];
        }, 0),
        'low_stock_count' => count(array_filter($medicines, function($m) {
            return $m['stock_quantity'] < 50;
        })),
        'expiring_soon_count' => count(array_filter($medicines, function($m) use ($today, $thirtyDaysLater) {
            return $m['expiry_date'] && $m['expiry_date'] <= $thirtyDaysLater && $m['expiry_date'] >= $today;
        }))
    ];
    
    // Recent sales (last 5)
    $recentSales = $_SESSION['sales'];
    usort($recentSales, function($a, $b) {
        return strcmp($b['sale_date'], $a['sale_date']);
    });
    $recentSales = array_slice($recentSales, 0, 5);
    $recentSales = array_map(function($sale) {
        return [
            'id' => $sale['id'],
            'total_amount' => $sale['total_amount'],
            'payment_method' => $sale['payment_method'],
            'sale_date' => $sale['sale_date'],
            'customer_name' => $sale['customer_name'] ?? null
        ];
    }, $recentSales);
    
    $stats = [
        'today' => [
            'sales_count' => count($todaySales),
            'revenue' => $todayRevenue
        ],
        'this_month' => [
            'sales_count' => count($monthSales),
            'revenue' => $monthRevenue
        ],
        'inventory' => $inventory,
        'total_customers' => count($_SESSION['customers']),
        'recent_sales' => $recentSales
    ];
    
    sendSuccess("Dashboard statistics retrieved successfully", $stats);
}

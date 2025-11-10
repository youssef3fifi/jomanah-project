<?php
/**
 * Pharmacy Management System API
 * Entry point for API requests
 */

require_once 'config/cors.php';

// API Information endpoint
$response = [
    'name' => 'Pharmacy Management System API',
    'version' => '1.0.0',
    'status' => 'active',
    'endpoints' => [
        'medicines' => [
            'GET /api/medicines' => 'List all medicines',
            'GET /api/medicines/{id}' => 'Get medicine details',
            'POST /api/medicines' => 'Create new medicine',
            'PUT /api/medicines/{id}' => 'Update medicine',
            'DELETE /api/medicines/{id}' => 'Delete medicine'
        ],
        'customers' => [
            'GET /api/customers' => 'List all customers',
            'GET /api/customers/{id}' => 'Get customer details',
            'POST /api/customers' => 'Create new customer',
            'PUT /api/customers/{id}' => 'Update customer',
            'DELETE /api/customers/{id}' => 'Delete customer'
        ],
        'sales' => [
            'GET /api/sales' => 'List all sales',
            'GET /api/sales/{id}' => 'Get sale details',
            'POST /api/sales' => 'Create new sale'
        ],
        'reports' => [
            'GET /api/reports/inventory' => 'Get inventory report',
            'GET /api/reports/sales' => 'Get sales report',
            'GET /api/reports/expiring' => 'Get expiring medicines report',
            'GET /api/reports/dashboard' => 'Get dashboard statistics'
        ]
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);

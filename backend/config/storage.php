<?php
/**
 * In-Memory Storage Configuration
 * Uses PHP Sessions for data persistence during runtime
 */

// Start PHP session for persistent storage during runtime
session_start();

// Auto-increment counters
if (!isset($_SESSION['counters'])) {
    $_SESSION['counters'] = [
        'medicine_id' => 7,
        'customer_id' => 4,
        'sale_id' => 3
    ];
}

// Initialize medicines array with sample data
if (!isset($_SESSION['medicines'])) {
    $_SESSION['medicines'] = [
        [
            'id' => 1,
            'name' => 'Paracetamol 500mg',
            'category' => 'Pain Relief',
            'price' => 50.00,
            'stock_quantity' => 100,
            'expiry_date' => '2026-12-31',
            'supplier' => 'PharmaCorp',
            'description' => 'Effective pain and fever relief',
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00'
        ],
        [
            'id' => 2,
            'name' => 'Aspirin 100mg',
            'category' => 'Cardiovascular',
            'price' => 30.00,
            'stock_quantity' => 200,
            'expiry_date' => '2027-06-30',
            'supplier' => 'MediSupply',
            'description' => 'Blood thinner and pain relief',
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00'
        ],
        [
            'id' => 3,
            'name' => 'Amoxicillin 250mg',
            'category' => 'Antibiotics',
            'price' => 120.00,
            'stock_quantity' => 50,
            'expiry_date' => '2025-12-31',
            'supplier' => 'PharmaCorp',
            'description' => 'Broad-spectrum antibiotic',
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00'
        ],
        [
            'id' => 4,
            'name' => 'Ibuprofen 400mg',
            'category' => 'Pain Relief',
            'price' => 65.00,
            'stock_quantity' => 150,
            'expiry_date' => '2026-08-15',
            'supplier' => 'HealthPlus',
            'description' => 'Anti-inflammatory and pain relief',
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00'
        ],
        [
            'id' => 5,
            'name' => 'Omeprazole 20mg',
            'category' => 'Gastrointestinal',
            'price' => 85.00,
            'stock_quantity' => 75,
            'expiry_date' => '2026-03-20',
            'supplier' => 'MediSupply',
            'description' => 'Reduces stomach acid',
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00'
        ],
        [
            'id' => 6,
            'name' => 'Vitamin D3 1000IU',
            'category' => 'Supplements',
            'price' => 45.00,
            'stock_quantity' => 300,
            'expiry_date' => '2027-01-10',
            'supplier' => 'HealthPlus',
            'description' => 'Bone health supplement',
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00'
        ]
    ];
}

// Initialize customers array with sample data
if (!isset($_SESSION['customers'])) {
    $_SESSION['customers'] = [
        [
            'id' => 1,
            'name' => 'Ahmed Hassan',
            'phone' => '01012345678',
            'email' => 'ahmed@example.com',
            'address' => '123 Main St, Cairo',
            'created_at' => '2025-01-15 09:30:00'
        ],
        [
            'id' => 2,
            'name' => 'Fatima Ali',
            'phone' => '01098765432',
            'email' => 'fatima@example.com',
            'address' => '456 Nile Ave, Giza',
            'created_at' => '2025-02-01 14:20:00'
        ],
        [
            'id' => 3,
            'name' => 'Mohamed Salah',
            'phone' => '01055555555',
            'email' => 'mohamed@example.com',
            'address' => '789 Tahrir Sq, Cairo',
            'created_at' => '2025-02-10 11:00:00'
        ]
    ];
}

// Initialize sales array with sample data
if (!isset($_SESSION['sales'])) {
    $_SESSION['sales'] = [
        [
            'id' => 1,
            'customer_id' => 1,
            'customer_name' => 'Ahmed Hassan',
            'total_amount' => 250.00,
            'payment_method' => 'Cash',
            'sale_date' => '2025-03-01',
            'created_at' => '2025-03-01 10:30:00',
            'items' => [
                [
                    'medicine_id' => 1,
                    'medicine_name' => 'Paracetamol 500mg',
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'subtotal' => 100.00
                ],
                [
                    'medicine_id' => 4,
                    'medicine_name' => 'Ibuprofen 400mg',
                    'quantity' => 2,
                    'unit_price' => 65.00,
                    'subtotal' => 130.00
                ]
            ]
        ],
        [
            'id' => 2,
            'customer_id' => 2,
            'customer_name' => 'Fatima Ali',
            'total_amount' => 360.00,
            'payment_method' => 'Credit Card',
            'sale_date' => '2025-03-05',
            'created_at' => '2025-03-05 15:45:00',
            'items' => [
                [
                    'medicine_id' => 3,
                    'medicine_name' => 'Amoxicillin 250mg',
                    'quantity' => 3,
                    'unit_price' => 120.00,
                    'subtotal' => 360.00
                ]
            ]
        ]
    ];
}

/**
 * Helper function to get next ID
 */
function getNextId($type) {
    $_SESSION['counters'][$type . '_id']++;
    return $_SESSION['counters'][$type . '_id'];
}

/**
 * Helper function to find item by ID
 */
function findById(&$array, $id) {
    foreach ($array as $key => $item) {
        if ($item['id'] == $id) {
            return $key;
        }
    }
    return false;
}

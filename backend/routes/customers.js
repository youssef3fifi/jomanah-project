/**
 * Customers API Routes
 * Handles all CRUD operations for customers
 */

const express = require('express');
const router = express.Router();
const storage = require('../data/storage');

// Get all customers with optional search
router.get('/', (req, res) => {
    try {
        let result = [...storage.customers];
        
        // Search by name, phone, or email
        if (req.query.search) {
            const searchTerm = req.query.search.toLowerCase();
            result = result.filter(c => 
                c.name.toLowerCase().includes(searchTerm) ||
                c.phone.includes(searchTerm) ||
                c.email.toLowerCase().includes(searchTerm)
            );
        }
        
        // Pagination (optional)
        const page = parseInt(req.query.page) || 1;
        const limit = parseInt(req.query.limit) || 100;
        const startIndex = (page - 1) * limit;
        const endIndex = startIndex + limit;
        const paginatedResult = result.slice(startIndex, endIndex);
        
        res.json({
            success: true,
            data: {
                items: paginatedResult,
                pagination: {
                    current_page: page,
                    total_pages: Math.ceil(result.length / limit),
                    total_items: result.length,
                    items_per_page: limit
                }
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error fetching customers',
            error: error.message
        });
    }
});

// Get single customer by ID
router.get('/:id', (req, res) => {
    try {
        const customer = storage.findById(storage.customers, req.params.id);
        
        if (!customer) {
            return res.status(404).json({
                success: false,
                message: 'Customer not found'
            });
        }
        
        res.json({
            success: true,
            data: customer
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error fetching customer',
            error: error.message
        });
    }
});

// Get customer purchase history
router.get('/:id/history', (req, res) => {
    try {
        const customerId = parseInt(req.params.id);
        const customer = storage.findById(storage.customers, customerId);
        
        if (!customer) {
            return res.status(404).json({
                success: false,
                message: 'Customer not found'
            });
        }
        
        const purchaseHistory = storage.sales.filter(s => s.customerId === customerId);
        
        res.json({
            success: true,
            data: {
                customer: customer,
                purchases: purchaseHistory,
                totalPurchases: purchaseHistory.length,
                totalSpent: purchaseHistory.reduce((sum, sale) => sum + sale.totalAmount, 0)
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error fetching customer history',
            error: error.message
        });
    }
});

// Create new customer
router.post('/', (req, res) => {
    try {
        const { name, phone, email, address } = req.body;
        
        // Validate required fields
        if (!name || !phone) {
            return res.status(400).json({
                success: false,
                message: 'Missing required fields: name, phone'
            });
        }
        
        // Validate email format if provided
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return res.status(400).json({
                success: false,
                message: 'Invalid email format'
            });
        }
        
        // Create new customer
        const newCustomer = {
            id: storage.getNextId('customerId'),
            name,
            phone,
            email: email || '',
            address: address || '',
            createdAt: new Date().toISOString()
        };
        
        storage.customers.push(newCustomer);
        
        res.status(201).json({
            success: true,
            message: 'Customer created successfully',
            data: newCustomer
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error creating customer',
            error: error.message
        });
    }
});

// Update customer
router.put('/:id', (req, res) => {
    try {
        const customer = storage.findById(storage.customers, req.params.id);
        
        if (!customer) {
            return res.status(404).json({
                success: false,
                message: 'Customer not found'
            });
        }
        
        const { name, phone, email, address } = req.body;
        
        // Validate email format if provided
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return res.status(400).json({
                success: false,
                message: 'Invalid email format'
            });
        }
        
        // Update fields
        if (name !== undefined) customer.name = name;
        if (phone !== undefined) customer.phone = phone;
        if (email !== undefined) customer.email = email;
        if (address !== undefined) customer.address = address;
        
        res.json({
            success: true,
            message: 'Customer updated successfully',
            data: customer
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error updating customer',
            error: error.message
        });
    }
});

// Delete customer
router.delete('/:id', (req, res) => {
    try {
        const deleted = storage.deleteById(storage.customers, req.params.id);
        
        if (!deleted) {
            return res.status(404).json({
                success: false,
                message: 'Customer not found'
            });
        }
        
        res.json({
            success: true,
            message: 'Customer deleted successfully'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error deleting customer',
            error: error.message
        });
    }
});

module.exports = router;

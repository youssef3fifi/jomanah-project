/**
 * Sales API Routes
 * Handles sales transactions and updates stock automatically
 */

const express = require('express');
const router = express.Router();
const storage = require('../data/storage');

// Get all sales with optional filters
router.get('/', (req, res) => {
    try {
        let result = [...storage.sales];
        
        // Filter by customer ID
        if (req.query.customerId) {
            const customerId = parseInt(req.query.customerId);
            result = result.filter(s => s.customerId === customerId);
        }
        
        // Filter by payment method
        if (req.query.paymentMethod) {
            result = result.filter(s => 
                s.paymentMethod.toLowerCase() === req.query.paymentMethod.toLowerCase()
            );
        }
        
        // Filter by date range
        if (req.query.dateFrom) {
            const fromDate = new Date(req.query.dateFrom);
            result = result.filter(s => new Date(s.saleDate) >= fromDate);
        }
        
        if (req.query.dateTo) {
            const toDate = new Date(req.query.dateTo);
            result = result.filter(s => new Date(s.saleDate) <= toDate);
        }
        
        // Sort by date (newest first)
        result.sort((a, b) => new Date(b.saleDate) - new Date(a.saleDate));
        
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
            message: 'Error fetching sales',
            error: error.message
        });
    }
});

// Get single sale by ID
router.get('/:id', (req, res) => {
    try {
        const sale = storage.findById(storage.sales, req.params.id);
        
        if (!sale) {
            return res.status(404).json({
                success: false,
                message: 'Sale not found'
            });
        }
        
        res.json({
            success: true,
            data: sale
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error fetching sale',
            error: error.message
        });
    }
});

// Create new sale (with automatic stock update)
router.post('/', (req, res) => {
    try {
        const { customerId, paymentMethod, items } = req.body;
        
        // Validate required fields
        if (!paymentMethod || !items || !Array.isArray(items) || items.length === 0) {
            return res.status(400).json({
                success: false,
                message: 'Missing required fields: paymentMethod, items (must be non-empty array)'
            });
        }
        
        // Validate payment method
        const validPaymentMethods = ['Cash', 'Credit Card', 'Debit Card', 'Insurance'];
        if (!validPaymentMethods.includes(paymentMethod)) {
            return res.status(400).json({
                success: false,
                message: 'Invalid payment method. Must be one of: ' + validPaymentMethods.join(', ')
            });
        }
        
        // Validate items and check stock
        let totalAmount = 0;
        const saleItems = [];
        
        for (const item of items) {
            if (!item.medicineId || !item.quantity || item.quantity <= 0) {
                return res.status(400).json({
                    success: false,
                    message: 'Each item must have medicineId and positive quantity'
                });
            }
            
            const medicine = storage.findById(storage.medicines, item.medicineId);
            if (!medicine) {
                return res.status(404).json({
                    success: false,
                    message: `Medicine with ID ${item.medicineId} not found`
                });
            }
            
            if (medicine.stock_quantity < item.quantity) {
                return res.status(400).json({
                    success: false,
                    message: `Insufficient stock for ${medicine.name}. Available: ${medicine.stock_quantity}, Requested: ${item.quantity}`
                });
            }
            
            const subtotal = medicine.price * item.quantity;
            totalAmount += subtotal;
            
            saleItems.push({
                medicineId: medicine.id,
                medicineName: medicine.name,
                quantity: item.quantity,
                unitPrice: medicine.price,
                subtotal: subtotal
            });
        }
        
        // Get customer name if customerId provided
        let customerName = 'Walk-in Customer';
        if (customerId) {
            const customer = storage.findById(storage.customers, customerId);
            if (customer) {
                customerName = customer.name;
            }
        }
        
        // Create new sale
        const newSale = {
            id: storage.getNextId('saleId'),
            customerId: customerId || null,
            customerName: customerName,
            totalAmount: parseFloat(totalAmount.toFixed(2)),
            paymentMethod: paymentMethod,
            saleDate: new Date().toISOString(),
            items: saleItems
        };
        
        // Update stock for each item
        for (const item of items) {
            const medicine = storage.findById(storage.medicines, item.medicineId);
            medicine.stock_quantity -= item.quantity;
        }
        
        storage.sales.push(newSale);
        
        res.status(201).json({
            success: true,
            message: 'Sale created successfully',
            data: newSale
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error creating sale',
            error: error.message
        });
    }
});

// Delete sale (does NOT restore stock - for data cleanup only)
router.delete('/:id', (req, res) => {
    try {
        const deleted = storage.deleteById(storage.sales, req.params.id);
        
        if (!deleted) {
            return res.status(404).json({
                success: false,
                message: 'Sale not found'
            });
        }
        
        res.json({
            success: true,
            message: 'Sale deleted successfully'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error deleting sale',
            error: error.message
        });
    }
});

module.exports = router;

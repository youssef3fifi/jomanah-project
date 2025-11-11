/**
 * Medicines API Routes
 * Handles all CRUD operations for medicines
 */

const express = require('express');
const router = express.Router();
const storage = require('../data/storage');

// Get all medicines with optional filters
router.get('/', (req, res) => {
    try {
        let result = [...storage.medicines];
        
        // Filter by category
        if (req.query.category) {
            result = result.filter(m => m.category === req.query.category);
        }
        
        // Search by name
        if (req.query.search) {
            const searchTerm = req.query.search.toLowerCase();
            result = result.filter(m => 
                m.name.toLowerCase().includes(searchTerm) ||
                m.description.toLowerCase().includes(searchTerm)
            );
        }
        
        // Filter low stock
        if (req.query.low_stock === 'true') {
            result = result.filter(m => m.stock_quantity < 50);
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
            message: 'Error fetching medicines',
            error: error.message
        });
    }
});

// Get low stock medicines
router.get('/low-stock', (req, res) => {
    try {
        const threshold = parseInt(req.query.threshold) || 50;
        const lowStock = storage.medicines.filter(m => m.stock_quantity < threshold);
        
        res.json({
            success: true,
            data: lowStock,
            count: lowStock.length
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error fetching low stock medicines',
            error: error.message
        });
    }
});

// Get single medicine by ID
router.get('/:id', (req, res) => {
    try {
        const medicine = storage.findById(storage.medicines, req.params.id);
        
        if (!medicine) {
            return res.status(404).json({
                success: false,
                message: 'Medicine not found'
            });
        }
        
        res.json({
            success: true,
            data: medicine
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error fetching medicine',
            error: error.message
        });
    }
});

// Create new medicine
router.post('/', (req, res) => {
    try {
        const { name, category, price, stock_quantity, expiry_date, supplier, description } = req.body;
        
        // Validate required fields
        if (!name || !category || price === undefined || stock_quantity === undefined) {
            return res.status(400).json({
                success: false,
                message: 'Missing required fields: name, category, price, stock_quantity'
            });
        }
        
        // Validate data types
        if (typeof price !== 'number' || price < 0) {
            return res.status(400).json({
                success: false,
                message: 'Price must be a positive number'
            });
        }
        
        if (typeof stock_quantity !== 'number' || stock_quantity < 0) {
            return res.status(400).json({
                success: false,
                message: 'Stock quantity must be a positive number'
            });
        }
        
        // Create new medicine
        const newMedicine = {
            id: storage.getNextId('medicineId'),
            name,
            category,
            price,
            stock_quantity,
            expiry_date: expiry_date || null,
            supplier: supplier || '',
            description: description || ''
        };
        
        storage.medicines.push(newMedicine);
        
        res.status(201).json({
            success: true,
            message: 'Medicine created successfully',
            data: newMedicine
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error creating medicine',
            error: error.message
        });
    }
});

// Update medicine
router.put('/:id', (req, res) => {
    try {
        const medicine = storage.findById(storage.medicines, req.params.id);
        
        if (!medicine) {
            return res.status(404).json({
                success: false,
                message: 'Medicine not found'
            });
        }
        
        const { name, category, price, stock_quantity, expiry_date, supplier, description } = req.body;
        
        // Validate data if provided
        if (price !== undefined && (typeof price !== 'number' || price < 0)) {
            return res.status(400).json({
                success: false,
                message: 'Price must be a positive number'
            });
        }
        
        if (stock_quantity !== undefined && (typeof stock_quantity !== 'number' || stock_quantity < 0)) {
            return res.status(400).json({
                success: false,
                message: 'Stock quantity must be a positive number'
            });
        }
        
        // Update fields
        if (name !== undefined) medicine.name = name;
        if (category !== undefined) medicine.category = category;
        if (price !== undefined) medicine.price = price;
        if (stock_quantity !== undefined) medicine.stock_quantity = stock_quantity;
        if (expiry_date !== undefined) medicine.expiry_date = expiry_date;
        if (supplier !== undefined) medicine.supplier = supplier;
        if (description !== undefined) medicine.description = description;
        
        res.json({
            success: true,
            message: 'Medicine updated successfully',
            data: medicine
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error updating medicine',
            error: error.message
        });
    }
});

// Delete medicine
router.delete('/:id', (req, res) => {
    try {
        const deleted = storage.deleteById(storage.medicines, req.params.id);
        
        if (!deleted) {
            return res.status(404).json({
                success: false,
                message: 'Medicine not found'
            });
        }
        
        res.json({
            success: true,
            message: 'Medicine deleted successfully'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error deleting medicine',
            error: error.message
        });
    }
});

module.exports = router;

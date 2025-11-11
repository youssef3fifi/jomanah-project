/**
 * Pharmacy Management System - Express Server
 * Node.js backend with in-memory storage
 */

require('dotenv').config();
const express = require('express');
const corsMiddleware = require('./middleware/cors');

// Import route handlers
const medicinesRouter = require('./routes/medicines');
const customersRouter = require('./routes/customers');
const salesRouter = require('./routes/sales');
const reportsRouter = require('./routes/reports');

// Initialize Express app
const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(corsMiddleware);
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Request logging middleware
app.use((req, res, next) => {
    console.log(`${new Date().toISOString()} - ${req.method} ${req.path}`);
    next();
});

// API Routes
app.use('/api/medicines', medicinesRouter);
app.use('/api/customers', customersRouter);
app.use('/api/sales', salesRouter);
app.use('/api/reports', reportsRouter);

// Health check endpoint
app.get('/api/health', (req, res) => {
    res.json({ 
        status: 'OK', 
        message: 'Pharmacy API is running',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
    });
});

// Root endpoint
app.get('/', (req, res) => {
    res.json({
        name: 'Pharmacy Management System API',
        version: '1.0.0',
        description: 'RESTful API with in-memory storage',
        endpoints: {
            medicines: '/api/medicines',
            customers: '/api/customers',
            sales: '/api/sales',
            reports: '/api/reports',
            health: '/api/health'
        }
    });
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({
        success: false,
        message: 'Endpoint not found'
    });
});

// Error handler
app.use((err, req, res, next) => {
    console.error('Error:', err);
    res.status(err.status || 500).json({
        success: false,
        message: err.message || 'Internal server error'
    });
});

// Start server
app.listen(PORT, '0.0.0.0', () => {
    console.log('═══════════════════════════════════════════════════════');
    console.log('🏥 Pharmacy Management System API');
    console.log('═══════════════════════════════════════════════════════');
    console.log(`🚀 Server running on http://0.0.0.0:${PORT}`);
    console.log(`📊 Environment: ${process.env.NODE_ENV || 'development'}`);
    console.log(`💾 Storage: In-Memory (resets on server restart)`);
    console.log('═══════════════════════════════════════════════════════');
    console.log('📚 API Endpoints:');
    console.log(`   • Medicines:  http://0.0.0.0:${PORT}/api/medicines`);
    console.log(`   • Customers:  http://0.0.0.0:${PORT}/api/customers`);
    console.log(`   • Sales:      http://0.0.0.0:${PORT}/api/sales`);
    console.log(`   • Reports:    http://0.0.0.0:${PORT}/api/reports`);
    console.log('═══════════════════════════════════════════════════════');
});

module.exports = app;

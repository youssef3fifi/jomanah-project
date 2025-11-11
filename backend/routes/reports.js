/**
 * Reports API Routes
 * Provides various reports and analytics
 */

const express = require('express');
const router = express.Router();
const storage = require('../data/storage');

// Dashboard statistics
router.get('/dashboard', (req, res) => {
    try {
        // Calculate total sales amount
        const totalSales = storage.sales.reduce((sum, sale) => sum + sale.totalAmount, 0);
        
        // Get today's sales
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todaySales = storage.sales.filter(s => new Date(s.saleDate) >= today);
        const todayRevenue = todaySales.reduce((sum, sale) => sum + sale.totalAmount, 0);
        
        // Count low stock items
        const lowStockCount = storage.medicines.filter(m => m.stock < 50).length;
        
        // Count expiring medicines (within 3 months)
        const threeMonthsFromNow = new Date();
        threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
        const expiringCount = storage.medicines.filter(m => {
            if (!m.expiryDate) return false;
            const expiryDate = new Date(m.expiryDate);
            return expiryDate <= threeMonthsFromNow && expiryDate >= new Date();
        }).length;
        
        res.json({
            success: true,
            data: {
                totalMedicines: storage.medicines.length,
                totalCustomers: storage.customers.length,
                totalSales: storage.sales.length,
                totalRevenue: parseFloat(totalSales.toFixed(2)),
                todaySales: todaySales.length,
                todayRevenue: parseFloat(todayRevenue.toFixed(2)),
                lowStockCount: lowStockCount,
                expiringCount: expiringCount
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error generating dashboard statistics',
            error: error.message
        });
    }
});

// Inventory report
router.get('/inventory', (req, res) => {
    try {
        // Group by category
        const categories = {};
        storage.medicines.forEach(medicine => {
            if (!categories[medicine.category]) {
                categories[medicine.category] = {
                    category: medicine.category,
                    count: 0,
                    totalValue: 0,
                    lowStock: 0
                };
            }
            categories[medicine.category].count++;
            categories[medicine.category].totalValue += medicine.price * medicine.stock;
            if (medicine.stock < 50) {
                categories[medicine.category].lowStock++;
            }
        });
        
        const categoryReport = Object.values(categories).map(cat => ({
            ...cat,
            totalValue: parseFloat(cat.totalValue.toFixed(2))
        }));
        
        // Overall inventory stats
        const totalValue = storage.medicines.reduce((sum, m) => sum + (m.price * m.stock), 0);
        const totalStock = storage.medicines.reduce((sum, m) => sum + m.stock, 0);
        
        res.json({
            success: true,
            data: {
                categories: categoryReport,
                totalMedicines: storage.medicines.length,
                totalValue: parseFloat(totalValue.toFixed(2)),
                totalStock: totalStock,
                lowStockItems: storage.medicines.filter(m => m.stock < 50)
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error generating inventory report',
            error: error.message
        });
    }
});

// Sales report
router.get('/sales', (req, res) => {
    try {
        let salesData = [...storage.sales];
        
        // Filter by date range if provided
        if (req.query.dateFrom) {
            const fromDate = new Date(req.query.dateFrom);
            salesData = salesData.filter(s => new Date(s.saleDate) >= fromDate);
        }
        
        if (req.query.dateTo) {
            const toDate = new Date(req.query.dateTo);
            salesData = salesData.filter(s => new Date(s.saleDate) <= toDate);
        }
        
        // Calculate totals
        const totalRevenue = salesData.reduce((sum, sale) => sum + sale.totalAmount, 0);
        const totalTransactions = salesData.length;
        const averageTransaction = totalTransactions > 0 ? totalRevenue / totalTransactions : 0;
        
        // Group by payment method
        const paymentMethods = {};
        salesData.forEach(sale => {
            if (!paymentMethods[sale.paymentMethod]) {
                paymentMethods[sale.paymentMethod] = {
                    method: sale.paymentMethod,
                    count: 0,
                    total: 0
                };
            }
            paymentMethods[sale.paymentMethod].count++;
            paymentMethods[sale.paymentMethod].total += sale.totalAmount;
        });
        
        const paymentMethodReport = Object.values(paymentMethods).map(pm => ({
            ...pm,
            total: parseFloat(pm.total.toFixed(2)),
            percentage: ((pm.total / totalRevenue) * 100).toFixed(1)
        }));
        
        // Group by date
        const dailySales = {};
        salesData.forEach(sale => {
            const date = new Date(sale.saleDate).toISOString().split('T')[0];
            if (!dailySales[date]) {
                dailySales[date] = {
                    date: date,
                    count: 0,
                    total: 0
                };
            }
            dailySales[date].count++;
            dailySales[date].total += sale.totalAmount;
        });
        
        const dailySalesReport = Object.values(dailySales)
            .map(ds => ({
                ...ds,
                total: parseFloat(ds.total.toFixed(2))
            }))
            .sort((a, b) => new Date(b.date) - new Date(a.date));
        
        res.json({
            success: true,
            data: {
                totalRevenue: parseFloat(totalRevenue.toFixed(2)),
                totalTransactions: totalTransactions,
                averageTransaction: parseFloat(averageTransaction.toFixed(2)),
                paymentMethods: paymentMethodReport,
                dailySales: dailySalesReport,
                recentSales: salesData.slice(0, 10)
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error generating sales report',
            error: error.message
        });
    }
});

// Expiring medicines report
router.get('/expiring', (req, res) => {
    try {
        const daysAhead = parseInt(req.query.days) || 90;
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + daysAhead);
        
        const expiringMedicines = storage.medicines.filter(medicine => {
            if (!medicine.expiryDate) return false;
            const expiryDate = new Date(medicine.expiryDate);
            const now = new Date();
            return expiryDate <= futureDate && expiryDate >= now;
        }).map(medicine => {
            const expiryDate = new Date(medicine.expiryDate);
            const daysUntilExpiry = Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24));
            return {
                ...medicine,
                daysUntilExpiry: daysUntilExpiry,
                status: daysUntilExpiry <= 30 ? 'Critical' : daysUntilExpiry <= 60 ? 'Warning' : 'Notice'
            };
        }).sort((a, b) => a.daysUntilExpiry - b.daysUntilExpiry);
        
        res.json({
            success: true,
            data: expiringMedicines,
            count: expiringMedicines.length
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error generating expiring medicines report',
            error: error.message
        });
    }
});

// Top selling medicines report
router.get('/top-selling', (req, res) => {
    try {
        const limit = parseInt(req.query.limit) || 10;
        
        // Calculate total quantity sold for each medicine
        const medicineSales = {};
        
        storage.sales.forEach(sale => {
            sale.items.forEach(item => {
                if (!medicineSales[item.medicineId]) {
                    medicineSales[item.medicineId] = {
                        medicineId: item.medicineId,
                        medicineName: item.medicineName,
                        totalQuantity: 0,
                        totalRevenue: 0,
                        transactionCount: 0
                    };
                }
                medicineSales[item.medicineId].totalQuantity += item.quantity;
                medicineSales[item.medicineId].totalRevenue += item.subtotal;
                medicineSales[item.medicineId].transactionCount++;
            });
        });
        
        const topSelling = Object.values(medicineSales)
            .map(ms => ({
                ...ms,
                totalRevenue: parseFloat(ms.totalRevenue.toFixed(2))
            }))
            .sort((a, b) => b.totalQuantity - a.totalQuantity)
            .slice(0, limit);
        
        res.json({
            success: true,
            data: topSelling,
            count: topSelling.length
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Error generating top selling medicines report',
            error: error.message
        });
    }
});

module.exports = router;

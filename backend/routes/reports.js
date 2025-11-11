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
        const totalStock = storage.medicines.reduce((sum, m) => sum + m.stock_quantity, 0);
        
        // Get today's sales
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todaySales = storage.sales.filter(s => new Date(s.saleDate) >= today);
        const todayRevenue = todaySales.reduce((sum, sale) => sum + sale.totalAmount, 0);
        
        // Get this month's sales
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const thisMonthSales = storage.sales.filter(s => new Date(s.saleDate) >= firstDayOfMonth);
        const thisMonthRevenue = thisMonthSales.reduce((sum, sale) => sum + sale.totalAmount, 0);
        
        // Count low stock items
        const lowStockCount = storage.medicines.filter(m => m.stock_quantity < 50).length;
        
        // Count expiring medicines (within 3 months)
        const threeMonthsFromNow = new Date();
        threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
        const expiringCount = storage.medicines.filter(m => {
            if (!m.expiry_date) return false;
            const expiryDate = new Date(m.expiry_date);
            return expiryDate <= threeMonthsFromNow && expiryDate >= new Date();
        }).length;
        
        // Get recent sales (last 5)
        const recentSales = [...storage.sales]
            .sort((a, b) => new Date(b.saleDate) - new Date(a.saleDate))
            .slice(0, 5)
            .map(sale => ({
                id: sale.id,
                customer_name: sale.customerName,
                total_amount: sale.totalAmount,
                payment_method: sale.paymentMethod,
                sale_date: sale.saleDate
            }));
        
        res.json({
            success: true,
            data: {
                today: {
                    sales_count: todaySales.length,
                    revenue: parseFloat(todayRevenue.toFixed(2))
                },
                this_month: {
                    sales_count: thisMonthSales.length,
                    revenue: parseFloat(thisMonthRevenue.toFixed(2))
                },
                inventory: {
                    total_medicines: storage.medicines.length,
                    total_stock: totalStock,
                    low_stock_count: lowStockCount,
                    expiring_soon_count: expiringCount
                },
                total_customers: storage.customers.length,
                recent_sales: recentSales
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
        // Overall inventory stats
        const totalValue = storage.medicines.reduce((sum, m) => sum + (m.price * m.stock_quantity), 0);
        const totalStock = storage.medicines.reduce((sum, m) => sum + m.stock_quantity, 0);
        const lowStockCount = storage.medicines.filter(m => m.stock_quantity < 50).length;
        const outOfStockCount = storage.medicines.filter(m => m.stock_quantity === 0).length;
        
        // Low stock items
        const lowStockItems = storage.medicines
            .filter(m => m.stock_quantity < 50)
            .map(m => ({
                id: m.id,
                name: m.name,
                category: m.category,
                stock_quantity: m.stock_quantity,
                price: m.price
            }));
        
        // Group by category
        const categoryMap = {};
        storage.medicines.forEach(medicine => {
            if (!categoryMap[medicine.category]) {
                categoryMap[medicine.category] = {
                    category: medicine.category,
                    medicine_count: 0,
                    total_stock: 0,
                    total_value: 0
                };
            }
            categoryMap[medicine.category].medicine_count++;
            categoryMap[medicine.category].total_stock += medicine.stock_quantity;
            categoryMap[medicine.category].total_value += medicine.price * medicine.stock_quantity;
        });
        
        const byCategory = Object.values(categoryMap).map(cat => ({
            ...cat,
            total_value: parseFloat(cat.total_value.toFixed(2))
        }));
        
        res.json({
            success: true,
            data: {
                summary: {
                    total_medicines: storage.medicines.length,
                    total_stock: totalStock,
                    total_value: parseFloat(totalValue.toFixed(2)),
                    low_stock_count: lowStockCount,
                    out_of_stock_count: outOfStockCount
                },
                low_stock_items: lowStockItems,
                by_category: byCategory
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
        if (req.query.date_from) {
            const fromDate = new Date(req.query.date_from);
            salesData = salesData.filter(s => new Date(s.saleDate) >= fromDate);
        }
        
        if (req.query.date_to) {
            const toDate = new Date(req.query.date_to);
            toDate.setHours(23, 59, 59, 999); // End of day
            salesData = salesData.filter(s => new Date(s.saleDate) <= toDate);
        }
        
        // Calculate totals
        const totalRevenue = salesData.reduce((sum, sale) => sum + sale.totalAmount, 0);
        const totalSales = salesData.length;
        const averageSale = totalSales > 0 ? totalRevenue / totalSales : 0;
        
        // Count unique customers
        const uniqueCustomers = new Set(salesData.filter(s => s.customerId).map(s => s.customerId)).size;
        
        // Group by payment method
        const paymentMethods = {};
        salesData.forEach(sale => {
            if (!paymentMethods[sale.paymentMethod]) {
                paymentMethods[sale.paymentMethod] = {
                    payment_method: sale.paymentMethod,
                    count: 0,
                    total: 0
                };
            }
            paymentMethods[sale.paymentMethod].count++;
            paymentMethods[sale.paymentMethod].total += sale.totalAmount;
        });
        
        const byPaymentMethod = Object.values(paymentMethods).map(pm => ({
            ...pm,
            total: parseFloat(pm.total.toFixed(2))
        }));
        
        // Calculate top selling medicines from sales items
        const medicinesSold = {};
        salesData.forEach(sale => {
            sale.items.forEach(item => {
                if (!medicinesSold[item.medicineId]) {
                    medicinesSold[item.medicineId] = {
                        medicine_id: item.medicineId,
                        name: item.medicineName,
                        category: '', // Will be filled from medicines array
                        total_quantity_sold: 0,
                        total_revenue: 0
                    };
                }
                medicinesSold[item.medicineId].total_quantity_sold += item.quantity;
                medicinesSold[item.medicineId].total_revenue += item.subtotal;
            });
        });
        
        // Fill category information and format
        const topMedicines = Object.values(medicinesSold)
            .map(ms => {
                const medicine = storage.medicines.find(m => m.id === ms.medicine_id);
                return {
                    ...ms,
                    category: medicine ? medicine.category : 'Unknown',
                    total_revenue: parseFloat(ms.total_revenue.toFixed(2))
                };
            })
            .sort((a, b) => b.total_quantity_sold - a.total_quantity_sold)
            .slice(0, 10);
        
        res.json({
            success: true,
            data: {
                summary: {
                    total_sales: totalSales,
                    total_revenue: parseFloat(totalRevenue.toFixed(2)),
                    average_sale: parseFloat(averageSale.toFixed(2)),
                    unique_customers: uniqueCustomers
                },
                by_payment_method: byPaymentMethod,
                top_medicines: topMedicines
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
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + daysAhead);
        futureDate.setHours(23, 59, 59, 999);
        
        const expired = [];
        const expiringSoon = []; // 0-30 days
        const expiringLater = []; // 31+ days
        
        storage.medicines.forEach(medicine => {
            if (!medicine.expiry_date) return;
            
            const expiryDate = new Date(medicine.expiry_date);
            const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));
            
            const medData = {
                id: medicine.id,
                name: medicine.name,
                category: medicine.category,
                stock_quantity: medicine.stock_quantity,
                price: medicine.price,
                expiry_date: medicine.expiry_date,
                days_until_expiry: daysUntilExpiry
            };
            
            if (daysUntilExpiry < 0) {
                expired.push(medData);
            } else if (daysUntilExpiry <= daysAhead) {
                if (daysUntilExpiry <= 30) {
                    expiringSoon.push(medData);
                } else {
                    expiringLater.push(medData);
                }
            }
        });
        
        // Sort by days until expiry
        expired.sort((a, b) => a.days_until_expiry - b.days_until_expiry);
        expiringSoon.sort((a, b) => a.days_until_expiry - b.days_until_expiry);
        expiringLater.sort((a, b) => a.days_until_expiry - b.days_until_expiry);
        
        res.json({
            success: true,
            data: {
                summary: {
                    expired_count: expired.length,
                    expiring_soon_count: expiringSoon.length,
                    expiring_later_count: expiringLater.length,
                    total_count: expired.length + expiringSoon.length + expiringLater.length
                },
                expired: expired,
                expiring_soon: expiringSoon,
                expiring_later: expiringLater
            }
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

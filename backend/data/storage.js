/**
 * In-Memory Storage
 * All data is stored in JavaScript arrays
 * Data persists only while the server is running
 * When server restarts, data resets to initial values
 */

// Sample medicines data
let medicines = [
    { 
        id: 1, 
        name: 'Paracetamol 500mg', 
        category: 'Pain Relief', 
        price: 50, 
        stock_quantity: 100, 
        expiry_date: '2026-12-31', 
        supplier: 'PharmaCorp', 
        description: 'Pain and fever relief' 
    },
    { 
        id: 2, 
        name: 'Aspirin 100mg', 
        category: 'Cardiovascular', 
        price: 30, 
        stock_quantity: 200, 
        expiry_date: '2027-06-30', 
        supplier: 'MediSupply', 
        description: 'Blood thinner' 
    },
    { 
        id: 3, 
        name: 'Amoxicillin 250mg', 
        category: 'Antibiotics', 
        price: 120, 
        stock_quantity: 50, 
        expiry_date: '2025-12-31', 
        supplier: 'PharmaCorp', 
        description: 'Antibiotic' 
    },
    { 
        id: 4, 
        name: 'Ibuprofen 400mg', 
        category: 'Pain Relief', 
        price: 65, 
        stock_quantity: 150, 
        expiry_date: '2026-08-15', 
        supplier: 'HealthPlus', 
        description: 'Anti-inflammatory' 
    },
    { 
        id: 5, 
        name: 'Omeprazole 20mg', 
        category: 'Gastrointestinal', 
        price: 85, 
        stock_quantity: 75, 
        expiry_date: '2026-03-20', 
        supplier: 'MediSupply', 
        description: 'Reduces stomach acid' 
    },
    { 
        id: 6, 
        name: 'Vitamin D3 1000IU', 
        category: 'Supplements', 
        price: 45, 
        stock_quantity: 300, 
        expiry_date: '2027-01-10', 
        supplier: 'HealthPlus', 
        description: 'Bone health' 
    }
];

// Sample customers data
let customers = [
    { 
        id: 1, 
        name: 'Ahmed Hassan', 
        phone: '01012345678', 
        email: 'ahmed@example.com', 
        address: '123 Main St, Cairo', 
        createdAt: '2025-01-15T09:30:00Z' 
    },
    { 
        id: 2, 
        name: 'Fatima Ali', 
        phone: '01098765432', 
        email: 'fatima@example.com', 
        address: '456 Nile Ave, Giza', 
        createdAt: '2025-02-01T14:20:00Z' 
    },
    { 
        id: 3, 
        name: 'Mohamed Salah', 
        phone: '01055555555', 
        email: 'mohamed@example.com', 
        address: '789 Tahrir Sq, Cairo', 
        createdAt: '2025-02-10T11:00:00Z' 
    }
];

// Sample sales data
let sales = [
    { 
        id: 1, 
        customerId: 1, 
        customerName: 'Ahmed Hassan',
        totalAmount: 250, 
        paymentMethod: 'Cash', 
        saleDate: '2025-03-01T10:30:00Z',
        items: [
            { medicineId: 1, medicineName: 'Paracetamol 500mg', quantity: 2, unitPrice: 50, subtotal: 100 },
            { medicineId: 4, medicineName: 'Ibuprofen 400mg', quantity: 2, unitPrice: 65, subtotal: 130 }
        ]
    },
    { 
        id: 2, 
        customerId: 2, 
        customerName: 'Fatima Ali',
        totalAmount: 360, 
        paymentMethod: 'Credit Card', 
        saleDate: '2025-03-05T14:15:00Z',
        items: [
            { medicineId: 3, medicineName: 'Amoxicillin 250mg', quantity: 3, unitPrice: 120, subtotal: 360 }
        ]
    }
];

// Auto-increment counters
let counters = {
    medicineId: 7,
    customerId: 4,
    saleId: 3
};

// Helper functions
const getNextId = (type) => {
    return counters[type]++;
};

const findById = (array, id) => {
    return array.find(item => item.id === parseInt(id));
};

const findIndexById = (array, id) => {
    return array.findIndex(item => item.id === parseInt(id));
};

const deleteById = (array, id) => {
    const index = findIndexById(array, id);
    if (index !== -1) {
        array.splice(index, 1);
        return true;
    }
    return false;
};

// Export storage and functions
module.exports = {
    medicines,
    customers,
    sales,
    counters,
    getNextId,
    findById,
    findIndexById,
    deleteById
};

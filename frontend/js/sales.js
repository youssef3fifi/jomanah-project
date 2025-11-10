/**
 * Sales Management JavaScript
 */

let cart = [];
let medicines = [];
let customers = [];
let currentPage = 1;

// Load initial data
async function loadInitialData() {
    await Promise.all([
        loadMedicinesForSale(),
        loadCustomers()
    ]);
    loadSales();
}

// Load medicines for sale
async function loadMedicinesForSale() {
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.medicines}?limit=100`);
        
        if (data.success) {
            medicines = data.data.items.filter(m => m.stock_quantity > 0);
            
            const select = document.getElementById('medicineSelect');
            select.innerHTML = '<option value="">Select a medicine...</option>';
            
            medicines.forEach(medicine => {
                const option = document.createElement('option');
                option.value = medicine.id;
                option.textContent = `${medicine.name} - ${PharmacyUtils.formatCurrency(medicine.price)} (Stock: ${medicine.stock_quantity})`;
                option.dataset.medicine = JSON.stringify(medicine);
                select.appendChild(option);
            });
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to load medicines: ' + error.message, 'error');
    }
}

// Load customers
async function loadCustomers() {
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.customers}?limit=100`);
        
        if (data.success) {
            customers = data.data.items;
            
            const select = document.getElementById('customerSelect');
            select.innerHTML = '<option value="">Walk-in Customer</option>';
            
            customers.forEach(customer => {
                const option = document.createElement('option');
                option.value = customer.id;
                option.textContent = `${customer.name} - ${customer.phone}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load customers:', error);
    }
}

// Add item to cart
function addItemToCart() {
    const medicineSelect = document.getElementById('medicineSelect');
    const quantityInput = document.getElementById('quantity');
    
    if (!medicineSelect.value) {
        PharmacyUtils.showNotification('Please select a medicine', 'warning');
        return;
    }
    
    const quantity = parseInt(quantityInput.value);
    if (quantity < 1) {
        PharmacyUtils.showNotification('Quantity must be at least 1', 'warning');
        return;
    }
    
    const medicine = JSON.parse(medicineSelect.options[medicineSelect.selectedIndex].dataset.medicine);
    
    // Check if medicine already in cart
    const existingItem = cart.find(item => item.medicine_id === medicine.id);
    
    if (existingItem) {
        const newQuantity = existingItem.quantity + quantity;
        if (newQuantity > medicine.stock_quantity) {
            PharmacyUtils.showNotification(`Not enough stock. Available: ${medicine.stock_quantity}`, 'warning');
            return;
        }
        existingItem.quantity = newQuantity;
    } else {
        if (quantity > medicine.stock_quantity) {
            PharmacyUtils.showNotification(`Not enough stock. Available: ${medicine.stock_quantity}`, 'warning');
            return;
        }
        
        cart.push({
            medicine_id: medicine.id,
            name: medicine.name,
            price: parseFloat(medicine.price),
            quantity: quantity,
            stock_available: medicine.stock_quantity
        });
    }
    
    // Reset selections
    medicineSelect.value = '';
    quantityInput.value = '1';
    
    updateCartDisplay();
    PharmacyUtils.showNotification('Item added to cart', 'success');
}

// Remove item from cart
function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

// Update cart display
function updateCartDisplay() {
    const container = document.getElementById('cartContainer');
    const summary = document.getElementById('cartSummary');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <p>No items in cart. Add medicines to create a sale.</p>
            </div>
        `;
        summary.classList.add('hidden');
        return;
    }
    
    let total = 0;
    let html = `
        <table>
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    cart.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        html += `
            <tr>
                <td>${item.name}</td>
                <td>${PharmacyUtils.formatCurrency(item.price)}</td>
                <td>
                    <input type="number" value="${item.quantity}" min="1" max="${item.stock_available}"
                           onchange="updateQuantity(${index}, this.value)" 
                           style="width: 80px; padding: 0.5rem;">
                </td>
                <td>${PharmacyUtils.formatCurrency(subtotal)}</td>
                <td>
                    <button class="icon-btn danger" onclick="removeFromCart(${index})">🗑️</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
    
    document.getElementById('totalAmount').textContent = PharmacyUtils.formatCurrency(total);
    summary.classList.remove('hidden');
}

// Update quantity
function updateQuantity(index, newQuantity) {
    const quantity = parseInt(newQuantity);
    
    if (quantity < 1) {
        PharmacyUtils.showNotification('Quantity must be at least 1', 'warning');
        updateCartDisplay();
        return;
    }
    
    if (quantity > cart[index].stock_available) {
        PharmacyUtils.showNotification(`Not enough stock. Available: ${cart[index].stock_available}`, 'warning');
        updateCartDisplay();
        return;
    }
    
    cart[index].quantity = quantity;
    updateCartDisplay();
}

// Clear cart
function clearCart() {
    if (cart.length === 0) return;
    
    if (PharmacyUtils.confirmAction('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCartDisplay();
    }
}

// Process sale
async function processSale() {
    if (cart.length === 0) {
        PharmacyUtils.showNotification('Cart is empty', 'warning');
        return;
    }
    
    const customerId = document.getElementById('customerSelect').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    const saleData = {
        customer_id: customerId || null,
        payment_method: paymentMethod,
        items: cart.map(item => ({
            medicine_id: item.medicine_id,
            quantity: item.quantity
        }))
    };
    
    const btn = document.getElementById('processSaleBtn');
    btn.disabled = true;
    btn.textContent = 'Processing...';
    
    try {
        const data = await PharmacyUtils.fetchAPI(API.sales, {
            method: 'POST',
            body: JSON.stringify(saleData)
        });
        
        if (data.success) {
            PharmacyUtils.showNotification('Sale completed successfully!', 'success');
            cart = [];
            updateCartDisplay();
            loadSales();
            loadMedicinesForSale(); // Reload to update stock
            
            // Reset form
            document.getElementById('customerSelect').value = '';
            document.getElementById('paymentMethod').value = 'cash';
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to process sale: ' + error.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = '💰 Process Sale';
    }
}

// Load sales history
async function loadSales() {
    const container = document.getElementById('salesContainer');
    PharmacyUtils.showLoading(container);
    
    try {
        const params = new URLSearchParams({
            page: currentPage,
            limit: 10
        });
        
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const paymentMethod = document.getElementById('paymentFilter').value;
        
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        if (paymentMethod) params.append('payment_method', paymentMethod);
        
        const data = await PharmacyUtils.fetchAPI(`${API.sales}?${params}`);
        
        if (data.success) {
            displaySales(data.data.items);
            
            if (data.data.pagination) {
                PharmacyUtils.createPagination(
                    document.getElementById('paginationContainer'),
                    data.data.pagination.current_page,
                    data.data.pagination.total_pages,
                    (page) => {
                        currentPage = page;
                        loadSales();
                    }
                );
            }
        }
    } catch (error) {
        PharmacyUtils.showError(container, error.message);
    }
}

// Display sales
function displaySales(sales) {
    const container = document.getElementById('salesContainer');
    
    if (!sales || sales.length === 0) {
        PharmacyUtils.showEmpty(container, 'No sales found');
        return;
    }
    
    let html = `
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Total Amount</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    sales.forEach(sale => {
        html += `
            <tr>
                <td><strong>#${sale.id}</strong></td>
                <td>${sale.customer_name || 'Walk-in'}</td>
                <td>${PharmacyUtils.formatCurrency(sale.total_amount)}</td>
                <td><span class="badge badge-info">${sale.payment_method}</span></td>
                <td>${PharmacyUtils.formatDateTime(sale.sale_date)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewSaleDetails(${sale.id})">
                        View Details
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// View sale details
async function viewSaleDetails(saleId) {
    const modal = document.getElementById('saleDetailsModal');
    const content = document.getElementById('saleDetailsContent');
    
    modal.classList.add('active');
    PharmacyUtils.showLoading(content);
    
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.sales}/${saleId}`);
        
        if (data.success) {
            const sale = data.data;
            
            let html = `
                <div style="margin-bottom: 1rem;">
                    <h4>Sale #${sale.id}</h4>
                    <p><strong>Date:</strong> ${PharmacyUtils.formatDateTime(sale.sale_date)}</p>
                    <p><strong>Customer:</strong> ${sale.customer_name || 'Walk-in Customer'}</p>
                    ${sale.customer_phone ? `<p><strong>Phone:</strong> ${sale.customer_phone}</p>` : ''}
                    <p><strong>Payment Method:</strong> <span class="badge badge-info">${sale.payment_method}</span></p>
                </div>
                
                <h4>Items</h4>
                <table style="margin-bottom: 1rem;">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            sale.items.forEach(item => {
                html += `
                    <tr>
                        <td>${item.medicine_name}</td>
                        <td>${item.category}</td>
                        <td>${item.quantity}</td>
                        <td>${PharmacyUtils.formatCurrency(item.unit_price)}</td>
                        <td>${PharmacyUtils.formatCurrency(item.subtotal)}</td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
                
                <div style="background: var(--primary-color); color: white; padding: 1rem; border-radius: 5px;">
                    <div class="flex-between">
                        <strong>Total Amount:</strong>
                        <strong style="font-size: 1.5rem;">${PharmacyUtils.formatCurrency(sale.total_amount)}</strong>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
        }
    } catch (error) {
        PharmacyUtils.showError(content, error.message);
    }
}

// Close sale details modal
function closeSaleDetailsModal() {
    document.getElementById('saleDetailsModal').classList.remove('active');
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadInitialData();
    
    // Set default date filters (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
    
    document.getElementById('dateTo').valueAsDate = today;
    document.getElementById('dateFrom').valueAsDate = thirtyDaysAgo;
    
    // Close modal on outside click
    document.getElementById('saleDetailsModal').addEventListener('click', (e) => {
        if (e.target.id === 'saleDetailsModal') {
            closeSaleDetailsModal();
        }
    });
});

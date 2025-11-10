/**
 * Customers Management JavaScript
 */

let currentPage = 1;
let searchQuery = '';
let editingId = null;

// Load customers
async function loadCustomers() {
    const container = document.getElementById('customersContainer');
    PharmacyUtils.showLoading(container);
    
    try {
        const params = new URLSearchParams({
            page: currentPage,
            limit: 10,
            ...(searchQuery && { search: searchQuery })
        });
        
        const data = await PharmacyUtils.fetchAPI(`${API.customers}?${params}`);
        
        if (data.success) {
            displayCustomers(data.data.items);
            
            if (data.data.pagination) {
                PharmacyUtils.createPagination(
                    document.getElementById('paginationContainer'),
                    data.data.pagination.current_page,
                    data.data.pagination.total_pages,
                    (page) => {
                        currentPage = page;
                        loadCustomers();
                    }
                );
            }
        }
    } catch (error) {
        PharmacyUtils.showError(container, error.message);
    }
}

// Display customers
function displayCustomers(customers) {
    const container = document.getElementById('customersContainer');
    
    if (!customers || customers.length === 0) {
        PharmacyUtils.showEmpty(container, 'No customers found');
        return;
    }
    
    let html = `
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Total Purchases</th>
                    <th>Total Spent</th>
                    <th>Member Since</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    customers.forEach(customer => {
        html += `
            <tr>
                <td><strong>${customer.name}</strong></td>
                <td>${customer.phone}</td>
                <td>${customer.email || 'N/A'}</td>
                <td><span class="badge badge-info">${customer.total_purchases || 0}</span></td>
                <td>${PharmacyUtils.formatCurrency(customer.total_spent || 0)}</td>
                <td>${PharmacyUtils.formatDate(customer.created_at)}</td>
                <td>
                    <div class="action-btns">
                        <button class="icon-btn" onclick="viewCustomerDetails(${customer.id})" title="View Details">👁️</button>
                        <button class="icon-btn" onclick="editCustomer(${customer.id})" title="Edit">✏️</button>
                        <button class="icon-btn danger" onclick="deleteCustomer(${customer.id}, '${customer.name}')" title="Delete">🗑️</button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Open add modal
function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Add Customer';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('customerModal').classList.add('active');
}

// Edit customer
async function editCustomer(id) {
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.customers}/${id}`);
        
        if (data.success) {
            editingId = id;
            const customer = data.data;
            
            document.getElementById('modalTitle').textContent = 'Edit Customer';
            document.getElementById('customerId').value = id;
            document.getElementById('name').value = customer.name;
            document.getElementById('phone').value = customer.phone;
            document.getElementById('email').value = customer.email || '';
            document.getElementById('address').value = customer.address || '';
            
            document.getElementById('customerModal').classList.add('active');
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to load customer: ' + error.message, 'error');
    }
}

// Delete customer
async function deleteCustomer(id, name) {
    if (!PharmacyUtils.confirmAction(`Are you sure you want to delete "${name}"?`)) {
        return;
    }
    
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.customers}/${id}`, {
            method: 'DELETE'
        });
        
        if (data.success) {
            PharmacyUtils.showNotification('Customer deleted successfully', 'success');
            loadCustomers();
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to delete customer: ' + error.message, 'error');
    }
}

// View customer details
async function viewCustomerDetails(id) {
    const modal = document.getElementById('customerDetailsModal');
    const content = document.getElementById('customerDetailsContent');
    
    modal.classList.add('active');
    PharmacyUtils.showLoading(content);
    
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.customers}/${id}`);
        
        if (data.success) {
            const customer = data.data;
            
            let html = `
                <div style="margin-bottom: 1.5rem;">
                    <h3>${customer.name}</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem;">
                        <div>
                            <p><strong>Phone:</strong> ${customer.phone}</p>
                            <p><strong>Email:</strong> ${customer.email || 'N/A'}</p>
                            <p><strong>Member Since:</strong> ${PharmacyUtils.formatDate(customer.created_at)}</p>
                        </div>
                        <div>
                            <p><strong>Total Purchases:</strong> ${customer.total_purchases || 0}</p>
                            <p><strong>Total Spent:</strong> ${PharmacyUtils.formatCurrency(customer.total_spent || 0)}</p>
                        </div>
                    </div>
                    ${customer.address ? `<p style="margin-top: 1rem;"><strong>Address:</strong><br>${customer.address}</p>` : ''}
                </div>
                
                <h4>Recent Purchase History</h4>
            `;
            
            if (customer.purchase_history && customer.purchase_history.length > 0) {
                html += `
                    <table>
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                customer.purchase_history.forEach(sale => {
                    html += `
                        <tr>
                            <td>#${sale.id}</td>
                            <td>${PharmacyUtils.formatCurrency(sale.total_amount)}</td>
                            <td><span class="badge badge-info">${sale.payment_method}</span></td>
                            <td>${PharmacyUtils.formatDateTime(sale.sale_date)}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table>';
            } else {
                html += '<p class="text-center" style="color: #999; padding: 2rem;">No purchase history</p>';
            }
            
            content.innerHTML = html;
        }
    } catch (error) {
        PharmacyUtils.showError(content, error.message);
    }
}

// Handle form submit
async function handleSubmit(event) {
    event.preventDefault();
    
    const formData = {
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        email: document.getElementById('email').value,
        address: document.getElementById('address').value
    };
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    try {
        const url = editingId ? `${API.customers}/${editingId}` : API.customers;
        const method = editingId ? 'PUT' : 'POST';
        
        const data = await PharmacyUtils.fetchAPI(url, {
            method: method,
            body: JSON.stringify(formData)
        });
        
        if (data.success) {
            PharmacyUtils.showNotification(
                editingId ? 'Customer updated successfully' : 'Customer added successfully',
                'success'
            );
            closeModal();
            loadCustomers();
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to save customer: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Customer';
    }
}

// Close modal
function closeModal() {
    document.getElementById('customerModal').classList.remove('active');
    document.getElementById('customerForm').reset();
    editingId = null;
}

// Close details modal
function closeDetailsModal() {
    document.getElementById('customerDetailsModal').classList.remove('active');
}

// Search handler
const handleSearch = PharmacyUtils.debounce((value) => {
    searchQuery = value;
    currentPage = 1;
    loadCustomers();
}, 500);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadCustomers();
    
    document.getElementById('searchInput').addEventListener('input', (e) => {
        handleSearch(e.target.value);
    });
    
    // Close modals on outside click
    document.getElementById('customerModal').addEventListener('click', (e) => {
        if (e.target.id === 'customerModal') {
            closeModal();
        }
    });
    
    document.getElementById('customerDetailsModal').addEventListener('click', (e) => {
        if (e.target.id === 'customerDetailsModal') {
            closeDetailsModal();
        }
    });
});

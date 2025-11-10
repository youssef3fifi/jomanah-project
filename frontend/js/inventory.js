/**
 * Inventory Management JavaScript
 */

let currentPage = 1;
let filters = {
    search: '',
    category: '',
    low_stock: false
};
let editingId = null;

// Load medicines
async function loadMedicines() {
    const container = document.getElementById('medicinesContainer');
    PharmacyUtils.showLoading(container);
    
    try {
        const params = new URLSearchParams({
            page: currentPage,
            limit: 10,
            ...(filters.search && { search: filters.search }),
            ...(filters.category && { category: filters.category }),
            ...(filters.low_stock && { low_stock: 'true' })
        });
        
        const data = await PharmacyUtils.fetchAPI(`${API.medicines}?${params}`);
        
        if (data.success) {
            displayMedicines(data.data.items);
            
            // Update pagination
            if (data.data.pagination) {
                PharmacyUtils.createPagination(
                    document.getElementById('paginationContainer'),
                    data.data.pagination.current_page,
                    data.data.pagination.total_pages,
                    (page) => {
                        currentPage = page;
                        loadMedicines();
                    }
                );
            }
            
            // Load categories for filter
            loadCategories(data.data.items);
        }
    } catch (error) {
        PharmacyUtils.showError(container, error.message);
    }
}

// Display medicines
function displayMedicines(medicines) {
    const container = document.getElementById('medicinesContainer');
    
    if (!medicines || medicines.length === 0) {
        PharmacyUtils.showEmpty(container, 'No medicines found');
        return;
    }
    
    let html = `
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Expiry Date</th>
                    <th>Supplier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    medicines.forEach(medicine => {
        const stockClass = medicine.stock_quantity < 50 ? 'badge-warning' : 
                          medicine.stock_quantity === 0 ? 'badge-danger' : 'badge-success';
        
        const expiryDate = medicine.expiry_date ? new Date(medicine.expiry_date) : null;
        const isExpiringSoon = expiryDate && expiryDate < new Date(Date.now() + 30 * 24 * 60 * 60 * 1000);
        const expiryClass = isExpiringSoon ? 'badge-danger' : '';
        
        html += `
            <tr>
                <td><strong>${medicine.name}</strong></td>
                <td>${medicine.category || 'N/A'}</td>
                <td>${PharmacyUtils.formatCurrency(medicine.price)}</td>
                <td><span class="badge ${stockClass}">${medicine.stock_quantity}</span></td>
                <td><span class="badge ${expiryClass}">${PharmacyUtils.formatDate(medicine.expiry_date)}</span></td>
                <td>${medicine.supplier || 'N/A'}</td>
                <td>
                    <div class="action-btns">
                        <button class="icon-btn" onclick="editMedicine(${medicine.id})" title="Edit">✏️</button>
                        <button class="icon-btn danger" onclick="deleteMedicine(${medicine.id}, '${medicine.name}')" title="Delete">🗑️</button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Load categories
function loadCategories(medicines) {
    const categoryFilter = document.getElementById('categoryFilter');
    const categories = [...new Set(medicines.map(m => m.category).filter(c => c))];
    
    const currentValue = categoryFilter.value;
    categoryFilter.innerHTML = '<option value="">All Categories</option>';
    
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category;
        option.textContent = category;
        categoryFilter.appendChild(option);
    });
    
    categoryFilter.value = currentValue;
}

// Open add modal
function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent = 'Add Medicine';
    document.getElementById('medicineForm').reset();
    document.getElementById('medicineId').value = '';
    document.getElementById('medicineModal').classList.add('active');
}

// Edit medicine
async function editMedicine(id) {
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.medicines}/${id}`);
        
        if (data.success) {
            editingId = id;
            const medicine = data.data;
            
            document.getElementById('modalTitle').textContent = 'Edit Medicine';
            document.getElementById('medicineId').value = id;
            document.getElementById('name').value = medicine.name;
            document.getElementById('category').value = medicine.category || '';
            document.getElementById('price').value = medicine.price;
            document.getElementById('stock_quantity').value = medicine.stock_quantity;
            document.getElementById('expiry_date').value = medicine.expiry_date || '';
            document.getElementById('supplier').value = medicine.supplier || '';
            document.getElementById('description').value = medicine.description || '';
            
            document.getElementById('medicineModal').classList.add('active');
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to load medicine: ' + error.message, 'error');
    }
}

// Delete medicine
async function deleteMedicine(id, name) {
    if (!PharmacyUtils.confirmAction(`Are you sure you want to delete "${name}"?`)) {
        return;
    }
    
    try {
        const data = await PharmacyUtils.fetchAPI(`${API.medicines}/${id}`, {
            method: 'DELETE'
        });
        
        if (data.success) {
            PharmacyUtils.showNotification('Medicine deleted successfully', 'success');
            loadMedicines();
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to delete medicine: ' + error.message, 'error');
    }
}

// Handle form submit
async function handleSubmit(event) {
    event.preventDefault();
    
    const formData = {
        name: document.getElementById('name').value,
        category: document.getElementById('category').value,
        price: parseFloat(document.getElementById('price').value),
        stock_quantity: parseInt(document.getElementById('stock_quantity').value),
        expiry_date: document.getElementById('expiry_date').value,
        supplier: document.getElementById('supplier').value,
        description: document.getElementById('description').value
    };
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    try {
        const url = editingId ? `${API.medicines}/${editingId}` : API.medicines;
        const method = editingId ? 'PUT' : 'POST';
        
        const data = await PharmacyUtils.fetchAPI(url, {
            method: method,
            body: JSON.stringify(formData)
        });
        
        if (data.success) {
            PharmacyUtils.showNotification(
                editingId ? 'Medicine updated successfully' : 'Medicine added successfully',
                'success'
            );
            closeModal();
            loadMedicines();
        }
    } catch (error) {
        PharmacyUtils.showNotification('Failed to save medicine: ' + error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Medicine';
    }
}

// Close modal
function closeModal() {
    document.getElementById('medicineModal').classList.remove('active');
    document.getElementById('medicineForm').reset();
    editingId = null;
}

// Toggle low stock filter
function toggleLowStock() {
    filters.low_stock = !filters.low_stock;
    currentPage = 1;
    loadMedicines();
    
    const btn = event.target;
    btn.style.background = filters.low_stock ? 'var(--warning-color)' : '';
    btn.style.color = filters.low_stock ? 'white' : '';
}

// Search handler
const handleSearch = PharmacyUtils.debounce((value) => {
    filters.search = value;
    currentPage = 1;
    loadMedicines();
}, 500);

// Category filter handler
function handleCategoryFilter(value) {
    filters.category = value;
    currentPage = 1;
    loadMedicines();
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadMedicines();
    
    document.getElementById('searchInput').addEventListener('input', (e) => {
        handleSearch(e.target.value);
    });
    
    document.getElementById('categoryFilter').addEventListener('change', (e) => {
        handleCategoryFilter(e.target.value);
    });
    
    // Close modal on outside click
    document.getElementById('medicineModal').addEventListener('click', (e) => {
        if (e.target.id === 'medicineModal') {
            closeModal();
        }
    });
});

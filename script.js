// Product price calculation for sales
function updateProductPrice() {
    const select = document.getElementById('product_select');
    const selected = select.options[select.selectedIndex];
    
    if (selected && selected.value) {
        const price = selected.dataset.price;
        document.getElementById('unit_price_display').value = '₱' + parseFloat(price).toFixed(2);
        calculateTotal();
    }
}

function calculateTotal() {
    const select = document.getElementById('product_select');
    const selected = select.options[select.selectedIndex];
    const quantity = document.getElementById('sale_quantity').value;
    
    if (selected && selected.value && quantity) {
        const price = parseFloat(selected.dataset.price);
        const stock = parseInt(selected.dataset.stock);
        
        if (parseInt(quantity) > stock) {
            alert('Insufficient stock! Available: ' + stock);
            document.getElementById('sale_quantity').value = stock;
            return;
        }
        
        const subtotal = price * quantity;
        const tax = subtotal * 0.12;
        const total = subtotal + tax;
        
        document.getElementById('tax_display').value = '₱' + tax.toFixed(2);
        document.getElementById('total_display').value = '₱' + total.toFixed(2);
    }
}

function validateSaleForm() {
    const select = document.getElementById('product_select');
    const quantity = document.getElementById('sale_quantity').value;
    const selected = select.options[select.selectedIndex];
    
    if (!selected || !selected.value) {
        alert('Please select a product');
        return false;
    }
    
    const stock = parseInt(selected.dataset.stock);
    if (parseInt(quantity) > stock) {
        alert('Insufficient stock! Available: ' + stock);
        return false;
    }
    
    return true;
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInventory');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('inventoryTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const productName = row.cells[0]?.textContent.toLowerCase();
                const category = row.cells[1]?.textContent.toLowerCase();
                
                if (productName && (productName.includes(searchTerm) || category.includes(searchTerm))) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }
    
    const salesSearch = document.getElementById('searchSales');
    if (salesSearch) {
        salesSearch.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('salesTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const productName = row.cells[1]?.textContent.toLowerCase();
                const date = row.cells[0]?.textContent.toLowerCase();
                
                if (productName && (productName.includes(searchTerm) || date.includes(searchTerm))) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }
});

// Modal functions
function showAddProductModal() {
    document.getElementById('addProductModal').style.display = 'block';
}

function hideAddProductModal() {
    document.getElementById('addProductModal').style.display = 'none';
}

function editProduct(id, name, category, stock, cost, selling) {
    document.getElementById('edit_product_id').value = id;
    document.getElementById('edit_product_name').value = name;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_stock').value = stock;
    document.getElementById('edit_cost').value = cost;
    document.getElementById('edit_selling').value = selling;
    document.getElementById('editProductModal').style.display = 'block';
}

function hideEditProductModal() {
    document.getElementById('editProductModal').style.display = 'none';
}

function showAddUserModal() {
    document.getElementById('addUserModal').style.display = 'block';
}

function hideAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
}

function editUser(id, name, email, role, status) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_status').value = status;
    document.getElementById('editUserModal').style.display = 'block';
}

function hideEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

// Tab switching
function showTab(tabName) {
    const tabs = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    const buttons = document.getElementsByClassName('tab-btn');
    for (let i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
    }
    
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

// Export functions (simplified - would need actual PDF/Excel library)
function exportToPDF() {
    alert('Export to PDF functionality would be implemented here');
}

function exportToExcel() {
    alert('Export to Excel functionality would be implemented here');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let i = 0; i < modals.length; i++) {
        if (event.target == modals[i]) {
            modals[i].style.display = 'none';
        }
    }
}

// Add these functions to your existing script.js

// Expense page functions
function showAddExpenseModal() {
    document.getElementById('addExpenseModal').style.display = 'block';
}

function hideAddExpenseModal() {
    document.getElementById('addExpenseModal').style.display = 'none';
}

// Filter expenses by category
function filterByCategory() {
    const category = document.getElementById('categoryFilter').value;
    const table = document.getElementById('expensesTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const rowCategory = row.cells[1]?.textContent;
        
        if (!category || rowCategory === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Search expenses
document.addEventListener('DOMContentLoaded', function() {
    const searchExpenses = document.getElementById('searchExpenses');
    if (searchExpenses) {
        searchExpenses.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('expensesTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const description = row.cells[2]?.textContent.toLowerCase();
                const category = row.cells[1]?.textContent.toLowerCase();
                
                if (description.includes(searchTerm) || category.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }
});

// Print functions
function printReport() {
    window.print();
}

// Data export functions
function exportReport(format) {
    // In a real application, this would trigger a server-side export
    alert(`Exporting report as ${format.toUpperCase()}...`);
}

function backupData() {
    alert('Creating backup... This may take a few moments.');
    setTimeout(() => {
        alert('Backup completed successfully!');
    }, 2000);
}

function scheduleBackup() {
    alert('Backup schedule saved successfully!');
}

function exportData(format) {
    alert('Exporting data as ' + format.toUpperCase() + '...');
}

function enable2FA() {
    alert('2FA setup would be implemented here');
}

function editCategory(name) {
    const newName = prompt('Edit category name:', name);
    if (newName && newName !== name) {
        alert('Category updated to: ' + newName);
    }
}

function deleteCategory(name) {
    if (confirm('Delete category "' + name + '"?')) {
        alert('Category deleted');
    }
}

// Budget history
function loadBudgetHistory() {
    const month = document.getElementById('historyMonth').value;
    if (month) {
        document.getElementById('historyDetails').innerHTML = `
            <h4>${month}</h4>
            <div class="history-item">
                <span>Sales Target:</span>
                <span>₱150,000.00</span>
            </div>
            <div class="history-item">
                <span>Actual Sales:</span>
                <span class="positive">₱142,500.00 (95%)</span>
            </div>
            <div class="history-item">
                <span>Expense Limit:</span>
                <span>₱50,000.00</span>
            </div>
            <div class="history-item">
                <span>Actual Expenses:</span>
                <span class="positive">₱48,200.00 (96%)</span>
            </div>
        `;
    }
}

// Form validation for sales
function validateSaleForm() {
    const select = document.getElementById('product_select');
    const quantity = document.getElementById('sale_quantity').value;
    
    if (!select || !select.value) {
        alert('Please select a product');
        return false;
    }
    
    const selected = select.options[select.selectedIndex];
    const stock = parseInt(selected.dataset.stock);
    
    if (parseInt(quantity) > stock) {
        alert('Insufficient stock! Available: ' + stock);
        return false;
    }
    
    if (quantity <= 0) {
        alert('Quantity must be greater than 0');
        return false;
    }
    
    return true;
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.getElementsByClassName('modal');
        for (let i = 0; i < modals.length; i++) {
            modals[i].style.display = 'none';
        }
    }
});

// Format currency inputs
document.addEventListener('DOMContentLoaded', function() {
    const currencyInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
});

// Confirm delete actions
document.addEventListener('DOMContentLoaded', function() {
    const deleteLinks = document.querySelectorAll('a.action-btn.delete');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - 30 + 'px';
            tooltip.style.left = rect.left + 'px';
            
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            });
        });
    });
});

// Modal Functions for Terms and Privacy
function openTermsModal() {
    document.getElementById('termsModal').style.display = 'flex';
    return false;
}

function closeTermsModal() {
    document.getElementById('termsModal').style.display = 'none';
}

function acceptTerms() {
    document.getElementById('terms').checked = true;
    closeTermsModal();
}

function openPrivacyModal() {
    document.getElementById('privacyModal').style.display = 'flex';
    return false;
}

function closePrivacyModal() {
    document.getElementById('privacyModal').style.display = 'none';
}

function acceptPrivacy() {
    // Privacy acceptance is implied by checking terms
    closePrivacyModal();
}
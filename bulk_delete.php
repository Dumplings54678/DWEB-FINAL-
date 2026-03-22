<?php
/**
 * HAUccountant Bulk Delete Operations
 * Delete multiple records at once
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

// Only admin can perform bulk delete
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to perform bulk delete operations.";
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: bulk_delete.php');
        exit();
    }
    
    $delete_type = $_POST['delete_type'] ?? '';
    $selected_ids = $_POST['selected_ids'] ?? [];
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    
    try {
        $pdo->beginTransaction();
        $deleted_count = 0;
        
        if (!empty($selected_ids) && is_array($selected_ids)) {
            // Delete specific records
            $ids = implode(',', array_map('intval', $selected_ids));
            
            switch ($delete_type) {
                case 'sales':
                    // First, restore stock for deleted sales
                    $stmt = $pdo->query("SELECT product_id, quantity FROM sales WHERE id IN ($ids)");
                    $sales_to_delete = $stmt->fetchAll();
                    
                    foreach ($sales_to_delete as $sale) {
                        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                        $stmt->execute([$sale['quantity'], $sale['product_id']]);
                    }
                    
                    $stmt = $pdo->exec("DELETE FROM sales WHERE id IN ($ids)");
                    $deleted_count = $stmt;
                    break;
                    
                case 'expenses':
                    $stmt = $pdo->exec("DELETE FROM expenses WHERE id IN ($ids)");
                    $deleted_count = $stmt;
                    break;
                    
                case 'products':
                    // Check if products have sales
                    $check = $pdo->query("SELECT COUNT(*) FROM sales WHERE product_id IN ($ids)")->fetchColumn();
                    if ($check > 0) {
                        throw new Exception("Cannot delete products with existing sales records.");
                    }
                    $stmt = $pdo->exec("DELETE FROM products WHERE id IN ($ids)");
                    $deleted_count = $stmt;
                    break;
                    
                case 'users':
                    // Don't allow deleting own account
                    if (in_array($user_id, $selected_ids)) {
                        throw new Exception("You cannot delete your own account.");
                    }
                    $stmt = $pdo->exec("DELETE FROM users WHERE id IN ($ids)");
                    $deleted_count = $stmt;
                    break;
            }
        } elseif (!empty($date_from) && !empty($date_to)) {
            // Delete by date range
            switch ($delete_type) {
                case 'sales':
                    // Get sales to restore stock
                    $stmt = $pdo->prepare("SELECT product_id, quantity FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
                    $stmt->execute([$date_from, $date_to]);
                    $sales_to_delete = $stmt->fetchAll();
                    
                    foreach ($sales_to_delete as $sale) {
                        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                        $stmt->execute([$sale['quantity'], $sale['product_id']]);
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
                    $stmt->execute([$date_from, $date_to]);
                    $deleted_count = $stmt->rowCount();
                    break;
                    
                case 'expenses':
                    $stmt = $pdo->prepare("DELETE FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?");
                    $stmt->execute([$date_from, $date_to]);
                    $deleted_count = $stmt->rowCount();
                    break;
            }
        }
        
        if ($deleted_count > 0) {
            logActivity($pdo, $user_id, 'BULK_DELETE', $delete_type, 
                "Bulk deleted {$deleted_count} records from {$delete_type}");
            $_SESSION['success'] = "Successfully deleted {$deleted_count} records.";
        } else {
            $_SESSION['error'] = "No records were deleted.";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: bulk_delete.php');
    exit();
}

// Get counts for display
$sales_count = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$expenses_count = $pdo->query("SELECT COUNT(*) FROM expenses")->fetchColumn();
$products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$users_count = $pdo->query("SELECT COUNT(*) FROM users WHERE id != $user_id")->fetchColumn();

// Get recent data for selection
$recent_sales = $pdo->query("
    SELECT s.id, s.receipt_no, p.product_name, s.quantity, s.total_amount, s.sale_date
    FROM sales s
    JOIN products p ON s.product_id = p.id
    ORDER BY s.created_at DESC
    LIMIT 50
")->fetchAll();

$recent_expenses = $pdo->query("
    SELECT id, category, amount, description, expense_date
    FROM expenses
    ORDER BY created_at DESC
    LIMIT 50
")->fetchAll();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Delete - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    
    <style>
        .delete-header {
            margin-bottom: 30px;
        }
        
        .delete-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .delete-tab {
            padding: 12px 28px;
            border: none;
            background: transparent;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .delete-tab:hover {
            color: #EF4444;
            background: #fee2e2;
        }
        
        .delete-tab.active {
            background: #EF4444;
            color: white;
        }
        
        .delete-section {
            background: white;
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
        }
        
        .delete-section h3 {
            font-size: 20px;
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #fee2e2;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .delete-section h3 i {
            color: #EF4444;
        }
        
        .delete-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .delete-option {
            background: #f8fafc;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }
        
        .delete-option h4 {
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .date-range {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .date-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .records-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .record-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .record-item:hover {
            background: #f8fafc;
        }
        
        .record-item:last-child {
            border-bottom: none;
        }
        
        .record-checkbox {
            width: 20px;
            height: 20px;
            accent-color: #EF4444;
        }
        
        .record-details {
            flex: 1;
        }
        
        .record-title {
            font-weight: 600;
            color: #0f172a;
        }
        
        .record-meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .record-amount {
            font-weight: 600;
            color: #EF4444;
        }
        
        .delete-btn {
            background: #EF4444;
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .delete-btn:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
        
        .delete-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #EF4444;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #64748b;
            font-size: 14px;
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        @media screen and (max-width: 768px) {
            .delete-options {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .date-range {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo-section">
                <h1>PLANORA</h1>
                <p class="tagline">"Accounting Ko ang Account Mo."</p>
            </div>
            
            <nav class="nav-menu">
                <a href="index.php" class="nav-item home">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="sales.php" class="nav-item sales">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sales</span>
                </a>
                <!-- Add after Sales link -->
<a href="bulk_sales.php" class="nav-item bulk-sales">
    <i class="fas fa-layer-group"></i>
    <span>Bulk Orders</span>
</a>

<!-- Add before About link for admin only -->
<?php if ($user_role === 'admin'): ?>
<a href="bulk_delete.php" class="nav-item bulk-delete">
    <i class="fas fa-trash-alt"></i>
    <span>Bulk Delete</span>
</a>
<?php endif; ?>
                <a href="expenses.php" class="nav-item expenses">
                    <i class="fas fa-receipt"></i>
                    <span>Expenses</span>
                </a>
                <a href="inventory.php" class="nav-item inventory">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                </a>
                <a href="reports.php" class="nav-item reports">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
                <a href="budget.php" class="nav-item budget">
                    <i class="fas fa-wallet"></i>
                    <span>Budget</span>
                </a>
                <?php if ($user_role === 'admin'): ?>
                <a href="users.php" class="nav-item users">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="settings.php" class="nav-item settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
                <a href="about.php" class="nav-item about">
                    <i class="fas fa-info-circle"></i>
                    <span>About</span>
                </a>
            </nav>

            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="user-role"><?php echo ucfirst($user_role); ?></p>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Alerts -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="delete-header">
                <h1>Bulk Delete Operations</h1>
                <p class="subtitle">Delete multiple records at once - <span style="color: #EF4444;">This action cannot be undone!</span></p>
            </div>

            <!-- Stats -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card">
                    <div class="number"><?php echo number_format($sales_count); ?></div>
                    <div class="label">Total Sales</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo number_format($expenses_count); ?></div>
                    <div class="label">Total Expenses</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo number_format($products_count); ?></div>
                    <div class="label">Total Products</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo number_format($users_count); ?></div>
                    <div class="label">Other Users</div>
                </div>
            </div>

            <!-- Delete Sales Section -->
            <div class="delete-section" data-aos="fade-up">
                <h3><i class="fas fa-shopping-cart"></i> Delete Sales Records</h3>
                
                <div class="delete-options">
                    <!-- Delete by selection -->
                    <div class="delete-option">
                        <h4><i class="fas fa-check-square"></i> Select Specific Sales</h4>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete the selected sales? This will also restore product stock.')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="delete_type" value="sales">
                            
                            <div class="select-all">
                                <input type="checkbox" id="selectAllSales" onclick="toggleAll('sales')">
                                <label for="selectAllSales">Select All</label>
                            </div>
                            
                            <div class="records-list">
                                <?php foreach ($recent_sales as $sale): ?>
                                <div class="record-item">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $sale['id']; ?>" class="record-checkbox sales-checkbox">
                                    <div class="record-details">
                                        <div class="record-title"><?php echo htmlspecialchars($sale['receipt_no'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($sale['product_name']); ?> x<?php echo $sale['quantity']; ?></div>
                                        <div class="record-meta"><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></div>
                                    </div>
                                    <div class="record-amount">₱<?php echo number_format($sale['total_amount'], 2); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" class="delete-btn" id="deleteSalesBtn" disabled>
                                <i class="fas fa-trash-alt"></i> Delete Selected Sales
                            </button>
                        </form>
                    </div>
                    
                    <!-- Delete by date range -->
                    <div class="delete-option">
                        <h4><i class="fas fa-calendar-alt"></i> Delete by Date Range</h4>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete all sales in this date range? This will also restore product stock.')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="delete_type" value="sales">
                            
                            <div class="date-range">
                                <input type="date" name="date_from" class="date-input" required>
                                <input type="date" name="date_to" class="date-input" required>
                            </div>
                            
                            <button type="submit" class="delete-btn">
                                <i class="fas fa-trash-alt"></i> Delete Sales in Date Range
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Expenses Section -->
            <div class="delete-section" data-aos="fade-up">
                <h3><i class="fas fa-receipt"></i> Delete Expenses</h3>
                
                <div class="delete-options">
                    <!-- Delete by selection -->
                    <div class="delete-option">
                        <h4><i class="fas fa-check-square"></i> Select Specific Expenses</h4>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete the selected expenses?')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="delete_type" value="expenses">
                            
                            <div class="select-all">
                                <input type="checkbox" id="selectAllExpenses" onclick="toggleAll('expenses')">
                                <label for="selectAllExpenses">Select All</label>
                            </div>
                            
                            <div class="records-list">
                                <?php foreach ($recent_expenses as $expense): ?>
                                <div class="record-item">
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $expense['id']; ?>" class="record-checkbox expenses-checkbox">
                                    <div class="record-details">
                                        <div class="record-title"><?php echo htmlspecialchars($expense['category']); ?> - <?php echo htmlspecialchars($expense['description']); ?></div>
                                        <div class="record-meta"><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?></div>
                                    </div>
                                    <div class="record-amount">₱<?php echo number_format($expense['amount'], 2); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" class="delete-btn" id="deleteExpensesBtn" disabled>
                                <i class="fas fa-trash-alt"></i> Delete Selected Expenses
                            </button>
                        </form>
                    </div>
                    
                    <!-- Delete by date range -->
                    <div class="delete-option">
                        <h4><i class="fas fa-calendar-alt"></i> Delete by Date Range</h4>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete all expenses in this date range?')">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="delete_type" value="expenses">
                            
                            <div class="date-range">
                                <input type="date" name="date_from" class="date-input" required>
                                <input type="date" name="date_to" class="date-input" required>
                            </div>
                            
                            <button type="submit" class="delete-btn">
                                <i class="fas fa-trash-alt"></i> Delete Expenses in Date Range
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Warning Message -->
            <div class="alert warning" style="margin-top: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Warning:</strong> Bulk delete operations cannot be undone. Please make sure you have a backup before proceeding.
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        // Toggle all checkboxes
        function toggleAll(type) {
            const selectAll = document.getElementById('selectAll' + type.charAt(0).toUpperCase() + type.slice(1));
            const checkboxes = document.querySelectorAll('.' + type + '-checkbox');
            const deleteBtn = document.getElementById('delete' + type.charAt(0).toUpperCase() + type.slice(1) + 'Btn');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            if (deleteBtn) {
                deleteBtn.disabled = !selectAll.checked;
            }
        }

        // Update delete button state
        document.querySelectorAll('.record-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const type = this.classList.contains('sales-checkbox') ? 'Sales' : 'Expenses';
                const checkboxes = document.querySelectorAll('.' + type.toLowerCase() + '-checkbox');
                const deleteBtn = document.getElementById('delete' + type + 'Btn');
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                
                if (deleteBtn) {
                    deleteBtn.disabled = !anyChecked;
                }
            });
        });

        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });
    </script>
</body>
</html>
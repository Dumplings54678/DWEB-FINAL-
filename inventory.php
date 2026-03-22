<?php
/**
 * HAUccountant Inventory Management
 * Enhanced with barcode support, stock history, and advanced filtering
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Get filter parameters
$filter_category = $_GET['category'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';

// Handle bulk add products
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_add'])) {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: inventory.php');
        exit();
    }
    
    $products = json_decode($_POST['bulk_products'], true);
    
    if (empty($products)) {
        $_SESSION['error'] = "No products to add.";
        header('Location: inventory.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        $success_count = 0;
        $errors = [];
        
        foreach ($products as $product) {
            // Generate SKU if not provided
            if (empty($product['sku'])) {
                $prefix = substr($product['category'], 0, 3);
                $sku = strtoupper($prefix) . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            } else {
                $sku = $product['sku'];
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    product_name, category, stock_quantity, cost_price, 
                    selling_price, sku, barcode, location, reorder_level, 
                    description, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $product['name'],
                $product['category'],
                $product['stock'],
                $product['cost'],
                $product['selling'],
                $sku,
                $product['barcode'],
                $product['location'],
                $product['reorder'],
                $product['description'],
                $user_id
            ]);
            
            $success_count++;
        }
        
        logActivity($pdo, $user_id, 'BULK_ADD_PRODUCTS', 'inventory', 
            "Added {$success_count} products in bulk");
        
        $pdo->commit();
        
        $_SESSION['success'] = "Successfully added {$success_count} products!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk product add failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add products. Please try again.";
    }
    
    header('Location: inventory.php');
    exit();
}

// Handle bulk delete products
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_delete'])) {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: inventory.php');
        exit();
    }
    
    $delete_ids = $_POST['delete_ids'] ?? [];
    
    if (empty($delete_ids)) {
        $_SESSION['error'] = "No products selected for deletion.";
        header('Location: inventory.php');
        exit();
    }
    
    // Sanitize IDs
    $delete_ids = array_map('intval', $delete_ids);
    $ids_string = implode(',', $delete_ids);
    
    try {
        $pdo->beginTransaction();
        
        // Check if any products have sales
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM sales WHERE product_id IN ($ids_string)");
        $sales_count = $stmt->fetch()['count'];
        
        if ($sales_count > 0) {
            throw new Exception("Cannot delete products with existing sales records.");
        }
        
        // Get product names for logging
        $stmt = $pdo->query("SELECT product_name FROM products WHERE id IN ($ids_string)");
        $products_to_delete = $stmt->fetchAll();
        $product_names = array_column($products_to_delete, 'product_name');
        
        // Delete products
        $stmt = $pdo->exec("DELETE FROM products WHERE id IN ($ids_string)");
        $deleted_count = $stmt;
        
        logActivity($pdo, $user_id, 'BULK_DELETE_PRODUCTS', 'inventory', 
            "Deleted {$deleted_count} products: " . implode(', ', $product_names));
        
        $pdo->commit();
        
        $_SESSION['success'] = "Successfully deleted {$deleted_count} products.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: inventory.php');
    exit();
}

// Handle add product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: inventory.php');
        exit();
    }
    
    $product_name = sanitizeInput($_POST['product_name']);
    $category = sanitizeInput($_POST['category']);
    $stock_quantity = (int)$_POST['stock_quantity'];
    $cost_price = (float)$_POST['cost_price'];
    $selling_price = (float)$_POST['selling_price'];
    $sku = sanitizeInput($_POST['sku'] ?? '');
    $barcode = sanitizeInput($_POST['barcode'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $reorder_level = (int)$_POST['reorder_level'] ?? 5;
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validate inputs
    $errors = [];
    
    if (empty($product_name)) {
        $errors[] = "Product name is required.";
    }
    
    if ($stock_quantity < 0) {
        $errors[] = "Stock quantity cannot be negative.";
    }
    
    if ($cost_price < 0) {
        $errors[] = "Cost price cannot be negative.";
    }
    
    if ($selling_price <= 0) {
        $errors[] = "Selling price must be greater than 0.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate SKU if not provided
            if (empty($sku)) {
                $prefix = substr($category, 0, 3);
                $sku = strtoupper($prefix) . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    product_name, category, stock_quantity, cost_price, 
                    selling_price, sku, barcode, location, reorder_level, 
                    description, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $product_name, $category, $stock_quantity, $cost_price,
                $selling_price, $sku, $barcode, $location, $reorder_level,
                $description, $user_id
            ]);
            
            $product_id = $pdo->lastInsertId();
            
            // Log activity
            logActivity($pdo, $user_id, 'ADD_PRODUCT', 'inventory', 
                "Added product: $product_name (SKU: $sku)");
            
            $pdo->commit();
            
            $_SESSION['success'] = "Product added successfully!";
            header('Location: inventory.php?highlight=' . $product_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Product add failed: " . $e->getMessage());
            $_SESSION['error'] = "Failed to add product. Please try again.";
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
    }
}

// Handle edit product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: inventory.php');
        exit();
    }
    
    $id = (int)$_POST['product_id'];
    $product_name = sanitizeInput($_POST['product_name']);
    $category = sanitizeInput($_POST['category']);
    $stock_quantity = (int)$_POST['stock_quantity'];
    $cost_price = (float)$_POST['cost_price'];
    $selling_price = (float)$_POST['selling_price'];
    $sku = sanitizeInput($_POST['sku']);
    $barcode = sanitizeInput($_POST['barcode'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $reorder_level = (int)$_POST['reorder_level'];
    $description = sanitizeInput($_POST['description'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // Get old values for logging
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            UPDATE products SET 
                product_name = ?, category = ?, stock_quantity = ?,
                cost_price = ?, selling_price = ?, sku = ?, barcode = ?,
                location = ?, reorder_level = ?, description = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $product_name, $category, $stock_quantity, $cost_price,
            $selling_price, $sku, $barcode, $location, $reorder_level,
            $description, $id
        ]);
        
        // Log changes
        $changes = [];
        if ($old['stock_quantity'] != $stock_quantity) {
            $changes[] = "stock: {$old['stock_quantity']} → $stock_quantity";
        }
        if ($old['selling_price'] != $selling_price) {
            $changes[] = "price: ₱{$old['selling_price']} → ₱$selling_price";
        }
        
        logActivity($pdo, $user_id, 'EDIT_PRODUCT', 'inventory', 
            "Edited product: $product_name (" . implode(', ', $changes) . ")");
        
        $pdo->commit();
        
        $_SESSION['success'] = "Product updated successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to update product.";
    }
    
    header('Location: inventory.php');
    exit();
}

// Handle delete product (admin only)
if (isset($_GET['delete']) && $user_role === 'admin') {
    $id = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Check if product has sales
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE product_id = ?");
        $stmt->execute([$id]);
        $sales_count = $stmt->fetch()['count'];
        
        if ($sales_count > 0) {
            $_SESSION['error'] = "Cannot delete product with existing sales records.";
        } else {
            // Get product name for logging
            $stmt = $pdo->prepare("SELECT product_name FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($pdo, $user_id, 'DELETE_PRODUCT', 'inventory', 
                "Deleted product: {$product['product_name']}");
            
            $_SESSION['success'] = "Product deleted successfully.";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete product.";
    }
    
    header('Location: inventory.php');
    exit();
}

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adjust_stock'])) {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: inventory.php');
        exit();
    }
    
    $id = (int)$_POST['product_id'];
    $adjustment = (int)$_POST['adjustment'];
    $reason = sanitizeInput($_POST['reason'] ?? 'Manual adjustment');
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        $new_stock = $product['stock_quantity'] + $adjustment;
        
        if ($new_stock < 0) {
            throw new Exception("Stock cannot be negative.");
        }
        
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
        $stmt->execute([$new_stock, $id]);
        
        // Record stock adjustment
        $stmt = $pdo->prepare("
            INSERT INTO stock_history (
                product_id, previous_stock, new_stock, adjustment,
                reason, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $id, $product['stock_quantity'], $new_stock, $adjustment,
            $reason, $user_id
        ]);
        
        logActivity($pdo, $user_id, 'ADJUST_STOCK', 'inventory', 
            "Adjusted stock for {$product['product_name']}: $adjustment units");
        
        $pdo->commit();
        
        $_SESSION['success'] = "Stock adjusted successfully!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: inventory.php');
    exit();
}

// Build products query with filters
$sql = "
    SELECT p.*, 
           (SELECT COUNT(*) FROM sales WHERE product_id = p.id) as times_sold,
           (SELECT SUM(quantity) FROM sales WHERE product_id = p.id) as total_sold
    FROM products p
    WHERE 1=1
";

$params = [];

if (!empty($filter_category)) {
    $sql .= " AND p.category = ?";
    $params[] = $filter_category;
}

if ($filter_status === 'low_stock') {
    $sql .= " AND p.stock_quantity < p.reorder_level";
} elseif ($filter_status === 'out_of_stock') {
    $sql .= " AND p.stock_quantity = 0";
} elseif ($filter_status === 'in_stock') {
    $sql .= " AND p.stock_quantity > 0";
}

if (!empty($search_query)) {
    $sql .= " AND (p.product_name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$sql .= " ORDER BY p.product_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
$categories = $stmt->fetchAll();

// Calculate inventory stats
$total_products = count($products);
$total_value = 0;
$total_cost = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

foreach ($products as $product) {
    $total_value += $product['stock_quantity'] * $product['selling_price'];
    $total_cost += $product['stock_quantity'] * $product['cost_price'];
    if ($product['stock_quantity'] < ($product['reorder_level'] ?? 5)) {
        $low_stock_count++;
    }
    if ($product['stock_quantity'] == 0) {
        $out_of_stock_count++;
    }
}

$potential_profit = $total_value - $total_cost;

// Get low stock products for alert
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE stock_quantity < reorder_level 
    ORDER BY stock_quantity ASC
");
$stmt->execute();
$low_stock_products = $stmt->fetchAll();

// Get recent stock movements
$stmt = $pdo->prepare("
    SELECT h.*, p.product_name, u.owner_name
    FROM stock_history h
    JOIN products p ON h.product_id = p.id
    LEFT JOIN users u ON h.created_by = u.id
    ORDER BY h.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_movements = $stmt->fetchAll();

// Product categories list
$product_categories = [
    'Electronics' => '📱 Electronics',
    'Office Supplies' => '📎 Office Supplies',
    'Furniture' => '🪑 Furniture',
    'Clothing' => '👕 Clothing',
    'Food' => '🍔 Food & Beverage',
    'Raw Materials' => '📦 Raw Materials',
    'Finished Goods' => '🏭 Finished Goods',
    'Equipment' => '⚙️ Equipment',
    'Tools' => '🔧 Tools',
    'Packaging' => '📦 Packaging',
    'Others' => '📌 Others'
];

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - PLANORA</title>
    
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
        /* Additional inventory page styles */
        .inventory-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 24px;
        }
        
        .stat-card .stat-label {
            color: var(--gray-600);
            font-size: 13px;
            margin-bottom: 4px;
        }
        
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-800);
        }
        
        .stat-card .stat-sub {
            font-size: 12px;
            color: var(--gray-500);
        }
        
        .alert-card {
            background: linear-gradient(135deg, #fff3cd, #fff9e6);
            border: 1px solid #ffe69c;
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .alert-card.warning {
            background: linear-gradient(135deg, #f8d7da, #fce4e4);
            border-color: #f5c2c7;
        }
        
        .alert-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-content h4 {
            margin-bottom: 4px;
            color: #856404;
        }
        
        .alert-content.warning h4 {
            color: #721c24;
        }
        
        .stock-badge {
            padding: 4px 12px;
            border-radius: var(--radius-full);
            font-size: 12px;
            font-weight: 600;
        }
        
        .stock-badge.critical {
            background: var(--danger-100);
            color: var(--danger-700);
        }
        
        .stock-badge.low {
            background: var(--warning-100);
            color: #b45f1b;
        }
        
        .stock-badge.good {
            background: var(--success-100);
            color: var(--success-700);
        }
        
        .product-sku {
            font-size: 11px;
            color: var(--gray-500);
            display: block;
        }
        
        .profit-margin {
            font-size: 12px;
            font-weight: 600;
        }
        
        .profit-margin.high {
            color: var(--success-700);
        }
        
        .profit-margin.medium {
            color: var(--warning-700);
        }
        
        .profit-margin.low {
            color: var(--danger-700);
        }
        
        .movement-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .movement-item:last-child {
            border-bottom: none;
        }
        
        .movement-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }
        
        .movement-details {
            flex: 1;
        }
        
        .movement-product {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .movement-meta {
            font-size: 11px;
            color: var(--gray-500);
        }
        
        .movement-adjustment {
            font-weight: 700;
        }
        
        .movement-adjustment.positive {
            color: var(--success-700);
        }
        
        .movement-adjustment.negative {
            color: var(--danger-700);
        }

        .bulk-item-row {
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        .bulk-item-row:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .remove-row-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fee2e2;
            color: #EF4444;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
        }
        .remove-row-btn:hover {
            background: #EF4444 !important;
            color: white !important;
        }
        .bulk-summary {
            background: linear-gradient(135deg, var(--primary-soft), var(--accent-soft));
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        @media screen and (max-width: 768px) {
            .inventory-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .alert-card {
                flex-direction: column;
                text-align: center;
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
    
    <!-- ADD THESE TWO LINES HERE - AFTER BUDGET -->
    <a href="history.php" class="nav-item history">
        <i class="fas fa-history"></i>
        <span>History</span>
    </a>
    <a href="contact.php" class="nav-item contact">
        <i class="fas fa-envelope"></i>
        <span>Contact</span>
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
    
    <!-- ADD THESE TWO LINES HERE - AFTER SETTINGS (FOR ADMIN ONLY) -->
    <a href="admin_messages.php" class="nav-item admin-messages">
        <i class="fas fa-inbox"></i>
        <span>Messages</span>
        <?php 
        $unread_count = getUnreadContactCount($pdo);
        if ($unread_count > 0): 
        ?>
        <span style="background: #EF4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 30px; margin-left: 5px;"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="admin_users.php" class="nav-item admin-users">
        <i class="fas fa-users-cog"></i>
        <span>Users Mgmt</span>
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
            <div class="content-header">
                <div>
                    <h1>Inventory Management</h1>
                    <p class="subtitle">Manage your products and stock levels</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus-circle"></i>
                        Add Product
                    </button>
                    <button class="btn btn-outline" onclick="showBulkAddModal()">
                        <i class="fas fa-layer-group"></i>
                        Bulk Add
                    </button>
                    <button class="btn btn-outline" onclick="exportInventory()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                    <?php if ($user_role === 'admin'): ?>
                    <button class="btn btn-outline" onclick="showBulkDeleteModal()" style="color: #EF4444; border-color: #EF4444;">
                        <i class="fas fa-trash-alt"></i>
                        Bulk Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success" data-aos="fade-in">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $_SESSION['success']; ?></span>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error" data-aos="fade-in">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['error']; ?></span>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Low Stock Alert -->
            <?php if ($low_stock_count > 0): ?>
            <div class="alert-card <?php echo $out_of_stock_count > 0 ? 'warning' : ''; ?>" data-aos="fade-down">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle" style="color: <?php echo $out_of_stock_count > 0 ? '#dc3545' : '#ffc107'; ?>;"></i>
                </div>
                <div class="alert-content <?php echo $out_of_stock_count > 0 ? 'warning' : ''; ?>">
                    <h4>Low Stock Alert</h4>
                    <p>
                        <?php echo $low_stock_count; ?> product(s) running low on stock.
                        <?php if ($out_of_stock_count > 0): ?>
                        <strong><?php echo $out_of_stock_count; ?> item(s) out of stock!</strong>
                        <?php endif; ?>
                    </p>
                </div>
                <a href="#low-stock-list" class="btn btn-sm btn-outline">View Details</a>
            </div>
            <?php endif; ?>

            <!-- Inventory Stats -->
            <div class="inventory-stats">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon" style="background: var(--primary-100); color: var(--primary-700);">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                </div>
                
                <div class="stat-card" data-aos="fade-up" data-aos-delay="150">
                    <div class="stat-icon" style="background: var(--success-100); color: var(--success-700);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-label">Inventory Value</div>
                    <div class="stat-value"><?php echo formatCurrency($total_value); ?></div>
                    <div class="stat-sub">Cost: <?php echo formatCurrency($total_cost); ?></div>
                </div>
                
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon" style="background: var(--warning-100); color: var(--warning-700);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-label">Potential Profit</div>
                    <div class="stat-value <?php echo $potential_profit >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo formatCurrency($potential_profit); ?>
                    </div>
                </div>
                
                <div class="stat-card" data-aos="fade-up" data-aos-delay="250">
                    <div class="stat-icon" style="background: var(--danger-100); color: var(--danger-700);">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="stat-label">Low Stock</div>
                    <div class="stat-value"><?php echo $low_stock_count; ?></div>
                    <div class="stat-sub"><?php echo $out_of_stock_count; ?> out of stock</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section" data-aos="fade-up">
                <form method="GET" action="" class="filter-form">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" placeholder="Search by name, SKU, barcode..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                    <?php echo $filter_category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="in_stock" <?php echo $filter_status === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                <option value="low_stock" <?php echo $filter_status === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out_of_stock" <?php echo $filter_status === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Search
                            </button>
                            <a href="inventory.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="content-card" data-aos="fade-up">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-box"></i>
                        Product List
                    </h3>
                    <div class="table-controls">
                        <span class="table-info"><?php echo count($products); ?> products</span>
                        <input type="text" id="tableSearch" placeholder="Quick filter..." class="search-input">
                    </div>
                </div>
                
                <?php if (count($products) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU/Barcode</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Cost Price</th>
                                <th>Selling Price</th>
                                <th>Margin</th>
                                <th>Location</th>
                                <th>Sold</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): 
                                $margin = $product['selling_price'] - $product['cost_price'];
                                $margin_percent = $product['cost_price'] > 0 ? round(($margin / $product['cost_price']) * 100) : 0;
                                
                                $stock_class = 'good';
                                if ($product['stock_quantity'] == 0) {
                                    $stock_class = 'critical';
                                } elseif ($product['stock_quantity'] < ($product['reorder_level'] ?? 5)) {
                                    $stock_class = 'low';
                                }
                                
                                $margin_class = 'high';
                                if ($margin_percent < 20) {
                                    $margin_class = 'low';
                                } elseif ($margin_percent < 40) {
                                    $margin_class = 'medium';
                                }
                                
                                $row_class = (isset($_GET['highlight']) && $_GET['highlight'] == $product['id']) ? 'highlight' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <?php if (!empty($product['description'])): ?>
                                    <small class="product-sku"><?php echo substr(htmlspecialchars($product['description']), 0, 30); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="product-sku">SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></span>
                                    <?php if (!empty($product['barcode'])): ?>
                                    <span class="product-sku">Barcode: <?php echo htmlspecialchars($product['barcode']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        <?php echo $product['stock_quantity']; ?> units
                                    </span>
                                    <?php if ($product['stock_quantity'] < ($product['reorder_level'] ?? 5)): ?>
                                    <br><small style="color: var(--danger-600);">Reorder at <?php echo $product['reorder_level'] ?? 5; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatCurrency($product['cost_price']); ?></td>
                                <td><?php echo formatCurrency($product['selling_price']); ?></td>
                                <td>
                                    <span class="profit-margin <?php echo $margin_class; ?>">
                                        <?php echo formatCurrency($margin); ?> (<?php echo $margin_percent; ?>%)
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($product['location'] ?? '—'); ?></td>
                                <td>
                                    <span class="badge"><?php echo (int)($product['total_sold'] ?? 0); ?> sold</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="editProduct(<?php echo $product['id']; ?>)" 
                                                class="action-btn edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="adjustStock(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>', <?php echo $product['stock_quantity']; ?>)" 
                                                class="action-btn" title="Adjust Stock" style="background: var(--primary-100); color: var(--primary-700);">
                                            <i class="fas fa-balance-scale"></i>
                                        </button>
                                        <?php if ($user_role === 'admin'): ?>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="action-btn delete" 
                                           onclick="return confirm('Delete this product? This action cannot be undone.')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>No products found</p>
                    <p class="empty-message">Add your first product to start tracking inventory</p>
                    <button class="btn btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus-circle"></i>
                        Add Product
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Stock Movements -->
            <?php if (!empty($recent_movements)): ?>
            <div class="content-card" data-aos="fade-up">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-history"></i>
                        Recent Stock Movements
                    </h3>
                </div>
                
                <div class="movements-list">
                    <?php foreach ($recent_movements as $movement): 
                        $adjustment_class = $movement['adjustment'] > 0 ? 'positive' : 'negative';
                    ?>
                    <div class="movement-item">
                        <div class="movement-icon">
                            <i class="fas <?php echo $movement['adjustment'] > 0 ? 'fa-plus' : 'fa-minus'; ?>" 
                               style="color: <?php echo $movement['adjustment'] > 0 ? 'var(--success-700)' : 'var(--danger-700)'; ?>"></i>
                        </div>
                        <div class="movement-details">
                            <div class="movement-product"><?php echo htmlspecialchars($movement['product_name']); ?></div>
                            <div class="movement-meta">
                                <?php echo htmlspecialchars($movement['reason']); ?> • 
                                by <?php echo htmlspecialchars($movement['owner_name'] ?? 'System'); ?> • 
                                <?php echo timeAgo($movement['created_at']); ?>
                            </div>
                        </div>
                        <div class="movement-adjustment <?php echo $adjustment_class; ?>">
                            <?php echo $movement['adjustment'] > 0 ? '+' : ''; ?><?php echo $movement['adjustment']; ?> units
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close" onclick="hideAddProductModal()">&times;</span>
            <h3>
                <i class="fas fa-plus-circle" style="color: var(--primary-600);"></i>
                Add New Product
            </h3>
            
            <form method="POST" action="" id="addProductForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="product_name" required placeholder="e.g., Premium Coffee Beans">
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($product_categories as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>SKU (Stock Keeping Unit)</label>
                        <input type="text" name="sku" placeholder="Auto-generated if empty">
                    </div>
                    
                    <div class="form-group">
                        <label>Barcode (Optional)</label>
                        <input type="text" name="barcode" placeholder="UPC/EAN/ISBN">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Initial Stock</label>
                        <input type="number" name="stock_quantity" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Reorder Level</label>
                        <input type="number" name="reorder_level" min="0" value="5">
                        <small class="form-text">Alert when stock below this</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Cost Price (₱)</label>
                        <input type="number" step="0.01" name="cost_price" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Selling Price (₱)</label>
                        <input type="number" step="0.01" name="selling_price" required placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Location/ Bin</label>
                        <input type="text" name="location" placeholder="e.g., Aisle 3, Shelf B">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" rows="2" placeholder="Product details, specifications, etc."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideAddProductModal()">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-success">
                        <i class="fas fa-check"></i>
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content modal-lg">
            <span class="close" onclick="hideEditProductModal()">&times;</span>
            <h3>
                <i class="fas fa-edit" style="color: var(--primary-600);"></i>
                Edit Product
            </h3>
            <div id="editProductContent"></div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div id="adjustStockModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideAdjustStockModal()">&times;</span>
            <h3>
                <i class="fas fa-balance-scale" style="color: var(--primary-600);"></i>
                Adjust Stock
            </h3>
            
            <form method="POST" action="" id="adjustStockForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="product_id" id="adjust_product_id">
                
                <div class="form-group">
                    <label>Product</label>
                    <input type="text" id="adjust_product_name" class="form-control" readonly disabled>
                </div>
                
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="text" id="adjust_current_stock" class="form-control" readonly disabled>
                </div>
                
                <div class="form-group">
                    <label>Adjustment</label>
                    <div class="input-group">
                        <select name="adjustment_type" id="adjustment_type" onchange="updateAdjustmentField()">
                            <option value="add">Add Stock (+)</option>
                            <option value="remove">Remove Stock (-)</option>
                            <option value="set">Set to Exact</option>
                        </select>
                        <input type="number" name="adjustment" id="adjustment_value" required>
                    </div>
                    <small class="form-text">Use positive numbers for add/set</small>
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <select name="reason" required>
                        <option value="Stock received">Stock received</option>
                        <option value="Stock return">Stock return</option>
                        <option value="Damaged goods">Damaged goods</option>
                        <option value="Inventory count">Inventory count adjustment</option>
                        <option value="Manual adjustment">Manual adjustment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideAdjustStockModal()">Cancel</button>
                    <button type="submit" name="adjust_stock" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Apply Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Add Products Modal -->
    <div id="bulkAddModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-layer-group" style="color: var(--primary);"></i> Bulk Add Products</h3>
                <span class="close" onclick="hideBulkAddModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px;">
                <p class="subtitle">Add multiple products at once. Fill in the details below.</p>
            </div>
            
            <div id="bulk-items-container">
                <!-- First item row (visible by default) -->
                <div class="bulk-item-row" id="bulk-row-0">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="color: var(--primary);">Item #1</h4>
                        <button type="button" class="remove-row-btn" onclick="removeBulkRow(0)" style="display: none;" id="remove-0">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" class="bulk-product-name" placeholder="e.g., Premium Coffee Beans" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Category</label>
                            <select class="bulk-category">
                                <option value="">Select Category</option>
                                <?php foreach ($product_categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" class="bulk-sku" placeholder="Auto-generated if empty">
                        </div>
                        
                        <div class="form-group">
                            <label>Barcode</label>
                            <input type="text" class="bulk-barcode" placeholder="Optional">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" class="bulk-stock" min="0" value="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Reorder Level</label>
                            <input type="number" class="bulk-reorder" min="0" value="5">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cost Price (₱)</label>
                            <input type="number" step="0.01" class="bulk-cost" placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Selling Price (₱)</label>
                            <input type="number" step="0.01" class="bulk-selling" placeholder="0.00" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" class="bulk-location" placeholder="e.g., Aisle 3, Shelf B">
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" class="bulk-description" placeholder="Brief description">
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; margin: 20px 0;">
                <button type="button" class="btn btn-outline" onclick="addBulkRow()" style="flex: 1;">
                    <i class="fas fa-plus-circle"></i> Add Another Item
                </button>
            </div>
            
            <div class="bulk-summary">
                <h4 style="margin-bottom: 10px;">Summary</h4>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Total Items to Add:</span>
                    <span class="bulk-total-items" style="font-weight: 700; font-size: 18px; color: var(--primary);">1</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="hideBulkAddModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBulkProducts()">
                    <i class="fas fa-check-circle"></i> Add All Items
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal -->
    <div id="bulkDeleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash-alt" style="color: #EF4444;"></i> Bulk Delete Products</h3>
                <span class="close" onclick="hideBulkDeleteModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px; color: #EF4444; background: #fee2e2; padding: 15px; border-radius: 12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> This action cannot be undone. Please select products carefully.
            </div>
            
            <div class="form-group">
                <label>Select Products to Delete</label>
                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px;">
                    <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="selectAllProducts" onchange="toggleAllProducts()">
                            <strong>Select All Products</strong>
                        </label>
                    </div>
                    
                    <?php foreach ($products as $product): ?>
                    <div style="margin-bottom: 8px; padding: 8px; border-radius: 8px; background: #f8fafc;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" onchange="updateDeleteButton()">
                            <div style="flex: 1;">
                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                <br>
                                <small>Stock: <?php echo $product['stock_quantity']; ?> | Price: ₱<?php echo number_format($product['selling_price'], 2); ?></small>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bulk-summary" style="background: #f8fafc; border-radius: 12px; padding: 15px; margin: 20px 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Selected Items:</span>
                    <span id="selectedCount" class="badge" style="background: #EF4444; color: white;">0</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="hideBulkDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmBulkDelete()" disabled>
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: true
        });

        // Modal functions
        function showAddProductModal() {
            document.getElementById('addProductModal').style.display = 'block';
        }

        function hideAddProductModal() {
            document.getElementById('addProductModal').style.display = 'none';
        }

        function editProduct(id) {
            fetch('get_product.php?id=' + id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('editProductContent').innerHTML = html;
                    document.getElementById('editProductModal').style.display = 'block';
                });
        }

        function hideEditProductModal() {
            document.getElementById('editProductModal').style.display = 'none';
        }

        function adjustStock(id, name, currentStock) {
            document.getElementById('adjust_product_id').value = id;
            document.getElementById('adjust_product_name').value = name;
            document.getElementById('adjust_current_stock').value = currentStock;
            document.getElementById('adjustStockModal').style.display = 'block';
        }

        function hideAdjustStockModal() {
            document.getElementById('adjustStockModal').style.display = 'none';
        }

        function updateAdjustmentField() {
            const type = document.getElementById('adjustment_type').value;
            const field = document.getElementById('adjustment_value');
            
            if (type === 'set') {
                field.placeholder = 'Enter new stock quantity';
            } else {
                field.placeholder = 'Enter quantity to ' + (type === 'add' ? 'add' : 'remove');
            }
        }

        // Bulk Add Functions
        let bulkRowCount = 1;

        function showBulkAddModal() {
            document.getElementById('bulkAddModal').style.display = 'flex';
            updateBulkSummary();
        }

        function hideBulkAddModal() {
            document.getElementById('bulkAddModal').style.display = 'none';
            // Reset to one row
            const container = document.getElementById('bulk-items-container');
            container.innerHTML = '';
            addBulkRow(); // Add first row
            bulkRowCount = 1;
        }

        function addBulkRow() {
            const container = document.getElementById('bulk-items-container');
            const rowId = bulkRowCount;
            
            const categories = <?php echo json_encode($product_categories); ?>;
            let categoryOptions = '<option value="">Select Category</option>';
            for (const [value, label] of Object.entries(categories)) {
                categoryOptions += `<option value="${value}">${label}</option>`;
            }
            
            const newRow = document.createElement('div');
            newRow.className = 'bulk-item-row';
            newRow.id = `bulk-row-${rowId}`;
            
            newRow.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="color: var(--primary);">Item #${rowId + 1}</h4>
                    <button type="button" class="remove-row-btn" onclick="removeBulkRow(${rowId})" id="remove-${rowId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" class="bulk-product-name-${rowId}" placeholder="e.g., Premium Coffee Beans" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select class="bulk-category-${rowId}">
                            ${categoryOptions}
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>SKU</label>
                        <input type="text" class="bulk-sku-${rowId}" placeholder="Auto-generated if empty">
                    </div>
                    
                    <div class="form-group">
                        <label>Barcode</label>
                        <input type="text" class="bulk-barcode-${rowId}" placeholder="Optional">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" class="bulk-stock-${rowId}" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Reorder Level</label>
                        <input type="number" class="bulk-reorder-${rowId}" min="0" value="5">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Cost Price (₱)</label>
                        <input type="number" step="0.01" class="bulk-cost-${rowId}" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Selling Price (₱)</label>
                        <input type="number" step="0.01" class="bulk-selling-${rowId}" placeholder="0.00" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" class="bulk-location-${rowId}" placeholder="e.g., Aisle 3, Shelf B">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" class="bulk-description-${rowId}" placeholder="Brief description">
                    </div>
                </div>
            `;
            
            container.appendChild(newRow);
            bulkRowCount++;
            updateBulkSummary();
        }

        function removeBulkRow(rowId) {
            const row = document.getElementById(`bulk-row-${rowId}`);
            if (row) {
                row.remove();
                // Renumber remaining items
                const rows = document.querySelectorAll('.bulk-item-row');
                rows.forEach((row, index) => {
                    const header = row.querySelector('h4');
                    if (header) {
                        header.textContent = `Item #${index + 1}`;
                    }
                });
                bulkRowCount = rows.length;
                updateBulkSummary();
            }
        }

        function updateBulkSummary() {
            const rows = document.querySelectorAll('.bulk-item-row');
            document.querySelector('.bulk-total-items').textContent = rows.length;
            
            // Show/hide remove buttons based on count
            if (rows.length <= 1) {
                document.querySelectorAll('.remove-row-btn').forEach(btn => {
                    btn.style.display = 'none';
                });
            } else {
                document.querySelectorAll('.remove-row-btn').forEach(btn => {
                    btn.style.display = 'flex';
                });
            }
        }

        function submitBulkProducts() {
            const rows = document.querySelectorAll('.bulk-item-row');
            const products = [];
            let isValid = true;
            
            rows.forEach((row, index) => {
                // Get values based on row structure
                let productName, category, stock, cost, selling;
                
                // Check if it's the first row (with class-based selectors) or subsequent rows (with index-based selectors)
                if (index === 0 && !row.querySelector(`.bulk-product-name-${index}`)) {
                    productName = row.querySelector('.bulk-product-name')?.value;
                    category = row.querySelector('.bulk-category')?.value;
                    stock = row.querySelector('.bulk-stock')?.value;
                    cost = row.querySelector('.bulk-cost')?.value;
                    selling = row.querySelector('.bulk-selling')?.value;
                } else {
                    productName = row.querySelector(`.bulk-product-name-${index}`)?.value;
                    category = row.querySelector(`.bulk-category-${index}`)?.value;
                    stock = row.querySelector(`.bulk-stock-${index}`)?.value;
                    cost = row.querySelector(`.bulk-cost-${index}`)?.value;
                    selling = row.querySelector(`.bulk-selling-${index}`)?.value;
                }
                
                if (!productName || !category || !stock || !cost || !selling) {
                    alert(`Please fill all required fields for Item #${index + 1}`);
                    isValid = false;
                    return;
                }
                
                products.push({
                    name: productName,
                    category: category,
                    sku: (index === 0 ? row.querySelector('.bulk-sku')?.value : row.querySelector(`.bulk-sku-${index}`)?.value) || '',
                    barcode: (index === 0 ? row.querySelector('.bulk-barcode')?.value : row.querySelector(`.bulk-barcode-${index}`)?.value) || '',
                    stock: parseInt(stock),
                    reorder: parseInt((index === 0 ? row.querySelector('.bulk-reorder')?.value : row.querySelector(`.bulk-reorder-${index}`)?.value) || 5),
                    cost: parseFloat(cost),
                    selling: parseFloat(selling),
                    location: (index === 0 ? row.querySelector('.bulk-location')?.value : row.querySelector(`.bulk-location-${index}`)?.value) || '',
                    description: (index === 0 ? row.querySelector('.bulk-description')?.value : row.querySelector(`.bulk-description-${index}`)?.value) || ''
                });
            });
            
            if (!isValid) return;
            
            if (products.length === 0) {
                alert('No products to add');
                return;
            }
            
            // Create a form to submit all products
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'inventory.php';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $csrf_token; ?>';
            form.appendChild(csrfInput);
            
            // Add bulk flag
            const bulkInput = document.createElement('input');
            bulkInput.type = 'hidden';
            bulkInput.name = 'bulk_add';
            bulkInput.value = '1';
            form.appendChild(bulkInput);
            
            // Add each product as JSON
            const productsInput = document.createElement('input');
            productsInput.type = 'hidden';
            productsInput.name = 'bulk_products';
            productsInput.value = JSON.stringify(products);
            form.appendChild(productsInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Bulk Delete Functions
        function showBulkDeleteModal() {
            document.getElementById('bulkDeleteModal').style.display = 'flex';
            updateDeleteButton();
        }

        function hideBulkDeleteModal() {
            document.getElementById('bulkDeleteModal').style.display = 'none';
        }

        function toggleAllProducts() {
            const selectAll = document.getElementById('selectAllProducts');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateDeleteButton();
        }

        function updateDeleteButton() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('confirmDeleteBtn').disabled = count === 0;
        }

        function confirmBulkDelete() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            
            if (ids.length === 0) {
                alert('Please select products to delete');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${ids.length} product(s)? This action cannot be undone.`)) {
                // Create form to submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'inventory.php';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo $csrf_token; ?>';
                form.appendChild(csrfInput);
                
                const bulkInput = document.createElement('input');
                bulkInput.type = 'hidden';
                bulkInput.name = 'bulk_delete';
                bulkInput.value = '1';
                form.appendChild(bulkInput);
                
                ids.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Table search
        document.getElementById('tableSearch')?.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('productsTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        });

        // Export inventory
        function exportInventory() {
            const format = prompt('Export as (pdf/excel/csv)?', 'excel');
            if (format) {
                window.location.href = 'export_inventory.php?format=' + format + window.location.search;
            }
        }

        // Time ago function
        function timeAgo(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            const intervals = {
                year: 31536000,
                month: 2592000,
                week: 604800,
                day: 86400,
                hour: 3600,
                minute: 60
            };
            
            for (const [unit, secondsInUnit] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / secondsInUnit);
                if (interval >= 1) {
                    return interval + ' ' + unit + (interval === 1 ? '' : 's') + ' ago';
                }
            }
            return 'just now';
        }

        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.alert').remove();
            });
        });

        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }

        // Highlight new product
        <?php if (isset($_GET['highlight'])): ?>
        setTimeout(() => {
            document.querySelector('.highlight')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 500);
        <?php endif; ?>

        // Initialize first row's remove button as hidden
        setTimeout(() => {
            updateBulkSummary();
        }, 100);
    </script>
</body>
</html>
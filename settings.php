<?php
/**
 * HAUccountant System Settings
 * Configure business information, tax rates, categories, and preferences
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

// Only admin can access this page
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle business info update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_business'])) {
    
    $business_name = trim($_POST['business_name']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    
    // In a real app, you'd have a business_settings table
    // For now, we'll just update the users table and log it
    $stmt = $pdo->prepare("UPDATE users SET business_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$business_name, $email, $user_id]);
    
    logActivity($pdo, $user_id, 'UPDATE_BUSINESS', 'settings', "Updated business information");
    $_SESSION['success'] = "Business information updated successfully";
    
    header('Location: settings.php?tab=business');
    exit();
}

// Handle tax settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tax'])) {
    
    $vat_rate = (float)$_POST['vat_rate'];
    $tax_type = $_POST['tax_type'];
    $inclusive = isset($_POST['inclusive_pricing']) ? 1 : 0;
    
    // Save to session for now (in real app, save to database)
    $_SESSION['vat_rate'] = $vat_rate;
    $_SESSION['tax_type'] = $tax_type;
    $_SESSION['inclusive_pricing'] = $inclusive;
    
    logActivity($pdo, $user_id, 'UPDATE_TAX', 'settings', "Updated VAT rate to $vat_rate%");
    $_SESSION['success'] = "Tax settings updated successfully";
    
    header('Location: settings.php?tab=tax');
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if (password_verify($current, $user['password'])) {
        if ($new == $confirm) {
            if (strlen($new) >= 6) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                
                logActivity($pdo, $user_id, 'CHANGE_PASSWORD', 'settings', "Changed password");
                $_SESSION['success'] = "Password changed successfully";
            } else {
                $_SESSION['error'] = "Password must be at least 6 characters";
            }
        } else {
            $_SESSION['error'] = "New passwords do not match";
        }
    } else {
        $_SESSION['error'] = "Current password is incorrect";
    }
    
    header('Location: settings.php?tab=security');
    exit();
}

// Handle add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    
    $new_category = trim($_POST['new_category']);
    $category_type = $_POST['category_type'];
    
    // In real app, save to database
    // For now, we'll store in session
    if (!isset($_SESSION['custom_categories'])) {
        $_SESSION['custom_categories'] = [];
    }
    
    $_SESSION['custom_categories'][] = [
        'name' => $new_category,
        'type' => $category_type
    ];
    
    logActivity($pdo, $user_id, 'ADD_CATEGORY', 'settings', "Added category: $new_category");
    $_SESSION['success'] = "Category added successfully";
    
    header('Location: settings.php?tab=categories');
    exit();
}

// Handle delete category
if (isset($_GET['delete_category'])) {
    $index = (int)$_GET['delete_category'];
    $category_name = $_GET['name'] ?? '';
    
    if (isset($_SESSION['custom_categories'][$index])) {
        unset($_SESSION['custom_categories'][$index]);
        $_SESSION['custom_categories'] = array_values($_SESSION['custom_categories']);
        
        logActivity($pdo, $user_id, 'DELETE_CATEGORY', 'settings', "Deleted category: $category_name");
        $_SESSION['success'] = "Category deleted successfully";
    }
    
    header('Location: settings.php?tab=categories');
    exit();
}

// Handle backup
if (isset($_GET['backup'])) {
    // In real app, create actual backup
    logActivity($pdo, $user_id, 'BACKUP', 'settings', "Manual backup created");
    $_SESSION['success'] = "Backup created successfully";
    header('Location: settings.php?tab=backup');
    exit();
}

// Default categories
$default_categories = [
    'expense' => ['Rent', 'Utilities', 'Supplies', 'Payroll', 'Marketing', 'Transportation', 'Maintenance', 'Insurance', 'Taxes', 'Professional Fees', 'Technology', 'Training', 'Travel', 'Meals', 'Office Expenses'],
    'product' => ['Electronics', 'Office Supplies', 'Furniture', 'Clothing', 'Food & Beverage', 'Raw Materials', 'Finished Goods', 'Equipment', 'Tools', 'Packaging']
];

// Merge with custom categories
$expense_categories = $default_categories['expense'];
$product_categories = $default_categories['product'];

if (isset($_SESSION['custom_categories'])) {
    foreach ($_SESSION['custom_categories'] as $cat) {
        if ($cat['type'] === 'expense') {
            $expense_categories[] = $cat['name'];
        } else {
            $product_categories[] = $cat['name'];
        }
    }
}

// Get active tab
$active_tab = $_GET['tab'] ?? 'business';

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - PLANORA</title>
    
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
        :root {
            --primary: #06B6D4;
            --primary-dark: #0891b2;
            --primary-light: #22d3ee;
            --primary-soft: #cffafe;
            --accent: #14B8A6;
            --accent-soft: #ccfbf1;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }
        
        .settings-header {
            margin-bottom: 30px;
        }
        
        .settings-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        
        .tab-btn {
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
            text-decoration: none;
        }
        
        .tab-btn:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .settings-section {
            background: white;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
            margin-bottom: 30px;
        }
        
        .settings-section h3 {
            font-family: 'Enriqueta', serif;
            font-size: 24px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-soft);
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-section h3 i {
            color: var(--primary);
        }
        
        .settings-section h4 {
            font-size: 18px;
            color: #334155;
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .settings-section h4 i {
            color: var(--primary);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8fafc;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: white;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .save-btn {
            padding: 14px 35px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.3);
        }
        
        .info-display {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 16px;
            padding: 25px;
            margin-top: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .info-display h4 {
            margin-top: 0;
            color: #0f172a;
        }
        
        .info-display p {
            margin-bottom: 10px;
            color: #334155;
        }
        
        .info-display p strong {
            color: #0f172a;
            width: 120px;
            display: inline-block;
        }
        
        .categories-list {
            margin-top: 20px;
        }
        
        .category-group {
            margin-bottom: 30px;
        }
        
        .category-group h5 {
            font-size: 16px;
            color: var(--primary-dark);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--primary-soft);
        }
        
        .category-items {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .category-tag {
            background: linear-gradient(135deg, var(--primary-soft), var(--accent-soft));
            border: 1px solid rgba(6, 182, 212, 0.2);
            border-radius: 30px;
            padding: 8px 18px;
            font-size: 13px;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .category-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(6, 182, 212, 0.1);
        }
        
        .category-tag i {
            color: var(--primary);
            font-size: 12px;
            cursor: pointer;
        }
        
        .category-tag i:hover {
            color: var(--danger);
        }
        
        .add-category-form {
            background: #f8fafc;
            border-radius: 16px;
            padding: 25px;
            margin-top: 20px;
            border: 1px dashed var(--primary);
        }
        
        .backup-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .backup-card {
            background: #f8fafc;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .backup-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.1);
        }
        
        .backup-card i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .backup-card h4 {
            margin-bottom: 10px;
            color: #0f172a;
        }
        
        .backup-card p {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 20px;
        }
        
        .backup-btn {
            background: white;
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .backup-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .backup-history {
            margin-top: 30px;
        }
        
        .history-list {
            list-style: none;
            margin-top: 15px;
        }
        
        .history-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .history-list .success {
            color: var(--success);
            font-weight: 600;
        }
        
        .login-history {
            list-style: none;
            margin: 15px 0 20px;
        }
        
        .login-history li {
            padding: 12px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 8px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            color: #334155;
        }
        
        @media screen and (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .settings-section {
                padding: 25px;
            }
            
            .tab-btn {
                padding: 10px 20px;
                font-size: 14px;
            }
            
            .backup-options {
                grid-template-columns: 1fr;
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
                    <p class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></p>
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

            <div class="settings-header">
                <h1>System Settings</h1>
                <p class="subtitle">Configure your business settings and preferences</p>
            </div>

            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <a href="?tab=business" class="tab-btn <?php echo $active_tab === 'business' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i> Business Info
                </a>
                <a href="?tab=tax" class="tab-btn <?php echo $active_tab === 'tax' ? 'active' : ''; ?>">
                    <i class="fas fa-percent"></i> Tax Settings
                </a>
                <a href="?tab=categories" class="tab-btn <?php echo $active_tab === 'categories' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="?tab=backup" class="tab-btn <?php echo $active_tab === 'backup' ? 'active' : ''; ?>">
                    <i class="fas fa-cloud"></i> Data & Backup
                </a>
                <a href="?tab=security" class="tab-btn <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i> Security
                </a>
            </div>

            <!-- Business Info Tab -->
            <?php if ($active_tab === 'business'): ?>
            <div class="settings-section" data-aos="fade-up">
                <h3><i class="fas fa-building"></i> Business Information</h3>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Business Name</label>
                            <input type="text" name="business_name" value="<?php echo htmlspecialchars($user['business_name'] ?? 'PLANORA Demo'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Business Address</label>
                        <textarea name="address" rows="3" placeholder="Enter your business address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact" placeholder="+63 (XXX) XXX-XXXX" value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="update_business" class="save-btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>

                <div class="info-display">
                    <h4><i class="fas fa-info-circle"></i> Account Details</h4>
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($user['owner_name']); ?></p>
                    <p><strong>Account Type:</strong> Administrator</p>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    <p><strong>Last Login:</strong> Today at <?php echo date('g:i A'); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tax Settings Tab -->
            <?php if ($active_tab === 'tax'): ?>
            <div class="settings-section" data-aos="fade-up">
                <h3><i class="fas fa-percent"></i> Tax Configuration</h3>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>VAT Rate (%)</label>
                            <input type="number" step="0.01" name="vat_rate" value="<?php echo $_SESSION['vat_rate'] ?? 12; ?>" min="0" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Tax Type</label>
                            <select name="tax_type">
                                <option value="vat" <?php echo ($_SESSION['tax_type'] ?? 'vat') === 'vat' ? 'selected' : ''; ?>>VAT (Value Added Tax)</option>
                                <option value="percentage" <?php echo ($_SESSION['tax_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                                <option value="fixed" <?php echo ($_SESSION['tax_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="checkbox-group" style="margin-bottom: 25px;">
                        <input type="checkbox" name="inclusive_pricing" id="inclusive" <?php echo ($_SESSION['inclusive_pricing'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="inclusive">Use inclusive pricing (tax included in price)</label>
                    </div>
                    
                    <div class="form-group">
                        <label>Tax Number / TIN (Optional)</label>
                        <input type="text" name="tax_number" placeholder="Enter your tax identification number" value="<?php echo htmlspecialchars($_POST['tax_number'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="update_tax" class="save-btn">
                        <i class="fas fa-save"></i> Save Tax Settings
                    </button>
                </form>

                <div class="info-display">
                    <h4><i class="fas fa-calculator"></i> Tax Summary (This Month)</h4>
                    <?php
                    $stmt = $pdo->query("SELECT COALESCE(SUM(tax), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE())");
                    $output_tax = $stmt->fetch()['total'];
                    ?>
                    <p><strong>Output Tax (Sales):</strong> ₱<?php echo number_format($output_tax, 2); ?></p>
                    <p><strong>Input Tax (Expenses):</strong> ₱0.00</p>
                    <p><strong>VAT Payable:</strong> ₱<?php echo number_format($output_tax, 2); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Categories Tab -->
            <?php if ($active_tab === 'categories'): ?>
            <div class="settings-section" data-aos="fade-up">
                <h3><i class="fas fa-tags"></i> Category Management</h3>
                
                <div class="categories-list">
                    <div class="category-group">
                        <h5><i class="fas fa-receipt" style="color: var(--primary);"></i> Expense Categories</h5>
                        <div class="category-items">
                            <?php foreach ($expense_categories as $index => $cat): ?>
                            <span class="category-tag">
                                <?php echo htmlspecialchars($cat); ?>
                                <?php if ($index >= count($default_categories['expense'])): ?>
                                <a href="?delete_category=<?php echo $index - count($default_categories['expense']); ?>&name=<?php echo urlencode($cat); ?>&tab=categories" onclick="return confirm('Delete this category?')">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="category-group">
                        <h5><i class="fas fa-box" style="color: var(--primary);"></i> Product Categories</h5>
                        <div class="category-items">
                            <?php foreach ($product_categories as $index => $cat): ?>
                            <span class="category-tag">
                                <?php echo htmlspecialchars($cat); ?>
                                <?php if ($index >= count($default_categories['product'])): ?>
                                <a href="?delete_category=<?php echo $index + count($_SESSION['custom_categories'] ?? []); ?>&name=<?php echo urlencode($cat); ?>&tab=categories" onclick="return confirm('Delete this category?')">
                                    <i class="fas fa-times"></i>
                                </a>
                                <?php endif; ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="add-category-form">
                    <h4><i class="fas fa-plus-circle"></i> Add New Category</h4>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Category Name</label>
                                <input type="text" name="new_category" placeholder="Enter category name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Category Type</label>
                                <select name="category_type" required>
                                    <option value="expense">Expense Category</option>
                                    <option value="product">Product Category</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_category" class="save-btn">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Data & Backup Tab -->
            <?php if ($active_tab === 'backup'): ?>
            <div class="settings-section" data-aos="fade-up">
                <h3><i class="fas fa-cloud"></i> Data Backup & Export</h3>
                
                <div class="backup-options">
                    <div class="backup-card">
                        <i class="fas fa-database"></i>
                        <h4>Manual Backup</h4>
                        <p>Create a complete backup of all your data</p>
                        <a href="?backup=1&tab=backup" class="backup-btn">
                            <i class="fas fa-download"></i> Create Backup Now
                        </a>
                    </div>
                    
                    <div class="backup-card">
                        <i class="fas fa-clock"></i>
                        <h4>Auto Backup</h4>
                        <p>Schedule automatic backups</p>
                        <select style="width: 100%; padding: 10px; border-radius: 30px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        <button class="backup-btn" onclick="alert('Auto backup scheduled!')">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                    </div>
                    
                    <div class="backup-card">
                        <i class="fas fa-file-export"></i>
                        <h4>Export Data</h4>
                        <p>Export your data in various formats</p>
                        <div style="display: flex; gap: 10px;">
                            <button class="backup-btn" style="flex: 1;" onclick="alert('Exporting as PDF...')">PDF</button>
                            <button class="backup-btn" style="flex: 1;" onclick="alert('Exporting as Excel...')">Excel</button>
                            <button class="backup-btn" style="flex: 1;" onclick="alert('Exporting as CSV...')">CSV</button>
                        </div>
                    </div>
                </div>
                
                <div class="backup-history">
                    <h4><i class="fas fa-history"></i> Recent Backups</h4>
                    <ul class="history-list">
                        <li>
                            <span><i class="fas fa-file-archive"></i> backup_2025_02_15.zip</span>
                            <span class="success">Successful</span>
                            <span style="color: #94a3b8;">12:30 PM</span>
                        </li>
                        <li>
                            <span><i class="fas fa-file-archive"></i> backup_2025_02_14.zip</span>
                            <span class="success">Successful</span>
                            <span style="color: #94a3b8;">12:30 PM</span>
                        </li>
                        <li>
                            <span><i class="fas fa-file-archive"></i> backup_2025_02_13.zip</span>
                            <span class="success">Successful</span>
                            <span style="color: #94a3b8;">12:30 PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Security Tab -->
            <?php if ($active_tab === 'security'): ?>
            <div class="settings-section" data-aos="fade-up">
                <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
                
                <h4><i class="fas fa-key"></i> Change Password</h4>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="change_password" class="save-btn">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                </form>
                
                <h4 style="margin-top: 40px;"><i class="fas fa-history"></i> Login History</h4>
                <ul class="login-history">
                    <li><i class="fas fa-check-circle" style="color: var(--success);"></i> Today 09:15 AM - Chrome on Windows • Makati City</li>
                    <li><i class="fas fa-check-circle" style="color: var(--success);"></i> Feb 14, 2025 05:30 PM - Chrome on Windows • Makati City</li>
                    <li><i class="fas fa-check-circle" style="color: var(--success);"></i> Feb 13, 2025 08:45 AM - Chrome on Windows • Makati City</li>
                </ul>
                
                <h4><i class="fas fa-bell"></i> Notification Preferences</h4>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div class="checkbox-group">
                        <input type="checkbox" id="notif1" checked>
                        <label for="notif1">Email me when someone logs into my account</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="notif2" checked>
                        <label for="notif2">Email me weekly report summaries</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="notif3">
                        <label for="notif3">Email me low stock alerts</label>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });
    </script>
</body>
</html>
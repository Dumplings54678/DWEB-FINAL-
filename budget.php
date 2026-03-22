<?php
/**
 * HAUccountant Budget Planning
 * Track and manage monthly financial targets
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Handle set budget
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_budget'])) {
    
    $month_year = trim($_POST['month_year']);
    $sales_target = (float)$_POST['sales_target'];
    $expense_limit = (float)$_POST['expense_limit'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($month_year)) {
        $errors[] = "Month is required.";
    }
    
    if ($sales_target <= 0) {
        $errors[] = "Sales target must be greater than 0.";
    }
    
    if ($expense_limit <= 0) {
        $errors[] = "Expense limit must be greater than 0.";
    }
    
    if (empty($errors)) {
        try {
            // Check if budgets table exists, if not create it
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS budgets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    month_year VARCHAR(20) NOT NULL,
                    sales_target DECIMAL(10,2) NOT NULL,
                    expense_limit DECIMAL(10,2) NOT NULL,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ");
            
            // Check if budget already exists for this month
            $stmt = $pdo->prepare("SELECT id FROM budgets WHERE month_year = ?");
            $stmt->execute([$month_year]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE budgets SET sales_target = ?, expense_limit = ? WHERE month_year = ?");
                $stmt->execute([$sales_target, $expense_limit, $month_year]);
                $_SESSION['success'] = "Budget updated successfully for " . $month_year;
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO budgets (month_year, sales_target, expense_limit, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$month_year, $sales_target, $expense_limit, $user_id]);
                $_SESSION['success'] = "Budget set successfully for " . $month_year;
            }
            
            header('Location: budget.php');
            exit();
            
        } catch (Exception $e) {
            error_log("Budget save failed: " . $e->getMessage());
            $_SESSION['error'] = "Failed to save budget. Please try again.";
            header('Location: budget.php');
            exit();
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
        header('Location: budget.php');
        exit();
    }
}

// Handle delete budget (admin only)
if (isset($_GET['delete']) && $user_role === 'admin') {
    $id = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Budget deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete budget.";
    }
    
    header('Location: budget.php');
    exit();
}

// Get selected month/year from URL or use current
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$selected_month_name = date('F Y', strtotime("$selected_year-$selected_month-01"));

// Get current month's budget
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE month_year = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$selected_month_name]);
$current_budget = $stmt->fetch();

// Get actual sales for selected month
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM sales 
    WHERE MONTH(sale_date) = ? AND YEAR(sale_date) = ?
");
$stmt->execute([$selected_month, $selected_year]);
$actual_sales = $stmt->fetch()['total'];

// Get actual expenses for selected month
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM expenses 
    WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?
");
$stmt->execute([$selected_month, $selected_year]);
$actual_expenses = $stmt->fetch()['total'];

// Calculate percentages
$sales_percentage = 0;
$expense_percentage = 0;
$remaining_sales = 0;
$remaining_expenses = 0;

if ($current_budget) {
    $sales_percentage = $current_budget['sales_target'] > 0 ? round(($actual_sales / $current_budget['sales_target']) * 100) : 0;
    $expense_percentage = $current_budget['expense_limit'] > 0 ? round(($actual_expenses / $current_budget['expense_limit']) * 100) : 0;
    $remaining_sales = max(0, $current_budget['sales_target'] - $actual_sales);
    $remaining_expenses = max(0, $current_budget['expense_limit'] - $actual_expenses);
}

// Get budget history (last 6 months)
$stmt = $pdo->prepare("
    SELECT * FROM budgets 
    ORDER BY created_at DESC 
    LIMIT 6
");
$stmt->execute();
$budget_history = $stmt->fetchAll();

// Generate months for dropdown (last 3 months to next 3 months)
$months = [];
for ($i = -2; $i <= 3; $i++) {
    $time = strtotime("$i months");
    $months[] = [
        'value' => date('F Y', $time),
        'month' => date('m', $time),
        'year' => date('Y', $time),
        'label' => date('F Y', $time)
    ];
}

// Get unread count for badge
$unread_count = getUnreadContactCount($pdo);

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planning - HAUccountant</title>
    
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
        
        .budget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .month-selector {
            display: flex;
            gap: 5px;
            align-items: center;
            background: white;
            padding: 5px;
            border-radius: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
            flex-wrap: wrap;
        }
        
        .month-btn {
            padding: 10px 20px;
            border: none;
            background: transparent;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #64748b;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .month-btn:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }
        
        .month-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .budget-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .budget-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
            transition: all 0.3s ease;
        }
        
        .budget-card:hover {
            box-shadow: 0 8px 30px rgba(6, 182, 212, 0.1);
            border-color: var(--primary);
        }
        
        .budget-card h3 {
            font-family: 'Enriqueta', serif;
            font-size: 22px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-soft);
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .budget-card h3 i {
            color: var(--primary);
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
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: white;
        }
        
        .set-budget-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .set-budget-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.3);
        }
        
        .performance-item {
            margin-bottom: 25px;
        }
        
        .performance-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        
        .progress-bar {
            height: 12px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 20px;
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .progress.over-budget {
            background: linear-gradient(90deg, var(--danger), #f87171);
        }
        
        .progress.warning {
            background: linear-gradient(90deg, var(--warning), #fbbf24);
        }
        
        .performance-stats {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #64748b;
        }
        
        .budget-warning {
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 16px 20px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #9a3412;
        }
        
        .budget-warning i {
            font-size: 24px;
            color: var(--warning);
        }
        
        .budget-warning.danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #fecaca;
            color: #991b1b;
        }
        
        .budget-warning.danger i {
            color: var(--danger);
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .history-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }
        
        .history-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        
        .history-table tr:hover td {
            background: #f8fafc;
        }
        
        .achieved {
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 30px;
            display: inline-block;
        }
        
        .achieved.good {
            background: #d1fae5;
            color: #065f46;
        }
        
        .achieved.warning {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .achieved.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .action-btn.delete {
            background: #fee2e2;
            color: var(--danger);
        }
        
        .action-btn.delete:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }
        
        .tips-card {
            background: linear-gradient(135deg, var(--primary-soft), var(--accent-soft));
            border-radius: 20px;
            padding: 24px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(6, 182, 212, 0.2);
        }
        
        .tips-card i {
            font-size: 32px;
            color: var(--primary);
        }
        
        .tips-card strong {
            color: #0f172a;
            font-size: 16px;
        }
        
        .tips-card p {
            color: #334155;
            margin-top: 5px;
        }
        
        /* CALENDAR MODAL STYLES */
        .calendar-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .calendar-modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 1000px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        
        .calendar-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-radius: 24px 24px 0 0;
        }
        
        .calendar-modal-header h3 {
            font-family: 'Enriqueta', serif;
            font-size: 22px;
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .calendar-modal-header .close {
            background: rgba(255,255,255,0.2);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
        }
        
        .calendar-body {
            padding: 25px;
        }
        
        .calendar-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .calendar-nav-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            background: white;
            cursor: pointer;
        }
        
        .calendar-nav-btn:hover {
            background: #cffafe;
            border-color: #06B6D4;
        }
        
        .current-month-year {
            font-size: 18px;
            font-weight: 600;
            min-width: 180px;
            text-align: center;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            border: 1px solid #e2e8f0;
        }
        
        .calendar-day:hover {
            border-color: #06B6D4;
            transform: scale(1.02);
        }
        
        .calendar-day.has-data {
            background: linear-gradient(135deg, #cffafe, #ccfbf1);
            border-color: #06B6D4;
        }
        
        .calendar-day-number {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .calendar-day-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 30px;
            margin-top: 4px;
        }
        
        .calendar-day-badge.good {
            background: #d1fae5;
            color: #065f46;
        }
        
        .calendar-day-badge.warning {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .calendar-day-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .calendar-day-amount {
            font-size: 10px;
            margin-top: 4px;
        }
        
        .day-details-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }
        
        .day-details-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        
        .day-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-radius: 24px 24px 0 0;
        }
        
        .day-details-header h3 {
            font-family: 'Enriqueta', serif;
            font-size: 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .day-details-header .close {
            background: rgba(255,255,255,0.2);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
        }
        
        .day-details-body {
            padding: 25px;
        }
        
        .day-details-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .day-stat {
            background: #f8fafc;
            padding: 15px;
            border-radius: 16px;
            text-align: center;
        }
        
        .day-stat .label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .day-stat .value {
            font-size: 20px;
            font-weight: 700;
        }
        
        .day-stat .value.positive {
            color: #10B981;
        }
        
        .day-stat .value.negative {
            color: #EF4444;
        }
        
        .performance-item {
            margin-bottom: 25px;
        }
        
        @media screen and (max-width: 768px) {
            .budget-grid {
                grid-template-columns: 1fr;
            }
            
            .budget-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .month-selector {
                width: 100%;
                overflow-x: auto;
                padding: 10px;
            }
            
            .tips-card {
                flex-direction: column;
                text-align: center;
            }
            
            .calendar-weekdays {
                font-size: 12px;
            }
            
            .calendar-day {
                padding: 5px;
            }
            
            .calendar-day-number {
                font-size: 12px;
            }
            
            .day-details-stats {
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

            <div class="budget-header">
                <div>
                    <h1>Budget Planning</h1>
                    <p class="subtitle">Set and track your monthly financial targets</p>
                </div>
                <div class="month-selector">
                    <?php foreach ($months as $m): ?>
                    <a href="?month=<?php echo $m['month']; ?>&year=<?php echo $m['year']; ?>" 
                       class="month-btn <?php echo ($m['month'] == $selected_month && $m['year'] == $selected_year) ? 'active' : ''; ?>">
                        <?php echo date('M', strtotime($m['value'])); ?>
                    </a>
                    <?php endforeach; ?>
                    <!-- ADDED CALENDAR BUTTON -->
                    <button class="month-btn" onclick="showBudgetCalendarModal()">
                        <i class="fas fa-calendar-alt"></i> Calendar
                    </button>
                </div>
            </div>

            <!-- Budget Grid -->
            <div class="budget-grid">
                <!-- Set Budget Form -->
                <div class="budget-card" data-aos="fade-right">
                    <h3><i class="fas fa-plus-circle"></i> Set Budget for <?php echo $selected_month_name; ?></h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label>Month</label>
                            <select name="month_year" required>
                                <?php foreach ($months as $m): ?>
                                <option value="<?php echo $m['value']; ?>" <?php echo ($m['value'] == $selected_month_name) ? 'selected' : ''; ?>>
                                    <?php echo $m['label']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Sales Target (₱)</label>
                            <input type="number" step="0.01" name="sales_target" 
                                   value="<?php echo $current_budget['sales_target'] ?? ''; ?>" 
                                   placeholder="Enter sales target" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Expense Limit (₱)</label>
                            <input type="number" step="0.01" name="expense_limit" 
                                   value="<?php echo $current_budget['expense_limit'] ?? ''; ?>" 
                                   placeholder="Enter expense limit" required>
                        </div>
                        
                        <button type="submit" name="set_budget" class="set-budget-btn">
                            <i class="fas fa-save"></i> Save Budget
                        </button>
                    </form>
                </div>

                <!-- Performance Card -->
                <div class="budget-card" data-aos="fade-left">
                    <h3><i class="fas fa-chart-line"></i> Performance Overview</h3>
                    
                    <?php if ($current_budget): ?>
                        <!-- Sales Target Progress -->
                        <div class="performance-item">
                            <div class="performance-header">
                                <span>Sales Target</span>
                                <span>₱<?php echo number_format($actual_sales, 2); ?> / ₱<?php echo number_format($current_budget['sales_target'], 2); ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress <?php echo $sales_percentage > 100 ? 'over-budget' : ''; ?>" 
                                     style="width: <?php echo min($sales_percentage, 100); ?>%"></div>
                            </div>
                            <div class="performance-stats">
                                <span><?php echo $sales_percentage; ?>% achieved</span>
                                <span>Remaining: ₱<?php echo number_format($remaining_sales, 2); ?></span>
                            </div>
                        </div>

                        <!-- Expense Limit Progress -->
                        <div class="performance-item">
                            <div class="performance-header">
                                <span>Expense Limit</span>
                                <span>₱<?php echo number_format($actual_expenses, 2); ?> / ₱<?php echo number_format($current_budget['expense_limit'], 2); ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress <?php echo $expense_percentage > 100 ? 'over-budget' : ($expense_percentage > 80 ? 'warning' : ''); ?>" 
                                     style="width: <?php echo min($expense_percentage, 100); ?>%"></div>
                            </div>
                            <div class="performance-stats">
                                <span><?php echo $expense_percentage; ?>% used</span>
                                <span>Remaining: ₱<?php echo number_format($remaining_expenses, 2); ?></span>
                            </div>
                        </div>

                        <!-- Warnings -->
                        <?php if ($expense_percentage > 100): ?>
                            <div class="budget-warning danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <strong>Budget Exceeded!</strong>
                                    <p>You have exceeded your expense limit by ₱<?php echo number_format($actual_expenses - $current_budget['expense_limit'], 2); ?></p>
                                </div>
                            </div>
                        <?php elseif ($expense_percentage > 80): ?>
                            <div class="budget-warning warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Approaching Budget Limit</strong>
                                    <p>You have used <?php echo $expense_percentage; ?>% of your expense limit</p>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div style="text-align: center; padding: 50px 20px;">
                            <i class="fas fa-chart-line" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b;">No budget set for <?php echo $selected_month_name; ?></p>
                            <p style="color: #94a3b8; font-size: 14px; margin-top: 10px;">Use the form to set your budget</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Budget History -->
            <?php if (!empty($budget_history)): ?>
            <div class="budget-card" data-aos="fade-up" style="margin-top: 20px;">
                <h3><i class="fas fa-history"></i> Budget History</h3>
                
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Sales Target</th>
                            <th>Expense Limit</th>
                            <th>Actual Sales</th>
                            <th>Actual Expenses</th>
                            <th>Achieved</th>
                            <?php if ($user_role === 'admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </thead>
                    <tbody>
                        <?php foreach ($budget_history as $history): 
                            // Get actuals for this budget month
                            $month_time = strtotime($history['month_year']);
                            $month_num = date('m', $month_time);
                            $year_num = date('Y', $month_time);
                            
                            $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE MONTH(sale_date) = ? AND YEAR(sale_date) = ?");
                            $stmt->execute([$month_num, $year_num]);
                            $hist_sales = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE MONTH(expense_date) = ? AND YEAR(expense_date) = ?");
                            $stmt->execute([$month_num, $year_num]);
                            $hist_expenses = $stmt->fetchColumn();
                            
                            $expense_pct = $history['expense_limit'] > 0 ? round(($hist_expenses / $history['expense_limit']) * 100) : 0;
                            
                            $status_class = 'good';
                            if ($expense_pct > 100) {
                                $status_class = 'danger';
                            } elseif ($expense_pct > 80) {
                                $status_class = 'warning';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo $history['month_year']; ?></strong></td>
                            <td>₱<?php echo number_format($history['sales_target'], 2); ?></td>
                            <td>₱<?php echo number_format($history['expense_limit'], 2); ?></td>
                            <td class="amount positive">₱<?php echo number_format($hist_sales, 2); ?></td>
                            <td class="amount negative">₱<?php echo number_format($hist_expenses, 2); ?></td>
                            <td>
                                <span class="achieved <?php echo $status_class; ?>">
                                    <?php echo $expense_pct; ?>%
                                </span>
                            </td>
                            <?php if ($user_role === 'admin'): ?>
                            <td>
                                <a href="?delete=<?php echo $history['id']; ?>" 
                                   class="action-btn delete"
                                   onclick="return confirm('Delete this budget?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Tips Card -->
            <div class="tips-card">
                <i class="fas fa-lightbulb"></i>
                <div>
                    <strong>Budget Tips:</strong>
                    <p>Try to keep your expenses below 80% of your budget limit to maintain a healthy profit margin. Review your budget monthly and adjust based on actual performance.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- BUDGET CALENDAR MODAL -->
    <div id="budgetCalendarModal" class="calendar-modal">
        <div class="calendar-modal-content">
            <div class="calendar-modal-header">
                <h3><i class="fas fa-calendar-alt"></i> Budget Calendar</h3>
                <span class="close" onclick="closeBudgetCalendarModal()">&times;</span>
            </div>
            <div class="calendar-body">
                <div class="calendar-grid">
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <button class="calendar-nav-btn" onclick="changeBudgetMonth(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span id="budgetCurrentMonthYear" class="current-month-year"></span>
                            <button class="calendar-nav-btn" onclick="changeBudgetMonth(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline" onclick="resetBudgetToCurrentMonth()">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                    </div>
                    <div class="calendar-weekdays">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div id="budgetCalendarDays" class="calendar-days"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- BUDGET MONTH DETAILS MODAL -->
    <div id="budgetMonthDetailsModal" class="day-details-modal">
        <div class="day-details-content">
            <div class="day-details-header">
                <h3><i class="fas fa-chart-line"></i> <span id="budgetMonthName"></span> Budget Overview</h3>
                <span class="close" onclick="closeBudgetMonthDetailsModal()">&times;</span>
            </div>
            <div class="day-details-body">
                <div class="day-details-stats">
                    <div class="day-stat">
                        <div class="label">Sales Target</div>
                        <div class="value positive" id="budgetSalesTarget">₱0.00</div>
                    </div>
                    <div class="day-stat">
                        <div class="label">Actual Sales</div>
                        <div class="value positive" id="budgetActualSales">₱0.00</div>
                    </div>
                    <div class="day-stat">
                        <div class="label">Expense Limit</div>
                        <div class="value negative" id="budgetExpenseLimit">₱0.00</div>
                    </div>
                    <div class="day-stat">
                        <div class="label">Actual Expenses</div>
                        <div class="value negative" id="budgetActualExpenses">₱0.00</div>
                    </div>
                </div>
                
                <div class="performance-item">
                    <div class="performance-header">
                        <span>Sales Achievement</span>
                        <span id="budgetSalesPercent">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div id="budgetSalesProgress" class="progress" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="performance-item">
                    <div class="performance-header">
                        <span>Expense Usage</span>
                        <span id="budgetExpensePercent">0%</span>
                    </div>
                    <div class="progress-bar">
                        <div id="budgetExpenseProgress" class="progress" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="day-stat" style="background: #f1f5f9; margin-top: 15px;">
                    <div class="label">Net Profit</div>
                    <div class="value" id="budgetProfit">₱0.00</div>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <a href="budget.php" class="btn btn-primary btn-sm">View Full Budget Details <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });

        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) alert.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // ============================================
        // BUDGET CALENDAR FUNCTIONS
        // ============================================
        let budgetCurrentYear = new Date().getFullYear();
        let budgetCurrentMonth = new Date().getMonth();
        let budgetData = {};

        function showBudgetCalendarModal() {
            document.getElementById('budgetCalendarModal').style.display = 'flex';
            loadBudgetData();
            renderBudgetCalendar();
        }

        function closeBudgetCalendarModal() {
            document.getElementById('budgetCalendarModal').style.display = 'none';
        }

        function closeBudgetMonthDetailsModal() {
            document.getElementById('budgetMonthDetailsModal').style.display = 'none';
        }

        function loadBudgetData() {
            <?php
            // Get budgets with actual vs target
            $budget_query = $pdo->query("
                SELECT 
                    b.month_year,
                    b.sales_target,
                    b.expense_limit,
                    COALESCE(SUM(s.total_amount), 0) as actual_sales,
                    COALESCE(SUM(e.amount), 0) as actual_expenses
                FROM budgets b
                LEFT JOIN sales s ON MONTH(s.sale_date) = MONTH(STR_TO_DATE(b.month_year, '%M %Y')) 
                    AND YEAR(s.sale_date) = YEAR(STR_TO_DATE(b.month_year, '%M %Y'))
                LEFT JOIN expenses e ON MONTH(e.expense_date) = MONTH(STR_TO_DATE(b.month_year, '%M %Y')) 
                    AND YEAR(e.expense_date) = YEAR(STR_TO_DATE(b.month_year, '%M %Y'))
                GROUP BY b.id
                ORDER BY STR_TO_DATE(b.month_year, '%M %Y') DESC
            ");
            $budget_data = $budget_query->fetchAll();
            ?>
            
            budgetData = {};
            <?php foreach ($budget_data as $budget): 
                $month_num = date('m', strtotime($budget['month_year']));
                $year_num = date('Y', strtotime($budget['month_year']));
            ?>
            budgetData['<?php echo $year_num . '-' . $month_num; ?>'] = {
                month: '<?php echo $budget['month_year']; ?>',
                sales_target: <?php echo $budget['sales_target']; ?>,
                expense_limit: <?php echo $budget['expense_limit']; ?>,
                actual_sales: <?php echo $budget['actual_sales']; ?>,
                actual_expenses: <?php echo $budget['actual_expenses']; ?>,
                profit: <?php echo $budget['actual_sales'] - $budget['actual_expenses']; ?>
            };
            <?php endforeach; ?>
        }

        function renderBudgetCalendar() {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('budgetCurrentMonthYear').innerHTML = monthNames[budgetCurrentMonth] + ' ' + budgetCurrentYear;
            
            const firstDay = new Date(budgetCurrentYear, budgetCurrentMonth, 1);
            const lastDay = new Date(budgetCurrentYear, budgetCurrentMonth + 1, 0);
            const startDayOfWeek = firstDay.getDay();
            const daysInMonth = lastDay.getDate();
            
            let calendarHTML = '';
            
            for (let i = 0; i < startDayOfWeek; i++) {
                calendarHTML += '<div class="calendar-day empty"></div>';
            }
            
            for (let i = 1; i <= daysInMonth; i++) {
                const monthKey = budgetCurrentYear + '-' + String(budgetCurrentMonth + 1).padStart(2, '0');
                const hasBudget = budgetData[monthKey];
                
                let dayClass = 'calendar-day';
                let badgeHTML = '';
                let amountHTML = '';
                
                if (hasBudget) {
                    dayClass = 'calendar-day has-data';
                    const expensePercent = (hasBudget.actual_expenses / hasBudget.expense_limit) * 100;
                    const statusClass = expensePercent > 100 ? 'danger' : (expensePercent > 80 ? 'warning' : 'good');
                    badgeHTML = `<div class="calendar-day-badge ${statusClass}">${expensePercent.toFixed(0)}%</div>`;
                    amountHTML = `<div class="calendar-day-amount">₱${hasBudget.actual_expenses.toFixed(2)} / ₱${hasBudget.expense_limit.toFixed(2)}</div>`;
                }
                
                calendarHTML += `
                    <div class="${dayClass}" onclick="showBudgetDayDetails('${monthKey}')">
                        <div class="calendar-day-number">${i}</div>
                        ${badgeHTML}
                        ${amountHTML}
                    </div>
                `;
            }
            
            const totalCells = startDayOfWeek + daysInMonth;
            const remainingCells = totalCells <= 35 ? 35 - totalCells : 42 - totalCells;
            for (let i = 0; i < remainingCells; i++) {
                calendarHTML += '<div class="calendar-day empty"></div>';
            }
            
            document.getElementById('budgetCalendarDays').innerHTML = calendarHTML;
        }

        function changeBudgetMonth(delta) {
            budgetCurrentMonth += delta;
            if (budgetCurrentMonth < 0) {
                budgetCurrentMonth = 11;
                budgetCurrentYear--;
            } else if (budgetCurrentMonth > 11) {
                budgetCurrentMonth = 0;
                budgetCurrentYear++;
            }
            renderBudgetCalendar();
        }

        function resetBudgetToCurrentMonth() {
            budgetCurrentYear = new Date().getFullYear();
            budgetCurrentMonth = new Date().getMonth();
            renderBudgetCalendar();
        }

        function showBudgetDayDetails(monthKey) {
            const data = budgetData[monthKey];
            if (!data) return;
            
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const monthNum = parseInt(monthKey.split('-')[1]) - 1;
            const yearNum = monthKey.split('-')[0];
            const formattedMonth = monthNames[monthNum] + ' ' + yearNum;
            
            document.getElementById('budgetMonthName').innerHTML = formattedMonth;
            document.getElementById('budgetSalesTarget').innerHTML = '₱' + data.sales_target.toFixed(2);
            document.getElementById('budgetActualSales').innerHTML = '₱' + data.actual_sales.toFixed(2);
            document.getElementById('budgetExpenseLimit').innerHTML = '₱' + data.expense_limit.toFixed(2);
            document.getElementById('budgetActualExpenses').innerHTML = '₱' + data.actual_expenses.toFixed(2);
            
            const salesPercent = (data.actual_sales / data.sales_target) * 100;
            const expensePercent = (data.actual_expenses / data.expense_limit) * 100;
            document.getElementById('budgetSalesPercent').innerHTML = salesPercent.toFixed(1) + '%';
            document.getElementById('budgetExpensePercent').innerHTML = expensePercent.toFixed(1) + '%';
            document.getElementById('budgetSalesProgress').style.width = Math.min(salesPercent, 100) + '%';
            document.getElementById('budgetExpenseProgress').style.width = Math.min(expensePercent, 100) + '%';
            
            const profit = data.profit;
            const profitClass = profit >= 0 ? 'positive' : 'negative';
            document.getElementById('budgetProfit').innerHTML = '₱' + profit.toFixed(2);
            document.getElementById('budgetProfit').className = profitClass;
            
            document.getElementById('budgetMonthDetailsModal').style.display = 'flex';
        }
    </script>
</body>
</html>
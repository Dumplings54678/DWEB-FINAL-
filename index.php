<?php
/**
 * HAUccountant Dashboard
 * Enhanced with real-time updates, interactive charts, stock watchlist, and better UX
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Get dashboard statistics
$stats = getDashboardStats($pdo, $user_id);

// Get recent sales with more details
$stmt = $pdo->prepare("
    SELECT s.*, p.product_name, p.category, p.stock_quantity,
           u.owner_name as cashier_name
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_sales = $stmt->fetchAll();

// Get recent expenses
$stmt = $pdo->prepare("
    SELECT e.*, u.owner_name as created_by_name
    FROM expenses e
    LEFT JOIN users u ON e.created_by = u.id
    ORDER BY e.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_expenses = $stmt->fetchAll();

// ============================================
// STOCK WATCHLIST - Get products for display
// ============================================
$stmt = $pdo->prepare("
    SELECT * FROM products 
    ORDER BY 
        CASE 
            WHEN stock_quantity = 0 THEN 1
            WHEN stock_quantity < COALESCE(reorder_level, 5) THEN 2
            ELSE 3
        END,
        stock_quantity ASC 
    LIMIT 10
");
$stmt->execute();
$watchlist_stocks = $stmt->fetchAll();

// If no products found, get any products
if (empty($watchlist_stocks)) {
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $watchlist_stocks = $stmt->fetchAll();
}

// ============================================
// FIXED: PORTFOLIO GROWTH - Using inventory_snapshots table
// ============================================

// First, ensure we have snapshots for the last 7 days
// This will create missing snapshots
$check_snapshots = $pdo->query("
    INSERT INTO inventory_snapshots (snapshot_date, total_value, total_cost, total_products, total_stock)
    SELECT 
        DATE_SUB(CURDATE(), INTERVAL days.n DAY) as snapshot_date,
        (SELECT SUM(stock_quantity * selling_price) FROM products) as total_value,
        (SELECT SUM(stock_quantity * cost_price) FROM products) as total_cost,
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT SUM(stock_quantity) FROM products) as total_stock
    FROM (
        SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
        UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
    ) days
    WHERE NOT EXISTS (
        SELECT 1 FROM inventory_snapshots is2 
        WHERE is2.snapshot_date = DATE_SUB(CURDATE(), INTERVAL days.n DAY)
    )
    ON DUPLICATE KEY UPDATE
        total_value = VALUES(total_value),
        total_cost = VALUES(total_cost),
        total_products = VALUES(total_products),
        total_stock = VALUES(total_stock)
");

// Get inventory snapshots for the last 7 days
$snapshots = $pdo->query("
    SELECT 
        snapshot_date,
        total_value,
        total_cost,
        total_products,
        total_stock
    FROM inventory_snapshots
    WHERE snapshot_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    ORDER BY snapshot_date ASC
")->fetchAll();

// Prepare data for the chart
$portfolio_dates = [];
$portfolio_values = [];
$portfolio_tooltips = [];

// If we have snapshots, use them
if (count($snapshots) > 0) {
    foreach ($snapshots as $snapshot) {
        $portfolio_dates[] = date('D', strtotime($snapshot['snapshot_date']));
        $portfolio_values[] = (float)$snapshot['total_value'];
        $portfolio_tooltips[] = date('M j, Y', strtotime($snapshot['snapshot_date'])) . 
                                "\nValue: ₱" . number_format($snapshot['total_value'], 2) .
                                "\nProducts: " . $snapshot['total_products'] .
                                "\nTotal Units: " . $snapshot['total_stock'];
    }
} else {
    // Fallback to current value if no snapshots exist
    $current_value = $pdo->query("SELECT SUM(stock_quantity * selling_price) FROM products")->fetchColumn();
    for ($i = 6; $i >= 0; $i--) {
        $date = date('D', strtotime("-$i days"));
        $portfolio_dates[] = $date;
        $portfolio_values[] = (float)$current_value;
    }
}

// Calculate growth from first to last day
$first_value = $portfolio_values[0] ?? 0;
$last_value = $portfolio_values[count($portfolio_values) - 1] ?? 0;
$overall_growth = $first_value > 0 ? round((($last_value - $first_value) / $first_value) * 100, 1) : 0;

// Get current inventory stats for the summary card
$current_inventory = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        SUM(stock_quantity) as total_stock,
        SUM(stock_quantity * selling_price) as total_value
    FROM products
")->fetch();
$current_value = $current_inventory['total_value'] ?? 0;
$total_products = $current_inventory['total_products'] ?? 0;
$total_stock = $current_inventory['total_stock'] ?? 0;

// ============================================
// Get sales trends for stock calculation
// ============================================
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.product_name,
        COALESCE(SUM(s.quantity), 0) as sold_last_30_days
    FROM products p
    LEFT JOIN sales s ON p.id = s.product_id 
        AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY p.id
");
$stmt->execute();
$sales_trends = $stmt->fetchAll();
$sales_trend_map = [];
foreach ($sales_trends as $trend) {
    $sales_trend_map[$trend['id']] = $trend['sold_last_30_days'];
}

// Calculate days until stock runs out for each product
foreach ($watchlist_stocks as &$stock) {
    $sold_last_30 = $sales_trend_map[$stock['id']] ?? 0;
    $daily_avg = $sold_last_30 > 0 ? $sold_last_30 / 30 : 0;
    
    if ($daily_avg > 0 && $stock['stock_quantity'] > 0) {
        $days_until_out = floor($stock['stock_quantity'] / $daily_avg);
        $stock['days_remaining'] = $days_until_out;
        $stock['trend'] = $daily_avg;
    } else {
        $stock['days_remaining'] = 'N/A';
        $stock['trend'] = 0;
    }
    
    // Calculate price change based on last 3 sales (if any)
    $price_stmt = $pdo->prepare("
        SELECT unit_price FROM sales 
        WHERE product_id = ? 
        ORDER BY sale_date DESC 
        LIMIT 3
    ");
    $price_stmt->execute([$stock['id']]);
    $recent_prices = $price_stmt->fetchAll();
    
    if (count($recent_prices) >= 2) {
        $avg_recent = array_sum(array_column($recent_prices, 'unit_price')) / count($recent_prices);
        $change_percent = round((($stock['selling_price'] - $avg_recent) / $avg_recent) * 100, 1);
        $stock['change_percent'] = $change_percent;
    } else {
        // No price change data, use 0
        $stock['change_percent'] = 0;
    }
    
    $stock['change_class'] = $stock['change_percent'] >= 0 ? 'positive' : 'negative';
    $stock['change_icon'] = $stock['change_percent'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
}

// Calculate totals
$total_revenue = array_sum(array_column($recent_sales, 'total_amount'));
$total_expenses = array_sum(array_column($recent_expenses, 'amount'));
$net_profit = $total_revenue - $total_expenses;

// Determine business status
$business_status = [
    'text' => $net_profit > 0 ? 'Profitable' : ($net_profit < 0 ? 'Losing' : 'Break-even'),
    'class' => $net_profit > 0 ? 'success' : ($net_profit < 0 ? 'danger' : 'warning'),
    'icon' => $net_profit > 0 ? 'fa-chart-line' : ($net_profit < 0 ? 'fa-chart-line' : 'fa-minus-circle')
];

// Get weekly sales data for revenue chart
$stmt = $pdo->prepare("
    SELECT DATE(sale_date) as date, 
           SUM(total_amount) as total,
           COUNT(*) as transactions
    FROM sales 
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY sale_date
");
$stmt->execute();
$weekly_sales = $stmt->fetchAll();

// Get weekly expenses data for revenue chart
$stmt = $pdo->prepare("
    SELECT DATE(expense_date) as date, 
           SUM(amount) as total
    FROM expenses 
    WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(expense_date)
    ORDER BY expense_date
");
$stmt->execute();
$weekly_expenses = $stmt->fetchAll();

// Get recent activities
$stmt = $pdo->prepare("
    (SELECT 
        'sale' as type,
        CONCAT('Sold ', s.quantity, ' x ', p.product_name) as description,
        s.total_amount as amount,
        s.created_at as date,
        u.owner_name as user
    FROM sales s
    JOIN products p ON s.product_id = p.id
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC
    LIMIT 5)
    UNION ALL
    (SELECT 
        'expense' as type,
        CONCAT('Expense: ', e.description) as description,
        -e.amount as amount,
        e.created_at as date,
        u.owner_name as user
    FROM expenses e
    LEFT JOIN users u ON e.created_by = u.id
    ORDER BY e.created_at DESC
    LIMIT 5)
    ORDER BY date DESC
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Get budget data
$stmt = $pdo->prepare("
    SELECT * FROM budgets 
    WHERE month_year = DATE_FORMAT(CURDATE(), '%M %Y')
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute();
$current_budget = $stmt->fetch();

$budget_progress = 0;
if ($current_budget && $current_budget['sales_target'] > 0) {
    $budget_progress = min(100, round(($stats['week_sales'] / $current_budget['sales_target']) * 100));
}

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
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
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        
        .dashboard-header h1 {
            font-family: 'Enriqueta', serif;
            font-size: 36px;
            color: var(--gray-900);
            margin-bottom: 4px;
        }
        
        .welcome-text {
            color: var(--gray-600);
            font-size: 16px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .date-display {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: white;
            border-radius: 30px;
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
            font-size: 14px;
        }
        
        .business-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .business-status.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .business-status.warning {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .business-status.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .welcome-banner {
            background: linear-gradient(105deg, var(--primary-dark), var(--primary), var(--accent));
            color: white;
            padding: 36px 40px;
            border-radius: 24px;
            margin-bottom: 36px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .welcome-banner h2 {
            font-family: 'Enriqueta', serif;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
        }
        
        .welcome-banner p {
            font-size: 18px;
            opacity: 0.95;
            position: relative;
            z-index: 2;
        }
        
        .banner-actions {
            display: flex;
            gap: 12px;
            position: relative;
            z-index: 2;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn-outline:hover {
            background: white;
            color: var(--primary);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 36px;
        }
        
        .summary-card {
            background: white;
            padding: 24px;
            border-radius: 20px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .summary-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .summary-card.revenue::before {
            background: linear-gradient(90deg, #10B981, #34D399);
        }
        
        .summary-card.expenses::before {
            background: linear-gradient(90deg, #EF4444, #F87171);
        }
        
        .summary-card.profit::before {
            background: linear-gradient(90deg, #F59E0B, #FBBF24);
        }
        
        .summary-card.portfolio::before {
            background: linear-gradient(90deg, #8B5CF6, #A78BFA);
        }
        
        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 24px;
        }
        
        .summary-card.revenue .summary-icon {
            background: #d1fae5;
            color: #10B981;
        }
        
        .summary-card.expenses .summary-icon {
            background: #fee2e2;
            color: #EF4444;
        }
        
        .summary-card.profit .summary-icon {
            background: #fed7aa;
            color: #F59E0B;
        }
        
        .summary-card.portfolio .summary-icon {
            background: #ede9fe;
            color: #8B5CF6;
        }
        
        .summary-label {
            color: var(--gray-600);
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .summary-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }
        
        .summary-value.positive {
            color: #10B981;
        }
        
        .summary-value.negative {
            color: #EF4444;
        }
        
        .summary-sub {
            color: var(--gray-500);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .summary-sub i.positive {
            color: #10B981;
        }
        
        .summary-sub i.negative {
            color: #EF4444;
        }
        
        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 36px;
        }
        
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-header h3 {
            font-family: 'Enriqueta', serif;
            font-size: 20px;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header h3 i {
            color: var(--primary);
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            background: var(--primary-soft);
            color: var(--primary-dark);
        }
        
        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge.warning {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .chart-container {
            height: 250px;
            position: relative;
        }
        
        /* Stock Watchlist */
        .watchlist-section {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 36px;
        }
        
        .section-title {
            font-family: 'Enriqueta', serif;
            font-size: 24px;
            color: var(--gray-900);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title i {
            color: var(--primary);
        }
        
        .section-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, var(--gray-300), transparent);
        }
        
        .section-header-actions {
            display: flex;
            gap: 10px;
        }
        
        .watchlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .stock-card {
            background: var(--gray-50);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stock-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stock-card.critical {
            border-left: 4px solid var(--danger);
        }
        
        .stock-card.low {
            border-left: 4px solid var(--warning);
        }
        
        .stock-card.good {
            border-left: 4px solid var(--success);
        }
        
        .stock-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .stock-name {
            font-weight: 700;
            color: var(--gray-900);
            font-size: 16px;
        }
        
        .stock-category {
            font-size: 11px;
            color: var(--gray-500);
            background: white;
            padding: 2px 8px;
            border-radius: 30px;
            border: 1px solid var(--gray-200);
        }
        
        .stock-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        
        .stock-change {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .stock-change.positive {
            color: #10B981;
        }
        
        .stock-change.negative {
            color: #EF4444;
        }
        
        .stock-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--gray-200);
            font-size: 13px;
        }
        
        .stock-quantity {
            color: var(--gray-600);
        }
        
        .stock-value {
            font-weight: 600;
            color: var(--primary);
        }
        
        .stock-badge {
            padding: 4px 8px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .stock-badge.critical {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .stock-badge.low {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .stock-badge.good {
            background: #d1fae5;
            color: #065f46;
        }
        
        .stock-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        
        .stock-action-btn {
            flex: 1;
            padding: 6px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        
        .stock-action-btn.restock {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }
        
        .stock-action-btn.restock:hover {
            background: var(--primary);
            color: white;
        }
        
        .stock-action-btn.view {
            background: #f1f5f9;
            color: var(--gray-700);
        }
        
        .stock-action-btn.view:hover {
            background: var(--gray-300);
        }
        
        .days-remaining {
            font-size: 11px;
            color: var(--gray-500);
            margin-top: 4px;
        }
        
        .days-remaining.warning {
            color: var(--warning);
        }
        
        .days-remaining.danger {
            color: var(--danger);
        }
        
        /* Dashboard Row */
        .dashboard-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-bottom: 36px;
        }
        
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s ease;
        }
        
        .view-all:hover {
            gap: 8px;
            color: var(--primary-dark);
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .activity-icon.sale {
            background: #d1fae5;
            color: #10B981;
        }
        
        .activity-icon.expense {
            background: #fee2e2;
            color: #EF4444;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-text {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
        }
        
        .activity-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--gray-500);
        }
        
        .activity-amount {
            font-weight: 600;
        }
        
        .activity-amount.sale {
            color: #10B981;
        }
        
        .activity-amount.expense {
            color: #EF4444;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-600);
            border-bottom: 2px solid var(--gray-200);
        }
        
        .data-table td {
            padding: 12px 8px;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-700);
        }
        
        .data-table tr:hover td {
            background: var(--gray-50);
        }
        
        .amount.positive {
            color: #10B981;
            font-weight: 600;
        }
        
        .amount.negative {
            color: #EF4444;
            font-weight: 600;
        }
        
        .badge.sale {
            background: #d1fae5;
            color: #065f46;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 11px;
        }
        
        .badge.expense {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 11px;
        }
        
        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-top: 36px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .action-card {
            background: linear-gradient(135deg, var(--primary-soft), var(--accent-soft));
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            text-decoration: none;
            color: var(--gray-900);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 12px 24px rgba(6, 182, 212, 0.2);
        }
        
        .action-card i {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 12px;
            background: white;
            width: 64px;
            height: 64px;
            border-radius: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .action-card h4 {
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .action-card p {
            color: var(--gray-600);
            font-size: 13px;
        }
        
        /* Modal Styles */
        .modal {
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
        
        .modal-content {
            background: white;
            padding: 35px;
            border-radius: 24px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-header h3 {
            font-size: 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            color: var(--gray-500);
            transition: all 0.3s ease;
        }
        
        .close:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--gray-50);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: white;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #F59E0B, #D97706);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-500);
        }
        
        .empty-state i {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 15px;
        }
        
        .empty-state p {
            margin-bottom: 5px;
        }
        
        .empty-state .empty-message {
            color: var(--gray-500);
            font-size: 14px;
        }
        
        @media screen and (max-width: 1200px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media screen and (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            
            .dashboard-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media screen and (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .date-display {
                width: 100%;
                justify-content: center;
            }
            
            .welcome-banner {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .watchlist-grid {
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
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div>
                    <h1>Dashboard</h1>
                    <p class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                </div>
                <div class="header-actions">
                    <div class="date-display">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="business-status <?php echo $business_status['class']; ?>">
                        <i class="fas <?php echo $business_status['icon']; ?>"></i>
                        <span><?php echo $business_status['text']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="welcome-banner" data-aos="fade-up">
                <div class="banner-content">
                    <h2>Welcome to PLANORA</h2>
                    <p>Your complete business management solution</p>
                </div>
                <div class="banner-actions">
                    <a href="sales.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        New Sale
                    </a>
                    <a href="inventory.php" class="btn btn-outline">
                        <i class="fas fa-box"></i>
                        Manage Inventory
                    </a>
                </div>
            </div>

            <!-- 1. Summary Cards -->
            <div class="summary-grid" data-aos="fade-up">
                <!-- Total Revenue -->
                <div class="summary-card revenue">
                    <div class="summary-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="summary-label">Total Revenue (Recent)</div>
                    <div class="summary-value positive">₱<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="summary-sub">
                        <i class="fas fa-arrow-up positive"></i>
                        <span>From recent transactions</span>
                    </div>
                </div>
                
                <!-- Total Expenses -->
                <div class="summary-card expenses">
                    <div class="summary-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="summary-label">Total Expenses (Recent)</div>
                    <div class="summary-value negative">₱<?php echo number_format($total_expenses, 2); ?></div>
                    <div class="summary-sub">
                        <i class="fas fa-arrow-down negative"></i>
                        <span>From recent expenses</span>
                    </div>
                </div>
                
                <!-- Net Profit -->
                <div class="summary-card profit">
                    <div class="summary-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="summary-label">Net Profit (Recent)</div>
                    <div class="summary-value <?php echo $net_profit >= 0 ? 'positive' : 'negative'; ?>">
                        ₱<?php echo number_format($net_profit, 2); ?>
                    </div>
                    <div class="summary-sub">
                        <i class="fas <?php echo $net_profit >= 0 ? 'fa-arrow-up positive' : 'fa-arrow-down negative'; ?>"></i>
                        <span><?php echo $net_profit > 0 ? round(($net_profit / max($total_revenue, 1)) * 100, 1) : 0; ?>% margin</span>
                    </div>
                </div>
                
                <!-- Portfolio Value -->
                <div class="summary-card portfolio">
                    <div class="summary-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="summary-label">Current Inventory Value</div>
                    <div class="summary-value">₱<?php echo number_format($current_value, 2); ?></div>
                    <div class="summary-sub">
                        <i class="fas fa-chart-line positive"></i>
                        <span><?php echo $total_products; ?> products | <?php echo $total_stock; ?> units</span>
                    </div>
                </div>
            </div>

            <!-- 2. Charts Row -->
            <div class="charts-row">
                <!-- Revenue vs Expenses Chart -->
                <div class="chart-card" data-aos="fade-right">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-chart-line" style="color: var(--primary);"></i>
                            Revenue vs Expenses
                        </h3>
                        <span class="badge">Last 7 Days</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueExpenseChart"></canvas>
                    </div>
                </div>
                
                <!-- FIXED: Portfolio Growth Chart - Shows actual daily inventory values -->
                <div class="chart-card" data-aos="fade-left">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-chart-bar" style="color: var(--accent);"></i>
                            Daily Inventory Value
                        </h3>
                        <span class="badge <?php echo $overall_growth >= 0 ? 'success' : 'danger'; ?>">
                            <?php echo $overall_growth >= 0 ? '+' : ''; ?><?php echo $overall_growth; ?>% change
                        </span>
                    </div>
                    <div class="chart-container">
                        <canvas id="portfolioChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- 3. Stock Watchlist -->
            <div class="watchlist-section" data-aos="fade-up">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 class="section-title" style="margin-bottom: 0;">
                        <i class="fas fa-chart-line"></i>
                        Stock Watchlist
                    </h3>
                    <div class="section-header-actions">
                        <a href="inventory.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-boxes"></i> Manage Inventory
                        </a>
                    </div>
                </div>
                
                <?php if (count($watchlist_stocks) > 0): ?>
                <div class="watchlist-grid">
                    <?php foreach ($watchlist_stocks as $stock): 
                        // Determine stock status class
                        if ($stock['stock_quantity'] == 0) {
                            $stock_class = 'critical';
                            $status_text = 'Out of Stock';
                        } elseif ($stock['stock_quantity'] < ($stock['reorder_level'] ?? 5)) {
                            $stock_class = 'low';
                            $status_text = 'Low Stock';
                        } else {
                            $stock_class = 'good';
                            $status_text = 'In Stock';
                        }
                        
                        // Calculate days until out of stock
                        $days_remaining = $stock['days_remaining'] ?? 'N/A';
                        $days_class = '';
                        if (is_numeric($days_remaining)) {
                            if ($days_remaining <= 3) $days_class = 'danger';
                            elseif ($days_remaining <= 7) $days_class = 'warning';
                        }
                    ?>
                    <div class="stock-card <?php echo $stock_class; ?>">
                        <div class="stock-header">
                            <span class="stock-name"><?php echo htmlspecialchars($stock['product_name']); ?></span>
                            <span class="stock-category"><?php echo htmlspecialchars($stock['category'] ?? 'Stock'); ?></span>
                        </div>
                        <div class="stock-price">₱<?php echo number_format($stock['selling_price'], 2); ?></div>
                        <div class="stock-change <?php echo $stock['change_class']; ?>">
                            <i class="fas <?php echo $stock['change_icon']; ?>"></i>
                            <span><?php echo $stock['change_percent'] >= 0 ? '+' : ''; ?><?php echo $stock['change_percent']; ?>%</span>
                        </div>
                        <div class="stock-details">
                            <span class="stock-quantity">Qty: <strong><?php echo $stock['stock_quantity']; ?></strong> units</span>
                            <span class="stock-value">₱<?php echo number_format($stock['stock_quantity'] * $stock['selling_price'], 2); ?></span>
                        </div>
                        
                        <!-- Days remaining estimate -->
                        <?php if (is_numeric($days_remaining) && $days_remaining > 0 && $stock['stock_quantity'] > 0): ?>
                        <div class="days-remaining <?php echo $days_class; ?>">
                            <i class="fas fa-clock"></i> Est. <?php echo $days_remaining; ?> days remaining
                        </div>
                        <?php elseif ($stock['stock_quantity'] > 0 && $stock['trend'] == 0): ?>
                        <div class="days-remaining">
                            <i class="fas fa-minus-circle"></i> No sales data
                        </div>
                        <?php endif; ?>
                        
                        <!-- Action buttons -->
                        <div class="stock-actions">
                            <a href="inventory.php?highlight=<?php echo $stock['id']; ?>" class="stock-action-btn view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <button class="stock-action-btn restock" onclick="showQuickRestock(<?php echo $stock['id']; ?>, '<?php echo htmlspecialchars($stock['product_name']); ?>', <?php echo $stock['stock_quantity']; ?>)">
                                <i class="fas fa-plus-circle"></i> Restock
                            </button>
                        </div>
                        
                        <!-- Status badge -->
                        <div style="margin-top: 8px;">
                            <span class="stock-badge <?php echo $stock_class; ?>">
                                <?php echo $status_text; ?>
                                <?php if ($stock['stock_quantity'] > 0): ?>
                                (Reorder at <?php echo $stock['reorder_level'] ?? 5; ?>)
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>No products in inventory</p>
                    <p class="empty-message">Add products to your inventory to see them here</p>
                    <a href="inventory.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus-circle"></i> Add Products
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- 4. Recent Activity & 5. Quick Actions combined row -->
            <div class="dashboard-row">
                <!-- Recent Activity -->
                <div class="content-card" data-aos="fade-right">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-history"></i>
                            Recent Activity
                        </h3>
                        <a href="reports.php" class="view-all">
                            View All
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <?php if (count($recent_activities) > 0): ?>
                    <div class="activity-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $activity['type']; ?>">
                                <i class="fas fa-<?php echo $activity['type'] === 'sale' ? 'shopping-cart' : 'receipt'; ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-text"><?php echo htmlspecialchars($activity['description']); ?></div>
                                <div class="activity-meta">
                                    <span><i class="far fa-clock"></i> <?php echo timeAgo($activity['date']); ?></span>
                                    <span><i class="far fa-user"></i> <?php echo htmlspecialchars($activity['user'] ?? 'System'); ?></span>
                                </div>
                            </div>
                            <div class="activity-amount <?php echo $activity['type']; ?>">
                                <?php echo $activity['type'] === 'sale' ? '+' : ''; ?>₱<?php echo number_format(abs($activity['amount']), 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No recent activity</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Transactions -->
                <div class="content-card" data-aos="fade-left">
                    <div class="card-header">
                        <h3>
                            <i class="fas fa-exchange-alt"></i>
                            Recent Transactions
                        </h3>
                        <a href="" class="view-all">
                            View All
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <?php if (count($recent_sales) > 0 || count($recent_expenses) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recent_sales, 0, 5) as $sale): ?>
                            <tr>
                                <td><span class="badge sale">SALE</span></td>
                                <td><?php echo htmlspecialchars($sale['product_name']); ?> x<?php echo $sale['quantity']; ?></td>
                                <td class="amount positive">+₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td><?php echo timeAgo($sale['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach (array_slice($recent_expenses, 0, 3) as $expense): ?>
                            <tr>
                                <td><span class="badge expense">EXPENSE</span></td>
                                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                <td class="amount negative">-₱<?php echo number_format($expense['amount'], 2); ?></td>
                                <td><?php echo timeAgo($expense['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-exchange-alt"></i>
                        <p>No transactions yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5. Quick Actions -->
            <div class="quick-actions" data-aos="fade-up">
                <h3 class="section-title" style="margin-bottom: 20px;">
                    <i class="fas fa-rocket"></i>
                    Quick Actions
                </h3>
                
                <div class="actions-grid">
                    <!-- Add Stock -->
                    <a href="inventory.php" class="action-card">
                        <i class="fas fa-box"></i>
                        <h4>Add Stock</h4>
                        <p>Add new products to your inventory</p>
                    </a>
                    
                    <!-- Add Expense -->
                    <a href="expenses.php" class="action-card">
                        <i class="fas fa-receipt"></i>
                        <h4>Add Expense</h4>
                        <p>Record a new business expense</p>
                    </a>
                    
                    <!-- Create Plan/Budget -->
                    <a href="budget.php" class="action-card">
                        <i class="fas fa-wallet"></i>
                        <h4>Create Plan</h4>
                        <p>Set budget targets and financial plans</p>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- Quick Restock Modal -->
    <div id="quickRestockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-box"></i> Quick Restock</h3>
                <span class="close" onclick="hideQuickRestockModal()">&times;</span>
            </div>
            
            <form method="POST" action="inventory.php" id="restockForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="product_id" id="restock_product_id">
                <input type="hidden" name="adjust_stock" value="1">
                
                <div class="form-group">
                    <label>Product</label>
                    <input type="text" id="restock_product_name" class="form-control" readonly disabled style="background: #f1f5f9;">
                </div>
                
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="text" id="restock_current_stock" class="form-control" readonly disabled style="background: #f1f5f9;">
                </div>
                
                <div class="form-group">
                    <label>Quantity to Add</label>
                    <input type="number" name="adjustment" id="restock_quantity" min="1" value="10" required placeholder="Enter quantity">
                    <small style="color: #64748b;">This will increase your stock level</small>
                </div>
                
                <div class="form-group">
                    <label>Reason</label>
                    <select name="reason" required>
                        <option value="Stock received">Stock received</option>
                        <option value="Restock">Restock</option>
                        <option value="New shipment">New shipment</option>
                        <option value="Manual adjustment">Manual adjustment</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideQuickRestockModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Restock Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Stock Modal -->
    <div id="quickAddStockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-box"></i> Quick Add Stock</h3>
                <span class="close" onclick="hideQuickAddStockModal()">&times;</span>
            </div>
            
            <form method="POST" action="inventory.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required placeholder="e.g., Premium Coffee Beans">
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Office Supplies">Office Supplies</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Food">Food & Beverage</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock_quantity" min="0" value="1" required>
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Cost Price (₱)</label>
                        <input type="number" step="0.01" name="cost_price" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label>Selling Price (₱)</label>
                        <input type="number" step="0.01" name="selling_price" required placeholder="0.00">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideQuickAddStockModal()">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-success">
                        <i class="fas fa-check"></i> Add Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Expense Modal -->
    <div id="quickAddExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Quick Add Expense</h3>
                <span class="close" onclick="hideQuickAddExpenseModal()">&times;</span>
            </div>
            
            <form method="POST" action="expenses.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select</option>
                            <option value="Rent">Rent</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Supplies">Supplies</option>
                            <option value="Payroll">Payroll</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount (₱)</label>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" required placeholder="What was this expense for?"></textarea>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="card">Credit Card</option>
                            <option value="gcash">GCash</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideQuickAddExpenseModal()">Cancel</button>
                    <button type="submit" name="add_expense" class="btn btn-warning">
                        <i class="fas fa-check"></i> Add Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: true
        });

        // ============================================
        // QUICK RESTOCK FUNCTIONS
        // ============================================
        function showQuickRestock(id, name, currentStock) {
            document.getElementById('restock_product_id').value = id;
            document.getElementById('restock_product_name').value = name;
            document.getElementById('restock_current_stock').value = currentStock + ' units';
            document.getElementById('quickRestockModal').style.display = 'flex';
        }

        function hideQuickRestockModal() {
            document.getElementById('quickRestockModal').style.display = 'none';
        }

        // Quick Add Stock Modal Functions
        function showQuickAddStockModal() {
            document.getElementById('quickAddStockModal').style.display = 'flex';
        }

        function hideQuickAddStockModal() {
            document.getElementById('quickAddStockModal').style.display = 'none';
        }

        function showQuickAddExpenseModal() {
            document.getElementById('quickAddExpenseModal').style.display = 'flex';
        }

        function hideQuickAddExpenseModal() {
            document.getElementById('quickAddExpenseModal').style.display = 'none';
        }

        // ============================================
        // REVENUE VS EXPENSES CHART
        // ============================================
        const revExpCtx = document.getElementById('revenueExpenseChart').getContext('2d');
        
        // Prepare data for the last 7 days
        const labels = [];
        const revenueData = [];
        const expenseData = [];
        
        <?php
        // Create a map of dates to values
        $revenue_map = [];
        foreach ($weekly_sales as $sale) {
            $revenue_map[$sale['date']] = $sale['total'];
        }
        
        $expense_map = [];
        foreach ($weekly_expenses as $expense) {
            $expense_map[$expense['date']] = $expense['total'];
        }
        
        // Loop through last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $display_date = date('D', strtotime($date));
            echo "labels.push('$display_date');\n";
            echo "revenueData.push(" . ($revenue_map[$date] ?? 0) . ");\n";
            echo "expenseData.push(" . ($expense_map[$date] ?? 0) . ");\n";
        }
        ?>
        
        new Chart(revExpCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueData,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#10B981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#EF4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₱' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value;
                            }
                        }
                    }
                }
            }
        });

        // ============================================
        // FIXED: PORTFOLIO GROWTH CHART - Shows actual daily inventory values
        // Each bar represents the inventory value on that specific day
        // ============================================
        const portfolioCtx = document.getElementById('portfolioChart').getContext('2d');
        
        // Portfolio data from snapshots
        const portfolioLabels = <?php echo json_encode($portfolio_dates); ?>;
        const portfolioData = <?php echo json_encode($portfolio_values); ?>;
        
        new Chart(portfolioCtx, {
            type: 'bar',
            data: {
                labels: portfolioLabels,
                datasets: [{
                    label: 'Inventory Value',
                    data: portfolioData,
                    backgroundColor: '#8B5CF6',
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toFixed(2);
                            },
                            afterLabel: function(context) {
                                return 'Value on ' + context.label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value;
                            }
                        }
                    }
                }
            }
        });

        // Time ago function
        function timeAgo(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            if (seconds < 2592000) return Math.floor(seconds / 86400) + ' days ago';
            return date.toLocaleDateString();
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

        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.alert').remove();
            });
        });
    </script>
</body>
</html>
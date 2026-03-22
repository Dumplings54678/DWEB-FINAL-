<?php
require_once 'config/database.php';
redirectIfNotLoggedIn(); // This function checks if logged in
// Rest of your code...
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get today's sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE sale_date = CURDATE()");
$stmt->execute();
$today_sales = $stmt->fetch()['total'];

// Get this week's sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE WEEK(sale_date) = WEEK(CURDATE())");
$stmt->execute();
$week_sales = $stmt->fetch()['total'];

// Get best selling product
$stmt = $pdo->prepare("
    SELECT p.product_name, SUM(s.quantity) as total_sold 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    GROUP BY s.product_id 
    ORDER BY total_sold DESC 
    LIMIT 1
");
$stmt->execute();
$best_product = $stmt->fetch();

// Get recent sales
$stmt = $pdo->prepare("
    SELECT s.*, p.product_name 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    ORDER BY s.sale_date DESC 
    LIMIT 5
");
$stmt->execute();
$recent_sales = $stmt->fetchAll();

// Get total expenses this month
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
$stmt->execute();
$month_expenses = $stmt->fetch()['total'];

// Calculate net profit
$net_profit = $week_sales - $month_expenses;
$profit_status = $net_profit > 0 ? 'PROFITABLE' : ($net_profit < 0 ? 'LOSING' : 'BREAK-EVEN');
$status_color = $net_profit > 0 ? 'profitable' : ($net_profit < 0 ? 'losing' : 'breakeven');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLANORA  - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Enriqueta:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-section">
                <h1>PLANORA</h1>
                <p class="tagline">"Accounting Ko ang Account Mo."</p>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item active">Home</a>
                <a href="sales.php" class="nav-item">Sales</a>
                <a href="expenses.php" class="nav-item">Expenses</a>
                <a href="inventory.php" class="nav-item">Inventory</a>
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="budget.php" class="nav-item">Budget</a>
                <a href="users.php" class="nav-item">Users</a>
                <a href="settings.php" class="nav-item">Settings</a>
                <a href="about.php" class="nav-item">About</a>
            </nav>

            <div class="user-info">
                <p class="user-name"><?php echo $_SESSION['user_name']; ?></p>
                <p class="user-email"><?php echo $_SESSION['user_email'] ?? '123@gmail.com'; ?></p>
                <p class="demo-text">PLANORA Demo</p>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="welcome-banner">
                <h2>Welcome Back, <?php echo $_SESSION['user_name']; ?>!</h2>
                <p>Your trusted partner in accounting solutions is ready to help you manage your finances.</p>
            </div>

            <div class="performance-header">
                <h2>Business Performance</h2>
                <span class="indicator-badge <?php echo $status_color; ?>">
                    <i class="fa-regular fa-circle-check"></i>
                    <?php echo $profit_status; ?>
                </span>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Sales Today</h4>
                    <div class="value">₱<?php echo number_format($today_sales, 2); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Total Expenses (Month)</h4>
                    <div class="value">₱<?php echo number_format($month_expenses, 2); ?></div>
                </div>
                <div class="stat-card profit-card">
                    <h4>Net Profit/Loss</h4>
                    <div class="value">₱<?php echo number_format($net_profit, 2); ?></div>
                </div>
            </div>

            <h3 class="section-title">Quick Access</h3>
            <div class="feature-grid">
                <a href="sales.php" class="feature-card">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Sales</span>
                </a>
                <a href="expenses.php" class="feature-card">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Expenses</span>
                </a>
                <a href="inventory.php" class="feature-card">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Inventory</span>
                </a>
                <a href="reports.php" class="feature-card">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Reports</span>
                </a>
                <a href="budget.php" class="feature-card">
                    <i class="fa-solid fa-coins"></i>
                    <span>Budget</span>
                </a>
                <a href="users.php" class="feature-card">
                    <i class="fa-solid fa-users"></i>
                    <span>Users</span>
                </a>
            </div>

            <div class="dashboard-row">
                <div class="content-card">
                    <h3>Recent Transactions</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></td>
                                <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                <td>Sales</td>
                                <td>₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_sales)): ?>
                            <tr>
                                <td colspan="4" class="empty-message">No recent transactions found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="content-card">
                    <h3>Notifications</h3>
                    <div class="alert-panel warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><strong>Low-stock alert:</strong> Check inventory for items running low.</span>
                    </div>
                    <div class="mini-pnl">
                        <h4>Mini P&L Overview</h4>
                        <p>Current Week Overview</p>
                        <div class="pnl-summary">
                            <div>Revenue: ₱<?php echo number_format($week_sales, 2); ?></div>
                            <div>Expenses: ₱<?php echo number_format($month_expenses, 2); ?></div>
                            <div class="<?php echo $status_color; ?>">Net: ₱<?php echo number_format($net_profit, 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
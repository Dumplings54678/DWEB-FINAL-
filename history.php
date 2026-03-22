<?php
/**
 * HAUccountant Transaction History
 * View all user transactions
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Get transactions
$transactions = getUserTransactionHistory($pdo, $user_role === 'admin' ? null : $user_id);

// Apply filters
if ($filter_type !== 'all') {
    $transactions = array_filter($transactions, function($t) use ($filter_type) {
        return $t['type'] === $filter_type;
    });
}

if (!empty($filter_date)) {
    $transactions = array_filter($transactions, function($t) use ($filter_date) {
        return date('Y-m-d', strtotime($t['date'])) === $filter_date;
    });
}

if (!empty($search)) {
    $search_lower = strtolower($search);
    $transactions = array_filter($transactions, function($t) use ($search_lower) {
        return strpos(strtolower($t['item_name']), $search_lower) !== false ||
               strpos(strtolower($t['reference'] ?? ''), $search_lower) !== false;
    });
}

// Calculate totals
$total_revenue = array_sum(array_column(array_filter($transactions, function($t) {
    return $t['type'] === 'sale';
}), 'amount'));
$total_expenses = array_sum(array_column(array_filter($transactions, function($t) {
    return $t['type'] === 'expense';
}), 'amount'));
$net = $total_revenue - $total_expenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - PLANORA</title>
    
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
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            background: white;
            padding: 20px;
            border-radius: 20px;
            border: 1px solid rgba(6, 182, 212, 0.1);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #475569;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            background: #f8fafc;
        }
        
        .filter-btn {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .reset-btn {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .stat-box .label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-box .value {
            font-size: 28px;
            font-weight: 700;
        }
        
        .stat-box .value.revenue {
            color: #10B981;
        }
        
        .stat-box .value.expense {
            color: #EF4444;
        }
        
        .stat-box .value.net {
            color: #06B6D4;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .history-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .history-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        
        .history-table tr:hover td {
            background: #f8fafc;
        }
        
        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .type-badge.sale {
            background: #d1fae5;
            color: #065f46;
        }
        
        .type-badge.expense {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .amount.positive {
            color: #10B981;
            font-weight: 600;
        }
        
        .amount.negative {
            color: #EF4444;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #94a3b8;
        }
        
        @media screen and (max-width: 768px) {
            .stats-summary {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
                justify-content: space-between;
            }
            
            .filter-group select,
            .filter-group input {
                flex: 1;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
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
            <div class="history-header">
                <div>
                    <h1>Transaction History</h1>
                    <p class="subtitle">View all your past transactions</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar" data-aos="fade-up">
                <div class="filter-group">
                    <label>Type:</label>
                    <select id="filterType">
                        <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                        <option value="sale" <?php echo $filter_type === 'sale' ? 'selected' : ''; ?>>Sales</option>
                        <option value="expense" <?php echo $filter_type === 'expense' ? 'selected' : ''; ?>>Expenses</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date:</label>
                    <input type="date" id="filterDate" value="<?php echo $filter_date; ?>">
                </div>
                <div class="filter-group">
                    <label>Search:</label>
                    <input type="text" id="searchInput" placeholder="Search by item or reference..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
                <button class="reset-btn" onclick="resetFilters()">Reset</button>
            </div>

            <!-- Stats Summary -->
            <div class="stats-summary" data-aos="fade-up">
                <div class="stat-box">
                    <div class="label">Total Revenue</div>
                    <div class="value revenue">₱<?php echo number_format($total_revenue, 2); ?></div>
                </div>
                <div class="stat-box">
                    <div class="label">Total Expenses</div>
                    <div class="value expense">₱<?php echo number_format($total_expenses, 2); ?></div>
                </div>
                <div class="stat-box">
                    <div class="label">Net Profit/Loss</div>
                    <div class="value net">₱<?php echo number_format($net, 2); ?></div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div style="background: white; border-radius: 24px; padding: 20px; overflow: hidden;" data-aos="fade-up">
                <?php if (count($transactions) > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Item / Description</th>
                            <th>Reference</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <?php if ($user_role === 'admin'): ?>
                            <th>User</th>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i A', strtotime($transaction['date'])); ?></td>
                                <td>
                                    <span class="type-badge <?php echo $transaction['type']; ?>">
                                        <?php echo ucfirst($transaction['type']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($transaction['item_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($transaction['reference'] ?? '—'); ?></td>
                                <td><?php echo $transaction['quantity'] ?? '—'; ?></td>
                                <td class="amount <?php echo $transaction['type'] === 'sale' ? 'positive' : 'negative'; ?>">
                                    <?php echo $transaction['type'] === 'sale' ? '+' : '-'; ?>₱<?php echo number_format($transaction['amount'], 2); ?>
                                </td>
                                <?php if ($user_role === 'admin'): ?>
                                <td><?php echo htmlspecialchars($transaction['user_name'] ?? 'System'); ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p>No transactions found</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });

        function applyFilters() {
            const type = document.getElementById('filterType').value;
            const date = document.getElementById('filterDate').value;
            const search = document.getElementById('searchInput').value;
            
            let url = 'history.php?';
            if (type !== 'all') url += 'type=' + type + '&';
            if (date) url += 'date=' + date + '&';
            if (search) url += 'search=' + encodeURIComponent(search);
            
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = 'history.php';
        }
    </script>
</body>
</html>
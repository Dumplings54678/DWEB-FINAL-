<?php
/**
 * HAUccountant Admin User Management
 * View all registered users with their details
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

// Admin only
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Get all users with details
$users = getAllUsersDetails($pdo);

// Get login history
$login_history = getUserLoginHistory($pdo);

// Handle user status toggle
if (isset($_GET['toggle_status'])) {
    $user_id_toggle = (int)$_GET['toggle_status'];
    $new_status = $_GET['status'] ?? 'active';
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id_toggle]);
    
    $_SESSION['success'] = "User status updated successfully.";
    header('Location: admin_users.php');
    exit();
}

// Handle delete user
if (isset($_GET['delete_user']) && $_GET['delete_user'] != $user_id) {
    $user_id_delete = (int)$_GET['delete_user'];
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id_delete]);
    
    $_SESSION['success'] = "User deleted successfully.";
    header('Location: admin_users.php');
    exit();
}

// Get unread count for badge
$unread_count = getUnreadContactCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - HAUccountant</title>
    
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
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }
        
        .admin-tab {
            padding: 10px 28px;
            border: none;
            background: transparent;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            font-size: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-tab:hover {
            color: #06B6D4;
            background: #cffafe;
        }
        
        .admin-tab.active {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .user-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .user-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        
        .user-table tr:hover td {
            background: #f8fafc;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            background: #cffafe;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #06B6D4;
            font-size: 18px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .action-icon.edit {
            background: #cffafe;
            color: #0891b2;
        }
        
        .action-icon.edit:hover {
            background: #06B6D4;
            color: white;
        }
        
        .action-icon.delete {
            background: #fee2e2;
            color: #EF4444;
        }
        
        .action-icon.delete:hover {
            background: #EF4444;
            color: white;
        }
        
        .action-icon.toggle {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .action-icon.toggle:hover {
            background: #F59E0B;
            color: white;
        }
        
        .login-history-table {
            font-size: 13px;
        }
        
        @media screen and (max-width: 1024px) {
            .user-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media screen and (max-width: 768px) {
            .admin-tabs {
                flex-direction: column;
                align-items: stretch;
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

            <div class="admin-header">
                <div>
                    <h1>User Management</h1>
                    <p class="subtitle">View and manage all registered users</p>
                </div>
            </div>

            <!-- Admin Tabs -->
            <div class="admin-tabs" data-aos="fade-up">
                <a href="#" class="admin-tab active" onclick="switchTab('users'); return false;">
                    <i class="fas fa-users"></i> Registered Users
                </a>
                <a href="#" class="admin-tab" onclick="switchTab('login'); return false;">
                    <i class="fas fa-history"></i> Login History
                </a>
            </div>

            <!-- Users Tab -->
            <div id="users-tab" class="tab-content active" data-aos="fade-up">
                <div style="background: white; border-radius: 24px; padding: 20px; overflow: hidden;">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Business Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Sales</th>
                                <th>Expenses</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="user-avatar-small">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <strong><?php echo htmlspecialchars($user['owner_name']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['business_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['total_sales']; ?> (₱<?php echo number_format($user['total_revenue'] ?? 0, 2); ?>)</td>
                                <td><?php echo $user['total_expenses']; ?> (₱<?php echo number_format($user['total_expense_amount'] ?? 0, 2); ?>)</td>
                                <td><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?toggle_status=<?php echo $user['id']; ?>&status=<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>" 
                                           class="action-icon toggle" title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <?php if ($user['id'] != $user_id): ?>
                                        <a href="?delete_user=<?php echo $user['id']; ?>" 
                                           class="action-icon delete" 
                                           onclick="return confirm('Are you sure you want to delete this user?')"
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
            </div>

            <!-- Login History Tab -->
            <div id="login-tab" class="tab-content" data-aos="fade-up">
                <div style="background: white; border-radius: 24px; padding: 20px; overflow: hidden;">
                    <table class="user-table login-history-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Login Time</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($login_history as $login): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($login['owner_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($login['email']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($login['login_time'])); ?></td>
                                <td><?php echo htmlspecialchars($login['ip_address']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $login['status']; ?>">
                                        <?php echo ucfirst($login['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.admin-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show selected tab content
            document.getElementById('users-tab').classList.remove('active');
            document.getElementById('login-tab').classList.remove('active');
            
            if (tab === 'users') {
                document.getElementById('users-tab').classList.add('active');
            } else {
                document.getElementById('login-tab').classList.add('active');
            }
        }
    </script>
</body>
</html>
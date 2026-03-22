<?php
/**
 * HAUccountant Multi-User Management
 * Manage staff accounts, roles, and permissions
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

// Handle add user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    
    if (empty($errors)) {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already exists.";
        } else {
            // Hash password and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (owner_name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            
            if ($stmt->execute([$name, $email, $hashed, $role])) {
                logActivity($pdo, $user_id, 'ADD_USER', 'users', "Added user: $email");
                $_SESSION['success'] = "User added successfully.";
            } else {
                $_SESSION['error'] = "Failed to add user.";
            }
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
    }
    
    header('Location: users.php');
    exit();
}

// Handle edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    
    $id = (int)$_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    
    // Don't allow changing own status/role
    if ($id == $user_id) {
        $_SESSION['error'] = "You cannot edit your own account here.";
        header('Location: users.php');
        exit();
    }
    
    $stmt = $pdo->prepare("UPDATE users SET owner_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
    
    if ($stmt->execute([$name, $email, $role, $status, $id])) {
        logActivity($pdo, $user_id, 'EDIT_USER', 'users', "Edited user ID: $id");
        $_SESSION['success'] = "User updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update user.";
    }
    
    header('Location: users.php');
    exit();
}

// Handle delete user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Don't allow deleting yourself
    if ($id == $user_id) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        // Get user email for logging
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                logActivity($pdo, $user_id, 'DELETE_USER', 'users', "Deleted user: {$user['email']}");
                $_SESSION['success'] = "User deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete user.";
            }
        }
    }
    
    header('Location: users.php');
    exit();
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    
    if ($id != $user_id) {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $new_status = $user['status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            
            logActivity($pdo, $user_id, 'TOGGLE_USER', 'users', "Changed user ID: $id to $new_status");
            $_SESSION['success'] = "User status updated.";
        }
    }
    
    header('Location: users.php');
    exit();
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Get activity logs
$stmt = $pdo->prepare("
    SELECT l.*, u.owner_name 
    FROM activity_logs l 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 50
");
$stmt->execute();
$logs = $stmt->fetchAll();

// Get user counts
$total_users = count($users);
$active_users = 0;
$admin_count = 0;
foreach ($users as $u) {
    if ($u['status'] === 'active') $active_users++;
    if ($u['role'] === 'admin') $admin_count++;
}

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - PLANORA</title>
    
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
        /* Cyan/Blue Theme Variables */
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
        
        .stats-grid-small {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(6, 182, 212, 0.1);
            border-color: var(--primary);
        }
        
        .stat-box .number {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .stat-box .label {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        
        .user-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        
        .tab-btn {
            padding: 10px 28px;
            border: none;
            background: transparent;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            font-size: 15px;
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
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .add-btn {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.2);
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.3);
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
        }
        
        .user-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        
        .user-table tr:hover td {
            background: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
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
        
        .role-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-badge.admin {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
        }
        
        .role-badge.staff {
            background: #f1f5f9;
            color: #475569;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .icon-btn {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .icon-btn.edit {
            background: #cffafe;
            color: #06B6D4;
        }
        
        .icon-btn.edit:hover {
            background: #06B6D4;
            color: white;
            transform: translateY(-2px);
        }
        
        .icon-btn.delete {
            background: #fee2e2;
            color: #EF4444;
        }
        
        .icon-btn.delete:hover {
            background: #EF4444;
            color: white;
            transform: translateY(-2px);
        }
        
        .icon-btn.toggle {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .icon-btn.toggle:hover {
            background: #F59E0B;
            color: white;
            transform: translateY(-2px);
        }
        
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-header h3 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            color: #94a3b8;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }
        
        .log-table {
            font-size: 13px;
        }
        
        .log-table td {
            padding: 12px;
        }
        
        .log-time {
            color: #94a3b8;
            font-size: 11px;
        }
        
        .search-input {
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            width: 280px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        @media screen and (max-width: 768px) {
            .stats-grid-small {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-input {
                width: 100%;
            }
            
            .user-table {
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

            <div class="content-header">
                <h1>User Management</h1>
                <p class="subtitle">Manage staff accounts, roles, and permissions</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid-small" data-aos="fade-up">
                <div class="stat-box">
                    <div class="number"><?php echo $total_users; ?></div>
                    <div class="label">Total Users</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $active_users; ?></div>
                    <div class="label">Active Users</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $admin_count; ?></div>
                    <div class="label">Administrators</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?php echo $total_users - $active_users; ?></div>
                    <div class="label">Inactive</div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="user-tabs" data-aos="fade-up">
                <button class="tab-btn active" onclick="showTab('users')">Staff Accounts</button>
                <button class="tab-btn" onclick="showTab('logs')">Activity Logs</button>
            </div>

            <!-- Users Tab -->
            <div id="users-tab" class="tab-content active">
                <div class="action-bar">
                    <button class="add-btn" onclick="showAddUserModal()">
                        <i class="fas fa-plus-circle"></i> Add Staff Member
                    </button>
                    <div>
                        <input type="text" id="searchUser" class="search-input" placeholder="Search users...">
                    </div>
                </div>

                <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid rgba(6, 182, 212, 0.1);">
                    <table class="user-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['owner_name']); ?></strong>
                                </td>
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
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($user['id'] != $user_id): ?>
                                            <button class="icon-btn edit" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['owner_name']); ?>', '<?php echo $user['email']; ?>', '<?php echo $user['role']; ?>', '<?php echo $user['status']; ?>')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?toggle=<?php echo $user['id']; ?>" class="icon-btn toggle" title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'ban' : 'check'; ?>"></i>
                                            </a>
                                            <a href="?delete=<?php echo $user['id']; ?>" class="icon-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 12px;">Current User</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Logs Tab -->
            <div id="logs-tab" class="tab-content">
                <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid rgba(6, 182, 212, 0.1);">
                    <h3 style="margin-bottom: 20px; font-size: 18px; color: #0f172a;">Recent Activity Logs</h3>
                    
                    <?php if (count($logs) > 0): ?>
                    <table class="user-table log-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['owner_name']); ?></strong>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: <?php 
                                        echo strpos($log['action'], 'ADD') !== false ? '#10B981' : 
                                            (strpos($log['action'], 'DELETE') !== false ? '#EF4444' : '#06B6D4'); 
                                    ?>;">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                    <br>
                                    <small style="color: #94a3b8;"><?php echo htmlspecialchars($log['affected_record']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td class="log-time"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center; padding: 60px;">
                        <i class="fas fa-history" style="font-size: 48px; color: #cbd5e1;"></i>
                        <p style="margin-top: 15px; color: #64748b;">No activity logs found</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus" style="color: #06B6D4; margin-right: 10px;"></i> Add Staff Member</h3>
                <span class="close" onclick="hideAddUserModal()">&times;</span>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Enter full name">
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="user@example.com">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                    <small style="color: #64748b;">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="staff">Staff (Limited Access)</option>
                        <option value="admin">Admin (Full Access)</option>
                    </select>
                </div>
                
                <button type="submit" name="add_user" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #06B6D4, #14B8A6); color: white; border: none; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                    <i class="fas fa-save"></i> Add User
                </button>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit" style="color: #06B6D4; margin-right: 10px;"></i> Edit User</h3>
                <span class="close" onclick="hideEditUserModal()">&times;</span>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role">
                        <option value="staff">Staff (Limited Access)</option>
                        <option value="admin">Admin (Full Access)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" name="edit_user" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #06B6D4, #14B8A6); color: white; border: none; border-radius: 30px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                    <i class="fas fa-save"></i> Update User
                </button>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });

        // Tab switching
        function showTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tab === 'users') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('users-tab').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('logs-tab').classList.add('active');
            }
        }

        // Modal functions
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'flex';
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
            document.getElementById('editUserModal').style.display = 'flex';
        }

        function hideEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Search functionality
        document.getElementById('searchUser')?.addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
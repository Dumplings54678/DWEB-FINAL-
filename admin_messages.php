<?php
/**
 * HAUccountant Admin Messages
 * View and reply to user messages with full conversation history
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

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Get messages
$messages = getContactMessages($pdo, $status_filter === 'all' ? null : $status_filter);

// Handle reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_message'])) {
    $message_id = (int)$_POST['message_id'];
    $reply = trim($_POST['reply']);
    
    if (!empty($reply)) {
        if (replyContactMessage($pdo, $message_id, $reply, $user_id)) {
            $_SESSION['success'] = "Reply sent successfully!";
        } else {
            $_SESSION['error'] = "Failed to send reply.";
        }
    } else {
        $_SESSION['error'] = "Reply cannot be empty.";
    }
    
    header('Location: admin_messages.php?status=' . $status_filter . '&message=' . $message_id);
    exit();
}

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $message_id = (int)$_GET['mark_read'];
    markContactMessageRead($pdo, $message_id);
    header('Location: admin_messages.php?status=' . $status_filter);
    exit();
}

// Get counts
$unread_count = getUnreadContactCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Messages - HAUccountant</title>
    
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
        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            background: white;
            padding: 5px;
            border-radius: 40px;
            border: 1px solid #e2e8f0;
        }
        
        .filter-tab {
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            color: #64748b;
        }
        
        .filter-tab:hover {
            background: #cffafe;
            color: #0891b2;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
        }
        
        .message-conversation {
            background: white;
            border-radius: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .message-conversation.unread {
            border-left: 4px solid #EF4444;
        }
        
        .message-conversation-header {
            background: #f8fafc;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .message-sender-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .message-sender-name {
            font-weight: 700;
            font-size: 18px;
            color: #0f172a;
        }
        
        .message-sender-email {
            color: #64748b;
            font-size: 14px;
        }
        
        .message-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .message-badge.unread {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .message-badge.read {
            background: #d1fae5;
            color: #065f46;
        }
        
        .message-badge.replied {
            background: #cffafe;
            color: #0891b2;
        }
        
        .message-date {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .message-subject {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
            margin: 15px 25px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .message-thread {
            padding: 20px 25px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .message-bubble {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .message-bubble.user {
            align-items: flex-start;
        }
        
        .message-bubble.admin {
            align-items: flex-end;
        }
        
        .bubble-content {
            max-width: 80%;
            padding: 15px 20px;
            border-radius: 20px;
            position: relative;
        }
        
        .message-bubble.user .bubble-content {
            background: #f1f5f9;
            border-bottom-left-radius: 5px;
            color: #0f172a;
        }
        
        .message-bubble.admin .bubble-content {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-bottom-right-radius: 5px;
            color: white;
        }
        
        .bubble-meta {
            font-size: 11px;
            margin-top: 5px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .message-bubble.user .bubble-meta {
            color: #64748b;
            padding-left: 5px;
        }
        
        .message-bubble.admin .bubble-meta {
            color: rgba(255,255,255,0.7);
            justify-content: flex-end;
            padding-right: 5px;
        }
        
        .reply-section {
            padding: 20px 25px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .reply-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .reply-form textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            font-size: 14px;
            resize: vertical;
            font-family: inherit;
            background: white;
        }
        
        .reply-form textarea:focus {
            border-color: #06B6D4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .reply-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .reply-btn {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .reply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3);
        }
        
        .mark-read-btn {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mark-read-btn:hover {
            background: #e2e8f0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #94a3b8;
        }
        
        @media screen and (max-width: 768px) {
            .messages-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-tabs {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .message-conversation-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .bubble-content {
                max-width: 95%;
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

            <div class="messages-header">
                <div>
                    <h1>User Messages</h1>
                    <p class="subtitle">View and respond to user inquiries</p>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs" data-aos="fade-up">
                <a href="?status=all" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?status=unread" class="filter-tab <?php echo $status_filter === 'unread' ? 'active' : ''; ?>">
                    Unread <?php if ($unread_count > 0): ?><span style="background: #EF4444; color: white; padding: 2px 6px; border-radius: 30px; margin-left: 5px;"><?php echo $unread_count; ?></span><?php endif; ?>
                </a>
                <a href="?status=read" class="filter-tab <?php echo $status_filter === 'read' ? 'active' : ''; ?>">Read</a>
                <a href="?status=replied" class="filter-tab <?php echo $status_filter === 'replied' ? 'active' : ''; ?>">Replied</a>
            </div>

            <!-- Messages List with Conversation Thread -->
            <div data-aos="fade-up">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): 
                        $highlight = (isset($_GET['message']) && $_GET['message'] == $message['id']);
                    ?>
                    <div class="message-conversation <?php echo $message['status']; ?>" id="message-<?php echo $message['id']; ?>" style="<?php echo $highlight ? 'border: 2px solid #06B6D4;' : ''; ?>">
                        <div class="message-conversation-header">
                            <div class="message-sender-info">
                                <span class="message-sender-name"><?php echo htmlspecialchars($message['name']); ?></span>
                                <span class="message-sender-email">(<?php echo htmlspecialchars($message['email']); ?>)</span>
                                <?php if ($message['user_name']): ?>
                                <span class="badge">User: <?php echo htmlspecialchars($message['user_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="message-badge <?php echo $message['status']; ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                                <span class="message-date"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="message-subject">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($message['subject']); ?>
                        </div>
                        
                        <div class="message-thread">
                            <!-- User's Original Message -->
                            <div class="message-bubble user">
                                <div class="bubble-content">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                                <div class="bubble-meta">
                                    <span><i class="far fa-user"></i> <?php echo htmlspecialchars($message['name']); ?></span>
                                    <span><i class="far fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <!-- Admin Reply (if exists) -->
                            <?php if (!empty($message['admin_reply'])): ?>
                            <div class="message-bubble admin">
                                <div class="bubble-content">
                                    <?php echo nl2br(htmlspecialchars($message['admin_reply'])); ?>
                                </div>
                                <div class="bubble-meta">
                                    <span><i class="fas fa-user-shield"></i> Admin (<?php echo htmlspecialchars($user_name); ?>)</span>
                                    <span><i class="far fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($message['replied_at'])); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Reply Form - Always visible to allow multiple replies -->
                        <div class="reply-section">
                            <form method="POST" action="" class="reply-form">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <textarea name="reply" rows="3" placeholder="Type your reply here..."></textarea>
                                <div class="reply-actions">
                                    <?php if ($message['status'] !== 'read' && $message['status'] !== 'replied'): ?>
                                    <a href="?mark_read=<?php echo $message['id']; ?>&status=<?php echo $status_filter; ?>" class="mark-read-btn">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </a>
                                    <?php endif; ?>
                                    <button type="submit" name="reply_message" class="reply-btn">
                                        <i class="fas fa-paper-plane"></i> Send Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p>No messages found</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });
        
        // Scroll to highlighted message
        <?php if (isset($_GET['message'])): ?>
        setTimeout(() => {
            const element = document.getElementById('message-<?php echo $_GET['message']; ?>');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 500);
        <?php endif; ?>
    </script>
</body>
</html>
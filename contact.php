<?php
/**
 * HAUccountant Contact Page
 * Users can submit issues or concerns and view conversation history
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_email = $_SESSION['user_email'] ?? '';

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required.";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required.";
    }
    
    if (empty($errors)) {
        if (saveContactMessage($pdo, $user_id, $name, $email, $subject, $message)) {
            $_SESSION['success'] = "Your message has been sent successfully! We'll get back to you soon.";
            header('Location: contact.php?success=1');
            exit();
        } else {
            $_SESSION['error'] = "Failed to send message. Please try again.";
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
    }
}

// Handle reply to existing message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_to_message'])) {
    $message_id = (int)$_POST['message_id'];
    $reply = trim($_POST['reply']);
    
    if (!empty($reply)) {
        // Add reply to the message thread
        $stmt = $pdo->prepare("
            UPDATE contact_messages 
            SET admin_reply = CONCAT(IFNULL(admin_reply, ''), '\n\n--- Reply from " . ucfirst($user_role) . " on ' , NOW() , ' ---\n', ?),
                replied_by = ?,
                replied_at = NOW(),
                status = 'replied'
            WHERE id = ? AND user_id = ?
        ");
        
        if ($stmt->execute([$reply, $user_id, $message_id, $user_id])) {
            $_SESSION['success'] = "Your reply has been sent!";
        } else {
            $_SESSION['error'] = "Failed to send reply.";
        }
    } else {
        $_SESSION['error'] = "Reply cannot be empty.";
    }
    
    header('Location: contact.php?message=' . $message_id);
    exit();
}

// Get user's messages
$stmt = $pdo->prepare("
    SELECT * FROM contact_messages 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll();

// Get unread count for badge
$unread_count = getUnreadContactCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - PLANORA</title>
    
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
        .contact-header {
            margin-bottom: 30px;
        }
        
        .contact-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
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
            color: #06B6D4;
            background: #cffafe;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
        }
        
        .contact-info {
            background: white;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid rgba(6, 182, 212, 0.1);
        }
        
        .contact-info h3 {
            font-family: 'Enriqueta', serif;
            font-size: 24px;
            margin-bottom: 20px;
            color: #0f172a;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 16px;
            transition: all 0.3s ease;
        }
        
        .info-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }
        
        .info-icon {
            width: 48px;
            height: 48px;
            background: #cffafe;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #06B6D4;
            font-size: 20px;
        }
        
        .info-content h4 {
            font-size: 16px;
            margin-bottom: 5px;
            color: #0f172a;
        }
        
        .info-content p {
            color: #475569;
            font-size: 14px;
        }
        
        .info-content a {
            color: #06B6D4;
            text-decoration: none;
        }
        
        .contact-form {
            background: white;
            border-radius: 24px;
            padding: 30px;
            border: 1px solid rgba(6, 182, 212, 0.1);
        }
        
        .contact-form h3 {
            font-family: 'Enriqueta', serif;
            font-size: 24px;
            margin-bottom: 25px;
            color: #0f172a;
        }
        
        .message-conversation {
            background: white;
            border-radius: 24px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .message-conversation-header {
            background: #f8fafc;
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .message-subject {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin: 12px 20px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .message-thread {
            padding: 15px 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .message-bubble {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        
        .message-bubble.user {
            align-items: flex-end;
        }
        
        .message-bubble.admin {
            align-items: flex-start;
        }
        
        .bubble-content {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
        }
        
        .message-bubble.user .bubble-content {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-bottom-right-radius: 4px;
            color: white;
        }
        
        .message-bubble.admin .bubble-content {
            background: #f1f5f9;
            border-bottom-left-radius: 4px;
            color: #0f172a;
        }
        
        .bubble-meta {
            font-size: 10px;
            margin-top: 4px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .message-bubble.user .bubble-meta {
            color: rgba(255,255,255,0.7);
            justify-content: flex-end;
        }
        
        .message-bubble.admin .bubble-meta {
            color: #64748b;
        }
        
        .reply-section {
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .reply-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .reply-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 13px;
            resize: vertical;
            font-family: inherit;
        }
        
        .reply-form textarea:focus {
            border-color: #06B6D4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .reply-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .reply-btn {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }
        
        .reply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3);
        }
        
        .message-status {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 30px;
        }
        
        .message-status.unread {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .message-status.replied {
            background: #cffafe;
            color: #0891b2;
        }
        
        .message-date {
            font-size: 11px;
            color: #94a3b8;
        }
        
        .empty-messages {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            background: white;
            border-radius: 24px;
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
            border-color: #06B6D4;
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: white;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.3);
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @media screen and (max-width: 1024px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media screen and (max-width: 768px) {
            .info-item {
                flex-direction: column;
                text-align: center;
            }
            
            .info-icon {
                margin: 0 auto;
            }
            
            .message-bubble.user .bubble-content,
            .message-bubble.admin .bubble-content {
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

            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span>Your message has been sent successfully! Our team will get back to you soon.</span>
                </div>
            <?php endif; ?>

            <div class="contact-header">
                <h1>Contact Us</h1>
                <p class="subtitle">Have questions or concerns? We're here to help!</p>
            </div>

            <!-- Tabs -->
            <div class="contact-tabs" data-aos="fade-up">
                <button class="tab-btn active" onclick="switchTab('new')">New Message</button>
                <button class="tab-btn" onclick="switchTab('history')">
                    My Messages 
                    <?php if (count($messages) > 0): ?>
                    <span style="background: #06B6D4; color: white; padding: 2px 8px; border-radius: 30px; font-size: 11px; margin-left: 5px;"><?php echo count($messages); ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- New Message Tab -->
            <div id="new-tab" class="tab-content active">
                <div class="contact-grid">
                    <!-- Contact Info -->
                    <div class="contact-info" data-aos="fade-right">
                        <h3>Get in Touch</h3>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4>Our Office</h4>
                                <p>123 Business District<br>Makati City, 1200<br>Philippines</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <h4>Phone Support</h4>
                                <p>+63 (02) 1234-5678<br>Mon-Fri, 9:00 AM - 6:00 PM</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p><a href="mailto:support@PLANORA.com">support@PLANORA.com</a><br>For general inquiries</p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h4>Response Time</h4>
                                <p>We typically respond within 24-48 hours during business days.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="contact-form" data-aos="fade-left">
                        <h3>Send Us a Message</h3>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label>Your Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Subject</label>
                                <select name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="Technical Issue">Technical Issue</option>
                                    <option value="Billing Question">Billing Question</option>
                                    <option value="Feature Request">Feature Request</option>
                                    <option value="Account Support">Account Support</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="message" rows="5" placeholder="Please describe your issue or concern in detail..." required></textarea>
                            </div>
                            
                            <button type="submit" name="send_message" class="submit-btn">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Message History Tab -->
            <div id="history-tab" class="tab-content">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): 
                        $highlight = (isset($_GET['message']) && $_GET['message'] == $message['id']);
                    ?>
                    <div class="message-conversation" id="message-<?php echo $message['id']; ?>" style="<?php echo $highlight ? 'border: 2px solid #06B6D4;' : ''; ?>">
                        <div class="message-conversation-header">
                            <div>
                                <strong><?php echo htmlspecialchars($message['subject']); ?></strong>
                            </div>
                            <div>
                                <span class="message-status <?php echo $message['status']; ?>">
                                    <?php echo ucfirst($message['status']); ?>
                                </span>
                                <span class="message-date"><?php echo date('M j, Y', strtotime($message['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="message-thread">
                            <!-- User's Original Message -->
                            <div class="message-bubble user">
                                <div class="bubble-content">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                                <div class="bubble-meta">
                                    <span><i class="far fa-user"></i> You</span>
                                    <span><i class="far fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <!-- Admin/Staff Reply (if exists) -->
                            <?php if (!empty($message['admin_reply'])): 
                                // Split replies by the separator
                                $replies = explode('--- Reply from', $message['admin_reply']);
                                foreach ($replies as $reply):
                                    if (trim($reply) == '') continue;
                                    $reply_parts = explode('---', $reply);
                                    $reply_content = trim($reply_parts[0] ?? $reply);
                                    $reply_meta = isset($reply_parts[1]) ? trim($reply_parts[1]) : '';
                            ?>
                            <div class="message-bubble admin">
                                <div class="bubble-content">
                                    <?php echo nl2br(htmlspecialchars($reply_content)); ?>
                                </div>
                                <div class="bubble-meta">
                                    <span><i class="fas fa-user-shield"></i> Support Team</span>
                                    <?php if ($reply_meta): ?>
                                    <span><i class="far fa-clock"></i> <?php echo $reply_meta; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </div>
                        
                        <!-- Reply Form - Users can reply back -->
                        <div class="reply-section">
                            <form method="POST" action="" class="reply-form">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <textarea name="reply" rows="2" placeholder="Type your reply here..."></textarea>
                                <div class="reply-actions">
                                    <button type="submit" name="reply_to_message" class="reply-btn">
                                        <i class="fas fa-paper-plane"></i> Send Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-messages">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p>No messages yet</p>
                    <p style="font-size: 14px;">Send us a message and we'll get back to you!</p>
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

        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show selected tab content
            document.getElementById('new-tab').classList.remove('active');
            document.getElementById('history-tab').classList.remove('active');
            
            if (tab === 'new') {
                document.getElementById('new-tab').classList.add('active');
            } else {
                document.getElementById('history-tab').classList.add('active');
            }
        }
        
        // Scroll to highlighted message
        <?php if (isset($_GET['message'])): ?>
        setTimeout(() => {
            const element = document.getElementById('message-<?php echo $_GET['message']; ?>');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Switch to history tab
                document.querySelectorAll('.tab-btn')[1].click();
            }
        }, 500);
        <?php endif; ?>
    </script>
</body>
</html>
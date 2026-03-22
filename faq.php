<?php
require_once 'config/database.php';
redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLANORA - FAQ</title>
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
                <a href="index.php" class="nav-item">Home</a>
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
                <p class="user-email">123@gmail.com</p>
                <p class="demo-text">PLANORA Demo</p>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="content-header">
                <h2>Frequently Asked Questions</h2>
            </div>

            <div class="faq-container">
                <div class="faq-section">
                    <h3>General Questions</h3>
                    
                    <div class="faq-item">
                        <h4>What is PLANORA?</h4>
                        <p>PLANORA is a comprehensive accounting and business management platform designed for small to medium-sized businesses in the Philippines. It helps you manage sales, track expenses, monitor inventory, generate reports, and make informed financial decisions.</p>
                    </div>

                    <div class="faq-item">
                        <h4>Is my data secure on PLANORA?</h4>
                        <p>Yes, we take data security seriously. All data is encrypted and stored securely in the cloud. We implement industry-standard security measures including SSL encryption, secure password hashing, and regular security audits. Your financial data is protected and never shared with third parties.</p>
                    </div>

                    <div class="faq-item">
                        <h4>Can I access PLANORA on mobile devices?</h4>
                        <p>Yes, PLANORA is fully responsive and can be accessed on smartphones and tablets through any web browser. The interface adapts to different screen sizes for optimal viewing and functionality.</p>
                    </div>
                </div>

                <div class="faq-section">
                    <h3>Account & Users</h3>

                    <div class="faq-item">
                        <h4>Can I add multiple users to my account?</h4>
                        <p>Yes! Navigate to the Multi-User Management page to add staff members. You can assign specific permissions to each user (Admin or Staff roles) and track their activities through the activity log. Admin users have full access while staff members have limited permissions based on your configuration.</p>
                    </div>

                    <div class="faq-item">
                        <h4>How do I reset my password?</h4>
                        <p>Click on the "Forgot Password" link on the login page. Enter your email address and you'll receive instructions to reset your password. If you're logged in, you can also change your password in Settings under the Security tab.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What happens if I forget my login credentials?</h4>
                        <p>Use the "Forgot Password" feature on the login page to reset your password. If you're still having issues, contact support at support@PLANORA.com for assistance.</p>
                    </div>
                </div>

                <div class="faq-section">
                    <h3>Sales & Inventory</h3>

                    <div class="faq-item">
                        <h4>How do I record a sale?</h4>
                        <p>Go to the Sales page, click "Add New Sale", select a product, enter the quantity, and submit. The system will automatically calculate tax, generate a receipt, and update your inventory stock levels. You can view all sales in the sales history table.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What happens when a product runs out of stock?</h4>
                        <p>When stock reaches zero, the system will prevent sales of that product and display a low-stock alert on your dashboard. You'll receive notifications to reorder. The product will be marked as "Out of Stock" in your inventory list.</p>
                    </div>

                    <div class="faq-item">
                        <h4>How does automatic stock deduction work?</h4>
                        <p>Every time you record a sale, the system automatically subtracts the sold quantity from your inventory. This ensures your stock levels are always accurate in real-time without manual updates.</p>
                    </div>
                </div>

                <div class="faq-section">
                    <h3>Expenses & Budget</h3>

                    <div class="faq-item">
                        <h4>How do I track my expenses?</h4>
                        <p>Navigate to the Expenses page and click "Add New Expense". Select a category, enter the amount, add a description, and choose the date. You can also upload receipts for record-keeping. The system will categorize and track all expenses for reporting.</p>
                    </div>

                    <div class="faq-item">
                        <h4>Can I set a budget limit?</h4>
                        <p>Yes, go to the Budget Planning page to set monthly sales targets and expense limits. The system will track your actual performance against these goals and alert you when you're approaching or exceeding limits.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What expense categories are available?</h4>
                        <p>Default categories include Rent, Utilities, Supplies, Payroll, Marketing, Maintenance, Transportation, Insurance, Taxes, and Other. You can also add custom categories in Settings under the Categories tab.</p>
                    </div>
                </div>

                <div class="faq-section">
                    <h3>Reports & Analytics</h3>

                    <div class="faq-item">
                        <h4>How do I export my financial reports?</h4>
                        <p>Go to the Reports page, select your desired time period (weekly/monthly/yearly), and click on the export buttons (PDF, Excel, or CSV) to download your reports. You can export P&L statements, sales trends, expense breakdowns, and more.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What reports can I generate?</h4>
                        <p>You can generate Profit & Loss statements, sales trend reports, expense breakdowns, best-selling products lists, slow-moving items reports, peak sales day analysis, and comprehensive financial summaries.</p>
                    </div>

                    <div class="faq-item">
                        <h4>How do I know if my business is profitable?</h4>
                        <p>The dashboard displays a clear business performance indicator with color coding: 🟢 Profitable (green), 🟡 Break-even (yellow), or 🔴 Losing (red). You can also view detailed P&L calculations in the Reports section.</p>
                    </div>
                </div>

                <div class="faq-section">
                    <h3>Tax & Compliance</h3>

                    <div class="faq-item">
                        <h4>How is tax calculated?</h4>
                        <p>Tax is automatically calculated based on your configured VAT rate (default 12%). When you record a sale, the system computes the tax amount and adds it to the total. You can view tax summaries in reports and settings.</p>
                    </div>

                    <div class="faq-item">
                        <h4>Can I customize the tax rate?</h4>
                        <p>Yes, go to Settings > Tax Settings to configure your VAT rate. You can set any percentage between 0-100%. The system will use this rate for all future tax calculations.</p>
                    </div>

                    <div class="faq-item">
                        <h4>How do I get tax summary reports?</h4>
                        <p>Navigate to Reports and select the desired period. The P&L summary includes tax information. You can also view tax details in Settings under Tax Settings where output tax is summarized.</p>
                    </div>
                </div>

                <div class="faq-section">
                    <h3>Data & Backup</h3>

                    <div class="faq-item">
                        <h4>How often is my data backed up?</h4>
                        <p>Data is backed up automatically on a regular basis. You can also create manual backups in Settings under the Data & Backup tab. We recommend regular backups for data security.</p>
                    </div>

                    <div class="faq-item">
                        <h4>Can I recover deleted data?</h4>
                        <p>Deleted data may be recoverable from backups depending on when the backup was created. Contact support immediately if you need to recover accidentally deleted information.</p>
                    </div>

                    <div class="faq-item">
                        <h4>What formats can I export my data in?</h4>
                        <p>You can export reports and data in PDF, Excel (XLSX), and CSV formats. This allows you to use the data in other applications or share with your accountant.</p>
                    </div>
                </div>

                <div class="contact-section">
                    <h3>Contact & Support</h3>
                    
                    <div class="contact-info">
                        <strong>Email Support</strong>
                        <p>support@PLANORA.com</p>
                    </div>

                    <div class="contact-info">
                        <strong>Phone Support</strong>
                        <p>+63 (02) 123-4567</p>
                        <p>Monday - Friday, 9:00 AM - 6:00 PM</p>
                    </div>

                    <div class="contact-info">
                        <strong>Office Address</strong>
                        <p>123 Business District, Makati City, Philippines</p>
                    </div>
                </div>

                <div class="legal-links">
                    <h3>Legal Information</h3>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Data Processing Agreement</a></li>
                        <li><a href="#">Security Practices</a></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
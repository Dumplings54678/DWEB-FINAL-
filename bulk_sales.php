<?php
/**
 * HAUccountant Bulk Sales Management
 * Handle multiple items in a single order
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Get all products for dropdown
$products_query = $pdo->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY product_name");
$products = $products_query->fetchAll();

// Handle bulk sale submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_bulk_sale'])) {
    
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $notes = trim($_POST['notes'] ?? '');
    $items = $_POST['items'] ?? [];
    
    if (empty($items)) {
        $_SESSION['error'] = "Please add at least one item to the order.";
        header('Location: bulk_sales.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Generate unique order group ID
        $order_group_id = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $order_date = date('Y-m-d H:i:s');
        
        $total_order_amount = 0;
        $total_order_tax = 0;
        $items_processed = [];
        
        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $quantity = (int)$item['quantity'];
            
            if ($product_id <= 0 || $quantity <= 0) {
                continue;
            }
            
            // Get product details
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception("Insufficient stock for {$product['product_name']}. Available: {$product['stock_quantity']}");
            }
            
            // Calculate totals
            $unit_price = $product['selling_price'];
            $subtotal = $unit_price * $quantity;
            $tax = $subtotal * 0.12; // 12% VAT
            $total = $subtotal + $tax;
            
            $total_order_amount += $total;
            $total_order_tax += $tax;
            
            // Generate individual receipt number for each item
            $receipt_no = 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insert sale
            $stmt = $pdo->prepare("
                INSERT INTO sales (
                    order_group_id, receipt_no, product_id, quantity, unit_price, 
                    tax, total_amount, customer_name, payment_method,
                    notes, sale_date, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, NOW())
            ");
            
            $stmt->execute([
                $order_group_id, $receipt_no, $product_id, $quantity, $unit_price,
                $tax, $total, $customer_name, $payment_method,
                $notes, $user_id
            ]);
            
            // Update stock
            $new_stock = $product['stock_quantity'] - $quantity;
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->execute([$new_stock, $product_id]);
            
            $items_processed[] = [
                'product_name' => $product['product_name'],
                'quantity' => $quantity,
                'total' => $total
            ];
        }
        
        // Log activity
        $item_count = count($items_processed);
        logActivity($pdo, $user_id, 'ADD_BULK_SALE', 'sales', 
            "Added bulk order: {$item_count} items - Total: ₱" . number_format($total_order_amount, 2));
        
        $pdo->commit();
        
        $_SESSION['success'] = "Bulk order processed successfully!";
        $_SESSION['last_order'] = $order_group_id;
        
        header('Location: bulk_sales.php?order=' . $order_group_id);
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: bulk_sales.php');
        exit();
    }
}

// Get recent bulk orders
$orders_query = $pdo->query("
    SELECT 
        s.order_group_id,
        s.customer_name,
        s.payment_method,
        COUNT(*) as item_count,
        SUM(s.total_amount) as total_amount,
        MIN(s.sale_date) as sale_date,
        MAX(s.created_at) as created_at,
        u.owner_name as cashier_name
    FROM sales s
    LEFT JOIN users u ON s.created_by = u.id
    WHERE s.order_group_id IS NOT NULL
    GROUP BY s.order_group_id
    ORDER BY s.created_at DESC
    LIMIT 20
");
$recent_orders = $orders_query->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Sales - PLANORA</title>
    
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
        .bulk-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            color: #64748b;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #f8fafc;
            border-color: #06B6D4;
            color: #06B6D4;
        }
        
        .order-items {
            background: white;
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
        }
        
        .items-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #cffafe;
        }
        
        .items-header h3 {
            font-size: 20px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .items-header h3 i {
            color: #06B6D4;
        }
        
        .add-item-btn {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .add-item-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 100px;
            gap: 15px;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .item-row:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .item-select {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 100%;
        }
        
        .item-quantity {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 100%;
        }
        
        .item-price {
            padding: 10px;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            color: #0f172a;
            text-align: right;
        }
        
        .remove-item {
            background: #fee2e2;
            color: #EF4444;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .remove-item:hover {
            background: #EF4444;
            color: white;
            transform: scale(1.05);
        }
        
        .order-summary {
            background: linear-gradient(135deg, #f8fafc, white);
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        
        .summary-row.total {
            font-weight: 700;
            font-size: 18px;
            border-bottom: none;
            padding-top: 15px;
            color: #06B6D4;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .submit-btn {
            flex: 1;
            padding: 16px;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.3);
        }
        
        .cancel-btn {
            flex: 1;
            padding: 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .cancel-btn:hover {
            background: #f8fafc;
            border-color: #EF4444;
            color: #EF4444;
        }
        
        .recent-orders {
            margin-top: 40px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-color: #06B6D4;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-id {
            font-weight: 700;
            color: #06B6D4;
            font-size: 16px;
        }
        
        .order-total {
            font-weight: 700;
            color: #10B981;
            font-size: 18px;
        }
        
        .order-details {
            display: flex;
            gap: 20px;
            color: #64748b;
            font-size: 13px;
        }
        
        .view-order {
            margin-top: 10px;
            padding: 8px 16px;
            background: #cffafe;
            color: #0891b2;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-order:hover {
            background: #06B6D4;
            color: white;
        }
        
        @media screen and (max-width: 768px) {
            .item-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .remove-item {
                width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
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
               <!-- Add after Sales link -->
<a href="bulk_sales.php" class="nav-item bulk-sales">
    <i class="fas fa-layer-group"></i>
    <span>Bulk Orders</span>
</a>

<!-- Add before About link for admin only -->
<?php if ($user_role === 'admin'): ?>
<a href="bulk_delete.php" class="nav-item bulk-delete">
    <i class="fas fa-trash-alt"></i>
    <span>Bulk Delete</span>
</a>
<?php endif; ?>
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
                <?php if ($user_role === 'admin'): ?>
                <a href="users.php" class="nav-item users">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="settings.php" class="nav-item settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
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

            <!-- Last Order -->
            <?php if (isset($_GET['order'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <span>Order #<?php echo htmlspecialchars($_GET['order']); ?> processed successfully!</span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <div class="bulk-header">
                <div>
                    <a href="sales.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Sales
                    </a>
                    <h1 style="margin-top: 15px;">Bulk Order Management</h1>
                    <p class="subtitle">Process multiple items in a single order</p>
                </div>
            </div>

            <!-- Bulk Order Form -->
            <div class="order-items" data-aos="fade-up">
                <div class="items-header">
                    <h3><i class="fas fa-shopping-cart"></i> Order Items</h3>
                    <button type="button" class="add-item-btn" onclick="addItemRow()">
                        <i class="fas fa-plus-circle"></i> Add Item
                    </button>
                </div>

                <form method="POST" action="" id="bulkOrderForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div id="items-container">
                        <!-- Items will be added here dynamically -->
                    </div>

                    <div class="order-summary" id="orderSummary" style="display: none;">
                        <h4 style="margin-bottom: 15px;">Order Summary</h4>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="summarySubtotal">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (12%):</span>
                            <span id="summaryTax">₱0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Amount:</span>
                            <span id="summaryTotal">₱0.00</span>
                        </div>
                    </div>

                    <div class="form-row" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Customer Name</label>
                            <input type="text" name="customer_name" placeholder="Walk-in Customer" value="Walk-in Customer">
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" rows="2" placeholder="Additional notes for this order..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="resetForm()">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                        <button type="submit" name="submit_bulk_sale" class="submit-btn" id="submitBtn" disabled>
                            <i class="fas fa-check-circle"></i> Process Order
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Bulk Orders -->
            <?php if (!empty($recent_orders)): ?>
            <div class="recent-orders" data-aos="fade-up">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-history"></i> Recent Bulk Orders</h3>
                
                <?php foreach ($recent_orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <span class="order-id"><?php echo htmlspecialchars($order['order_group_id']); ?></span>
                        <span class="order-total">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="order-details">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['customer_name']); ?></span>
                        <span><i class="fas fa-box"></i> <?php echo $order['item_count']; ?> items</span>
                        <span><i class="fas fa-credit-card"></i> <?php echo ucfirst($order['payment_method']); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($order['sale_date'])); ?></span>
                    </div>
                    <button class="view-order" onclick="viewOrder('<?php echo $order['order_group_id']; ?>')">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: true
        });

        let itemCount = 0;
        const products = <?php echo json_encode($products); ?>;

        // Add item row
        function addItemRow() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.id = 'item-' + itemCount;
            
            // Create product select options
            let options = '<option value="">Select Product</option>';
            products.forEach(product => {
                options += `<option value="${product.id}" data-price="${product.selling_price}" data-stock="${product.stock_quantity}">${product.product_name} (Stock: ${product.stock_quantity}) - ₱${parseFloat(product.selling_price).toFixed(2)}</option>`;
            });
            
            row.innerHTML = `
                <select class="item-select" name="items[${itemCount}][product_id]" onchange="updateItemPrice(${itemCount})" required>
                    ${options}
                </select>
                <input type="number" class="item-quantity" name="items[${itemCount}][quantity]" min="1" value="1" onchange="updateItemPrice(${itemCount})" required>
                <div class="item-price" id="price-${itemCount}">₱0.00</div>
                <button type="button" class="remove-item" onclick="removeItemRow(${itemCount})">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            container.appendChild(row);
            itemCount++;
            updateOrderSummary();
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('orderSummary').style.display = 'block';
        }

        // Update item price
        function updateItemPrice(index) {
            const select = document.querySelector(`#item-${index} .item-select`);
            const quantity = document.querySelector(`#item-${index} .item-quantity`).value;
            const priceDisplay = document.getElementById(`price-${index}`);
            
            if (select.value && quantity) {
                const selected = select.options[select.selectedIndex];
                const price = parseFloat(selected.dataset.price);
                const stock = parseInt(selected.dataset.stock);
                
                if (parseInt(quantity) > stock) {
                    alert('Insufficient stock! Available: ' + stock);
                    document.querySelector(`#item-${index} .item-quantity`).value = stock;
                }
                
                const total = price * quantity;
                priceDisplay.textContent = '₱' + total.toFixed(2);
            } else {
                priceDisplay.textContent = '₱0.00';
            }
            
            updateOrderSummary();
        }

        // Remove item row
        function removeItemRow(index) {
            const row = document.getElementById('item-' + index);
            row.remove();
            updateOrderSummary();
            
            if (document.querySelectorAll('.item-row').length === 0) {
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('orderSummary').style.display = 'none';
            }
        }

        // Update order summary
        function updateOrderSummary() {
            let subtotal = 0;
            const rows = document.querySelectorAll('.item-row');
            
            rows.forEach(row => {
                const priceText = row.querySelector('.item-price').textContent;
                const price = parseFloat(priceText.replace('₱', '')) || 0;
                subtotal += price;
            });
            
            const tax = subtotal * 0.12;
            const total = subtotal + tax;
            
            document.getElementById('summarySubtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('summaryTax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('summaryTotal').textContent = '₱' + total.toFixed(2);
        }

        // Reset form
        function resetForm() {
            if (confirm('Clear all items from this order?')) {
                document.getElementById('items-container').innerHTML = '';
                itemCount = 0;
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('orderSummary').style.display = 'none';
            }
        }

        // View order details
        function viewOrder(orderId) {
            window.location.href = 'view_order.php?order=' + orderId;
        }

        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.parentElement.style.display = 'none';
            });
        });

        // Add first item row automatically
        window.onload = function() {
            if (document.querySelectorAll('.item-row').length === 0) {
                addItemRow();
            }
        };
    </script>
</body>
</html>
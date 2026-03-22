<?php
/**
 * HAUccountant Sales Management
 * Complete working sales system with database operations
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Handle bulk add sales (bulk order)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_add_sales'])) {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: sales.php');
        exit();
    }
    
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $notes = trim($_POST['notes'] ?? '');
    $items = json_decode($_POST['bulk_items'], true);
    
    if (empty($items)) {
        $_SESSION['error'] = "No items to process.";
        header('Location: sales.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Generate unique order group ID for bulk orders
        $order_group_id = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $total_order_amount = 0;
        $total_order_tax = 0;
        $success_count = 0;
        
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
            
            // Insert sale with order group ID
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
            
            $success_count++;
        }
        
        if ($success_count > 0) {
            logActivity($pdo, $user_id, 'BULK_ADD_SALES', 'sales', 
                "Added bulk order: {$success_count} items - Total: ₱" . number_format($total_order_amount, 2));
            
            $pdo->commit();
            
            $_SESSION['success'] = "Bulk order processed successfully with {$success_count} items!";
            $_SESSION['last_order'] = $order_group_id;
            
            header('Location: sales.php?order=' . $order_group_id);
            exit();
        } else {
            throw new Exception("No items were processed");
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: sales.php');
        exit();
    }
}

// Handle bulk delete sales (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_delete_sales']) && $user_role === 'admin') {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: sales.php');
        exit();
    }
    
    $delete_ids = $_POST['delete_ids'] ?? [];
    $delete_method = $_POST['delete_method'] ?? 'select';
    
    try {
        $pdo->beginTransaction();
        $deleted_count = 0;
        $deleted_total = 0;
        
        if ($delete_method === 'select' && !empty($delete_ids)) {
            // Delete specific sales
            $delete_ids = array_map('intval', $delete_ids);
            $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
            
            // Get sale details to restore stock
            $stmt = $pdo->prepare("SELECT product_id, quantity, total_amount FROM sales WHERE id IN ($placeholders)");
            $stmt->execute($delete_ids);
            $sales_to_delete = $stmt->fetchAll();
            
            // Restore stock for each sale
            foreach ($sales_to_delete as $sale) {
                $deleted_total += $sale['total_amount'];
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                $stmt->execute([$sale['quantity'], $sale['product_id']]);
            }
            
            // Delete sales
            $stmt = $pdo->prepare("DELETE FROM sales WHERE id IN ($placeholders)");
            $stmt->execute($delete_ids);
            $deleted_count = $stmt->rowCount();
            
        } elseif ($delete_method === 'date') {
            // Delete by date range
            $date_from = $_POST['date_from'] ?? '';
            $date_to = $_POST['date_to'] ?? '';
            
            if (!empty($date_from) && !empty($date_to)) {
                // Get sale details to restore stock
                $stmt = $pdo->prepare("SELECT product_id, quantity, total_amount FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $sales_to_delete = $stmt->fetchAll();
                
                // Restore stock for each sale
                foreach ($sales_to_delete as $sale) {
                    $deleted_total += $sale['total_amount'];
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $stmt->execute([$sale['quantity'], $sale['product_id']]);
                }
                
                // Delete sales
                $stmt = $pdo->prepare("DELETE FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $deleted_count = $stmt->rowCount();
            }
        } elseif ($delete_method === 'customer') {
            // Delete by customer
            $customer = $_POST['delete_customer'] ?? '';
            
            if (!empty($customer)) {
                // Get sale details to restore stock
                $stmt = $pdo->prepare("SELECT product_id, quantity, total_amount FROM sales WHERE customer_name LIKE ?");
                $stmt->execute(["%$customer%"]);
                $sales_to_delete = $stmt->fetchAll();
                
                // Restore stock for each sale
                foreach ($sales_to_delete as $sale) {
                    $deleted_total += $sale['total_amount'];
                    $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $stmt->execute([$sale['quantity'], $sale['product_id']]);
                }
                
                // Delete sales
                $stmt = $pdo->prepare("DELETE FROM sales WHERE customer_name LIKE ?");
                $stmt->execute(["%$customer%"]);
                $deleted_count = $stmt->rowCount();
            }
        }
        
        if ($deleted_count > 0) {
            logActivity($pdo, $user_id, 'BULK_DELETE_SALES', 'sales', 
                "Deleted {$deleted_count} sales totaling ₱" . number_format($deleted_total, 2));
            $_SESSION['success'] = "Successfully deleted {$deleted_count} sales and restored stock.";
        } else {
            $_SESSION['error'] = "No sales were deleted.";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete sales: " . $e->getMessage();
    }
    
    header('Location: sales.php');
    exit();
}

// Handle add sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sale'])) {
    
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate
    $errors = [];
    
    if ($product_id <= 0) {
        $errors[] = "Please select a product.";
    }
    
    if ($quantity <= 0) {
        $errors[] = "Quantity must be greater than 0.";
    }
    
    if (empty($errors)) {
        // Get product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            $_SESSION['error'] = "Product not found.";
        } elseif ($product['stock_quantity'] < $quantity) {
            $_SESSION['error'] = "Insufficient stock. Available: {$product['stock_quantity']}";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Calculate totals
                $unit_price = $product['selling_price'];
                $subtotal = $unit_price * $quantity;
                $tax = $subtotal * 0.12; // 12% VAT
                $total = $subtotal + $tax;
                
                // Generate receipt number
                $receipt_no = 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Insert sale
                $stmt = $pdo->prepare("
                    INSERT INTO sales (
                        receipt_no, product_id, quantity, unit_price, 
                        tax, total_amount, customer_name, payment_method,
                        notes, sale_date, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, NOW())
                ");
                
                $stmt->execute([
                    $receipt_no, $product_id, $quantity, $unit_price,
                    $tax, $total, $customer_name, $payment_method,
                    $notes, $user_id
                ]);
                
                $sale_id = $pdo->lastInsertId();
                
                // Update stock
                $new_stock = $product['stock_quantity'] - $quantity;
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                $stmt->execute([$new_stock, $product_id]);
                
                // Log activity
                logActivity($pdo, $user_id, 'ADD_SALE', 'sales', 
                    "Added sale: {$quantity} x {$product['product_name']} - Receipt: {$receipt_no}");
                
                $pdo->commit();
                
                $_SESSION['success'] = "Sale recorded successfully!";
                $_SESSION['last_receipt'] = $receipt_no;
                
                header('Location: sales.php?receipt=' . $receipt_no);
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Sale failed: " . $e->getMessage());
                $_SESSION['error'] = "Failed to record sale. Please try again.";
                header('Location: sales.php');
                exit();
            }
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
        header('Location: sales.php');
        exit();
    }
}

// Handle delete sale (admin only)
if (isset($_GET['delete']) && $user_role === 'admin') {
    $sale_id = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Get sale details
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch();
        
        if ($sale) {
            // Restore stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->execute([$sale['quantity'], $sale['product_id']]);
            
            // Delete sale
            $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
            $stmt->execute([$sale_id]);
            
            // Log activity
            logActivity($pdo, $user_id, 'DELETE_SALE', 'sales', 
                "Deleted sale ID: $sale_id - Receipt: {$sale['receipt_no']}");
            
            $_SESSION['success'] = "Sale deleted successfully.";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete sale.";
    }
    
    header('Location: sales.php');
    exit();
}

// Get all sales with product details
$sales_query = $pdo->query("
    SELECT s.*, p.product_name, p.category, u.owner_name as cashier_name
    FROM sales s
    JOIN products p ON s.product_id = p.id
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC
");
$sales = $sales_query->fetchAll();

// Get products for dropdown (only in stock)
$products_query = $pdo->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY product_name");
$products = $products_query->fetchAll();

// Calculate today's stats
$today_query = $pdo->query("
    SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
    FROM sales WHERE DATE(sale_date) = CURDATE()
");
$today_stats = $today_query->fetch();

// Calculate this week's stats
$week_query = $pdo->query("
    SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
    FROM sales WHERE YEARWEEK(sale_date) = YEARWEEK(CURDATE())
");
$week_stats = $week_query->fetch();

// Get best selling product
$best_query = $pdo->query("
    SELECT p.product_name, SUM(s.quantity) as total_sold 
    FROM sales s 
    JOIN products p ON s.product_id = p.id 
    GROUP BY s.product_id 
    ORDER BY total_sold DESC 
    LIMIT 1
");
$best_product = $best_query->fetch();

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - PLANORA</title>
    
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
        
        .sales-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.1);
        }
        
        .stat-card .label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-card .label i {
            color: var(--primary);
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }
        
        .stat-card .sub {
            color: #94a3b8;
            font-size: 13px;
        }
        
        .add-sale-btn {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .add-sale-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
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
            border-radius: 30px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        }
        
        .modal-content.modal-lg {
            max-width: 900px;
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
            color: #94a3b8;
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
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: white;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .product-details {
            background: linear-gradient(135deg, var(--primary-soft), var(--accent-soft));
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            display: none;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .detail-item {
            background: white;
            padding: 12px;
            border-radius: 12px;
        }
        
        .detail-item .label {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .detail-item .value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        
        .detail-item.total {
            grid-column: span 2;
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }
        
        .detail-item.total .label,
        .detail-item.total .value {
            color: white;
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn-primary {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3);
        }
        
        .btn-secondary {
            flex: 1;
            padding: 14px;
            background: #f1f5f9;
            color: #64748b;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-danger {
            flex: 1;
            padding: 14px;
            background: #EF4444;
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn-outline {
            flex: 1;
            padding: 14px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
        }
        
        .btn-outline:hover {
            background: #f8fafc;
            border-color: var(--primary);
        }
        
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        
        .sales-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .sales-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        
        .sales-table tr:hover td {
            background: #f8fafc;
        }
        
        .receipt-badge {
            background: var(--primary-soft);
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .order-badge {
            background: var(--accent-soft);
            color: var(--accent-dark);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 5px;
        }
        
        .payment-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .payment-badge.cash {
            background: #d1fae5;
            color: #065f46;
        }
        
        .payment-badge.card {
            background: #cffafe;
            color: #0891b2;
        }
        
        .payment-badge.gcash {
            background: #c7d2fe;
            color: #3730a3;
        }
        
        .payment-badge.maya {
            background: #fae8ff;
            color: #7e22ce;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .action-btn.view {
            background: var(--primary-soft);
            color: var(--primary);
        }
        
        .action-btn.view:hover {
            background: var(--primary);
            color: white;
        }
        
        .action-btn.delete {
            background: #fee2e2;
            color: var(--danger);
        }
        
        .action-btn.delete:hover {
            background: var(--danger);
            color: white;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        .alert-close {
            margin-left: auto;
            background: transparent;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: 0.5;
        }
        
        .alert-close:hover {
            opacity: 1;
        }
        
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 14px;
        }
        
        .search-box input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }
        
        .bulk-item-row {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .bulk-item-row:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .remove-row-btn {
            background: #fee2e2;
            color: #EF4444;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-row-btn:hover {
            background: #EF4444 !important;
            color: white !important;
        }
        
        .bulk-summary {
            background: linear-gradient(135deg, var(--primary-soft), var(--accent-soft));
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .delete-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .delete-option-btn {
            padding: 10px 20px;
            border: none;
            background: transparent;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
        }
        
        .delete-option-btn:hover {
            color: var(--danger);
            background: #fee2e2;
        }
        
        .delete-option-btn.active {
            background: #EF4444;
            color: white;
        }
        
        .records-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .record-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .record-item:hover {
            background: #f8fafc;
        }
        
        .record-item:last-child {
            border-bottom: none;
        }
        
        .record-checkbox {
            width: 20px;
            height: 20px;
            accent-color: #EF4444;
        }
        
        .record-details {
            flex: 1;
        }
        
        .record-title {
            font-weight: 600;
            color: #0f172a;
        }
        
        .record-meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .record-amount {
            font-weight: 600;
            color: #10B981;
        }
        
        .select-all {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .date-range {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .date-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        @media screen and (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media screen and (max-width: 768px) {
            .sales-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .sales-table {
                display: block;
                overflow-x: auto;
            }
            
            .delete-options {
                flex-direction: column;
            }
            
            .date-range {
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

            <!-- Last Receipt -->
            <?php if (isset($_GET['receipt'])): ?>
                <div class="alert success">
                    <i class="fas fa-receipt"></i>
                    <span>Receipt #<?php echo htmlspecialchars($_GET['receipt']); ?> generated successfully!</span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Last Order -->
            <?php if (isset($_GET['order'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <span>Bulk Order #<?php echo htmlspecialchars($_GET['order']); ?> processed successfully!</span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>

            <div class="sales-header">
                <div>
                    <h1>Sales Management</h1>
                    <p class="subtitle">Track and manage your sales transactions</p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="add-sale-btn" onclick="showAddSaleModal()">
                        <i class="fas fa-plus-circle"></i> New Sale
                    </button>
                    <button class="btn btn-outline" onclick="showBulkAddModal()">
                        <i class="fas fa-layer-group"></i> Bulk Order
                    </button>
                    <?php if ($user_role === 'admin'): ?>
                    <button class="btn btn-outline" onclick="showBulkDeleteModal()" style="color: #EF4444; border-color: #EF4444;">
                        <i class="fas fa-trash-alt"></i> Bulk Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card">
                    <div class="label">
                        <i class="fas fa-calendar-day"></i> Today's Sales
                    </div>
                    <div class="value">₱<?php echo number_format($today_stats['total'], 2); ?></div>
                    <div class="sub"><?php echo $today_stats['count']; ?> transactions</div>
                </div>
                
                <div class="stat-card">
                    <div class="label">
                        <i class="fas fa-calendar-week"></i> This Week
                    </div>
                    <div class="value">₱<?php echo number_format($week_stats['total'], 2); ?></div>
                    <div class="sub"><?php echo $week_stats['count']; ?> transactions</div>
                </div>
                
                <div class="stat-card">
                    <div class="label">
                        <i class="fas fa-crown"></i> Best Seller
                    </div>
                    <div class="value"><?php echo $best_product ? htmlspecialchars($best_product['product_name']) : 'N/A'; ?></div>
                    <div class="sub"><?php echo $best_product ? $best_product['total_sold'] . ' units' : ''; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="label">
                        <i class="fas fa-chart-line"></i> Total Sales
                    </div>
                    <div class="value"><?php echo count($sales); ?></div>
                    <div class="sub">All transactions</div>
                </div>
            </div>

            <!-- Search Box -->
            <div class="search-box" data-aos="fade-up">
                <input type="text" id="searchSales" placeholder="Search by receipt, product, customer, or order ID...">
                <i class="fas fa-search" style="color: var(--primary);"></i>
            </div>

            <!-- Sales Table -->
            <div style="background: white; border-radius: 24px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);" data-aos="fade-up">
                <?php if (count($sales) > 0): ?>
                <table class="sales-table" id="salesTable">
                    <thead>
                        <tr>
                            <th>Order/Receipt</th>
                            <th>Date & Time</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Tax</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Cashier</th>
                            <?php if ($user_role === 'admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td>
                                <?php if (!empty($sale['order_group_id'])): ?>
                                <span class="order-badge" title="Bulk Order">
                                    <i class="fas fa-layer-group"></i>
                                </span>
                                <?php endif; ?>
                                <span class="receipt-badge">
                                    <?php echo htmlspecialchars($sale['receipt_no'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($sale['sale_date'])); ?>
                                <br>
                                <small style="color: #94a3b8;"><?php echo date('g:i A', strtotime($sale['created_at'])); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($sale['product_name']); ?></strong>
                                <br>
                                <small style="color: #94a3b8;"><?php echo htmlspecialchars($sale['category'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td>₱<?php echo number_format($sale['unit_price'], 2); ?></td>
                            <td>₱<?php echo number_format($sale['tax'], 2); ?></td>
                            <td style="color: var(--success); font-weight: 600;">₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                            <td>
                                <span class="payment-badge <?php echo strtolower($sale['payment_method'] ?? 'cash'); ?>">
                                    <?php echo ucfirst($sale['payment_method'] ?? 'Cash'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($sale['cashier_name'] ?? 'System'); ?></td>
                            <?php if ($user_role === 'admin'): ?>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn view" onclick="viewReceipt('<?php echo $sale['receipt_no']; ?>')" title="View Receipt">
                                        <i class="fas fa-receipt"></i>
                                    </button>
                                    <a href="?delete=<?php echo $sale['id']; ?>" 
                                       class="action-btn delete" 
                                       onclick="return confirm('Are you sure you want to delete this sale? This action cannot be undone.')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-shopping-cart" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: #334155; margin-bottom: 10px;">No Sales Yet</h3>
                    <p style="color: #64748b; margin-bottom: 20px;">Start by adding your first sale</p>
                    <button class="add-sale-btn" onclick="showAddSaleModal()" style="display: inline-flex;">
                        <i class="fas fa-plus-circle"></i> Add First Sale
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Sale Modal -->
    <div id="addSaleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> New Sale</h3>
                <span class="close" onclick="hideAddSaleModal()">&times;</span>
            </div>
            
            <form method="POST" action="" id="saleForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Product</label>
                        <select name="product_id" id="product_select" required onchange="updateProductDetails()">
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" 
                                    data-price="<?php echo $product['selling_price']; ?>"
                                    data-stock="<?php echo $product['stock_quantity']; ?>"
                                    data-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?> 
                                (Stock: <?php echo $product['stock_quantity']; ?>) - 
                                ₱<?php echo number_format($product['selling_price'], 2); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="sale_quantity" min="1" required oninput="calculateTotal()">
                    </div>
                </div>
                
                <div class="product-details" id="productDetails">
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="label">Unit Price</div>
                            <div class="value" id="display_price">₱0.00</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Available Stock</div>
                            <div class="value" id="display_stock">0</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Subtotal</div>
                            <div class="value" id="display_subtotal">₱0.00</div>
                        </div>
                        <div class="detail-item">
                            <div class="label">Tax (12%)</div>
                            <div class="value" id="display_tax">₱0.00</div>
                        </div>
                        <div class="detail-item total">
                            <div class="label">Total Amount</div>
                            <div class="value" id="display_total">₱0.00</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Customer Name</label>
                        <input type="text" name="customer_name" placeholder="Walk-in Customer">
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
                    <textarea name="notes" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="hideAddSaleModal()">Cancel</button>
                    <button type="submit" name="add_sale" class="btn-primary" id="submitSale">
                        Complete Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Add Sales Modal (Bulk Order) -->
    <div id="bulkAddModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-layer-group" style="color: var(--primary);"></i> Bulk Order</h3>
                <span class="close" onclick="hideBulkAddModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px;">
                <p class="subtitle">Process multiple items in a single order.</p>
            </div>
            
            <div id="bulk-items-container">
                <!-- First item row -->
                <div class="bulk-item-row" id="bulk-row-0">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="color: var(--primary);">Item #1</h4>
                        <button type="button" class="remove-row-btn" onclick="removeBulkRow(0)" style="display: none;" id="remove-0">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product</label>
                            <select class="bulk-product" onchange="updateBulkItemPrice(0)" required>
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" 
                                        data-price="<?php echo $product['selling_price']; ?>"
                                        data-stock="<?php echo $product['stock_quantity']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    <?php echo htmlspecialchars($product['product_name']); ?> 
                                    (Stock: <?php echo $product['stock_quantity']; ?>) - 
                                    ₱<?php echo number_format($product['selling_price'], 2); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" class="bulk-quantity" min="1" value="1" onchange="updateBulkItemPrice(0)" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Unit Price</label>
                            <input type="text" class="bulk-price" id="bulk-price-0" value="₱0.00" readonly disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Subtotal</label>
                            <input type="text" class="bulk-subtotal" id="bulk-subtotal-0" value="₱0.00" readonly disabled>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Tax (12%)</label>
                            <input type="text" class="bulk-tax" id="bulk-tax-0" value="₱0.00" readonly disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Total</label>
                            <input type="text" class="bulk-total" id="bulk-total-0" value="₱0.00" readonly disabled style="color: var(--success); font-weight: 600;">
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; margin: 20px 0;">
                <button type="button" class="btn btn-outline" onclick="addBulkRow()" style="flex: 1;">
                    <i class="fas fa-plus-circle"></i> Add Another Item
                </button>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" id="bulk_customer_name" placeholder="Walk-in Customer" value="Walk-in Customer">
                </div>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <select id="bulk_payment_method">
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
                <textarea id="bulk_notes" rows="2" placeholder="Additional notes for this order..."></textarea>
            </div>
            
            <div class="bulk-summary">
                <h4 style="margin-bottom: 10px;">Order Summary</h4>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span>Total Items:</span>
                    <span class="bulk-total-items" style="font-weight: 700;">1</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span>Subtotal:</span>
                    <span class="bulk-grand-subtotal" style="font-weight: 700;">₱0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span>Total Tax:</span>
                    <span class="bulk-grand-tax" style="font-weight: 700;">₱0.00</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 18px; margin-top: 10px; padding-top: 10px; border-top: 2px dashed var(--primary);">
                    <span>Grand Total:</span>
                    <span class="bulk-grand-total" style="font-weight: 700; color: var(--success);">₱0.00</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="hideBulkAddModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBulkOrder()">
                    <i class="fas fa-check-circle"></i> Process Order
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal (Admin Only) -->
    <?php if ($user_role === 'admin'): ?>
    <div id="bulkDeleteModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-trash-alt" style="color: #EF4444;"></i> Bulk Delete Sales</h3>
                <span class="close" onclick="hideBulkDeleteModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px; color: #EF4444; background: #fee2e2; padding: 15px; border-radius: 12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> This action cannot be undone. Product stock will be restored.
            </div>
            
            <div class="delete-options">
                <button type="button" class="delete-option-btn active" onclick="showDeleteMethod('select')" id="selectMethodBtn">Select Specific Sales</button>
                <button type="button" class="delete-option-btn" onclick="showDeleteMethod('date')" id="dateMethodBtn">Delete by Date Range</button>
                <button type="button" class="delete-option-btn" onclick="showDeleteMethod('customer')" id="customerMethodBtn">Delete by Customer</button>
            </div>
            
            <form method="POST" action="" id="bulkDeleteForm" onsubmit="return confirm('Are you sure you want to delete the selected sales? Stock will be restored.')">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="bulk_delete_sales" value="1">
                <input type="hidden" name="delete_method" id="delete_method" value="select">
                
                <!-- Select Specific Sales -->
                <div id="selectMethod" style="display: block;">
                    <div class="form-group">
                        <label>Select Sales to Delete</label>
                        <div class="select-all">
                            <input type="checkbox" id="selectAllSales" onchange="toggleAllSales()">
                            <label for="selectAllSales"><strong>Select All Sales</strong></label>
                        </div>
                        
                        <div class="records-list">
                            <?php foreach ($sales as $sale): ?>
                            <div class="record-item">
                                <input type="checkbox" name="delete_ids[]" value="<?php echo $sale['id']; ?>" class="record-checkbox sale-checkbox" onchange="updateBulkDeleteButton()">
                                <div class="record-details">
                                    <div class="record-title">
                                        <?php echo htmlspecialchars($sale['receipt_no'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($sale['product_name']); ?> x<?php echo $sale['quantity']; ?>
                                        <?php if (!empty($sale['order_group_id'])): ?>
                                        <span class="order-badge" style="margin-left: 5px;">Bulk</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="record-meta"><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?> • <?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></div>
                                </div>
                                <div class="record-amount">₱<?php echo number_format($sale['total_amount'], 2); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Delete by Date Range -->
                <div id="dateMethod" style="display: none;">
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="date-range">
                            <input type="date" name="date_from" id="date_from" class="date-input" onchange="updateDateRangeDelete()">
                            <span style="align-self: center;">to</span>
                            <input type="date" name="date_to" id="date_to" class="date-input" onchange="updateDateRangeDelete()">
                        </div>
                        <small style="color: #64748b;">All sales between these dates will be deleted</small>
                    </div>
                    
                    <div class="bulk-summary">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Sales in this range:</span>
                            <span id="dateRangeCount" class="badge" style="background: #EF4444; color: white;">0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Delete by Customer -->
                <div id="customerMethod" style="display: none;">
                    <div class="form-group">
                        <label>Customer Name</label>
                        <input type="text" name="delete_customer" id="delete_customer" class="form-control" placeholder="Enter customer name" onkeyup="updateCustomerDelete()">
                        <small style="color: #64748b;">All sales with this customer name will be deleted</small>
                    </div>
                    
                    <div class="bulk-summary">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Sales for this customer:</span>
                            <span id="customerCount" class="badge" style="background: #EF4444; color: white;">0</span>
                        </div>
                    </div>
                </div>
                
                <div class="bulk-summary" style="background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Total to Delete:</span>
                        <span id="totalSelected" class="badge" style="background: #EF4444; color: white;">0</span>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideBulkDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="confirmBulkDeleteBtn" disabled>
                        <i class="fas fa-trash-alt"></i> Delete Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i class="fas fa-receipt" style="color: #06B6D4;"></i> Sales Receipt</h3>
                <span class="close" onclick="hideReceiptModal()">&times;</span>
            </div>
            <div id="receiptContent" style="padding: 20px; text-align: center;">
                <!-- Receipt content will be inserted here -->
            </div>
            <div style="text-align: center; padding: 0 20px 20px;">
                <button onclick="window.print()" style="padding: 10px 20px; background: linear-gradient(135deg, #06B6D4, #14B8A6); color: white; border: none; border-radius: 30px; cursor: pointer; margin-right: 10px;">
                    <i class="fas fa-print"></i> Print
                </button>
                <button onclick="hideReceiptModal()" style="padding: 10px 20px; background: #f1f5f9; color: #64748b; border: none; border-radius: 30px; cursor: pointer;">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: true
        });

        // ========== MODAL FUNCTIONS ==========
        
        // Show add sale modal
        function showAddSaleModal() {
            document.getElementById('addSaleModal').style.display = 'flex';
            resetForm();
        }

        // Hide add sale modal
        function hideAddSaleModal() {
            document.getElementById('addSaleModal').style.display = 'none';
        }

        // Hide receipt modal
        function hideReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }

        // Reset form
        function resetForm() {
            document.getElementById('product_select').value = '';
            document.getElementById('sale_quantity').value = '';
            document.getElementById('productDetails').style.display = 'none';
        }

        // ========== SINGLE SALE CALCULATIONS ==========
        
        // Update product details when selected
        function updateProductDetails() {
            const select = document.getElementById('product_select');
            const selected = select.options[select.selectedIndex];
            
            if (selected && selected.value) {
                const price = parseFloat(selected.dataset.price);
                const stock = parseInt(selected.dataset.stock);
                
                document.getElementById('display_price').textContent = '₱' + price.toFixed(2);
                document.getElementById('display_stock').textContent = stock;
                document.getElementById('productDetails').style.display = 'block';
                
                if (document.getElementById('sale_quantity').value) {
                    calculateTotal();
                }
            } else {
                document.getElementById('productDetails').style.display = 'none';
            }
        }

        // Calculate total
        function calculateTotal() {
            const select = document.getElementById('product_select');
            const selected = select.options[select.selectedIndex];
            const quantity = document.getElementById('sale_quantity').value;
            
            if (selected && selected.value && quantity) {
                const price = parseFloat(selected.dataset.price);
                const stock = parseInt(selected.dataset.stock);
                
                if (parseInt(quantity) > stock) {
                    alert('Insufficient stock! Available: ' + stock);
                    document.getElementById('sale_quantity').value = stock;
                    return;
                }
                
                if (parseInt(quantity) <= 0) {
                    alert('Quantity must be greater than 0');
                    return;
                }
                
                const subtotal = price * quantity;
                const tax = subtotal * 0.12;
                const total = subtotal + tax;
                
                document.getElementById('display_subtotal').textContent = '₱' + subtotal.toFixed(2);
                document.getElementById('display_tax').textContent = '₱' + tax.toFixed(2);
                document.getElementById('display_total').textContent = '₱' + total.toFixed(2);
            }
        }

        // ========== BULK ORDER FUNCTIONS ==========
        
        let bulkRowCount = 1;

        function showBulkAddModal() {
            document.getElementById('bulkAddModal').style.display = 'flex';
            updateBulkSummary();
        }

        function hideBulkAddModal() {
            document.getElementById('bulkAddModal').style.display = 'none';
            // Reset to one row
            const container = document.getElementById('bulk-items-container');
            container.innerHTML = '';
            addBulkRow(); // Add first row
            bulkRowCount = 1;
            updateBulkSummary();
        }

        function addBulkRow() {
            const container = document.getElementById('bulk-items-container');
            const rowId = bulkRowCount;
            
            const products = <?php echo json_encode($products); ?>;
            let productOptions = '<option value="">Select Product</option>';
            products.forEach(product => {
                productOptions += `<option value="${product.id}" data-price="${product.selling_price}" data-stock="${product.stock_quantity}" data-name="${product.product_name}">${product.product_name} (Stock: ${product.stock_quantity}) - ₱${parseFloat(product.selling_price).toFixed(2)}</option>`;
            });
            
            const newRow = document.createElement('div');
            newRow.className = 'bulk-item-row';
            newRow.id = `bulk-row-${rowId}`;
            
            newRow.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="color: var(--primary);">Item #${rowId + 1}</h4>
                    <button type="button" class="remove-row-btn" onclick="removeBulkRow(${rowId})" id="remove-${rowId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Product</label>
                        <select class="bulk-product-${rowId}" onchange="updateBulkItemPrice(${rowId})" required>
                            ${productOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" class="bulk-quantity-${rowId}" min="1" value="1" onchange="updateBulkItemPrice(${rowId})" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Unit Price</label>
                        <input type="text" class="bulk-price-${rowId}" id="bulk-price-${rowId}" value="₱0.00" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Subtotal</label>
                        <input type="text" class="bulk-subtotal-${rowId}" id="bulk-subtotal-${rowId}" value="₱0.00" readonly disabled>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tax (12%)</label>
                        <input type="text" class="bulk-tax-${rowId}" id="bulk-tax-${rowId}" value="₱0.00" readonly disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Total</label>
                        <input type="text" class="bulk-total-${rowId}" id="bulk-total-${rowId}" value="₱0.00" readonly disabled style="color: var(--success); font-weight: 600;">
                    </div>
                </div>
            `;
            
            container.appendChild(newRow);
            bulkRowCount++;
            updateBulkSummary();
        }

        function removeBulkRow(rowId) {
            const row = document.getElementById(`bulk-row-${rowId}`);
            if (row) {
                row.remove();
                // Renumber remaining items
                const rows = document.querySelectorAll('.bulk-item-row');
                rows.forEach((row, index) => {
                    const header = row.querySelector('h4');
                    if (header) {
                        header.textContent = `Item #${index + 1}`;
                    }
                });
                bulkRowCount = rows.length;
                updateBulkSummary();
            }
        }

        function updateBulkItemPrice(rowId) {
            let select, quantity, price, stock;
            
            if (rowId === 0) {
                select = document.querySelector('.bulk-product');
                quantity = document.querySelector('.bulk-quantity').value;
            } else {
                select = document.querySelector(`.bulk-product-${rowId}`);
                quantity = document.querySelector(`.bulk-quantity-${rowId}`).value;
            }
            
            if (select && select.value && quantity) {
                const selected = select.options[select.selectedIndex];
                price = parseFloat(selected.dataset.price);
                stock = parseInt(selected.dataset.stock);
                
                if (parseInt(quantity) > stock) {
                    alert('Insufficient stock! Available: ' + stock);
                    if (rowId === 0) {
                        document.querySelector('.bulk-quantity').value = stock;
                    } else {
                        document.querySelector(`.bulk-quantity-${rowId}`).value = stock;
                    }
                    quantity = stock;
                }
                
                const subtotal = price * quantity;
                const tax = subtotal * 0.12;
                const total = subtotal + tax;
                
                if (rowId === 0) {
                    document.getElementById('bulk-price-0').value = '₱' + price.toFixed(2);
                    document.getElementById('bulk-subtotal-0').value = '₱' + subtotal.toFixed(2);
                    document.getElementById('bulk-tax-0').value = '₱' + tax.toFixed(2);
                    document.getElementById('bulk-total-0').value = '₱' + total.toFixed(2);
                } else {
                    document.getElementById(`bulk-price-${rowId}`).value = '₱' + price.toFixed(2);
                    document.getElementById(`bulk-subtotal-${rowId}`).value = '₱' + subtotal.toFixed(2);
                    document.getElementById(`bulk-tax-${rowId}`).value = '₱' + tax.toFixed(2);
                    document.getElementById(`bulk-total-${rowId}`).value = '₱' + total.toFixed(2);
                }
            }
            
            updateBulkSummary();
        }

        function updateBulkSummary() {
            const rows = document.querySelectorAll('.bulk-item-row');
            document.querySelector('.bulk-total-items').textContent = rows.length;
            
            let grandSubtotal = 0;
            let grandTax = 0;
            let grandTotal = 0;
            
            rows.forEach((row, index) => {
                let subtotal = 0, tax = 0, total = 0;
                
                if (index === 0) {
                    subtotal = parseFloat(document.getElementById('bulk-subtotal-0')?.value.replace('₱', '')) || 0;
                    tax = parseFloat(document.getElementById('bulk-tax-0')?.value.replace('₱', '')) || 0;
                    total = parseFloat(document.getElementById('bulk-total-0')?.value.replace('₱', '')) || 0;
                } else {
                    subtotal = parseFloat(document.getElementById(`bulk-subtotal-${index}`)?.value.replace('₱', '')) || 0;
                    tax = parseFloat(document.getElementById(`bulk-tax-${index}`)?.value.replace('₱', '')) || 0;
                    total = parseFloat(document.getElementById(`bulk-total-${index}`)?.value.replace('₱', '')) || 0;
                }
                
                grandSubtotal += subtotal;
                grandTax += tax;
                grandTotal += total;
            });
            
            document.querySelector('.bulk-grand-subtotal').textContent = '₱' + grandSubtotal.toFixed(2);
            document.querySelector('.bulk-grand-tax').textContent = '₱' + grandTax.toFixed(2);
            document.querySelector('.bulk-grand-total').textContent = '₱' + grandTotal.toFixed(2);
            
            // Show/hide remove buttons based on count
            if (rows.length <= 1) {
                document.querySelectorAll('.remove-row-btn').forEach(btn => {
                    btn.style.display = 'none';
                });
            } else {
                document.querySelectorAll('.remove-row-btn').forEach(btn => {
                    btn.style.display = 'flex';
                });
            }
        }

        function submitBulkOrder() {
            const rows = document.querySelectorAll('.bulk-item-row');
            const items = [];
            let isValid = true;
            
            rows.forEach((row, index) => {
                let productId, quantity;
                
                if (index === 0) {
                    productId = row.querySelector('.bulk-product')?.value;
                    quantity = row.querySelector('.bulk-quantity')?.value;
                } else {
                    productId = row.querySelector(`.bulk-product-${index}`)?.value;
                    quantity = row.querySelector(`.bulk-quantity-${index}`)?.value;
                }
                
                if (!productId || !quantity || quantity <= 0) {
                    alert(`Please select a product and enter valid quantity for Item #${index + 1}`);
                    isValid = false;
                    return;
                }
                
                items.push({
                    product_id: parseInt(productId),
                    quantity: parseInt(quantity)
                });
            });
            
            if (!isValid) return;
            
            if (items.length === 0) {
                alert('No items to process');
                return;
            }
            
            const customerName = document.getElementById('bulk_customer_name').value || 'Walk-in Customer';
            const paymentMethod = document.getElementById('bulk_payment_method').value;
            const notes = document.getElementById('bulk_notes').value;
            
            // Create a form to submit all items
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'sales.php';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $csrf_token; ?>';
            form.appendChild(csrfInput);
            
            // Add bulk flag
            const bulkInput = document.createElement('input');
            bulkInput.type = 'hidden';
            bulkInput.name = 'bulk_add_sales';
            bulkInput.value = '1';
            form.appendChild(bulkInput);
            
            // Add customer details
            const customerInput = document.createElement('input');
            customerInput.type = 'hidden';
            customerInput.name = 'customer_name';
            customerInput.value = customerName;
            form.appendChild(customerInput);
            
            const paymentInput = document.createElement('input');
            paymentInput.type = 'hidden';
            paymentInput.name = 'payment_method';
            paymentInput.value = paymentMethod;
            form.appendChild(paymentInput);
            
            const notesInput = document.createElement('input');
            notesInput.type = 'hidden';
            notesInput.name = 'notes';
            notesInput.value = notes;
            form.appendChild(notesInput);
            
            // Add items as JSON
            const itemsInput = document.createElement('input');
            itemsInput.type = 'hidden';
            itemsInput.name = 'bulk_items';
            itemsInput.value = JSON.stringify(items);
            form.appendChild(itemsInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        // ========== BULK DELETE FUNCTIONS ==========
        
        let currentDeleteMethod = 'select';

        function showBulkDeleteModal() {
            document.getElementById('bulkDeleteModal').style.display = 'flex';
            showDeleteMethod('select');
            updateBulkDeleteButton();
        }

        function hideBulkDeleteModal() {
            document.getElementById('bulkDeleteModal').style.display = 'none';
        }

        function showDeleteMethod(method) {
            currentDeleteMethod = method;
            document.getElementById('delete_method').value = method;
            
            // Update tab buttons
            document.querySelectorAll('.delete-option-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(method + 'MethodBtn').classList.add('active');
            
            // Show/hide sections
            document.getElementById('selectMethod').style.display = method === 'select' ? 'block' : 'none';
            document.getElementById('dateMethod').style.display = method === 'date' ? 'block' : 'none';
            document.getElementById('customerMethod').style.display = method === 'customer' ? 'block' : 'none';
            
            if (method === 'select') {
                updateBulkDeleteButton();
            } else if (method === 'date') {
                updateDateRangeDelete();
            } else if (method === 'customer') {
                updateCustomerDelete();
            }
        }

        function toggleAllSales() {
            const selectAll = document.getElementById('selectAllSales');
            const checkboxes = document.querySelectorAll('.sale-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkDeleteButton();
        }

        function updateBulkDeleteButton() {
            if (currentDeleteMethod === 'select') {
                const checkboxes = document.querySelectorAll('.sale-checkbox:checked');
                const count = checkboxes.length;
                document.getElementById('totalSelected').textContent = count;
                document.getElementById('confirmBulkDeleteBtn').disabled = count === 0;
            }
        }

        function updateDateRangeDelete() {
            const fromDate = document.getElementById('date_from').value;
            const toDate = document.getElementById('date_to').value;
            
            if (fromDate && toDate) {
                document.getElementById('confirmBulkDeleteBtn').disabled = false;
                document.getElementById('dateRangeCount').textContent = '?';
                document.getElementById('totalSelected').textContent = '?';
            } else {
                document.getElementById('confirmBulkDeleteBtn').disabled = true;
                document.getElementById('dateRangeCount').textContent = '0';
                document.getElementById('totalSelected').textContent = '0';
            }
        }

        function updateCustomerDelete() {
            const customer = document.getElementById('delete_customer').value;
            
            if (customer.trim() !== '') {
                document.getElementById('confirmBulkDeleteBtn').disabled = false;
                document.getElementById('customerCount').textContent = '?';
                document.getElementById('totalSelected').textContent = '?';
            } else {
                document.getElementById('confirmBulkDeleteBtn').disabled = true;
                document.getElementById('customerCount').textContent = '0';
                document.getElementById('totalSelected').textContent = '0';
            }
        }

        // ========== RECEIPT FUNCTIONS ==========
        
        function viewReceipt(receiptNo) {
            if (!receiptNo || receiptNo === 'N/A' || receiptNo === null) {
                receiptNo = 'TEMP-' + new Date().getTime();
            }
            
            // Get current date and time
            const now = new Date();
            const dateStr = now.toLocaleDateString();
            const timeStr = now.toLocaleTimeString();
            
            // Create receipt content
            const receiptContent = document.getElementById('receiptContent');
            receiptContent.innerHTML = `
                <div style="text-align: center;">
                    <h3 style="color: #06B6D4; margin-bottom: 5px;">PLANORA</h3>
                    <p style="color: #64748b; font-size: 12px; margin-bottom: 15px;">"Accounting Ko ang Account Mo."</p>
                    <hr style="margin: 15px 0; border: 1px dashed #e2e8f0;">
                    <p style="text-align: left; margin-bottom: 5px;"><strong>Receipt #:</strong> ${receiptNo}</p>
                    <p style="text-align: left; margin-bottom: 5px;"><strong>Date:</strong> ${dateStr}</p>
                    <p style="text-align: left; margin-bottom: 5px;"><strong>Time:</strong> ${timeStr}</p>
                    <hr style="margin: 15px 0; border: 1px dashed #e2e8f0;">
                    <p style="color: #334155; font-style: italic;">Thank you for your business!</p>
                </div>
            `;
            
            // Show the modal
            document.getElementById('receiptModal').style.display = 'flex';
        }

        // ========== SEARCH FUNCTIONALITY ==========
        
        // Search sales table
        document.getElementById('searchSales')?.addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            const rows = document.querySelectorAll('#salesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });

        // ========== MODAL CLOSE ON OUTSIDE CLICK ==========
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

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

        // Initialize sale checkboxes
        document.querySelectorAll('.sale-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkDeleteButton);
        });

        // Initialize bulk summary
        setTimeout(() => {
            updateBulkSummary();
        }, 100);
    </script>
</body>
</html>
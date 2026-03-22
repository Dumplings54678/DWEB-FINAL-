<?php
/**
 * HAUccountant Expense Management
 * Complete working expense system with database operations
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Handle bulk add expenses
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_add_expenses'])) {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: expenses.php');
        exit();
    }
    
    $expenses = json_decode($_POST['bulk_expenses'], true);
    
    if (empty($expenses)) {
        $_SESSION['error'] = "No expenses to add.";
        header('Location: expenses.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        $success_count = 0;
        $total_amount = 0;
        
        foreach ($expenses as $expense) {
            // Validate
            if (empty($expense['category']) || empty($expense['description']) || $expense['amount'] <= 0) {
                continue;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO expenses (
                    category, amount, description, expense_date,
                    payment_method, vendor, reference_no,
                    created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $expense['category'],
                $expense['amount'],
                $expense['description'],
                $expense['expense_date'] ?? date('Y-m-d'),
                $expense['payment_method'] ?? 'cash',
                $expense['vendor'] ?? '',
                $expense['reference'] ?? '',
                $user_id
            ]);
            
            $success_count++;
            $total_amount += $expense['amount'];
        }
        
        logActivity($pdo, $user_id, 'BULK_ADD_EXPENSES', 'expenses', 
            "Added {$success_count} expenses totaling ₱" . number_format($total_amount, 2));
        
        $pdo->commit();
        
        $_SESSION['success'] = "Successfully added {$success_count} expenses!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk expense add failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add expenses. Please try again.";
    }
    
    header('Location: expenses.php');
    exit();
}

// Handle bulk delete expenses (admin only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_delete_expenses']) && $user_role === 'admin') {
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token.";
        header('Location: expenses.php');
        exit();
    }
    
    $delete_ids = $_POST['delete_ids'] ?? [];
    $delete_method = $_POST['delete_method'] ?? 'select';
    
    try {
        $pdo->beginTransaction();
        $deleted_count = 0;
        $deleted_total = 0;
        
        if ($delete_method === 'select' && !empty($delete_ids)) {
            // Delete specific expenses
            $delete_ids = array_map('intval', $delete_ids);
            $placeholders = implode(',', array_fill(0, count($delete_ids), '?'));
            
            // Get expense details for logging
            $stmt = $pdo->prepare("SELECT category, amount FROM expenses WHERE id IN ($placeholders)");
            $stmt->execute($delete_ids);
            $expenses_to_delete = $stmt->fetchAll();
            
            foreach ($expenses_to_delete as $expense) {
                $deleted_total += $expense['amount'];
            }
            
            // Delete expenses
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id IN ($placeholders)");
            $stmt->execute($delete_ids);
            $deleted_count = $stmt->rowCount();
            
        } elseif ($delete_method === 'date') {
            // Delete by date range
            $date_from = $_POST['date_from'] ?? '';
            $date_to = $_POST['date_to'] ?? '';
            
            if (!empty($date_from) && !empty($date_to)) {
                // Get expense details for logging
                $stmt = $pdo->prepare("SELECT category, amount FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $expenses_to_delete = $stmt->fetchAll();
                
                foreach ($expenses_to_delete as $expense) {
                    $deleted_total += $expense['amount'];
                }
                
                // Delete expenses
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE DATE(expense_date) BETWEEN ? AND ?");
                $stmt->execute([$date_from, $date_to]);
                $deleted_count = $stmt->rowCount();
            }
        } elseif ($delete_method === 'category') {
            // Delete by category
            $category = $_POST['delete_category'] ?? '';
            
            if (!empty($category)) {
                // Get expense details for logging
                $stmt = $pdo->prepare("SELECT category, amount FROM expenses WHERE category = ?");
                $stmt->execute([$category]);
                $expenses_to_delete = $stmt->fetchAll();
                
                foreach ($expenses_to_delete as $expense) {
                    $deleted_total += $expense['amount'];
                }
                
                // Delete expenses
                $stmt = $pdo->prepare("DELETE FROM expenses WHERE category = ?");
                $stmt->execute([$category]);
                $deleted_count = $stmt->rowCount();
            }
        }
        
        if ($deleted_count > 0) {
            logActivity($pdo, $user_id, 'BULK_DELETE_EXPENSES', 'expenses', 
                "Deleted {$deleted_count} expenses totaling ₱" . number_format($deleted_total, 2));
            $_SESSION['success'] = "Successfully deleted {$deleted_count} expenses.";
        } else {
            $_SESSION['error'] = "No expenses were deleted.";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete expenses: " . $e->getMessage();
    }
    
    header('Location: expenses.php');
    exit();
}

// Handle add expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    
    $category = trim($_POST['category']);
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $vendor = trim($_POST['vendor'] ?? '');
    $reference = trim($_POST['reference'] ?? '');
    
    // Validate
    $errors = [];
    
    if (empty($category)) {
        $errors[] = "Category is required.";
    }
    
    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0.";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert expense
            $stmt = $pdo->prepare("
                INSERT INTO expenses (
                    category, amount, description, expense_date,
                    payment_method, vendor, reference_no,
                    created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $category, $amount, $description, $expense_date,
                $payment_method, $vendor, $reference,
                $user_id
            ]);
            
            $expense_id = $pdo->lastInsertId();
            
            // Log activity
            logActivity($pdo, $user_id, 'ADD_EXPENSE', 'expenses', 
                "Added expense: {$category} - ₱" . number_format($amount, 2));
            
            $pdo->commit();
            
            $_SESSION['success'] = "Expense added successfully!";
            header('Location: expenses.php?highlight=' . $expense_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Expense add failed: " . $e->getMessage());
            $_SESSION['error'] = "Failed to add expense. Please try again.";
            header('Location: expenses.php');
            exit();
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
        header('Location: expenses.php');
        exit();
    }
}

// Handle edit expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_expense'])) {
    
    $expense_id = (int)$_POST['expense_id'];
    $category = trim($_POST['category']);
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    $expense_date = $_POST['expense_date'];
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $vendor = trim($_POST['vendor'] ?? '');
    $reference = trim($_POST['reference'] ?? '');
    
    // Validate
    $errors = [];
    
    if ($expense_id <= 0) {
        $errors[] = "Invalid expense ID.";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required.";
    }
    
    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0.";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Get old expense details for logging
            $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
            $old_expense = $stmt->fetch();
            
            if (!$old_expense) {
                throw new Exception("Expense not found.");
            }
            
            // Update expense
            $stmt = $pdo->prepare("
                UPDATE expenses SET 
                    category = ?, 
                    amount = ?, 
                    description = ?, 
                    expense_date = ?,
                    payment_method = ?,
                    vendor = ?,
                    reference_no = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $category, $amount, $description, $expense_date,
                $payment_method, $vendor, $reference,
                $expense_id
            ]);
            
            // Log activity
            logActivity($pdo, $user_id, 'EDIT_EXPENSE', 'expenses', 
                "Edited expense ID: {$expense_id} - {$category} - ₱" . number_format($amount, 2));
            
            $pdo->commit();
            
            $_SESSION['success'] = "Expense updated successfully!";
            header('Location: expenses.php?highlight=' . $expense_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Expense edit failed: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update expense. Please try again.";
            header('Location: expenses.php');
            exit();
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
        header('Location: expenses.php');
        exit();
    }
}

// Handle get expense for edit (AJAX)
if (isset($_GET['get_expense'])) {
    $id = (int)$_GET['get_expense'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
        $stmt->execute([$id]);
        $expense = $stmt->fetch();
        
        if ($expense) {
            header('Content-Type: application/json');
            echo json_encode($expense);
            exit();
        }
    } catch (Exception $e) {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['error' => 'Expense not found']);
        exit();
    }
}

// Handle delete expense (admin only)
if (isset($_GET['delete']) && $user_role === 'admin') {
    $expense_id = (int)$_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // Get expense details for logging
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
        $stmt->execute([$expense_id]);
        $expense = $stmt->fetch();
        
        if ($expense) {
            // Delete expense
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
            
            // Log activity
            logActivity($pdo, $user_id, 'DELETE_EXPENSE', 'expenses', 
                "Deleted expense: {$expense['category']} - ₱" . number_format($expense['amount'], 2));
            
            $_SESSION['success'] = "Expense deleted successfully.";
        }
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete expense.";
    }
    
    header('Location: expenses.php');
    exit();
}

// Get all expenses
$stmt = $pdo->query("
    SELECT e.*, u.owner_name as created_by_name
    FROM expenses e
    LEFT JOIN users u ON e.created_by = u.id
    ORDER BY e.expense_date DESC, e.created_at DESC
");
$expenses = $stmt->fetchAll();

// Calculate totals
$total_expenses = array_sum(array_column($expenses, 'amount'));

// Get expense breakdown by category
$stmt = $pdo->query("
    SELECT category, COUNT(*) as count, SUM(amount) as total
    FROM expenses
    GROUP BY category
    ORDER BY total DESC
");
$category_breakdown = $stmt->fetchAll();

// Get current month's total
$stmt = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) as total
    FROM expenses
    WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())
");
$month_total = $stmt->fetch()['total'];

// Get budget data
$current_month = date('F Y');
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE month_year = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$current_month]);
$current_budget = $stmt->fetch();

$budget_percentage = 0;
if ($current_budget && $current_budget['expense_limit'] > 0) {
    $budget_percentage = min(100, round(($month_total / $current_budget['expense_limit']) * 100));
}

// Expense categories list
$expense_categories = [
    'Rent' => '🏢 Rent',
    'Utilities' => '💡 Utilities',
    'Supplies' => '📦 Supplies',
    'Payroll' => '👥 Payroll',
    'Marketing' => '📢 Marketing',
    'Transportation' => '🚚 Transportation',
    'Maintenance' => '🔧 Maintenance',
    'Insurance' => '🛡️ Insurance',
    'Taxes' => '📊 Taxes',
    'Professional Fees' => '⚖️ Professional Fees',
    'Technology' => '💻 Technology',
    'Training' => '📚 Training',
    'Travel' => '✈️ Travel',
    'Meals' => '🍽️ Meals',
    'Office Expenses' => '📎 Office Expenses',
    'Other' => '📌 Other'
];

// Payment methods
$payment_methods = [
    'cash' => '💵 Cash',
    'bank' => '🏦 Bank Transfer',
    'card' => '💳 Credit Card',
    'gcash' => '📱 GCash',
    'maya' => '📱 Maya',
    'check' => '📝 Check'
];

// Get unread count for badge
$unread_count = getUnreadContactCount($pdo);

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management - PLANORA</title>
    
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
        
        .expenses-header {
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
        
        .stat-card.warning .value {
            color: var(--warning);
        }
        
        .stat-card.danger .value {
            color: var(--danger);
        }
        
        .add-expense-btn {
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
        
        .add-expense-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
        }
        
        .budget-warning {
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #9a3412;
        }
        
        .budget-warning i {
            font-size: 24px;
            color: var(--warning);
        }
        
        .budget-warning.danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #fecaca;
            color: #991b1b;
        }
        
        .budget-warning.danger i {
            color: var(--danger);
        }
        
        .category-breakdown {
            background: white;
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
        }
        
        .category-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .category-item:hover {
            background: #f8fafc;
        }
        
        .category-info {
            width: 150px;
        }
        
        .category-name {
            font-weight: 600;
            color: #0f172a;
        }
        
        .category-count {
            font-size: 11px;
            color: #64748b;
        }
        
        .category-bar {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 20px;
            margin: 0 16px;
            overflow: hidden;
        }
        
        .category-progress {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 20px;
            transition: width 1s ease;
        }
        
        .category-amount {
            font-weight: 600;
            color: var(--primary-dark);
            min-width: 100px;
            text-align: right;
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
            max-height: 85vh;
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
        
        .btn-success {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        .expenses-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }
        
        .expenses-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .expenses-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        
        .expenses-table tr:hover td {
            background: #f8fafc;
        }
        
        .expenses-table tr.highlight {
            background: #cffafe;
        }
        
        .category-badge {
            background: var(--primary-soft);
            color: var(--primary-dark);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
        
        .payment-badge.bank {
            background: #cffafe;
            color: #0891b2;
        }
        
        .payment-badge.card {
            background: #c7d2fe;
            color: #3730a3;
        }
        
        .payment-badge.gcash {
            background: #fef9c3;
            color: #854d0e;
        }
        
        .payment-badge.maya {
            background: #fae8ff;
            color: #7e22ce;
        }
        
        .amount.negative {
            color: var(--danger);
            font-weight: 600;
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
        
        .action-btn.edit {
            background: var(--primary-soft);
            color: var(--primary);
        }
        
        .action-btn.edit:hover {
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
        
        .search-box i {
            color: var(--primary);
        }
        
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid rgba(6, 182, 212, 0.1);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
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
            color: #EF4444;
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
        
        /* CALENDAR MODAL STYLES */
        .calendar-modal {
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
        
        .calendar-modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 1000px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        
        .calendar-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-radius: 24px 24px 0 0;
        }
        
        .calendar-modal-header h3 {
            font-family: 'Enriqueta', serif;
            font-size: 22px;
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .calendar-modal-header .close {
            background: rgba(255,255,255,0.2);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
        }
        
        .calendar-body {
            padding: 25px;
        }
        
        .calendar-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .calendar-nav-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            background: white;
            cursor: pointer;
        }
        
        .calendar-nav-btn:hover {
            background: #cffafe;
            border-color: #06B6D4;
        }
        
        .current-month-year {
            font-size: 18px;
            font-weight: 600;
            min-width: 180px;
            text-align: center;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
        }
        
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            border: 1px solid #e2e8f0;
        }
        
        .calendar-day:hover {
            border-color: #06B6D4;
            transform: scale(1.02);
        }
        
        .calendar-day.has-data {
            background: linear-gradient(135deg, #cffafe, #ccfbf1);
            border-color: #06B6D4;
        }
        
        .calendar-day-number {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .calendar-day-badge {
            font-size: 10px;
            background: #EF4444;
            color: white;
            border-radius: 30px;
            padding: 2px 6px;
            margin-top: 4px;
        }
        
        .calendar-day-amount {
            font-size: 10px;
            font-weight: 600;
            margin-top: 4px;
        }
        
        .calendar-day-amount.negative {
            color: #EF4444;
        }
        
        .day-details-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }
        
        .day-details-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        
        .day-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-radius: 24px 24px 0 0;
        }
        
        .day-details-header h3 {
            font-family: 'Enriqueta', serif;
            font-size: 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .day-details-header .close {
            background: rgba(255,255,255,0.2);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
        }
        
        .day-details-body {
            padding: 25px;
        }
        
        .day-details-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .day-stat {
            background: #f8fafc;
            padding: 15px;
            border-radius: 16px;
            text-align: center;
        }
        
        .day-stat .label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 5px;
        }
        
        .day-stat .value {
            font-size: 20px;
            font-weight: 700;
        }
        
        .day-stat .value.negative {
            color: #EF4444;
        }
        
        .day-details-list {
            margin-top: 20px;
        }
        
        .day-details-list h4 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .day-transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .day-transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-desc {
            font-weight: 500;
        }
        
        .transaction-amount {
            font-weight: 600;
        }
        
        .transaction-amount.negative {
            color: #EF4444;
        }
        
        .empty-day {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }
        
        @media screen and (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media screen and (max-width: 768px) {
            .expenses-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .category-item {
                flex-wrap: wrap;
            }
            
            .category-info {
                width: 100%;
                margin-bottom: 8px;
            }
            
            .category-bar {
                margin: 8px 0;
                width: 100%;
                order: 3;
            }
            
            .category-amount {
                width: 100%;
                text-align: left;
                order: 2;
            }
            
            .expenses-table {
                display: block;
                overflow-x: auto;
            }
            
            .delete-options {
                flex-direction: column;
            }
            
            .date-range {
                flex-direction: column;
            }
            
            .calendar-weekdays {
                font-size: 12px;
            }
            
            .calendar-day {
                padding: 5px;
            }
            
            .calendar-day-number {
                font-size: 12px;
            }
            
            .day-details-stats {
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

            <div class="expenses-header">
                <div>
                    <h1>Expense Management</h1>
                    <p class="subtitle">Track and categorize your business expenses</p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button class="add-expense-btn" onclick="showAddExpenseModal()">
                        <i class="fas fa-plus-circle"></i> Add Expense
                    </button>
                    <button class="btn btn-outline" onclick="showBulkAddModal()">
                        <i class="fas fa-layer-group"></i> Bulk Add
                    </button>
                    <button class="btn btn-outline" onclick="showCalendarModal()">
                        <i class="fas fa-calendar-alt"></i> Calendar
                    </button>
                    <?php if ($user_role === 'admin'): ?>
                    <button class="btn btn-outline" onclick="showBulkDeleteModal()" style="color: #EF4444; border-color: #EF4444;">
                        <i class="fas fa-trash-alt"></i> Bulk Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Budget Warning -->
            <?php if ($current_budget && $budget_percentage >= 80): ?>
            <div class="budget-warning <?php echo $budget_percentage > 100 ? 'danger' : ''; ?>">
                <i class="fas <?php echo $budget_percentage > 100 ? 'fa-exclamation-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <div>
                    <strong><?php echo $budget_percentage > 100 ? 'Budget Exceeded!' : 'Approaching Budget Limit'; ?></strong>
                    <p>You have used <?php echo $budget_percentage; ?>% of your monthly expense limit (₱<?php echo number_format($current_budget['expense_limit'], 2); ?>).</p>
                </div>
                <a href="budget.php" style="margin-left: auto; color: var(--primary); text-decoration: none; font-weight: 600;">View Budget →</a>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card">
                    <div class="label">
                        <i class="fas fa-calendar-month"></i> This Month
                    </div>
                    <div class="value">₱<?php echo number_format($month_total, 2); ?></div>
                    <div class="sub">Total expenses</div>
                </div>
                
                <div class="stat-card">
                    <div class="label">
                        <i class="fas fa-layer-group"></i> Categories
                    </div>
                    <div class="value"><?php echo count($category_breakdown); ?></div>
                    <div class="sub">Active categories</div>
                </div>
                
                <div class="stat-card <?php echo $budget_percentage > 100 ? 'danger' : ($budget_percentage > 80 ? 'warning' : ''); ?>">
                    <div class="label">
                        <i class="fas fa-chart-pie"></i> Budget Used
                    </div>
                    <div class="value"><?php echo $budget_percentage; ?>%</div>
                    <div class="sub">of ₱<?php echo number_format($current_budget['expense_limit'] ?? 0, 2); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="label">
                        <i class="fas fa-receipt"></i> Total Expenses
                    </div>
                    <div class="value"><?php echo count($expenses); ?></div>
                    <div class="sub">All time</div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <?php if (!empty($category_breakdown)): ?>
            <div class="category-breakdown" data-aos="fade-up">
                <h3 style="margin-bottom: 20px; color: #0f172a;">
                    <i class="fas fa-chart-pie" style="color: var(--primary); margin-right: 10px;"></i>
                    Expense Breakdown by Category
                </h3>
                
                <?php foreach ($category_breakdown as $index => $category): 
                    $percentage = $total_expenses > 0 ? round(($category['total'] / $total_expenses) * 100) : 0;
                ?>
                <div class="category-item" data-aos="fade-right" data-aos-delay="<?php echo $index * 50; ?>">
                    <div class="category-info">
                        <div class="category-name"><?php echo htmlspecialchars($category['category']); ?></div>
                        <div class="category-count"><?php echo $category['count']; ?> transactions</div>
                    </div>
                    <div class="category-bar">
                        <div class="category-progress" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="category-amount">
                        ₱<?php echo number_format($category['total'], 2); ?>
                        <small style="color: #64748b;">(<?php echo $percentage; ?>%)</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Search Box -->
            <div class="search-box" data-aos="fade-up">
                <input type="text" id="searchExpenses" placeholder="Search by category, description, or vendor...">
                <i class="fas fa-search" style="color: var(--primary);"></i>
            </div>

            <!-- Expenses Table -->
            <div style="background: white; border-radius: 24px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);" data-aos="fade-up">
                <?php if (count($expenses) > 0): ?>
                <table class="expenses-table" id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Vendor</th>
                            <th>Payment</th>
                            <th>Amount</th>
                            <th>Added By</th>
                            <?php if ($user_role === 'admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): 
                            $row_class = (isset($_GET['highlight']) && $_GET['highlight'] == $expense['id']) ? 'highlight' : '';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td>
                                <?php echo date('M j, Y', strtotime($expense['expense_date'])); ?>
                                <br>
                                <small style="color: #94a3b8;"><?php echo date('g:i A', strtotime($expense['created_at'])); ?></small>
                            </td>
                            <td>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($expense['category']); ?>
                                </span>
                             </td>
                             <td>
                                <strong><?php echo htmlspecialchars($expense['description']); ?></strong>
                                <?php if (!empty($expense['reference_no'])): ?>
                                <br>
                                <small style="color: #64748b;">Ref: <?php echo htmlspecialchars($expense['reference_no']); ?></small>
                                <?php endif; ?>
                             </td>
                             <td><?php echo htmlspecialchars($expense['vendor'] ?? '—'); ?></td>
                             <td>
                                <?php 
                                $payment_method_value = $expense['payment_method'] ?? 'cash';
                                $payment_method_label = $payment_methods[$payment_method_value] ?? 'Cash';
                                ?>
                                <span class="payment-badge <?php echo strtolower($payment_method_value); ?>">
                                    <?php echo $payment_method_label; ?>
                                </span>
                             </td>
                            <td class="amount negative">-₱<?php echo number_format($expense['amount'], 2); ?></td>
                             <td><?php echo htmlspecialchars($expense['created_by_name'] ?? 'System'); ?></td>
                            <?php if ($user_role === 'admin'): ?>
                             <td>
                                <div class="action-buttons">
                                    <button class="action-btn edit" onclick="editExpense(<?php echo $expense['id']; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $expense['id']; ?>" 
                                       class="action-btn delete" 
                                       onclick="return confirm('Are you sure you want to delete this expense? This action cannot be undone.')"
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
                    <i class="fas fa-receipt" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h3 style="color: #334155; margin-bottom: 10px;">No Expenses Yet</h3>
                    <p style="color: #64748b; margin-bottom: 20px;">Start by adding your first expense</p>
                    <button class="add-expense-btn" onclick="showAddExpenseModal()" style="display: inline-flex;">
                        <i class="fas fa-plus-circle"></i> Add First Expense
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Expense</h3>
                <span class="close" onclick="hideAddExpenseModal()">&times;</span>
            </div>
            
            <form method="POST" action="" id="expenseForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($expense_categories as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <?php foreach ($payment_methods as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Vendor/Payee</label>
                        <input type="text" name="vendor" placeholder="Who was paid?">
                    </div>
                    
                    <div class="form-group">
                        <label>Reference No.</label>
                        <input type="text" name="reference" placeholder="Invoice/OR number">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="hideAddExpenseModal()">Cancel</button>
                    <button type="submit" name="add_expense" class="btn-primary">
                        <i class="fas fa-check"></i> Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div id="editExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Expense</h3>
                <span class="close" onclick="hideEditExpenseModal()">&times;</span>
            </div>
            
            <form method="POST" action="" id="editExpenseForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="expense_id" id="edit_expense_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="edit_category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($expense_categories as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount (₱)</label>
                        <input type="number" step="0.01" name="amount" id="edit_amount" required placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="2" required placeholder="What was this expense for?"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="expense_date" id="edit_expense_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" id="edit_payment_method">
                            <?php foreach ($payment_methods as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Vendor/Payee</label>
                        <input type="text" name="vendor" id="edit_vendor" placeholder="Who was paid?">
                    </div>
                    
                    <div class="form-group">
                        <label>Reference No.</label>
                        <input type="text" name="reference" id="edit_reference" placeholder="Invoice/OR number">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="hideEditExpenseModal()">Cancel</button>
                    <button type="submit" name="edit_expense" class="btn-success">
                        <i class="fas fa-save"></i> Update Expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Add Expenses Modal -->
    <div id="bulkAddModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-layer-group" style="color: var(--primary);"></i> Bulk Add Expenses</h3>
                <span class="close" onclick="hideBulkAddModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px;">
                <p class="subtitle">Add multiple expenses at once. Fill in the details below.</p>
            </div>
            
            <div id="bulk-items-container">
                <!-- First item row -->
                <div class="bulk-item-row" id="bulk-row-0">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="color: var(--primary);">Expense #1</h4>
                        <button type="button" class="remove-row-btn" onclick="removeBulkRow(0)" style="display: none;" id="remove-0">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select class="bulk-category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($expense_categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Amount (₱)</label>
                            <input type="number" step="0.01" class="bulk-amount" placeholder="0.00" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="bulk-description" rows="2" placeholder="What was this expense for?" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" class="bulk-date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select class="bulk-payment">
                                <?php foreach ($payment_methods as $value => $label): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Vendor/Payee</label>
                            <input type="text" class="bulk-vendor" placeholder="Who was paid?">
                        </div>
                        
                        <div class="form-group">
                            <label>Reference No.</label>
                            <input type="text" class="bulk-reference" placeholder="Invoice/OR number">
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; margin: 20px 0;">
                <button type="button" class="btn btn-outline" onclick="addBulkRow()" style="flex: 1;">
                    <i class="fas fa-plus-circle"></i> Add Another Expense
                </button>
            </div>
            
            <div class="bulk-summary">
                <h4 style="margin-bottom: 10px;">Summary</h4>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Total Expenses to Add:</span>
                    <span class="bulk-total-items" style="font-weight: 700; font-size: 18px; color: var(--primary);">1</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                    <span>Total Amount:</span>
                    <span class="bulk-total-amount" style="font-weight: 700; font-size: 18px; color: var(--danger);">₱0.00</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="hideBulkAddModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBulkExpenses()">
                    <i class="fas fa-check-circle"></i> Add All Expenses
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal (Admin Only) -->
    <?php if ($user_role === 'admin'): ?>
    <div id="bulkDeleteModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-trash-alt" style="color: #EF4444;"></i> Bulk Delete Expenses</h3>
                <span class="close" onclick="hideBulkDeleteModal()">&times;</span>
            </div>
            
            <div style="margin-bottom: 20px; color: #EF4444; background: #fee2e2; padding: 15px; border-radius: 12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> This action cannot be undone. Please select expenses carefully.
            </div>
            
            <div class="delete-options">
                <button type="button" class="delete-option-btn active" onclick="showDeleteMethod('select')" id="selectMethodBtn">Select Specific Expenses</button>
                <button type="button" class="delete-option-btn" onclick="showDeleteMethod('date')" id="dateMethodBtn">Delete by Date Range</button>
                <button type="button" class="delete-option-btn" onclick="showDeleteMethod('category')" id="categoryMethodBtn">Delete by Category</button>
            </div>
            
            <form method="POST" action="" id="bulkDeleteForm" onsubmit="return confirm('Are you sure you want to delete the selected expenses?')">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="bulk_delete_expenses" value="1">
                <input type="hidden" name="delete_method" id="delete_method" value="select">
                
                <!-- Select Specific Expenses -->
                <div id="selectMethod" style="display: block;">
                    <div class="form-group">
                        <label>Select Expenses to Delete</label>
                        <div class="select-all">
                            <input type="checkbox" id="selectAllExpenses" onchange="toggleAllExpenses()">
                            <label for="selectAllExpenses"><strong>Select All Expenses</strong></label>
                        </div>
                        
                        <div class="records-list">
                            <?php foreach ($expenses as $expense): ?>
                            <div class="record-item">
                                <input type="checkbox" name="delete_ids[]" value="<?php echo $expense['id']; ?>" class="record-checkbox expense-checkbox" onchange="updateBulkDeleteButton()">
                                <div class="record-details">
                                    <div class="record-title"><?php echo htmlspecialchars($expense['category']); ?> - <?php echo htmlspecialchars($expense['description']); ?></div>
                                    <div class="record-meta"><?php echo date('M j, Y', strtotime($expense['expense_date'])); ?> • <?php echo htmlspecialchars($expense['vendor'] ?? 'N/A'); ?></div>
                                </div>
                                <div class="record-amount">₱<?php echo number_format($expense['amount'], 2); ?></div>
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
                        <small style="color: #64748b;">All expenses between these dates will be deleted</small>
                    </div>
                    
                    <div class="bulk-summary">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Expenses in this range:</span>
                            <span id="dateRangeCount" class="badge" style="background: #EF4444; color: white;">0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Delete by Category -->
                <div id="categoryMethod" style="display: none;">
                    <div class="form-group">
                        <label>Select Category</label>
                        <select name="delete_category" id="delete_category" class="form-control" onchange="updateCategoryDelete()">
                            <option value="">Select Category</option>
                            <?php foreach ($expense_categories as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="bulk-summary">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Expenses in this category:</span>
                            <span id="categoryCount" class="badge" style="background: #EF4444; color: white;">0</span>
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

    <!-- CALENDAR MODAL -->
    <div id="calendarModal" class="calendar-modal">
        <div class="calendar-modal-content">
            <div class="calendar-modal-header">
                <h3><i class="fas fa-calendar-alt"></i> Expense Calendar</h3>
                <span class="close" onclick="closeCalendarModal()">&times;</span>
            </div>
            <div class="calendar-body">
                <div class="calendar-grid">
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <button class="calendar-nav-btn" onclick="changeMonth(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span id="currentMonthYear" class="current-month-year"></span>
                            <button class="calendar-nav-btn" onclick="changeMonth(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline" onclick="resetToCurrentMonth()">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                    </div>
                    <div class="calendar-weekdays">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div id="calendarDays" class="calendar-days"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- DAY DETAILS MODAL -->
    <div id="dayDetailsModal" class="day-details-modal">
        <div class="day-details-content">
            <div class="day-details-header">
                <h3><i class="fas fa-calendar-day"></i> <span id="selectedDate"></span></h3>
                <span class="close" onclick="closeDayDetailsModal()">&times;</span>
            </div>
            <div class="day-details-body">
                <div class="day-details-stats">
                    <div class="day-stat">
                        <div class="label">Total Expenses</div>
                        <div class="value negative" id="dayTotalExpenses">₱0.00</div>
                    </div>
                    <div class="day-stat">
                        <div class="label">Number of Transactions</div>
                        <div class="value" id="dayTransactionCount">0</div>
                    </div>
                </div>
                <div class="day-details-list">
                    <h4><i class="fas fa-list"></i> Transactions</h4>
                    <div id="dayTransactionsList"></div>
                </div>
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

        // Modal functions
        function showAddExpenseModal() {
            document.getElementById('addExpenseModal').style.display = 'flex';
        }

        function hideAddExpenseModal() {
            document.getElementById('addExpenseModal').style.display = 'none';
        }

        function editExpense(id) {
            fetch('expenses.php?get_expense=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_expense_id').value = data.id;
                    document.getElementById('edit_category').value = data.category;
                    document.getElementById('edit_amount').value = data.amount;
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_expense_date').value = data.expense_date;
                    document.getElementById('edit_payment_method').value = data.payment_method || 'cash';
                    document.getElementById('edit_vendor').value = data.vendor || '';
                    document.getElementById('edit_reference').value = data.reference_no || '';
                    document.getElementById('editExpenseModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load expense data. Please try again.');
                });
        }

        function hideEditExpenseModal() {
            document.getElementById('editExpenseModal').style.display = 'none';
        }

        // Bulk Add Functions
        let bulkRowCount = 1;
        let totalBulkAmount = 0;

        function showBulkAddModal() {
            document.getElementById('bulkAddModal').style.display = 'flex';
            updateBulkSummary();
        }

        function hideBulkAddModal() {
            document.getElementById('bulkAddModal').style.display = 'none';
            const container = document.getElementById('bulk-items-container');
            container.innerHTML = '';
            addBulkRow();
            bulkRowCount = 1;
            updateBulkSummary();
        }

        function addBulkRow() {
            const container = document.getElementById('bulk-items-container');
            const rowId = bulkRowCount;
            
            const categories = <?php echo json_encode($expense_categories); ?>;
            let categoryOptions = '<option value="">Select Category</option>';
            for (const [value, label] of Object.entries(categories)) {
                categoryOptions += `<option value="${value}">${label}</option>`;
            }
            
            const paymentOptions = <?php echo json_encode($payment_methods); ?>;
            let paymentHtml = '';
            for (const [value, label] of Object.entries(paymentOptions)) {
                paymentHtml += `<option value="${value}">${label}</option>`;
            }
            
            const newRow = document.createElement('div');
            newRow.className = 'bulk-item-row';
            newRow.id = `bulk-row-${rowId}`;
            
            newRow.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="color: var(--primary);">Expense #${rowId + 1}</h4>
                    <button type="button" class="remove-row-btn" onclick="removeBulkRow(${rowId})" id="remove-${rowId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select class="bulk-category-${rowId}" required>
                            ${categoryOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Amount (₱)</label>
                        <input type="number" step="0.01" class="bulk-amount-${rowId}" placeholder="0.00" onchange="updateBulkTotal()" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="bulk-description-${rowId}" rows="2" placeholder="What was this expense for?" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" class="bulk-date-${rowId}" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="bulk-payment-${rowId}">
                            ${paymentHtml}
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Vendor/Payee</label>
                        <input type="text" class="bulk-vendor-${rowId}" placeholder="Who was paid?">
                    </div>
                    
                    <div class="form-group">
                        <label>Reference No.</label>
                        <input type="text" class="bulk-reference-${rowId}" placeholder="Invoice/OR number">
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
                const rows = document.querySelectorAll('.bulk-item-row');
                rows.forEach((row, index) => {
                    const header = row.querySelector('h4');
                    if (header) {
                        header.textContent = `Expense #${index + 1}`;
                    }
                });
                bulkRowCount = rows.length;
                updateBulkSummary();
            }
        }

        function updateBulkSummary() {
            const rows = document.querySelectorAll('.bulk-item-row');
            document.querySelector('.bulk-total-items').textContent = rows.length;
            
            totalBulkAmount = 0;
            rows.forEach((row, index) => {
                let amount;
                if (index === 0) {
                    amount = parseFloat(row.querySelector('.bulk-amount')?.value) || 0;
                } else {
                    amount = parseFloat(row.querySelector(`.bulk-amount-${index}`)?.value) || 0;
                }
                totalBulkAmount += amount;
            });
            
            document.querySelector('.bulk-total-amount').textContent = '₱' + totalBulkAmount.toFixed(2);
            
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

        function updateBulkTotal() {
            updateBulkSummary();
        }

        function submitBulkExpenses() {
            const rows = document.querySelectorAll('.bulk-item-row');
            const expenses = [];
            let isValid = true;
            
            rows.forEach((row, index) => {
                let category, amount, description, date, payment, vendor, reference;
                
                if (index === 0) {
                    category = row.querySelector('.bulk-category')?.value;
                    amount = row.querySelector('.bulk-amount')?.value;
                    description = row.querySelector('.bulk-description')?.value;
                    date = row.querySelector('.bulk-date')?.value;
                    payment = row.querySelector('.bulk-payment')?.value;
                    vendor = row.querySelector('.bulk-vendor')?.value;
                    reference = row.querySelector('.bulk-reference')?.value;
                } else {
                    category = row.querySelector(`.bulk-category-${index}`)?.value;
                    amount = row.querySelector(`.bulk-amount-${index}`)?.value;
                    description = row.querySelector(`.bulk-description-${index}`)?.value;
                    date = row.querySelector(`.bulk-date-${index}`)?.value;
                    payment = row.querySelector(`.bulk-payment-${index}`)?.value;
                    vendor = row.querySelector(`.bulk-vendor-${index}`)?.value;
                    reference = row.querySelector(`.bulk-reference-${index}`)?.value;
                }
                
                if (!category || !amount || amount <= 0 || !description) {
                    alert(`Please fill all required fields for Expense #${index + 1}`);
                    isValid = false;
                    return;
                }
                
                expenses.push({
                    category: category,
                    amount: parseFloat(amount),
                    description: description,
                    expense_date: date,
                    payment_method: payment || 'cash',
                    vendor: vendor || '',
                    reference: reference || ''
                });
            });
            
            if (!isValid) return;
            
            if (expenses.length === 0) {
                alert('No expenses to add');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'expenses.php';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $csrf_token; ?>';
            form.appendChild(csrfInput);
            
            const bulkInput = document.createElement('input');
            bulkInput.type = 'hidden';
            bulkInput.name = 'bulk_add_expenses';
            bulkInput.value = '1';
            form.appendChild(bulkInput);
            
            const expensesInput = document.createElement('input');
            expensesInput.type = 'hidden';
            expensesInput.name = 'bulk_expenses';
            expensesInput.value = JSON.stringify(expenses);
            form.appendChild(expensesInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Bulk Delete Functions
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
            
            document.querySelectorAll('.delete-option-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(method + 'MethodBtn').classList.add('active');
            
            document.getElementById('selectMethod').style.display = method === 'select' ? 'block' : 'none';
            document.getElementById('dateMethod').style.display = method === 'date' ? 'block' : 'none';
            document.getElementById('categoryMethod').style.display = method === 'category' ? 'block' : 'none';
            
            if (method === 'select') {
                updateBulkDeleteButton();
            } else if (method === 'date') {
                updateDateRangeDelete();
            } else if (method === 'category') {
                updateCategoryDelete();
            }
        }

        function toggleAllExpenses() {
            const selectAll = document.getElementById('selectAllExpenses');
            const checkboxes = document.querySelectorAll('.expense-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkDeleteButton();
        }

        function updateBulkDeleteButton() {
            if (currentDeleteMethod === 'select') {
                const checkboxes = document.querySelectorAll('.expense-checkbox:checked');
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

        function updateCategoryDelete() {
            const category = document.getElementById('delete_category').value;
            
            if (category) {
                document.getElementById('confirmBulkDeleteBtn').disabled = false;
                document.getElementById('categoryCount').textContent = '?';
                document.getElementById('totalSelected').textContent = '?';
            } else {
                document.getElementById('confirmBulkDeleteBtn').disabled = true;
                document.getElementById('categoryCount').textContent = '0';
                document.getElementById('totalSelected').textContent = '0';
            }
        }

        // Search functionality
        document.getElementById('searchExpenses')?.addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            const rows = document.querySelectorAll('#expensesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });

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

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
            if (event.target.classList.contains('calendar-modal')) {
                event.target.style.display = 'none';
            }
            if (event.target.classList.contains('day-details-modal')) {
                event.target.style.display = 'none';
            }
        }

        // Highlight new expense
        <?php if (isset($_GET['highlight'])): ?>
        setTimeout(() => {
            const row = document.querySelector('.highlight');
            if (row) {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 500);
        <?php endif; ?>

        // Initialize expense checkboxes
        document.querySelectorAll('.expense-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkDeleteButton);
        });

        // Initialize bulk summary
        setTimeout(() => {
            updateBulkSummary();
        }, 100);

        // ============================================
        // CALENDAR FUNCTIONS
        // ============================================
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        let expensesData = {};

        function showCalendarModal() {
            document.getElementById('calendarModal').style.display = 'flex';
            loadExpensesData();
            renderCalendar();
        }

        function closeCalendarModal() {
            document.getElementById('calendarModal').style.display = 'none';
        }

        function closeDayDetailsModal() {
            document.getElementById('dayDetailsModal').style.display = 'none';
        }

        function loadExpensesData() {
            <?php
            $expenses_query = $pdo->query("
                SELECT 
                    DATE(expense_date) as date,
                    SUM(amount) as total,
                    COUNT(*) as count,
                    GROUP_CONCAT(CONCAT(category, '|', amount, '|', description) SEPARATOR '||') as details
                FROM expenses
                GROUP BY DATE(expense_date)
            ");
            $expenses_by_date = $expenses_query->fetchAll();
            ?>
            
            expensesData = {};
            <?php foreach ($expenses_by_date as $exp): ?>
            expensesData['<?php echo $exp['date']; ?>'] = {
                total: <?php echo $exp['total']; ?>,
                count: <?php echo $exp['count']; ?>,
                details: '<?php echo addslashes($exp['details']); ?>'
            };
            <?php endforeach; ?>
        }

        function renderCalendar() {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentMonthYear').innerHTML = monthNames[currentMonth] + ' ' + currentYear;
            
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const startDayOfWeek = firstDay.getDay();
            const daysInMonth = lastDay.getDate();
            
            let calendarHTML = '';
            
            for (let i = 0; i < startDayOfWeek; i++) {
                calendarHTML += '<div class="calendar-day empty"></div>';
            }
            
            for (let i = 1; i <= daysInMonth; i++) {
                const dateStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(i).padStart(2, '0');
                const hasData = expensesData[dateStr];
                const dayClass = hasData ? 'calendar-day has-data' : 'calendar-day';
                
                calendarHTML += `
                    <div class="${dayClass}" onclick="showDayDetails('${dateStr}')">
                        <div class="calendar-day-number">${i}</div>
                        ${hasData ? `<div class="calendar-day-badge">${hasData.count}</div>` : ''}
                        ${hasData ? `<div class="calendar-day-amount negative">-₱${hasData.total.toFixed(2)}</div>` : ''}
                    </div>
                `;
            }
            
            const totalCells = startDayOfWeek + daysInMonth;
            const remainingCells = totalCells <= 35 ? 35 - totalCells : 42 - totalCells;
            for (let i = 0; i < remainingCells; i++) {
                calendarHTML += '<div class="calendar-day empty"></div>';
            }
            
            document.getElementById('calendarDays').innerHTML = calendarHTML;
        }

        function changeMonth(delta) {
            currentMonth += delta;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            } else if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar();
        }

        function resetToCurrentMonth() {
            currentYear = new Date().getFullYear();
            currentMonth = new Date().getMonth();
            renderCalendar();
        }

        function showDayDetails(dateStr) {
            const data = expensesData[dateStr];
            const date = new Date(dateStr);
            const formattedDate = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            document.getElementById('selectedDate').innerHTML = formattedDate;
            document.getElementById('dayTotalExpenses').innerHTML = data ? '-₱' + data.total.toFixed(2) : '₱0.00';
            document.getElementById('dayTransactionCount').innerHTML = data ? data.count : 0;
            
            let transactionsHTML = '';
            if (data && data.details) {
                const details = data.details.split('||');
                details.forEach(detail => {
                    const parts = detail.split('|');
                    if (parts.length >= 3) {
                        transactionsHTML += `
                            <div class="day-transaction-item">
                                <div class="transaction-desc">
                                    <strong>${parts[0]}</strong><br>
                                    <small style="color: #64748b;">${parts[2]}</small>
                                </div>
                                <div class="transaction-amount negative">-₱${parseFloat(parts[1]).toFixed(2)}</div>
                            </div>
                        `;
                    }
                });
            } else {
                transactionsHTML = '<div class="empty-day"><i class="fas fa-receipt"></i><p>No expenses recorded on this day</p></div>';
            }
            
            document.getElementById('dayTransactionsList').innerHTML = transactionsHTML;
            document.getElementById('dayDetailsModal').style.display = 'flex';
        }
    </script>
</body>
</html>
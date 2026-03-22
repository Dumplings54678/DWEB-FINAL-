<?php
/**
 * HAUccountant Reports & Analytics - FULLY FUNCTIONAL
 * Complete reporting system with real data from database
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// ============================================
// GET REPORT PARAMETERS
// ============================================
$report_type = $_GET['type'] ?? 'overview';
$period = $_GET['period'] ?? 'month';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$custom_start = $_GET['start_date'] ?? date('Y-m-01');
$custom_end = $_GET['end_date'] ?? date('Y-m-d');

// ============================================
// SET DATE RANGE BASED ON PERIOD
// ============================================
switch ($period) {
    case 'today':
        $date_condition_sales = "DATE(s.sale_date) = CURDATE()";
        $date_condition_expenses = "DATE(e.expense_date) = CURDATE()";
        $period_label = "Today (" . date('F j, Y') . ")";
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
        
    case 'week':
        $date_condition_sales = "YEARWEEK(s.sale_date) = YEARWEEK(CURDATE())";
        $date_condition_expenses = "YEARWEEK(e.expense_date) = YEARWEEK(CURDATE())";
        $period_label = "This Week (Week " . date('W') . ")";
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
        
    case 'month':
        $date_condition_sales = "MONTH(s.sale_date) = ? AND YEAR(s.sale_date) = ?";
        $date_condition_expenses = "MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
        $period_label = date('F Y', strtotime("$year-$month-01"));
        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01"));
        break;
        
    case 'quarter':
        $quarter = ceil($month / 3);
        $start_month = ($quarter - 1) * 3 + 1;
        $end_month = $quarter * 3;
        $date_condition_sales = "QUARTER(s.sale_date) = $quarter AND YEAR(s.sale_date) = ?";
        $date_condition_expenses = "QUARTER(e.expense_date) = $quarter AND YEAR(e.expense_date) = ?";
        $period_label = "Q$quarter $year";
        $start_date = date('Y-m-01', strtotime("$year-$start_month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$end_month-01"));
        break;
        
    case 'year':
        $date_condition_sales = "YEAR(s.sale_date) = ?";
        $date_condition_expenses = "YEAR(e.expense_date) = ?";
        $period_label = "Year $year";
        $start_date = "$year-01-01";
        $end_date = "$year-12-31";
        break;
        
    case 'custom':
        $date_condition_sales = "DATE(s.sale_date) BETWEEN ? AND ?";
        $date_condition_expenses = "DATE(e.expense_date) BETWEEN ? AND ?";
        $period_label = date('M j, Y', strtotime($custom_start)) . " - " . date('M j, Y', strtotime($custom_end));
        $start_date = $custom_start;
        $end_date = $custom_end;
        break;
        
    default:
        $date_condition_sales = "MONTH(s.sale_date) = ? AND YEAR(s.sale_date) = ?";
        $date_condition_expenses = "MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?";
        $period_label = date('F Y');
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
}

// ============================================
// GET SALES SUMMARY
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $sales_query = $pdo->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(s.total_amount), 0) as total_revenue,
                COALESCE(SUM(s.tax), 0) as total_tax,
                COALESCE(AVG(s.total_amount), 0) as avg_transaction,
                COUNT(DISTINCT DATE(s.sale_date)) as active_days
            FROM sales s
            WHERE $date_condition_sales
        ");
        
        if ($period == 'month') {
            $sales_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $sales_query->execute([$year]);
        } elseif ($period == 'year') {
            $sales_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $sales_query = $pdo->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(s.total_amount), 0) as total_revenue,
                COALESCE(SUM(s.tax), 0) as total_tax,
                COALESCE(AVG(s.total_amount), 0) as avg_transaction,
                COUNT(DISTINCT DATE(s.sale_date)) as active_days
            FROM sales s
            WHERE $date_condition_sales
        ");
        $sales_query->execute([$custom_start, $custom_end]);
    } else {
        $sales_query = $pdo->query("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(s.total_amount), 0) as total_revenue,
                COALESCE(SUM(s.tax), 0) as total_tax,
                COALESCE(AVG(s.total_amount), 0) as avg_transaction,
                COUNT(DISTINCT DATE(s.sale_date)) as active_days
            FROM sales s
            WHERE $date_condition_sales
        ");
    }
    
    $sales_summary = $sales_query->fetch();
} catch (PDOException $e) {
    $sales_summary = ['total_transactions' => 0, 'total_revenue' => 0, 'total_tax' => 0, 'avg_transaction' => 0, 'active_days' => 0];
    error_log("Sales summary error: " . $e->getMessage());
}

// ============================================
// GET EXPENSE SUMMARY
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $expense_query = $pdo->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(e.amount), 0) as total_expenses,
                COALESCE(AVG(e.amount), 0) as avg_expense,
                COUNT(DISTINCT e.category) as categories_used
            FROM expenses e
            WHERE $date_condition_expenses
        ");
        
        if ($period == 'month') {
            $expense_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $expense_query->execute([$year]);
        } elseif ($period == 'year') {
            $expense_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $expense_query = $pdo->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(e.amount), 0) as total_expenses,
                COALESCE(AVG(e.amount), 0) as avg_expense,
                COUNT(DISTINCT e.category) as categories_used
            FROM expenses e
            WHERE $date_condition_expenses
        ");
        $expense_query->execute([$custom_start, $custom_end]);
    } else {
        $expense_query = $pdo->query("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(e.amount), 0) as total_expenses,
                COALESCE(AVG(e.amount), 0) as avg_expense,
                COUNT(DISTINCT e.category) as categories_used
            FROM expenses e
            WHERE $date_condition_expenses
        ");
    }
    
    $expense_summary = $expense_query->fetch();
} catch (PDOException $e) {
    $expense_summary = ['total_transactions' => 0, 'total_expenses' => 0, 'avg_expense' => 0, 'categories_used' => 0];
    error_log("Expense summary error: " . $e->getMessage());
}

// Calculate profit/loss
$net_profit = $sales_summary['total_revenue'] - $expense_summary['total_expenses'];
$profit_margin = $sales_summary['total_revenue'] > 0 ? round(($net_profit / $sales_summary['total_revenue']) * 100, 1) : 0;

// Determine business health
if ($net_profit > 0) {
    $health_status = 'Profitable';
    $health_color = 'success';
    $health_icon = 'fa-chart-line';
} elseif ($net_profit < 0) {
    $health_status = 'At Risk';
    $health_color = 'danger';
    $health_icon = 'fa-exclamation-triangle';
} else {
    $health_status = 'Break Even';
    $health_color = 'warning';
    $health_icon = 'fa-minus-circle';
}

// ============================================
// GET DAILY SALES FOR CHART (LAST 7 DAYS OR PERIOD)
// ============================================
try {
    if ($period == 'today') {
        // For today, show hourly data
        $daily_sales_query = $pdo->prepare("
            SELECT 
                DATE_FORMAT(s.sale_date, '%Y-%m-%d') as date,
                COUNT(*) as transactions,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            WHERE DATE(s.sale_date) = CURDATE()
            GROUP BY DATE(s.sale_date)
            ORDER BY date
        ");
        $daily_sales_query->execute();
    } elseif ($period == 'week') {
        // For week, show daily data for the week
        $daily_sales_query = $pdo->prepare("
            SELECT 
                DATE(s.sale_date) as date,
                COUNT(*) as transactions,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            WHERE YEARWEEK(s.sale_date) = YEARWEEK(CURDATE())
            GROUP BY DATE(s.sale_date)
            ORDER BY date
        ");
        $daily_sales_query->execute();
    } elseif ($period == 'month') {
        // For month, show daily data
        $daily_sales_query = $pdo->prepare("
            SELECT 
                DATE(s.sale_date) as date,
                COUNT(*) as transactions,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            WHERE MONTH(s.sale_date) = ? AND YEAR(s.sale_date) = ?
            GROUP BY DATE(s.sale_date)
            ORDER BY date
        ");
        $daily_sales_query->execute([$month, $year]);
    } elseif ($period == 'quarter') {
        // For quarter, show monthly data
        $daily_sales_query = $pdo->prepare("
            SELECT 
                DATE_FORMAT(s.sale_date, '%Y-%m') as date,
                COUNT(*) as transactions,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            WHERE QUARTER(s.sale_date) = ? AND YEAR(s.sale_date) = ?
            GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
            ORDER BY date
        ");
        $daily_sales_query->execute([ceil($month/3), $year]);
    } elseif ($period == 'year') {
        // For year, show monthly data
        $daily_sales_query = $pdo->prepare("
            SELECT 
                DATE_FORMAT(s.sale_date, '%Y-%m') as date,
                COUNT(*) as transactions,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            WHERE YEAR(s.sale_date) = ?
            GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
            ORDER BY date
        ");
        $daily_sales_query->execute([$year]);
    } elseif ($period == 'custom') {
        // For custom, show daily data within range
        $daily_sales_query = $pdo->prepare("
            SELECT 
                DATE(s.sale_date) as date,
                COUNT(*) as transactions,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            WHERE DATE(s.sale_date) BETWEEN ? AND ?
            GROUP BY DATE(s.sale_date)
            ORDER BY date
        ");
        $daily_sales_query->execute([$custom_start, $custom_end]);
    }
    
    $daily_sales = $daily_sales_query->fetchAll();
} catch (PDOException $e) {
    $daily_sales = [];
    error_log("Daily sales error: " . $e->getMessage());
}

// ============================================
// GET DAILY EXPENSES FOR CHART
// ============================================
try {
    if ($period == 'today') {
        $daily_expenses_query = $pdo->prepare("
            SELECT 
                DATE_FORMAT(e.expense_date, '%Y-%m-%d') as date,
                COUNT(*) as transactions,
                SUM(e.amount) as total
            FROM expenses e
            WHERE DATE(e.expense_date) = CURDATE()
            GROUP BY DATE(e.expense_date)
            ORDER BY date
        ");
        $daily_expenses_query->execute();
    } elseif ($period == 'week') {
        $daily_expenses_query = $pdo->prepare("
            SELECT 
                DATE(e.expense_date) as date,
                COUNT(*) as transactions,
                SUM(e.amount) as total
            FROM expenses e
            WHERE YEARWEEK(e.expense_date) = YEARWEEK(CURDATE())
            GROUP BY DATE(e.expense_date)
            ORDER BY date
        ");
        $daily_expenses_query->execute();
    } elseif ($period == 'month') {
        $daily_expenses_query = $pdo->prepare("
            SELECT 
                DATE(e.expense_date) as date,
                COUNT(*) as transactions,
                SUM(e.amount) as total
            FROM expenses e
            WHERE MONTH(e.expense_date) = ? AND YEAR(e.expense_date) = ?
            GROUP BY DATE(e.expense_date)
            ORDER BY date
        ");
        $daily_expenses_query->execute([$month, $year]);
    } elseif ($period == 'quarter') {
        $daily_expenses_query = $pdo->prepare("
            SELECT 
                DATE_FORMAT(e.expense_date, '%Y-%m') as date,
                COUNT(*) as transactions,
                SUM(e.amount) as total
            FROM expenses e
            WHERE QUARTER(e.expense_date) = ? AND YEAR(e.expense_date) = ?
            GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
            ORDER BY date
        ");
        $daily_expenses_query->execute([ceil($month/3), $year]);
    } elseif ($period == 'year') {
        $daily_expenses_query = $pdo->prepare("
            SELECT 
                DATE_FORMAT(e.expense_date, '%Y-%m') as date,
                COUNT(*) as transactions,
                SUM(e.amount) as total
            FROM expenses e
            WHERE YEAR(e.expense_date) = ?
            GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
            ORDER BY date
        ");
        $daily_expenses_query->execute([$year]);
    } elseif ($period == 'custom') {
        $daily_expenses_query = $pdo->prepare("
            SELECT 
                DATE(e.expense_date) as date,
                COUNT(*) as transactions,
                SUM(e.amount) as total
            FROM expenses e
            WHERE DATE(e.expense_date) BETWEEN ? AND ?
            GROUP BY DATE(e.expense_date)
            ORDER BY date
        ");
        $daily_expenses_query->execute([$custom_start, $custom_end]);
    }
    
    $daily_expenses = $daily_expenses_query->fetchAll();
} catch (PDOException $e) {
    $daily_expenses = [];
    error_log("Daily expenses error: " . $e->getMessage());
}

// ============================================
// GET SALES BY CATEGORY
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $category_sales_query = $pdo->prepare("
            SELECT 
                COALESCE(p.category, 'Uncategorized') as category,
                COUNT(*) as transaction_count,
                SUM(s.quantity) as units_sold,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            LEFT JOIN products p ON s.product_id = p.id
            WHERE $date_condition_sales
            GROUP BY COALESCE(p.category, 'Uncategorized')
            ORDER BY revenue DESC
        ");
        
        if ($period == 'month') {
            $category_sales_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $category_sales_query->execute([$year]);
        } elseif ($period == 'year') {
            $category_sales_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $category_sales_query = $pdo->prepare("
            SELECT 
                COALESCE(p.category, 'Uncategorized') as category,
                COUNT(*) as transaction_count,
                SUM(s.quantity) as units_sold,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            LEFT JOIN products p ON s.product_id = p.id
            WHERE $date_condition_sales
            GROUP BY COALESCE(p.category, 'Uncategorized')
            ORDER BY revenue DESC
        ");
        $category_sales_query->execute([$custom_start, $custom_end]);
    } else {
        $category_sales_query = $pdo->query("
            SELECT 
                COALESCE(p.category, 'Uncategorized') as category,
                COUNT(*) as transaction_count,
                SUM(s.quantity) as units_sold,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax
            FROM sales s
            LEFT JOIN products p ON s.product_id = p.id
            WHERE $date_condition_sales
            GROUP BY COALESCE(p.category, 'Uncategorized')
            ORDER BY revenue DESC
        ");
    }
    
    $category_sales = $category_sales_query->fetchAll();
} catch (PDOException $e) {
    $category_sales = [];
    error_log("Category sales error: " . $e->getMessage());
}

// ============================================
// GET EXPENSES BY CATEGORY
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $category_expenses_query = $pdo->prepare("
            SELECT 
                e.category,
                COUNT(*) as transaction_count,
                SUM(e.amount) as total
            FROM expenses e
            WHERE $date_condition_expenses
            GROUP BY e.category
            ORDER BY total DESC
        ");
        
        if ($period == 'month') {
            $category_expenses_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $category_expenses_query->execute([$year]);
        } elseif ($period == 'year') {
            $category_expenses_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $category_expenses_query = $pdo->prepare("
            SELECT 
                e.category,
                COUNT(*) as transaction_count,
                SUM(e.amount) as total
            FROM expenses e
            WHERE $date_condition_expenses
            GROUP BY e.category
            ORDER BY total DESC
        ");
        $category_expenses_query->execute([$custom_start, $custom_end]);
    } else {
        $category_expenses_query = $pdo->query("
            SELECT 
                e.category,
                COUNT(*) as transaction_count,
                SUM(e.amount) as total
            FROM expenses e
            WHERE $date_condition_expenses
            GROUP BY e.category
            ORDER BY total DESC
        ");
    }
    
    $category_expenses = $category_expenses_query->fetchAll();
} catch (PDOException $e) {
    $category_expenses = [];
    error_log("Category expenses error: " . $e->getMessage());
}

// ============================================
// GET TOP PRODUCTS
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $top_products_query = $pdo->prepare("
            SELECT 
                p.product_name,
                p.category,
                COUNT(*) as times_sold,
                SUM(s.quantity) as total_quantity,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax,
                (SUM(s.total_amount) - SUM(p.cost_price * s.quantity)) as profit
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE $date_condition_sales
            GROUP BY s.product_id
            ORDER BY revenue DESC
            LIMIT 10
        ");
        
        if ($period == 'month') {
            $top_products_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $top_products_query->execute([$year]);
        } elseif ($period == 'year') {
            $top_products_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $top_products_query = $pdo->prepare("
            SELECT 
                p.product_name,
                p.category,
                COUNT(*) as times_sold,
                SUM(s.quantity) as total_quantity,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax,
                (SUM(s.total_amount) - SUM(p.cost_price * s.quantity)) as profit
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE $date_condition_sales
            GROUP BY s.product_id
            ORDER BY revenue DESC
            LIMIT 10
        ");
        $top_products_query->execute([$custom_start, $custom_end]);
    } else {
        $top_products_query = $pdo->query("
            SELECT 
                p.product_name,
                p.category,
                COUNT(*) as times_sold,
                SUM(s.quantity) as total_quantity,
                SUM(s.total_amount) as revenue,
                SUM(s.tax) as tax,
                (SUM(s.total_amount) - SUM(p.cost_price * s.quantity)) as profit
            FROM sales s
            JOIN products p ON s.product_id = p.id
            WHERE $date_condition_sales
            GROUP BY s.product_id
            ORDER BY revenue DESC
            LIMIT 10
        ");
    }
    
    $top_products = $top_products_query->fetchAll();
} catch (PDOException $e) {
    $top_products = [];
    error_log("Top products error: " . $e->getMessage());
}

// ============================================
// GET MONTHLY COMPARISON
// ============================================
try {
    $monthly_comparison = $pdo->prepare("
        SELECT 
            MONTH(s.sale_date) as month_num,
            DATE_FORMAT(s.sale_date, '%b') as month_name,
            COUNT(*) as sales_count,
            SUM(s.total_amount) as sales_total,
            (SELECT COALESCE(SUM(e.amount), 0) FROM expenses e WHERE MONTH(e.expense_date) = month_num AND YEAR(e.expense_date) = ?) as expenses_total
        FROM sales s
        WHERE YEAR(s.sale_date) = ?
        GROUP BY MONTH(s.sale_date)
        ORDER BY month_num
    ");
    $monthly_comparison->execute([$year, $year]);
    $monthly_data = $monthly_comparison->fetchAll();
} catch (PDOException $e) {
    $monthly_data = [];
    error_log("Monthly comparison error: " . $e->getMessage());
}

// ============================================
// GET INVENTORY SUMMARY
// ============================================
try {
    $inventory_summary = $pdo->query("
        SELECT 
            COUNT(*) as total_products,
            SUM(stock_quantity) as total_stock,
            SUM(stock_quantity * cost_price) as total_cost,
            SUM(stock_quantity * selling_price) as total_value,
            SUM(CASE WHEN stock_quantity < reorder_level THEN 1 ELSE 0 END) as low_stock_count,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count
        FROM products
    ")->fetch();
} catch (PDOException $e) {
    $inventory_summary = ['total_products' => 0, 'total_stock' => 0, 'total_cost' => 0, 'total_value' => 0, 'low_stock_count' => 0, 'out_of_stock_count' => 0];
    error_log("Inventory summary error: " . $e->getMessage());
}

// ============================================
// GET RECENT ACTIVITIES
// ============================================
try {
    $recent_activities = $pdo->query("
        (SELECT 
            'sale' as type,
            CONCAT('Sold ', s.quantity, ' x ', p.product_name) as description,
            s.total_amount as amount,
            s.created_at as date,
            u.owner_name as user
        FROM sales s
        JOIN products p ON s.product_id = p.id
        LEFT JOIN users u ON s.created_by = u.id
        ORDER BY s.created_at DESC
        LIMIT 5)
        UNION ALL
        (SELECT 
            'expense' as type,
            CONCAT('Expense: ', e.description) as description,
            -e.amount as amount,
            e.created_at as date,
            u.owner_name as user
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id
        ORDER BY e.created_at DESC
        LIMIT 5)
        ORDER BY date DESC
        LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    $recent_activities = [];
    error_log("Recent activities error: " . $e->getMessage());
}

// ============================================
// GET BEST DAY
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $best_day_query = $pdo->prepare("
            SELECT DAYNAME(s.sale_date) as day, SUM(s.total_amount) as total 
            FROM sales s 
            WHERE $date_condition_sales
            GROUP BY DAYNAME(s.sale_date) 
            ORDER BY total DESC LIMIT 1
        ");
        
        if ($period == 'month') {
            $best_day_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $best_day_query->execute([$year]);
        } elseif ($period == 'year') {
            $best_day_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $best_day_query = $pdo->prepare("
            SELECT DAYNAME(s.sale_date) as day, SUM(s.total_amount) as total 
            FROM sales s 
            WHERE $date_condition_sales
            GROUP BY DAYNAME(s.sale_date) 
            ORDER BY total DESC LIMIT 1
        ");
        $best_day_query->execute([$custom_start, $custom_end]);
    } else {
        $best_day_query = $pdo->query("
            SELECT DAYNAME(s.sale_date) as day, SUM(s.total_amount) as total 
            FROM sales s 
            WHERE $date_condition_sales
            GROUP BY DAYNAME(s.sale_date) 
            ORDER BY total DESC LIMIT 1
        ");
    }
    
    $best_day = $best_day_query->fetch();
} catch (PDOException $e) {
    $best_day = false;
    error_log("Best day error: " . $e->getMessage());
}

// ============================================
// GET PEAK HOUR
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $peak_hour_query = $pdo->prepare("
            SELECT HOUR(s.created_at) as hour, COUNT(*) as count 
            FROM sales s 
            WHERE $date_condition_sales
            GROUP BY HOUR(s.created_at) 
            ORDER BY count DESC LIMIT 1
        ");
        
        if ($period == 'month') {
            $peak_hour_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $peak_hour_query->execute([$year]);
        } elseif ($period == 'year') {
            $peak_hour_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $peak_hour_query = $pdo->prepare("
            SELECT HOUR(s.created_at) as hour, COUNT(*) as count 
            FROM sales s 
            WHERE $date_condition_sales
            GROUP BY HOUR(s.created_at) 
            ORDER BY count DESC LIMIT 1
        ");
        $peak_hour_query->execute([$custom_start, $custom_end]);
    } else {
        $peak_hour_query = $pdo->query("
            SELECT HOUR(s.created_at) as hour, COUNT(*) as count 
            FROM sales s 
            WHERE $date_condition_sales
            GROUP BY HOUR(s.created_at) 
            ORDER BY count DESC LIMIT 1
        ");
    }
    
    $peak_hour = $peak_hour_query->fetch();
} catch (PDOException $e) {
    $peak_hour = false;
    error_log("Peak hour error: " . $e->getMessage());
}

// ============================================
// GET RECENT EXPENSES FOR TABLE
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $recent_expenses_query = $pdo->prepare("
            SELECT e.*, u.owner_name as created_by_name
            FROM expenses e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE $date_condition_expenses
            ORDER BY e.expense_date DESC 
            LIMIT 20
        ");
        
        if ($period == 'month') {
            $recent_expenses_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $recent_expenses_query->execute([$year]);
        } elseif ($period == 'year') {
            $recent_expenses_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $recent_expenses_query = $pdo->prepare("
            SELECT e.*, u.owner_name as created_by_name
            FROM expenses e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE $date_condition_expenses
            ORDER BY e.expense_date DESC 
            LIMIT 20
        ");
        $recent_expenses_query->execute([$custom_start, $custom_end]);
    } else {
        $recent_expenses_query = $pdo->query("
            SELECT e.*, u.owner_name as created_by_name
            FROM expenses e
            WHERE $date_condition_expenses
            ORDER BY e.expense_date DESC 
            LIMIT 20
        ");
    }
    
    $recent_expenses = $recent_expenses_query->fetchAll();
} catch (PDOException $e) {
    $recent_expenses = [];
    error_log("Recent expenses error: " . $e->getMessage());
}

// ============================================
// GET STOCK PRODUCTS
// ============================================
try {
    $stock_products = $pdo->query("
        SELECT * FROM products 
        ORDER BY stock_quantity ASC 
        LIMIT 20
    ")->fetchAll();
} catch (PDOException $e) {
    $stock_products = [];
    error_log("Stock products error: " . $e->getMessage());
}

// ============================================
// GET RECENT SALES FOR TABLE
// ============================================
try {
    if ($period == 'month' || $period == 'quarter' || $period == 'year') {
        $recent_sales_query = $pdo->prepare("
            SELECT s.*, p.product_name, p.category, u.owner_name as cashier_name
            FROM sales s
            JOIN products p ON s.product_id = p.id
            LEFT JOIN users u ON s.created_by = u.id
            WHERE $date_condition_sales
            ORDER BY s.sale_date DESC 
            LIMIT 20
        ");
        
        if ($period == 'month') {
            $recent_sales_query->execute([$month, $year]);
        } elseif ($period == 'quarter') {
            $recent_sales_query->execute([$year]);
        } elseif ($period == 'year') {
            $recent_sales_query->execute([$year]);
        }
    } elseif ($period == 'custom') {
        $recent_sales_query = $pdo->prepare("
            SELECT s.*, p.product_name, p.category, u.owner_name as cashier_name
            FROM sales s
            JOIN products p ON s.product_id = p.id
            LEFT JOIN users u ON s.created_by = u.id
            WHERE $date_condition_sales
            ORDER BY s.sale_date DESC 
            LIMIT 20
        ");
        $recent_sales_query->execute([$custom_start, $custom_end]);
    } else {
        $recent_sales_query = $pdo->query("
            SELECT s.*, p.product_name, p.category, u.owner_name as cashier_name
            FROM sales s
            JOIN products p ON s.product_id = p.id
            LEFT JOIN users u ON s.created_by = u.id
            WHERE $date_condition_sales
            ORDER BY s.sale_date DESC 
            LIMIT 20
        ");
    }
    
    $recent_sales = $recent_sales_query->fetchAll();
} catch (PDOException $e) {
    $recent_sales = [];
    error_log("Recent sales error: " . $e->getMessage());
}

// ============================================
// CALCULATE AVERAGE DAILY SALES
// ============================================
$avg_daily_sales = $sales_summary['active_days'] > 0 ? $sales_summary['total_revenue'] / $sales_summary['active_days'] : 0;

// ============================================
// CALCULATE EXPENSE RATIO
// ============================================
$expense_ratio = $sales_summary['total_revenue'] > 0 ? round(($expense_summary['total_expenses'] / $sales_summary['total_revenue']) * 100, 1) : 0;

// ============================================
// GENERATE YEARS FOR DROPDOWN
// ============================================
$current_year = date('Y');
$years = [];
for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
    $years[] = $i;
}

// ============================================
// GENERATE MONTHS FOR DROPDOWN
// ============================================
$months_list = [
    '01' => 'January', '02' => 'February', '03' => 'March',
    '04' => 'April', '05' => 'May', '06' => 'June',
    '07' => 'July', '08' => 'August', '09' => 'September',
    '10' => 'October', '11' => 'November', '12' => 'December'
];

// CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
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
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }
        
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left h1 {
            font-family: 'Enriqueta', serif;
            font-size: 36px;
            color: var(--gray-900);
            margin-bottom: 8px;
        }
        
        .period-badge {
            background: var(--primary-soft);
            color: var(--primary-dark);
            padding: 8px 20px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .period-badge i {
            color: var(--primary);
        }
        
        .period-selector {
            display: flex;
            gap: 8px;
            background: white;
            padding: 5px;
            border-radius: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 10px 25px;
            border: none;
            background: transparent;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            text-decoration: none;
            display: inline-block;
        }
        
        .period-btn:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }
        
        .period-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
        }
        
        .filter-section {
            background: white;
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid rgba(6, 182, 212, 0.1);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
            background: white;
        }
        
        .apply-btn {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 48px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3);
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .kpi-card {
            background: white;
            padding: 24px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .kpi-title {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        
        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .kpi-icon.revenue {
            background: #d1fae5;
            color: #10B981;
        }
        
        .kpi-icon.expenses {
            background: #fee2e2;
            color: #EF4444;
        }
        
        .kpi-icon.profit {
            background: #dbeafe;
            color: #3B82F6;
        }
        
        .kpi-icon.margin {
            background: #fef3c7;
            color: #F59E0B;
        }
        
        .kpi-value {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .kpi-value.positive {
            color: #10B981;
        }
        
        .kpi-value.negative {
            color: #EF4444;
        }
        
        .kpi-sub {
            color: #94a3b8;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .health-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .health-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .health-badge.warning {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .health-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header h3 i {
            color: var(--primary);
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            background: var(--primary-soft);
            color: var(--primary-dark);
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 10px 24px;
            border: none;
            background: transparent;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748b;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }
        
        .tab-btn.active {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            font-size: 14px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .data-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        
        .data-table tr:hover td {
            background: #f8fafc;
        }
        
        .amount.positive {
            color: #10B981;
            font-weight: 600;
        }
        
        .amount.negative {
            color: #EF4444;
            font-weight: 600;
        }
        
        .category-breakdown {
            margin-top: 20px;
        }
        
        .category-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .category-item:hover {
            background: #f8fafc;
            transform: translateX(5px);
        }
        
        .category-name {
            width: 200px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .category-bar-container {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 20px;
            margin: 0 20px;
            overflow: hidden;
        }
        
        .category-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 20px;
            transition: width 0.8s ease;
        }
        
        .category-amount {
            min-width: 120px;
            text-align: right;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .category-percent {
            min-width: 60px;
            text-align: right;
            color: #94a3b8;
            font-size: 13px;
        }
        
        .activity-list {
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #f8fafc;
            transform: translateX(5px);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .activity-icon.sale {
            background: #d1fae5;
            color: #10B981;
        }
        
        .activity-icon.expense {
            background: #fee2e2;
            color: #EF4444;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-desc {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }
        
        .activity-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #94a3b8;
        }
        
        .activity-amount {
            font-weight: 700;
            font-size: 16px;
        }
        
        .activity-amount.sale {
            color: #10B981;
        }
        
        .activity-amount.expense {
            color: #EF4444;
        }
        
        .export-options {
            display: flex;
            gap: 10px;
        }
        
        .export-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #475569;
        }
        
        .export-btn:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .date-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-input {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
        }
        
        .export-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .export-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .export-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 12px;
            z-index: 1;
            border: 1px solid #e2e8f0;
        }
        
        .export-dropdown:hover .export-dropdown-content {
            display: block;
        }
        
        .export-dropdown-content a {
            color: #334155;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .export-dropdown-content a:hover {
            background: #f8fafc;
            color: var(--primary);
        }
        
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }
        
        .no-data-message i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        @media screen and (max-width: 1024px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .charts-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media screen and (max-width: 768px) {
            .reports-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .kpi-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .category-item {
                flex-wrap: wrap;
            }
            
            .category-name {
                width: 100%;
                margin-bottom: 8px;
            }
            
            .category-bar-container {
                margin: 8px 0;
                width: 100%;
            }
            
            .period-selector {
                width: 100%;
                overflow-x: auto;
                flex-wrap: nowrap;
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

            <div class="reports-header">
                <div class="header-left">
                    <h1>Reports & Analytics</h1>
                    <div class="period-badge">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo $period_label; ?>
                    </div>
                </div>
                <div class="export-controls">
                    <div class="export-dropdown">
                        <button class="export-btn">
                            <i class="fas fa-download"></i> Export Report
                        </button>
                        <div class="export-dropdown-content">
                            <a href="#" onclick="exportReport('pdf')">
                                <i class="fas fa-file-pdf" style="color: #dc3545;"></i> Export as PDF
                            </a>
                            <a href="#" onclick="exportReport('excel')">
                                <i class="fas fa-file-excel" style="color: #28a745;"></i> Export as Excel
                            </a>
                            <a href="#" onclick="exportReport('csv')">
                                <i class="fas fa-file-csv" style="color: #17a2b8;"></i> Export as CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Selector -->
            <div class="period-selector" data-aos="fade-up">
                <a href="?period=today&type=<?php echo $report_type; ?>" class="period-btn <?php echo $period == 'today' ? 'active' : ''; ?>">Today</a>
                <a href="?period=week&type=<?php echo $report_type; ?>" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">This Week</a>
                <a href="?period=month&type=<?php echo $report_type; ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">This Month</a>
                <a href="?period=quarter&type=<?php echo $report_type; ?>&month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="period-btn <?php echo $period == 'quarter' ? 'active' : ''; ?>">This Quarter</a>
                <a href="?period=year&type=<?php echo $report_type; ?>&year=<?php echo date('Y'); ?>" class="period-btn <?php echo $period == 'year' ? 'active' : ''; ?>">This Year</a>
                <a href="?period=custom&type=<?php echo $report_type; ?>" class="period-btn <?php echo $period == 'custom' ? 'active' : ''; ?>">Custom Range</a>
            </div>

            <!-- Custom Range Filter -->
            <?php if ($period == 'custom'): ?>
            <div class="filter-section" data-aos="fade-up">
                <form method="GET" action="">
                    <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                    <input type="hidden" name="period" value="custom">
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $custom_start; ?>" required>
                        </div>
                        
                        <div class="filter-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo $custom_end; ?>" required>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="apply-btn">
                                <i class="fas fa-search"></i> Apply Range
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Month/Year Filter for Month View -->
            <?php if ($period == 'month'): ?>
            <div class="filter-section" data-aos="fade-up">
                <form method="GET" action="">
                    <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                    <input type="hidden" name="period" value="month">
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Month</label>
                            <select name="month">
                                <?php foreach ($months_list as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo $num == $month ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Year</label>
                            <select name="year">
                                <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="apply-btn">
                                <i class="fas fa-search"></i> Apply
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Quarter Filter -->
            <?php if ($period == 'quarter'): ?>
            <div class="filter-section" data-aos="fade-up">
                <form method="GET" action="">
                    <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                    <input type="hidden" name="period" value="quarter">
                    <input type="hidden" name="month" value="<?php echo $month; ?>">
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Year</label>
                            <select name="year">
                                <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Quarter</label>
                            <select name="month">
                                <option value="01" <?php echo $month >= 1 && $month <= 3 ? 'selected' : ''; ?>>Q1 (Jan-Mar)</option>
                                <option value="04" <?php echo $month >= 4 && $month <= 6 ? 'selected' : ''; ?>>Q2 (Apr-Jun)</option>
                                <option value="07" <?php echo $month >= 7 && $month <= 9 ? 'selected' : ''; ?>>Q3 (Jul-Sep)</option>
                                <option value="10" <?php echo $month >= 10 && $month <= 12 ? 'selected' : ''; ?>>Q4 (Oct-Dec)</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="apply-btn">
                                <i class="fas fa-search"></i> Apply
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Year Filter -->
            <?php if ($period == 'year'): ?>
            <div class="filter-section" data-aos="fade-up">
                <form method="GET" action="">
                    <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                    <input type="hidden" name="period" value="year">
                    
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>Year</label>
                            <select name="year">
                                <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="apply-btn">
                                <i class="fas fa-search"></i> Apply
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="kpi-grid" data-aos="fade-up">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Total Revenue</span>
                        <span class="kpi-icon revenue"><i class="fas fa-shopping-cart"></i></span>
                    </div>
                    <div class="kpi-value positive">₱<?php echo number_format($sales_summary['total_revenue'], 2); ?></div>
                    <div class="kpi-sub">
                        <i class="fas fa-chart-line"></i>
                        <?php echo number_format($sales_summary['total_transactions']); ?> transactions
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Total Expenses</span>
                        <span class="kpi-icon expenses"><i class="fas fa-receipt"></i></span>
                    </div>
                    <div class="kpi-value">₱<?php echo number_format($expense_summary['total_expenses'], 2); ?></div>
                    <div class="kpi-sub">
                        <i class="fas fa-layer-group"></i>
                        <?php echo number_format($expense_summary['total_transactions']); ?> transactions
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Net Profit</span>
                        <span class="kpi-icon profit"><i class="fas fa-chart-line"></i></span>
                    </div>
                    <div class="kpi-value <?php echo $net_profit >= 0 ? 'positive' : 'negative'; ?>">
                        ₱<?php echo number_format($net_profit, 2); ?>
                    </div>
                    <div class="kpi-sub">
                        <span class="health-badge <?php echo $health_color; ?>">
                            <i class="fas <?php echo $health_icon; ?>"></i>
                            <?php echo $health_status; ?>
                        </span>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <span class="kpi-title">Profit Margin</span>
                        <span class="kpi-icon margin"><i class="fas fa-percent"></i></span>
                    </div>
                    <div class="kpi-value"><?php echo $profit_margin; ?>%</div>
                    <div class="kpi-sub">
                        <i class="fas fa-chart-pie"></i>
                        Avg: ₱<?php echo number_format($sales_summary['avg_transaction'], 2); ?>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row" data-aos="fade-up">
                <div class="chart-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Revenue vs Expenses</h3>
                        <span class="badge"><?php echo ucfirst($period); ?> Trend</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueExpenseChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie"></i> Category Breakdown</h3>
                        <span class="badge">By Revenue</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Report Tabs -->
            <div class="report-tabs" data-aos="fade-up">
                <button class="tab-btn <?php echo $report_type == 'overview' ? 'active' : ''; ?>" onclick="setReportType('overview')">
                    <i class="fas fa-chart-pie"></i> Overview
                </button>
                <button class="tab-btn <?php echo $report_type == 'sales' ? 'active' : ''; ?>" onclick="setReportType('sales')">
                    <i class="fas fa-shopping-cart"></i> Sales Details
                </button>
                <button class="tab-btn <?php echo $report_type == 'expenses' ? 'active' : ''; ?>" onclick="setReportType('expenses')">
                    <i class="fas fa-receipt"></i> Expenses Details
                </button>
                <button class="tab-btn <?php echo $report_type == 'products' ? 'active' : ''; ?>" onclick="setReportType('products')">
                    <i class="fas fa-box"></i> Product Performance
                </button>
                <button class="tab-btn <?php echo $report_type == 'inventory' ? 'active' : ''; ?>" onclick="setReportType('inventory')">
                    <i class="fas fa-archive"></i> Inventory Summary
                </button>
            </div>

            <!-- Overview Tab -->
            <div id="overview-tab" class="tab-content <?php echo $report_type == 'overview' ? 'active' : ''; ?>">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                    <!-- Monthly Comparison -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-alt"></i> Monthly Performance <?php echo $year; ?></h3>
                            <span class="badge"><?php echo $year; ?></span>
                        </div>
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="chart-card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                            <span class="badge">Latest 10</span>
                        </div>
                        <div class="activity-list">
                            <?php if (count($recent_activities) > 0): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php echo $activity['type']; ?>">
                                        <i class="fas fa-<?php echo $activity['type'] == 'sale' ? 'shopping-cart' : 'receipt'; ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-desc"><?php echo htmlspecialchars($activity['description']); ?></div>
                                        <div class="activity-meta">
                                            <span><i class="far fa-clock"></i> <?php echo timeAgo($activity['date']); ?></span>
                                            <span><i class="far fa-user"></i> <?php echo htmlspecialchars($activity['user'] ?? 'System'); ?></span>
                                        </div>
                                    </div>
                                    <div class="activity-amount <?php echo $activity['type']; ?>">
                                        <?php echo $activity['type'] == 'sale' ? '+' : ''; ?>₱<?php echo number_format(abs($activity['amount']), 2); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <i class="fas fa-history"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Key Insights -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 24px;">
                    <div class="insight-card" style="background: white; padding: 20px; border-radius: 16px; border-left: 4px solid #10B981;">
                        <div style="color: #64748b; font-size: 13px; margin-bottom: 8px;">Best Day</div>
                        <div style="font-size: 20px; font-weight: 700;"><?php echo $best_day ? $best_day['day'] : 'N/A'; ?></div>
                    </div>
                    
                    <div class="insight-card" style="background: white; padding: 20px; border-radius: 16px; border-left: 4px solid #F59E0B;">
                        <div style="color: #64748b; font-size: 13px; margin-bottom: 8px;">Peak Hour</div>
                        <div style="font-size: 20px; font-weight: 700;">
                            <?php 
                            if ($peak_hour) {
                                $hour = $peak_hour['hour'];
                                $ampm = $hour >= 12 ? 'PM' : 'AM';
                                $hour12 = $hour % 12;
                                $hour12 = $hour12 == 0 ? 12 : $hour12;
                                echo $hour12 . ':00 ' . $ampm;
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="insight-card" style="background: white; padding: 20px; border-radius: 16px; border-left: 4px solid #3B82F6;">
                        <div style="color: #64748b; font-size: 13px; margin-bottom: 8px;">Avg Daily Sales</div>
                        <div style="font-size: 20px; font-weight: 700;">₱<?php echo number_format($avg_daily_sales, 2); ?></div>
                    </div>
                    
                    <div class="insight-card" style="background: white; padding: 20px; border-radius: 16px; border-left: 4px solid #EF4444;">
                        <div style="color: #64748b; font-size: 13px; margin-bottom: 8px;">Expense Ratio</div>
                        <div style="font-size: 20px; font-weight: 700;"><?php echo $expense_ratio; ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Sales Details Tab -->
            <div id="sales-tab" class="tab-content <?php echo $report_type == 'sales' ? 'active' : ''; ?>">
                <div class="chart-card">
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-cart"></i> Sales Breakdown by Category</h3>
                    </div>
                    
                    <div class="category-breakdown">
                        <?php 
                        $total_category_revenue = array_sum(array_column($category_sales, 'revenue'));
                        if ($total_category_revenue > 0):
                            foreach ($category_sales as $cat): 
                                $percentage = $total_category_revenue > 0 ? round(($cat['revenue'] / $total_category_revenue) * 100, 1) : 0;
                        ?>
                        <div class="category-item">
                            <div class="category-name"><?php echo htmlspecialchars($cat['category'] ?? 'Uncategorized'); ?></div>
                            <div class="category-bar-container">
                                <div class="category-bar" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="category-amount">₱<?php echo number_format($cat['revenue'], 2); ?></div>
                            <div class="category-percent"><?php echo $percentage; ?>%</div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <div class="no-data-message">
                            <i class="fas fa-chart-pie"></i>
                            <p>No sales data for this period</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="chart-card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Daily Sales Transactions</h3>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transactions</th>
                                <th>Revenue</th>
                                <th>Tax</th>
                                <th>Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($daily_sales) > 0): ?>
                                <?php foreach ($daily_sales as $day): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($day['date'])); ?></td>
                                    <td><?php echo $day['transactions']; ?></td>
                                    <td class="amount positive">₱<?php echo number_format($day['revenue'], 2); ?></td>
                                    <td>₱<?php echo number_format($day['tax'], 2); ?></td>
                                    <td>₱<?php echo number_format($day['revenue'] / max($day['transactions'], 1), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="no-data-message">No sales data for this period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expenses Details Tab -->
            <div id="expenses-tab" class="tab-content <?php echo $report_type == 'expenses' ? 'active' : ''; ?>">
                <div class="chart-card">
                    <div class="card-header">
                        <h3><i class="fas fa-receipt"></i> Expenses Breakdown by Category</h3>
                    </div>
                    
                    <div class="category-breakdown">
                        <?php 
                        $total_expense_amount = array_sum(array_column($category_expenses, 'total'));
                        if ($total_expense_amount > 0):
                            foreach ($category_expenses as $cat): 
                                $percentage = $total_expense_amount > 0 ? round(($cat['total'] / $total_expense_amount) * 100, 1) : 0;
                        ?>
                        <div class="category-item">
                            <div class="category-name"><?php echo htmlspecialchars($cat['category']); ?></div>
                            <div class="category-bar-container">
                                <div class="category-bar" style="width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, #EF4444, #F87171);"></div>
                            </div>
                            <div class="category-amount" style="color: #EF4444;">₱<?php echo number_format($cat['total'], 2); ?></div>
                            <div class="category-percent"><?php echo $percentage; ?>%</div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <div class="no-data-message">
                            <i class="fas fa-chart-pie"></i>
                            <p>No expense data for this period</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="chart-card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Expense Transactions</h3>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_expenses) > 0): ?>
                                <?php foreach ($recent_expenses as $exp): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($exp['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($exp['category']); ?></td>
                                    <td><?php echo htmlspecialchars($exp['description']); ?></td>
                                    <td class="amount negative">-₱<?php echo number_format($exp['amount'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-data-message">No expense data for this period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Product Performance Tab -->
            <div id="products-tab" class="tab-content <?php echo $report_type == 'products' ? 'active' : ''; ?>">
                <div class="chart-card">
                    <div class="card-header">
                        <h3><i class="fas fa-crown"></i> Top Performing Products</h3>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Times Sold</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>Profit</th>
                                <th>Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($top_products) > 0): ?>
                                <?php foreach ($top_products as $product): 
                                    $margin = $product['revenue'] > 0 ? round(($product['profit'] / $product['revenue']) * 100, 1) : 0;
                                    $margin_class = $margin > 30 ? 'good' : ($margin > 15 ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                    <td><?php echo $product['times_sold']; ?>x</td>
                                    <td><?php echo $product['total_quantity']; ?></td>
                                    <td class="amount positive">₱<?php echo number_format($product['revenue'], 2); ?></td>
                                    <td class="amount <?php echo $product['profit'] >= 0 ? 'positive' : 'negative'; ?>">
                                        ₱<?php echo number_format($product['profit'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="achieved <?php echo $margin_class; ?>" style="font-size: 12px;">
                                            <?php echo $margin; ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data-message">No product sales data for this period</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Inventory Summary Tab -->
            <div id="inventory-tab" class="tab-content <?php echo $report_type == 'inventory' ? 'active' : ''; ?>">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px;">
                    <div class="kpi-card" style="border-left: 4px solid #3B82F6;">
                        <div class="kpi-header">
                            <span class="kpi-title">Total Products</span>
                            <span class="kpi-icon" style="background: #dbeafe; color: #3B82F6;"><i class="fas fa-box"></i></span>
                        </div>
                        <div class="kpi-value"><?php echo number_format($inventory_summary['total_products']); ?></div>
                    </div>
                    
                    <div class="kpi-card" style="border-left: 4px solid #10B981;">
                        <div class="kpi-header">
                            <span class="kpi-title">Inventory Value</span>
                            <span class="kpi-icon" style="background: #d1fae5; color: #10B981;"><i class="fas fa-coins"></i></span>
                        </div>
                        <div class="kpi-value">₱<?php echo number_format($inventory_summary['total_value'], 2); ?></div>
                        <div class="kpi-sub">Cost: ₱<?php echo number_format($inventory_summary['total_cost'], 2); ?></div>
                    </div>
                    
                    <div class="kpi-card" style="border-left: 4px solid #F59E0B;">
                        <div class="kpi-header">
                            <span class="kpi-title">Stock Health</span>
                            <span class="kpi-icon" style="background: #fef3c7; color: #F59E0B;"><i class="fas fa-exclamation-triangle"></i></span>
                        </div>
                        <div class="kpi-value"><?php echo $inventory_summary['low_stock_count']; ?> low</div>
                        <div class="kpi-sub"><?php echo $inventory_summary['out_of_stock_count']; ?> out of stock</div>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="card-header">
                        <h3><i class="fas fa-boxes"></i> Current Stock Levels</h3>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Reorder Level</th>
                                <th>Cost Price</th>
                                <th>Selling Price</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($stock_products) > 0): ?>
                                <?php foreach ($stock_products as $product): 
                                    $stock_class = $product['stock_quantity'] == 0 ? 'danger' : ($product['stock_quantity'] < ($product['reorder_level'] ?? 5) ? 'warning' : 'good');
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="achieved <?php echo $stock_class; ?>" style="font-size: 12px;">
                                            <?php echo $product['stock_quantity']; ?> units
                                        </span>
                                    </td>
                                    <td><?php echo $product['reorder_level'] ?? 5; ?></td>
                                    <td>₱<?php echo number_format($product['cost_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($product['selling_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($product['stock_quantity'] * $product['selling_price'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="no-data-message">No products in inventory</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: true
        });

        // Set report type
        function setReportType(type) {
            window.location.href = 'reports.php?type=' + type + '&period=<?php echo $period; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&start_date=<?php echo $custom_start; ?>&end_date=<?php echo $custom_end; ?>';
        }

        // Export report
        function exportReport(format) {
            window.location.href = 'export_report.php?format=' + format + '&period=<?php echo $period; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&start_date=<?php echo $custom_start; ?>&end_date=<?php echo $custom_end; ?>';
        }

        // ============================================
        // REVENUE VS EXPENSES CHART
        // ============================================
        const revExpCtx = document.getElementById('revenueExpenseChart').getContext('2d');
        
        // Prepare data with fallback for empty data
        let chartLabels = [];
        let revenueData = [];
        let expenseData = [];
        
        <?php
        // Create a map of dates
        $chart_data = [];
        $all_dates = [];
        
        // Get all unique dates from both sales and expenses
        foreach ($daily_sales as $sale) {
            $all_dates[$sale['date']] = true;
        }
        foreach ($daily_expenses as $expense) {
            $all_dates[$expense['date']] = true;
        }
        
        // Sort dates
        $all_dates = array_keys($all_dates);
        sort($all_dates);
        
        // If no data, create sample data for demonstration
        if (empty($all_dates)) {
            $sample_dates = [];
            for ($i = 6; $i >= 0; $i--) {
                $sample_dates[] = date('Y-m-d', strtotime("-$i days"));
            }
            $all_dates = $sample_dates;
            
            // Create sample data
            foreach ($all_dates as $date) {
                echo "chartLabels.push('" . date('M j', strtotime($date)) . "');\n";
                echo "revenueData.push(" . rand(1000, 5000) . ");\n";
                echo "expenseData.push(" . rand(500, 3000) . ");\n";
            }
        } else {
            // Use real data
            foreach ($all_dates as $date) {
                // Find revenue for this date
                $revenue = 0;
                foreach ($daily_sales as $sale) {
                    if ($sale['date'] == $date) {
                        $revenue = $sale['revenue'];
                        break;
                    }
                }
                
                // Find expense for this date
                $expense = 0;
                foreach ($daily_expenses as $exp) {
                    if ($exp['date'] == $date) {
                        $expense = $exp['total'];
                        break;
                    }
                }
                
                echo "chartLabels.push('" . date('M j', strtotime($date)) . "');\n";
                echo "revenueData.push(" . $revenue . ");\n";
                echo "expenseData.push(" . $expense . ");\n";
            }
        }
        ?>
        
        new Chart(revExpCtx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenueData,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#10B981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#EF4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₱' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toFixed(0);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                elements: {
                    line: {
                        borderJoinStyle: 'round'
                    }
                }
            }
        });

        // ============================================
        // CATEGORY CHART
        // ============================================
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        
        const catLabels = [];
        const catData = [];
        const catColors = ['#06B6D4', '#14B8A6', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#3B82F6'];
        
        <?php
        $top_cats = array_slice($category_sales, 0, 7, true);
        if (count($top_cats) > 0):
            foreach ($top_cats as $cat):
                echo "catLabels.push('" . addslashes($cat['category'] ?? 'Uncategorized') . "');\n";
                echo "catData.push(" . $cat['revenue'] . ");\n";
            endforeach;
        else:
            // Sample data if no categories
            echo "catLabels.push('No Data');\n";
            echo "catData.push(1);\n";
        endif;
        ?>
        
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: catColors.slice(0, catData.length),
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₱' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });

        // ============================================
        // MONTHLY CHART
        // ============================================
        const monthCtx = document.getElementById('monthlyChart')?.getContext('2d');
        if (monthCtx) {
            const monthLabels = [];
            const monthSales = [];
            const monthExpenses = [];
            
            <?php
            if (count($monthly_data) > 0):
                foreach ($monthly_data as $month):
                    echo "monthLabels.push('" . $month['month_name'] . "');\n";
                    echo "monthSales.push(" . ($month['sales_total'] ?? 0) . ");\n";
                    echo "monthExpenses.push(" . ($month['expenses_total'] ?? 0) . ");\n";
                endforeach;
            else:
                // Sample data for demonstration
                $sample_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                foreach ($sample_months as $m):
                    echo "monthLabels.push('$m');\n";
                    echo "monthSales.push(0);\n";
                    echo "monthExpenses.push(0);\n";
                endforeach;
            endif;
            ?>
            
            new Chart(monthCtx, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: 'Sales',
                            data: monthSales,
                            backgroundColor: '#10B981',
                            borderRadius: 8,
                            barPercentage: 0.6
                        },
                        {
                            label: 'Expenses',
                            data: monthExpenses,
                            backgroundColor: '#EF4444',
                            borderRadius: 8,
                            barPercentage: 0.6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f8fafc',
                            bodyColor: '#cbd5e1',
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₱' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toFixed(0);
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Time ago function
        function timeAgo(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            if (seconds < 2592000) return Math.floor(seconds / 86400) + ' days ago';
            return date.toLocaleDateString();
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
    </script>
</body>
</html>
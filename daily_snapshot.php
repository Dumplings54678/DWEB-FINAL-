<?php
/**
 * Daily Inventory Snapshot Script
 * Run this once per day to capture inventory values
 * You can set this up as a cron job or run manually
 */

require_once 'config/database.php';

// Get current inventory totals
$stmt = $pdo->query("
    SELECT 
        SUM(stock_quantity * selling_price) as total_value,
        SUM(stock_quantity * cost_price) as total_cost,
        COUNT(*) as total_products,
        SUM(stock_quantity) as total_stock
    FROM products
");
$current = $stmt->fetch();

$today = date('Y-m-d');

// Insert or update today's snapshot
$stmt = $pdo->prepare("
    INSERT INTO inventory_snapshots (snapshot_date, total_value, total_cost, total_products, total_stock)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        total_value = VALUES(total_value),
        total_cost = VALUES(total_cost),
        total_products = VALUES(total_products),
        total_stock = VALUES(total_stock)
");

$stmt->execute([
    $today,
    $current['total_value'] ?? 0,
    $current['total_cost'] ?? 0,
    $current['total_products'] ?? 0,
    $current['total_stock'] ?? 0
]);

echo "Inventory snapshot for $today saved successfully.\n";
?>
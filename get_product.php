<?php
// get_product.php - Fetch product details for editing
session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('Invalid product ID');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die('Product not found');
}

$csrf_token = generateCSRFToken();
?>
<form method="POST" action="inventory.php">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
    
    <div class="form-row">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Category</label>
            <input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>SKU</label>
            <input type="text" name="sku" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Barcode</label>
            <input type="text" name="barcode" value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" name="stock_quantity" value="<?php echo $product['stock_quantity']; ?>" min="0" required>
        </div>
        
        <div class="form-group">
            <label>Reorder Level</label>
            <input type="number" name="reorder_level" value="<?php echo $product['reorder_level'] ?? 5; ?>" min="0">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label>Cost Price (₱)</label>
            <input type="number" step="0.01" name="cost_price" value="<?php echo $product['cost_price']; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Selling Price (₱)</label>
            <input type="number" step="0.01" name="selling_price" value="<?php echo $product['selling_price']; ?>" required>
        </div>
    </div>
    
    <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="2"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
    </div>
    
    <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="hideEditProductModal()">Cancel</button>
        <button type="submit" name="edit_product" class="btn btn-success">
            <i class="fas fa-check"></i>
            Update Product
        </button>
    </div>
</form>
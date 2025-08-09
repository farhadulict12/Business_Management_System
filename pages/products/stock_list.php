<?php
include('../../includes/header.php');
include('../../includes/config.php');

$user_id = $_SESSION['user_id'];
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';

// Fetch all products from the database for the logged-in user
$sql = "SELECT product_name, quantity, final_cost_per_unit, mrp FROM products WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
$conn->close();
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary mb-0">Product Stock List</h2>
        <div>
            <a href="../customers/sale.php" class="btn btn-success me-2">
                <i class="fas fa-plus"></i> Sale
            </a>
            <a href="../suppliers/purchase.php" class="btn btn-warning">
                <i class="fas fa-plus-circle"></i> Purchase
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Avg. Cost Rate (৳)</th>
                            <th>MRP (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                    <td><?php echo number_format(htmlspecialchars($product['final_cost_per_unit']), 2); ?></td>
                                    <td><?php echo number_format(htmlspecialchars($product['mrp']), 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No products found in stock.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('../../includes/footer.php'); ?>
<?php
include('../../includes/db.php');
include('../../includes/header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// SQL Query to calculate balance for each supplier
$sql = "
    SELECT 
        s.id,
        s.supplier_name,
        s.mobile_number,
        COALESCE(SUM(st.total_cost), 0) - COALESCE(SUM(st.paid_amount), 0) AS balance
    FROM suppliers s
    LEFT JOIN supplier_transactions st ON s.id = st.supplier_id AND st.user_id = ?
    WHERE s.user_id = ?
    GROUP BY s.id
    ORDER BY s.supplier_name ASC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-success">Supplier List</h4>
            <div>
            <a href="purchase.php" class="btn btn-success">
                <i class="fas fa-plus"></i> New Purchase
            </a>
             <a href="record_payment_to_supplier.php" class="btn btn-info ml-2">
                    <i class="fas fa-money-bill-wave"></i> Record Payment
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Mobile Number</th>
                            <th>Supplier Name</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                    <td class="balance-cell">
                                        <?php
                                        $balance_class = 'text-success';
                                        if ($row['balance'] < 0) {
                                            $balance_class = 'text-danger';
                                        } elseif ($row['balance'] == 0) {
                                            $balance_class = 'text-muted';
                                        }
                                        ?>
                                        <span class="<?php echo $balance_class; ?>">
                                            <?php echo number_format($row['balance'], 2) . ' Taka'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No suppliers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include('../../includes/footer.php'); ?>
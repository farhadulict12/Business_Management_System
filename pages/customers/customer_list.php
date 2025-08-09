<?php
include('../../includes/header.php');
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
        }
    </style>
<?php

// Fetch all customers and their total balances
$user_id = $_SESSION['user_id'];
$sql = "SELECT 
            c.*,
            (SELECT COALESCE(SUM(CASE WHEN ct.transaction_type = 'Sale' THEN ct.amount ELSE -ct.amount END), 0)
             FROM customer_transactions AS ct
             WHERE ct.customer_id = c.id AND ct.user_id = '$user_id') AS balance
        FROM customers AS c
        WHERE c.user_id = '$user_id'
        ORDER BY c.customer_name ASC";

$result = mysqli_query($conn, $sql);
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-primary">Customer List</h4>
            <div>
                <a href="sale.php" class="btn btn-success">
                    <i class="fas fa-cash-register"></i> Sale
                </a>
                <a href="payment_from_customers.php" class="btn btn-info ml-2">
                    <i class="fas fa-money-bill-wave"></i>  Payment from customer
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Mobile Number</th>
                            <th>Customer Name</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $balance_class = ($row['balance'] >= 0) ? 'text-danger' : 'text-success';
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['mobile_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
                                echo "<td class='$balance_class'>" . number_format($row['balance'], 2) . " Taka</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>No customers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include('../../includes/footer.php');
?>
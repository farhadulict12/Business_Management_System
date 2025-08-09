<?php
//session_start();

include('../../includes/header.php');
// Assuming config.php defines $conn. You may need to adjust the path.
include('../../includes/config.php'); 

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

// Fetch all customers to populate the dropdown list
$customers_sql = "SELECT id, customer_name FROM customers WHERE user_id = '{$_SESSION['user_id']}' ORDER BY customer_name ASC";
$customers_result = mysqli_query($conn, $customers_sql);

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $payment_amount = mysqli_real_escape_string($conn, $_POST['payment_amount']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $user_id = $_SESSION['user_id'];

    // Input validation
    if (empty($customer_id) || empty($payment_amount)) {
        $error_message = "Customer and payment amount are required.";
    } else {
        // Use a prepared statement for security and to prevent SQL injection
        $sql = "INSERT INTO customer_transactions (customer_id, transaction_type, amount, description, user_id) VALUES (?, 'Payment', ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "idsi", $customer_id, $payment_amount, $description, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Payment of " . number_format($payment_amount, 2) . " Taka recorded successfully for the customer.";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-success">Payment from customers</h4>
            <div>
                <a href="customer_list.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Customer List
                </a>
                <a href="sale.php" class="btn btn-success ml-2">
                    <i class="fas fa-cash-register"></i> Sale
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php
            if (!empty($success_message)) {
                echo "<div class='alert alert-success'>" . $success_message . "</div>";
            }
            if (!empty($error_message)) {
                echo "<div class='alert alert-danger'>" . $error_message . "</div>";
            }
            ?>
            <form action="payment_from_customers.php" method="POST">
                <div class="form-group">
                    <label for="customer_id">Select Customer:</label>
                    <select class="form-control" id="customer_id" name="customer_id" required>
                        <option value="">Select a customer</option>
                        <?php
                        if (mysqli_num_rows($customers_result) > 0) {
                            while($row = mysqli_fetch_assoc($customers_result)) {
                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['customer_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Current Due Amount:</label>
                    <p class="form-control-static" id="current_due_amount">0.00 Taka</p>
                </div>
                <div class="form-group">
                    <label for="payment_amount">Payment Amount:</label>
                    <input type="number" step="0.01" class="form-control" id="payment_amount" name="payment_amount" required min="0.01">
                </div>
                <div class="form-group">
                    <label>New Balance:</label>
                    <p class="form-control-static" id="new_balance">0.00 Taka</p>
                </div>
                <div class="form-group">
                    <label for="description">Description (Optional):</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Record Payment</button>
                <a href="customer_list.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
    var currentBalance = 0;

    $('#customer_id').on('change', function() {
        var customerId = $(this).val();
        if (customerId) {
            $.ajax({
                url: 'fetch_data.php',
                type: 'GET',
                data: { customer_balance_id: customerId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        currentBalance = parseFloat(response.data.balance);
                        $('#current_due_amount').text(currentBalance.toFixed(2) + " Taka");
                        updateNewBalance();
                    } else {
                        currentBalance = 0;
                        $('#current_due_amount').text("0.00 Taka");
                        updateNewBalance();
                    }
                }
            });
        } else {
            currentBalance = 0;
            $('#current_due_amount').text("0.00 Taka");
            updateNewBalance();
        }
    });

    $('#payment_amount').on('input', function() {
        updateNewBalance();
    });

    function updateNewBalance() {
        var payment = parseFloat($('#payment_amount').val()) || 0;
        var newBalance = currentBalance - payment;
        $('#new_balance').text(newBalance.toFixed(2) + " Taka");
    }
});
</script>

<?php
include('../../includes/footer.php');
?>
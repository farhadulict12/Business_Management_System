<?php
// Start a session to use session variables.
//session_start();

// includes/db.php-এর সঠিক পাথ
// Please ensure this path is correct for your project's file structure.
include('../../includes/db.php');
include('../../includes/header.php');

// Check if the user is NOT logged in.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = $_POST['supplier_id'];
    $payment_amount = $_POST['payment_amount'];
    $payment_date = $_POST['payment_date'];
    $description = $_POST['description'];

    // Start a transaction for data integrity
    mysqli_begin_transaction($conn);

    try {
        // Insert the payment record into a dedicated payments table
        $sql_payment = "INSERT INTO supplier_payments (supplier_id, amount, payment_date, description, user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_payment = mysqli_prepare($conn, $sql_payment);
        mysqli_stmt_bind_param($stmt_payment, 'idssi', $supplier_id, $payment_amount, $payment_date, $description, $user_id);

        if (!mysqli_stmt_execute($stmt_payment)) {
            throw new Exception("Error recording payment: " . mysqli_error($conn));
        }

        // Insert a new transaction record to update the balance
        // A payment is a credit, so it reduces the total due.
        $sql_transaction = "INSERT INTO supplier_transactions (supplier_id, transaction_date, transaction_type, total_cost, paid_amount, user_id, description) VALUES (?, ?, 'Payment', 0, ?, ?, ?)";
        $stmt_transaction = mysqli_prepare($conn, $sql_transaction);
        mysqli_stmt_bind_param($stmt_transaction, 'isdis', $supplier_id, $payment_date, $payment_amount, $user_id, $description);
        
        if (!mysqli_stmt_execute($stmt_transaction)) {
            throw new Exception("Error recording transaction: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        $success_message = "Payment of " . number_format($payment_amount, 2) . " Taka to supplier ID " . $supplier_id . " recorded successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Transaction failed: " . $e->getMessage();
    }
}

// Fetch all suppliers for the dropdown
$suppliers_sql = "SELECT id, supplier_name FROM suppliers WHERE user_id = ? ORDER BY supplier_name ASC";
$stmt = mysqli_prepare($conn, $suppliers_sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$suppliers_result = mysqli_stmt_get_result($stmt);
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-success"> Payment to Supplier</h4>
            <div>
                <a href="purchase.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> New Purchase
                </a>
                <a href="supplier_list.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Supplier List
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php 
            if ($success_message) {
                echo "<div class='alert alert-success'>" . $success_message . "</div>";
            }
            if ($error_message) {
                echo "<div class='alert alert-danger'>" . $error_message . "</div>";
            }
            ?>
            <div id="dynamic-alert" class="alert d-none" role="alert"></div>
            
            <form action="payment_to_supplier.php" method="POST">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="supplier_id">Select Supplier:</label>
                        <select class="form-control" id="supplier_id" name="supplier_id" required>
                            <option value="">Select a supplier</option>
                            <?php while ($supplier = mysqli_fetch_assoc($suppliers_result)): ?>
                                <option value="<?php echo $supplier['id']; ?>">
                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="payment_date">Payment Date:</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Current Due Amount:</label>
                        <p class="form-control-static" id="current_due_amount">0.00 Taka</p>
                        <input type="hidden" id="current_due_amount_value" name="current_due_amount_value">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="payment_amount">Payment Amount:</label>
                        <input type="number" step="0.01" class="form-control" id="payment_amount" name="payment_amount" required min="0.01" value="0.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>New Balance:</label>
                    <p class="form-control-static" id="new_balance">0.00 Taka</p>
                </div>
                
                <div class="form-group">
                    <label for="description">Description (Optional):</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-success">Record Payment</button>
                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    function showDynamicAlert(message, type = 'info') {
        const alertDiv = $('#dynamic-alert');
        alertDiv.removeClass().addClass(`alert alert-${type}`).text(message).removeClass('d-none');
        setTimeout(() => alertDiv.addClass('d-none'), 3000);
    }

    // Function to calculate new balance
    function calculateNewBalance() {
        var currentDue = parseFloat($('#current_due_amount_value').val()) || 0;
        var payment = parseFloat($('#payment_amount').val()) || 0;
        var newBalance = currentDue - payment;
        $('#new_balance').text(newBalance.toFixed(2) + " Taka");
    }

    // Event listener for when a supplier is selected
    $('#supplier_id').on('change', function() {
        var supplierId = $(this).val();
        if (supplierId) {
            $.ajax({
                url: 'fetch_supplier_balance.php',
                type: 'GET',
                data: { supplier_id: supplierId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var balance = parseFloat(response.balance) || 0;
                        $('#current_due_amount').text(balance.toFixed(2) + " Taka");
                        $('#current_due_amount_value').val(balance.toFixed(2));
                        
                        // Update new balance after fetching due amount
                        calculateNewBalance();
                    } else {
                        $('#current_due_amount').text("0.00 Taka");
                        $('#current_due_amount_value').val("0.00");
                        showDynamicAlert("Could not fetch balance.", 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + " - " + error);
                    console.error("Response: " + xhr.responseText);
                    showDynamicAlert("An error occurred while fetching data.", 'danger');
                    $('#current_due_amount').text("0.00 Taka");
                    $('#current_due_amount_value').val("0.00");
                }
            });
        } else {
            $('#current_due_amount').text("0.00 Taka");
            $('#current_due_amount_value').val("0.00");
            calculateNewBalance();
        }
    });

    // Event listener for payment amount input
    $('#payment_amount').on('input', function() {
        calculateNewBalance();
    });

    // Initial calculation on page load
    calculateNewBalance();
});
</script>
<?php
include('../../includes/footer.php');
?>
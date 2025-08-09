<?php
// Start a session to use session variables.
//session_start();

include('../../includes/header.php');
include('../../includes/config.php'); // Assuming config.php defines $conn

// Check if the user is NOT logged in.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = $_POST['customer_name'] ?? '';
    $mobile_number = $_POST['mobile_number'] ?? '';
    $product_id = $_POST['product_id'] ?? '';
    $sale_quantity = $_POST['sale_quantity'] ?? 0;
    $sale_price = $_POST['sale_price'] ?? 0;
    $paid_amount = $_POST['paid_amount'] ?? 0;
    $sale_date = $_POST['sale_date'] ?? date('Y-m-d');

    // Input Validation (Added for safety)
    if (empty($customer_name) || empty($mobile_number) || empty($product_id) || $sale_quantity <= 0 || $sale_price <= 0) {
        $error_message = "Please fill in all required fields correctly.";
    } else {
        $total_price = $sale_quantity * $sale_price;

        mysqli_begin_transaction($conn);

        try {
            $customer_id = null;
            
            // Prepared Statement to check for existing customer
            $check_customer_sql = "SELECT id FROM customers WHERE mobile_number = ? AND user_id = ?";
            $check_customer_stmt = mysqli_prepare($conn, $check_customer_sql);
            mysqli_stmt_bind_param($check_customer_stmt, "si", $mobile_number, $user_id);
            mysqli_stmt_execute($check_customer_stmt);
            $customer_result = mysqli_stmt_get_result($check_customer_stmt);
            $customer_row = mysqli_fetch_assoc($customer_result);

            if (mysqli_num_rows($customer_result) == 0) {
                // Prepared Statement to insert new customer
                $insert_customer_sql = "INSERT INTO customers (customer_name, mobile_number, user_id) VALUES (?, ?, ?)";
                $insert_customer_stmt = mysqli_prepare($conn, $insert_customer_sql);
                mysqli_stmt_bind_param($insert_customer_stmt, "ssi", $customer_name, $mobile_number, $user_id);
                if (!mysqli_stmt_execute($insert_customer_stmt)) {
                    throw new Exception("Error adding new customer: " . mysqli_stmt_error($insert_customer_stmt));
                }
                $customer_id = mysqli_insert_id($conn);
            } else {
                $customer_id = $customer_row['id'];
            }
            mysqli_stmt_close($check_customer_stmt);
            
            // Prepared Statement to check stock
            $stock_check_sql = "SELECT quantity FROM products WHERE id = ? AND user_id = ?";
            $stock_check_stmt = mysqli_prepare($conn, $stock_check_sql);
            mysqli_stmt_bind_param($stock_check_stmt, "ii", $product_id, $user_id);
            mysqli_stmt_execute($stock_check_stmt);
            $stock_check_result = mysqli_stmt_get_result($stock_check_stmt);
            $stock_row = mysqli_fetch_assoc($stock_check_result);
            $current_stock = $stock_row['quantity'];
            mysqli_stmt_close($stock_check_stmt);

            if ($current_stock < $sale_quantity) {
                throw new Exception("Not enough stock. Available quantity: " . $current_stock);
            }

            // Prepared Statement to insert sale transaction
            $sale_sql = "INSERT INTO sales_transactions (customer_id, product_id, sale_quantity, sale_price, total_price, paid_amount, sale_date, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $sale_stmt = mysqli_prepare($conn, $sale_sql);
            mysqli_stmt_bind_param($sale_stmt, "iiidddsi", $customer_id, $product_id, $sale_quantity, $sale_price, $total_price, $paid_amount, $sale_date, $user_id);
            if (!mysqli_stmt_execute($sale_stmt)) {
                throw new Exception("Error inserting into sales_transactions: " . mysqli_stmt_error($sale_stmt));
            }
            mysqli_stmt_close($sale_stmt);

            // Prepared Statement to update product stock
            $update_stock_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND user_id = ?";
            $update_stock_stmt = mysqli_prepare($conn, $update_stock_sql);
            mysqli_stmt_bind_param($update_stock_stmt, "iii", $sale_quantity, $product_id, $user_id);
            if (!mysqli_stmt_execute($update_stock_stmt)) {
                throw new Exception("Error updating product stock: " . mysqli_stmt_error($update_stock_stmt));
            }
            mysqli_stmt_close($update_stock_stmt);

            // Prepared Statements for customer transactions
            if ($total_price > 0) {
                $customer_sale_sql = "INSERT INTO customer_transactions (customer_id, transaction_type, amount, description, user_id) VALUES (?, 'Sale', ?, 'Product sale', ?)";
                $customer_sale_stmt = mysqli_prepare($conn, $customer_sale_sql);
                mysqli_stmt_bind_param($customer_sale_stmt, "idi", $customer_id, $total_price, $user_id);
                if (!mysqli_stmt_execute($customer_sale_stmt)) {
                    throw new Exception("Error inserting sale transaction: " . mysqli_stmt_error($customer_sale_stmt));
                }
                mysqli_stmt_close($customer_sale_stmt);
            }

            if ($paid_amount > 0) {
                $customer_payment_sql = "INSERT INTO customer_transactions (customer_id, transaction_type, amount, description, user_id) VALUES (?, 'Payment', ?, 'Payment for sale', ?)";
                $customer_payment_stmt = mysqli_prepare($conn, $customer_payment_sql);
                mysqli_stmt_bind_param($customer_payment_stmt, "idi", $customer_id, $paid_amount, $user_id);
                if (!mysqli_stmt_execute($customer_payment_stmt)) {
                    throw new Exception("Error inserting payment transaction: " . mysqli_stmt_error($customer_payment_stmt));
                }
                mysqli_stmt_close($customer_payment_stmt);
            }

            mysqli_commit($conn);
            $success_message = "Sale recorded successfully.";
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    }
}
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
            <h4 class="mb-0">Sale</h4>
            <div>
                <a href="customer_list.php" class="btn btn-light btn-sm">
                    <i class="fas fa-users"></i> Customer List
                </a>
                <a href="payment_from_customers.php" class="btn btn-warning btn-sm ml-2">
                    <i class="fas fa-money-bill-wave"></i> Payment from customers
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
            <div id="dynamic-alert" class="alert d-none" role="alert"></div>
            <form action="sale.php" method="POST">
                <input type="hidden" id="product_id" name="product_id">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="mobile_number">Customer Mobile Number:</label>
                        <input type="text" class="form-control" id="mobile_number" name="mobile_number" placeholder="Enter mobile number" required>
                        <small class="form-text text-muted">Enter a mobile number to check for an existing customer.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="customer_name">Customer Name:</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" required readonly>
                    </div>
                </div>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="product_name">Product Name:</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter product name" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="sale_date">Sale Date:</label>
                        <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="sale_quantity">Quantity:</label>
                        <input type="number" class="form-control" id="sale_quantity" name="sale_quantity" required min="1">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="sale_price">Price per Unit (Taka):</label>
                        <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" required min="0.01">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="total_price">Total Price (Taka):</label>
                        <input type="number" step="0.01" class="form-control" id="total_price" name="total_price" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="paid_amount">Paid Amount (Taka):</label>
                        <input type="number" step="0.01" class="form-control" id="paid_amount" name="paid_amount" value="0.00">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Due Amount:</label>
                        <p class="form-control-static" id="due_amount">0.00 Taka</p>
                    </div>
                </div>
                <button type="submit" class="btn btn-success" name="record_sale">Record Sale</button>
                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
    // Function to show dynamic alerts
    function showDynamicAlert(message, type = 'info') {
        const alertDiv = $('#dynamic-alert');
        alertDiv.removeClass().addClass(`alert alert-${type}`).text(message).removeClass('d-none');
        setTimeout(() => alertDiv.addClass('d-none'), 3000);
    }
    
    // Check for existing customer on mobile number input
    $('#mobile_number').on('blur', function() {
        var mobile = $(this).val();
        if (mobile.length > 0) {
            $.ajax({
                url: 'fetch_data.php',
                type: 'GET',
                data: { mobile_number: mobile },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        $('#customer_name').val(response.data.customer_name).prop('readonly', true);
                        showDynamicAlert("Existing customer found: " + response.data.customer_name, 'success');
                    } else {
                        $('#customer_name').val('').prop('readonly', false).focus();
                        showDynamicAlert("New customer. Please enter name.", 'info');
                    }
                }
            });
        }
    });

    // Fetch product details based on product name
    $('#product_name').on('blur', function() {
        var productName = $(this).val();
        if (productName.length > 0) {
            $.ajax({
                url: 'fetch_data.php',
                type: 'GET',
                data: { product_name: productName },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        $('#product_id').val(response.data.id);
                        $('#sale_price').val(response.data.mrp);
                        showDynamicAlert("Existing product found. MRP loaded.", 'success');
                    } else {
                        $('#product_id').val('');
                        $('#sale_price').val('');
                        showDynamicAlert("Product not found or new. Please enter details.", 'warning');
                    }
                    calculateTotal();
                    calculateDue();
                }
            });
        }
    });

    // Calculate total price and due on quantity, price, or paid amount change
    $('#sale_quantity, #sale_price, #paid_amount').on('input', function() {
        calculateTotal();
        calculateDue();
    });

    function calculateTotal() {
        var quantity = parseFloat($('#sale_quantity').val()) || 0;
        var price = parseFloat($('#sale_price').val()) || 0;
        var total = quantity * price;
        $('#total_price').val(total.toFixed(2));
    }

    function calculateDue() {
        var total = parseFloat($('#total_price').val()) || 0;
        var paid = parseFloat($('#paid_amount').val()) || 0;
        var due = total - paid;
        $('#due_amount').text(due.toFixed(2) + " Taka");
    }
});
</script>
<?php
include('../../includes/footer.php');
?>
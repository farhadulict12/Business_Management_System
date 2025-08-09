<?php
// Start a session to use session variables.
//session_start();

// includes/db.php-এর সঠিক পাথ
// যদি আপনার ফাইল স্ট্রাকচার সঠিক না হয়, এই লাইনটি পরিবর্তন করতে হতে পারে।
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_name = $_POST['supplier_name'];
    $mobile_number = $_POST['mobile_number'];
    $purchase_date = $_POST['purchase_date'];
    
    $product_id = null;
    $product_name = $_POST['product_name'];
    $mrp = $_POST['mrp'];
    
    $purchase_quantity = $_POST['purchase_quantity'];
    $cost_per_unit = $_POST['cost_per_unit'];
    $paid_amount = $_POST['paid_amount'];
    $notes = $_POST['notes'];
    $total_cost = $purchase_quantity * $cost_per_unit;

    // Start database transaction for data integrity
    mysqli_begin_transaction($conn);

    try {
        $supplier_id = null;
        // Check for existing supplier using prepared statement
        $check_supplier_sql = "SELECT id FROM suppliers WHERE mobile_number = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $check_supplier_sql);
        mysqli_stmt_bind_param($stmt, 'si', $mobile_number, $user_id);
        mysqli_stmt_execute($stmt);
        $supplier_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($supplier_result) == 0) {
            // Supplier does not exist, insert new supplier
            $insert_supplier_sql = "INSERT INTO suppliers (supplier_name, mobile_number, user_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_supplier_sql);
            mysqli_stmt_bind_param($stmt, 'ssi', $supplier_name, $mobile_number, $user_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error adding new supplier: " . mysqli_error($conn));
            }
            $supplier_id = mysqli_insert_id($conn);
        } else {
            // Supplier exists, get their ID
            $supplier_row = mysqli_fetch_assoc($supplier_result);
            $supplier_id = $supplier_row['id'];
        }

        // Check for existing product using prepared statement
        $product_check_sql = "SELECT id, quantity, final_cost_per_unit, mrp FROM products WHERE product_name = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $product_check_sql);
        mysqli_stmt_bind_param($stmt, 'si', $product_name, $user_id);
        mysqli_stmt_execute($stmt);
        $product_check_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($product_check_result) > 0) {
            // Product exists, update its quantity and costs
            $product_row = mysqli_fetch_assoc($product_check_result);
            $product_id = $product_row['id'];
            $previous_quantity = $product_row['quantity'];
            $previous_cost = $product_row['final_cost_per_unit'];
            $previous_mrp = $product_row['mrp'];

            // Calculate new weighted average cost and MRP
            $new_total_quantity = $previous_quantity + $purchase_quantity;
            $new_final_cost = (($previous_cost * $previous_quantity) + ($cost_per_unit * $purchase_quantity)) / $new_total_quantity;
            $new_mrp = (($previous_mrp * $previous_quantity) + ($mrp * $purchase_quantity)) / $new_total_quantity;
            
            // Update product stock and price
            $update_product_sql = "UPDATE products SET quantity = ?, final_cost_per_unit = ?, mrp = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $update_product_sql);
            mysqli_stmt_bind_param($stmt, 'iddii', $new_total_quantity, $new_final_cost, $new_mrp, $product_id, $user_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error updating product stock and price: " . mysqli_error($conn));
            }
        } else {
                // New product, insert it with the supplier_id and quantity
            $insert_product_sql = "INSERT INTO products (product_name, mrp, final_cost_per_unit, quantity, supplier_id, user_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_product_sql);
            // Note the added 'd' for the new quantity parameter
            mysqli_stmt_bind_param($stmt, 'sddiii', $product_name, $mrp, $cost_per_unit, $purchase_quantity, $supplier_id, $user_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error adding new product: " . mysqli_error($conn));
            }
            $product_id = mysqli_insert_id($conn);
        }

        // Insert purchase transaction
        $purchase_sql = "INSERT INTO supplier_transactions (supplier_id, product_id, purchase_quantity, cost_per_unit, total_cost, paid_amount, purchase_date, notes, user_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $purchase_sql);
        mysqli_stmt_bind_param($stmt, 'iiidddssi', $supplier_id, $product_id, $purchase_quantity, $cost_per_unit, $total_cost, $paid_amount, $purchase_date, $notes, $user_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error inserting into supplier_transactions: " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        $success_message = "Purchase recorded successfully.";
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Transaction failed: " . $e->getMessage();
    }
}
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-success">Purchase</h4>
            <div>
                <a href="supplier_list.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Supplier List
                </a>
                <a href="payment_to_supplier.php" class="btn btn-info ml-2">
                    <i class="fas fa-money-bill-wave"></i> Payment to Supplier
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
            <form action="purchase.php" method="POST">
                <input type="hidden" id="product_id" name="product_id">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="mobile_number">Supplier Mobile Number:</label>
                        <input type="text" class="form-control" id="mobile_number" name="mobile_number" placeholder="Enter mobile number" required>
                        <small class="form-text text-muted">Enter a mobile number to check for an existing supplier.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="supplier_name">Supplier Name:</label>
                        <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="product_name">Product Name:</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter product name" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="purchase_date">Purchase Date:</label>
                        <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <hr>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="purchase_quantity">Quantity:</label>
                        <input type="number" class="form-control" id="purchase_quantity" name="purchase_quantity" required min="1">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="cost_per_unit">Cost per Unit (Taka):</label>
                        <input type="number" step="0.01" class="form-control" id="cost_per_unit" name="cost_per_unit" required min="0.01">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="mrp">MRP (Selling Price):</label>
                        <input type="number" step="0.01" class="form-control" id="mrp" name="mrp" min="0.01">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="total_cost">Total Cost (Taka):</label>
                        <input type="number" step="0.01" class="form-control" id="total_cost" name="total_cost" readonly>
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
                <div class="form-group">
                    <label for="notes">Notes (Optional):</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Purchase</button>
                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Function to show dynamic alerts
    function showDynamicAlert(message, type = 'info') {
        const alertDiv = $('#dynamic-alert');
        alertDiv.removeClass().addClass(`alert alert-${type}`).text(message).removeClass('d-none');
        setTimeout(() => alertDiv.addClass('d-none'), 3000);
    }

    // Check for existing supplier on mobile number input
    $('#mobile_number').on('blur', function() {
        var mobile = $(this).val();
        if (mobile.length > 0) {
            $.ajax({
                url: 'fetch_data.php',
                type: 'GET',
                data: { supplier_mobile: mobile },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        $('#supplier_name').val(response.data.supplier_name).prop('readonly', true);
                        showDynamicAlert("Existing supplier found: " + response.data.supplier_name, 'success');
                    } else {
                        $('#supplier_name').val('').prop('readonly', false).focus();
                        showDynamicAlert("New supplier. Please enter name.", 'info');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + " - " + error);
                    console.error("Response: " + xhr.responseText);
                    showDynamicAlert("An error occurred while fetching supplier data.", 'danger');
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
                        $('#cost_per_unit').val(parseFloat(response.data.final_cost_per_unit).toFixed(2));
                        $('#mrp').val(parseFloat(response.data.mrp).toFixed(2));
                        showDynamicAlert("Existing product found. Data loaded.", 'success');
                    } else {
                        $('#product_id').val('');
                        $('#cost_per_unit').val('');
                        $('#mrp').val('');
                        showDynamicAlert("New product. Please enter prices.", 'info');
                    }
                    // Update: Calculate total before due to ensure correct values
                    calculateTotal();
                    calculateDue();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + " - " + error);
                    console.error("Response: " + xhr.responseText);
                    showDynamicAlert("An error occurred while fetching product data.", 'danger');
                }
            });
        }
    });

    // Calculate total cost and due on quantity, cost, or paid amount change
    $('#purchase_quantity, #cost_per_unit, #paid_amount').on('input', function() {
        calculateTotal();
        calculateDue();
    });

    function calculateTotal() {
        var quantity = parseFloat($('#purchase_quantity').val()) || 0;
        var cost = parseFloat($('#cost_per_unit').val()) || 0;
        var total = quantity * cost;
        $('#total_cost').val(total.toFixed(2));
    }

    function calculateDue() {
        var totalCost = parseFloat($('#total_cost').val()) || 0;
        var paidAmount = parseFloat($('#paid_amount').val()) || 0;
        var dueAmount = totalCost - paidAmount;
        $('#due_amount').text(dueAmount.toFixed(2) + " Taka");
    }
});
</script>
<?php
include('../../includes/footer.php');
?>
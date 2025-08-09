<?php
include('../../includes/db.php');
session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'data' => null];

// Get customer data by mobile number
if (isset($_GET['mobile_number'])) {
    $mobile_number = mysqli_real_escape_string($conn, $_GET['mobile_number']);
    $sql = "SELECT id, customer_name FROM customers WHERE mobile_number = '$mobile_number' AND user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['data'] = $row;
    }
} 
// Get product details by name (for both sale & purchase pages)
elseif (isset($_GET['product_name'])) {
    $product_name = mysqli_real_escape_string($conn, $_GET['product_name']);
    $product_sql = "SELECT id, mrp, final_cost_per_unit FROM products WHERE product_name = '$product_name' AND user_id = '$user_id'";
    $product_result = mysqli_query($conn, $product_sql);

    if ($product_row = mysqli_fetch_assoc($product_result)) {
        $response['success'] = true;
        $response['data'] = $product_row;
    } else {
        $response['success'] = false;
    }
}
// Get customer balance by ID
elseif (isset($_GET['customer_balance_id'])) {
    $customer_id = mysqli_real_escape_string($conn, $_GET['customer_balance_id']);
    $sql = "SELECT COALESCE(SUM(CASE WHEN ct.transaction_type = 'Sale' THEN ct.amount ELSE -ct.amount END), 0) AS balance
            FROM customer_transactions AS ct
            WHERE ct.customer_id = '$customer_id' AND ct.user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['data'] = $row;
    }
} 
// Get supplier data by mobile number
elseif (isset($_GET['supplier_mobile'])) {
    $mobile_number = mysqli_real_escape_string($conn, $_GET['supplier_mobile']);
    $sql = "SELECT id, supplier_name FROM suppliers WHERE mobile_number = '$mobile_number' AND user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['data'] = $row;
    }
}
// Get supplier balance by ID
elseif (isset($_GET['supplier_balance_id'])) {
    $supplier_id = mysqli_real_escape_string($conn, $_GET['supplier_balance_id']);
    $sql = "SELECT COALESCE(SUM(CASE WHEN st.transaction_type = 'Purchase' THEN st.amount ELSE -st.amount END), 0) AS balance
            FROM supplier_transactions AS st
            WHERE st.supplier_id = '$supplier_id' AND st.user_id = '$user_id'";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['data'] = $row;
    }
}

echo json_encode($response);
mysqli_close($conn);
?>
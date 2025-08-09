<?php
session_start();
include('../../includes/db.php');

header('Content-Type: application/json');

$response = ['success' => false, 'balance' => 0];

if (!isset($_SESSION['user_id']) || !isset($_GET['supplier_id'])) {
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$supplier_id = $_GET['supplier_id'];

// SQL Query to calculate total purchase due (total cost - paid amount)
$sql = "
    SELECT 
        COALESCE(SUM(st.total_cost) - SUM(st.paid_amount), 0) AS balance
    FROM supplier_transactions st
    WHERE st.supplier_id = ? AND st.user_id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $supplier_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $response['success'] = true;
    $response['balance'] = $row['balance'];
}

echo json_encode($response);
?>
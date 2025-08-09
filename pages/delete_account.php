<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

include '../includes/config.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Check if a confirmation form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    $current_password = $_POST['current_password'];

    // Fetch the stored password hash
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($current_password, $user['password'])) {
        // Password is correct, proceed with deletion
        $conn->begin_transaction();
        try {
            // Delete related transactions first (e.g., from customer_transactions)
            // Note: You need to adjust this query if your transaction table has a different structure.
            $sql_del_transactions = "DELETE FROM customer_transactions WHERE user_id = ?";
            $stmt_del_transactions = $conn->prepare($sql_del_transactions);
            $stmt_del_transactions->bind_param("i", $user_id);
            $stmt_del_transactions->execute();
            $stmt_del_transactions->close();

            // Delete user account
            $sql_del_user = "DELETE FROM users WHERE id = ?";
            $stmt_del_user = $conn->prepare($sql_del_user);
            $stmt_del_user->bind_param("i", $user_id);
            $stmt_del_user->execute();
            $stmt_del_user->close();

            $conn->commit();

            session_destroy();
            header("Location: ../../public/index.php?message=" . urlencode("Your account has been successfully deleted."));
            exit();

        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $message = "Error deleting account: " . $e->getMessage();
        }
    } else {
        $message = "Incorrect password. Account deletion failed.";
    }
}

// Fetch user data for display. We use `email` instead of a non-existent `name` or `username` column.
$sql = "SELECT email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .delete-container { background-color: #ffffff; padding: 40px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
        .delete-container h2 { color: #dc3545; margin-bottom: 20px; }
        .delete-container p { color: #555; margin-bottom: 30px; line-height: 1.6; }
        .delete-container .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; color: #fff; transition: transform 0.2s; }
        .delete-container .btn-danger { background-color: #dc3545; }
        .delete-container .btn-danger:hover { background-color: #c82333; transform: translateY(-2px); }
        .delete-container .btn-secondary { background-color: #6c757d; margin-left: 10px; }
        .delete-container .btn-secondary:hover { background-color: #5a6268; transform: translateY(-2px); }
        .message { margin-bottom: 20px; padding: 15px; border-radius: 8px; font-weight: 500; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { text-align: left; margin-bottom: 20px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="delete-container">
    <h2>Are you absolutely sure?</h2>
    <p>This action **cannot** be undone. This will permanently delete your account with the email **<?php echo htmlspecialchars($user_data['email']); ?>** and all associated data, including all customer and sales information.</p>

    <?php if (!empty($message)): ?>
        <div class="message message-error"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="delete_account.php" method="POST">
        <div class="form-group">
            <label for="current_password">Please type your password to confirm:</label>
            <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>
        <button type="submit" name="confirm_delete" class="btn btn-danger">Confirm and Delete My Account</button>
        <a href="settings.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>
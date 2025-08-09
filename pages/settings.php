<?php
// Start a session at the very top of the page.
session_start();

// Check if the user is logged in. If not, redirect.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Include the database configuration file.
include '../includes/config.php';

// --- Handle all form submissions at the very beginning, before any HTML or includes ---

// 1. Profile Picture Upload
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic_file'])) {
    $file = $_FILES['profile_pic_file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif');

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) {
                $sql_user_check = "SELECT profile_pic FROM users WHERE id = ?";
                $stmt_user_check = $conn->prepare($sql_user_check);
                $stmt_user_check->bind_param("i", $user_id);
                $stmt_user_check->execute();
                $user_result_check = $stmt_user_check->get_result();
                $user_current = $user_result_check->fetch_assoc();
                $stmt_user_check->close();
                
                if ($user_current['profile_pic'] !== 'default_profile.jpg') {
                    $oldFilePath = '../public/assets/images/' . $user_current['profile_pic'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                $fileNameNew = uniqid('', true) . "." . $fileExt;
                $fileDestination = '../public/assets/images/' . $fileNameNew;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $update_sql = "UPDATE users SET profile_pic = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("si", $fileNameNew, $user_id);
                    if ($update_stmt->execute()) {
                        $message = "Profile picture uploaded successfully!";
                    } else {
                        $message = "Error updating database: " . $update_stmt->error;
                    }
                    $update_stmt->close();
                } else {
                    $message = "Error uploading file. Please try again.";
                }
            } else {
                $message = "Your file is too large (max 5MB).";
            }
        } else {
            $message = "There was an error uploading your file.";
        }
    } else {
        $message = "You cannot upload files of this type.";
    }
    header("Location: settings.php?message=" . urlencode($message));
    exit();
}

// 2. Edit Profile Details
if (isset($_POST['edit_details'])) {
    $current_password = $_POST['current_password'];
    $new_email = $_POST['email'];
    $new_mobile = $_POST['mobile_number'];
    
    // Changed 'password_hash' to 'password'
    $sql_password_check = "SELECT password FROM users WHERE id = ?";
    $stmt_password_check = $conn->prepare($sql_password_check);
    $stmt_password_check->bind_param("i", $user_id);
    $stmt_password_check->execute();
    $password_result = $stmt_password_check->get_result();
    $password_hash_row = $password_result->fetch_assoc();
    $stmt_password_check->close();
    
    // Assuming your 'password' column stores a hashed password
    if (password_verify($current_password, $password_hash_row['password'])) {
        $update_sql = "UPDATE users SET email = ?, mobile_number = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $new_email, $new_mobile, $user_id);
        if ($update_stmt->execute()) {
            $message = "Contact info updated successfully!";
        } else {
            $message = "Error updating contact info: " . $update_stmt->error;
        }
        $update_stmt->close();
    } else {
        $message = "Incorrect password.";
    }
    header("Location: settings.php?message=" . urlencode($message));
    exit();
}

// 3. Change Password
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Changed 'password_hash' to 'password'
    $sql_password_check = "SELECT password FROM users WHERE id = ?";
    $stmt_password_check = $conn->prepare($sql_password_check);
    $stmt_password_check->bind_param("i", $user_id);
    $stmt_password_check->execute();
    $password_result = $stmt_password_check->get_result();
    $password_hash_row = $password_result->fetch_assoc();
    $stmt_password_check->close();
    
    // Assuming your 'password' column stores a hashed password
    if (password_verify($current_password, $password_hash_row['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            // Changed 'password_hash' to 'password'
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_new_password, $user_id);
            if ($update_stmt->execute()) {
                $message = "Password changed successfully!";
            } else {
                $message = "Error changing password: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $message = "New passwords do not match.";
        }
    } else {
        $message = "Incorrect current password.";
    }
    header("Location: settings.php?message=" . urlencode($message));
    exit();
}

// 4. Delete Account
if (isset($_POST['delete_account'])) {
    header("Location: /business_management/pages/delete_account.php");
    exit();
}

// Fetch user data after all form submissions have been processed
// Changed 'password_hash' to 'password' in this SQL query as well
$sql_user = "SELECT email, mobile_number, password FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();

// Check for and display message from a redirect
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .dashboard-container {
            display: flex;
            gap: 20px;
        }
        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background-color: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            border-radius: 10px;
            height: fit-content;
            transition: all 0.3s ease;
        }
        .sidebar:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .sidebar h2 {
            font-size: 1.5rem;
            color: #007bff;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li {
            margin-bottom: 10px;
        }
        .sidebar ul li a {
            text-decoration: none;
            color: #555;
            font-size: 1.1rem;
            display: block;
            padding: 12px 15px;
            border-radius: 8px;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .sidebar ul li a:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #007bff, #00c6ff);
            z-index: -1;
            transition: all 0.3s ease;
        }
        .sidebar ul li a:hover:before,
        .sidebar ul li a.active:before {
            left: 0;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            color: #ffffff;
            font-weight: 600;
        }
        .main-content {
            flex-grow: 1;
        }
        .card {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-header {
            margin-bottom: 20px;
            background: linear-gradient(45deg, #007bff, #00c6ff);
            color: #fff;
            padding: 25px 30px;
            text-align: center;
            font-weight: 600;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 20px -30px;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.8rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        .settings-section {
            padding: 20px 0;
        }
        .settings-section h4 {
            font-size: 1.4rem;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #555;
        }
        .form-control {
            width: 100%;
            padding: 12px 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.2);
            outline: none;
        }
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: transform 0.2s ease-in-out, box-shadow 0.3s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #0056b3, #003d80);
        }
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #495057);
        }
        .btn-secondary:hover {
            background: linear-gradient(45deg, #495057, #343a40);
        }
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        .btn-danger:hover {
            background: linear-gradient(45deg, #c82333, #a71d2a);
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            animation: fadeIn 0.5s ease-in-out;
        }
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .delete-account {
            text-align: center;
        }
        .warning-box {
            background-color: #fff3cd;
            color: #856404;
            padding: 20px;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        hr {
            border: 0;
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <aside class="sidebar">
            <h2>Business Management</h2>
            <nav>
                <ul>
                    <li><a href="/business_management/public/dashboard.php">Dashboard</a></li>
                    <li><a href="/business_management/pages/suppliers/purchase.php">Purchase</a></li>
                    <li><a href="/business_management/pages/products/stock_list.php">Stock</a></li>
                    <li><a href="/business_management/pages/customers/sale.php">Sale</a></li>
                    <li><a href="#" class="active">Settings</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="card">
                <div class="card-header">
                    <h3>User Settings</h3>
                </div>
                <div class="card-body">

                    <?php if ($message): ?>
                        <div class="message <?php echo (strpos($message, 'Error') !== false || strpos($message, 'Incorrect') !== false) ? 'message-error' : 'message-success'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <section class="settings-section">
                        <h4>Edit Contact Info</h4>
                        <form action="settings.php" method="POST">
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="mobile_number">Phone Number:</label>
                                <input type="text" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($user['mobile_number']); ?>" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="current_password_contact">Current Password to Confirm:</label>
                                <input type="password" id="current_password_contact" name="current_password" class="form-control" required>
                            </div>
                            <button type="submit" name="edit_details" class="btn btn-primary">Save Changes</button>
                        </form>
                    </section>

                    <hr>

                    <section class="settings-section">
                        <h4>Change Password</h4>
                        <form action="settings.php" method="POST">
                            <div class="form-group">
                                <label for="current_password_change">Current Password:</label>
                                <input type="password" id="current_password_change" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password:</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password:</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-secondary">Change Password</button>
                        </form>
                    </section>

                    <hr>

                    <section class="settings-section delete-account">
                        <h4>Delete Account</h4>
                        <div class="warning-box">
                            <p><strong>Warning:</strong> Deleting your account is a permanent action and cannot be undone. All your data will be erased forever.</p>
                        </div>
                        <form action="settings.php" method="POST">
                            <button type="submit" name="delete_account" class="btn btn-danger">Delete My Account</button>
                        </form>
                    </section>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
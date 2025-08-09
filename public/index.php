<?php
// Start a session to access session variables.
session_start();

// Include the database configuration file.
include '../includes/config.php';

// Redirect to the dashboard if the user is already logged in.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Check if the form was submitted.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];

    // Validate that both fields are not empty.
    if (empty($login_id) || empty($password)) {
        $error = "Both fields are required.";
    } else {
        $sql = "";
        // Check if the input is a valid email address.
        if (filter_var($login_id, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT id, password, 2fa_secret FROM users WHERE email = ?";
        } else {
            // If not an email, assume it's a mobile number.
            $sql = "SELECT id, password, 2fa_secret FROM users WHERE mobile_number = ?";
        }

        // If a valid SQL query was formed, prepare and execute it.
        if (!empty($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $login_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                // Verify the provided password against the hashed password in the database.
                if (password_verify($password, $user['password'])) {
                    
                    // --- 2FA Logic Starts Here ---
                    // Store user data in a temporary session variable before verifying 2FA.
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['temp_2fa_secret'] = $user['2fa_secret'];

                    if (empty($user['2fa_secret'])) {
                        // If 2FA is not set up, redirect to the setup page.
                        header("Location: 2fa_setup.php");
                    } else {
                        // If 2FA is set up, redirect to the verification page.
                        header("Location: 2fa_verify.php");
                    }
                    exit();
                    // --- 2FA Logic Ends Here ---
                    
                } else {
                    $error = "Invalid login credentials.";
                }
            } else {
                $error = "Invalid login credentials.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #007bff 0%, #28a745 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            color: #007bff;
            margin-bottom: 25px;
            font-weight: 700;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        input:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            border: none;
            background: #007bff;
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        p {
            margin-top: 25px;
            color: #6c757d;
        }
        a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        .error-message {
            background-color: #f8d7da;
            color: #dc3545;
            border: 1px solid #f5c6cb;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login to your account</h1>
        
        <?php if ($error): ?>
            <div class="message error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message success-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="login_id">Email or Mobile Number:</label>
                <input type="text" id="login_id" name="login_id" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p>
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
</body>
</html>
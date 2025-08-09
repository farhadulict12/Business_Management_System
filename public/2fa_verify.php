<?php
// Start a session to access session variables.
session_start();

// Include the database configuration file.
include '../includes/config.php';
// Include the Google Authenticator library.
require 'PHPGangsta/GoogleAuthenticator.php';

// Check if the user has a temporary login session and a secret.
// If not, redirect them to the login page.
if (!isset($_SESSION['temp_user_id']) || empty($_SESSION['temp_2fa_secret'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$message = '';

// Check if the 2FA code form was submitted.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['temp_user_id'];
    $secret = $_SESSION['temp_2fa_secret'];
    $code = $_POST['2fa_code'];
    
    $ga = new PHPGangsta_GoogleAuthenticator();
    
    // Verify the provided 2FA code.
    if ($ga->verifyCode($secret, $code)) {
        // If the code is correct, set the final user session and clear temporary sessions.
        $_SESSION['user_id'] = $user_id;
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_2fa_secret']);

        $_SESSION['message'] = "Login successful!";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid 2FA code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification</title>
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
            color: #495057;
        }
        .container {
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
            margin-bottom: 10px;
            font-weight: 700;
        }
        p {
            color: #6c757d;
            margin-bottom: 25px;
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
        input[type="text"] {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Enter 2FA Code</h1>
        <p>Please enter the code from your authenticator app to complete your login.</p>
        
        <?php if ($error): ?>
            <div class="message error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="2fa_verify.php" method="POST">
            <div class="form-group">
                <label for="2fa_code">2FA Code:</label>
                <input type="text" id="2fa_code" name="2fa_code" required>
            </div>
            <button type="submit">Verify</button>
        </form>
        
        <p class="text-center mt-6 text-sm text-gray-600">
            <a href="index.php">Go back to login</a>
        </p>
    </div>
</body>
</html>
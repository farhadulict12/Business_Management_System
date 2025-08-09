<?php
// Start a session to use session variables.
session_start();

// Include the database configuration file.
include '../includes/config.php';

$error = '';
$email = '';
$mobile_number = '';

// Check if the form was submitted.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $mobile_number = trim($_POST['mobile_number']);
    $password = $_POST['password'];

    // Ensure that at least one of the fields (email or mobile number) is provided.
    if (empty($email) && empty($mobile_number)) {
        $error = "Please provide either an email or a mobile number.";
    } elseif (empty($password)) {
        $error = "Password is required.";
    } else {
        // Hash the password for security.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database.
        $sql = "INSERT INTO users (email, mobile_number, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $mobile_number, $hashed_password);

        if ($stmt->execute()) {
            // If registration is successful, set a success message and redirect.
            $_SESSION['message'] = "Registration successful. Please log in.";
            header("Location: index.php");
            exit();
        } else {
            // Check for a specific "Duplicate entry" error.
            if ($stmt->errno == 1062) {
                $error = "An account with this email or mobile number already exists.";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --accent-color: #28a745;
            --bg-color-light: #f8f9fa;
            --bg-color-dark: #343a40;
            --text-color: #495057;
            --border-color: #ced4da;
            --error-color: #dc3545;
            --success-color: #28a745;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: var(--text-color);
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
        h2 {
            color: var(--primary-color);
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
            color: var(--text-color);
        }
        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            border: none;
            background: var(--primary-color);
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
            color: var(--secondary-color);
        }
        a {
            color: var(--primary-color);
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
            color: var(--error-color);
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Register New User</h2>
        <?php if ($error): ?>
            <div class="message error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="mobile_number">Mobile Number:</label>
                <input type="text" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($mobile_number ?? ''); ?>" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="index.php">Log in here</a></p>
    </div>
</body>
</html>
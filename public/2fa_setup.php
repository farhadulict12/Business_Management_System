<?php
// Start a session to access session variables.
session_start();

// Include the database configuration file.
include '../includes/config.php';
// Include the Google Authenticator library.
require 'PHPGangsta/GoogleAuthenticator.php';

// Check if the user has a temporary login session.
// If not, redirect them to the login page.
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['temp_user_id'];
$error = '';
$message = '';
$ga = new PHPGangsta_GoogleAuthenticator();

// Fetch the user's secret from the database.
$stmt = $conn->prepare("SELECT 2fa_secret FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If no secret exists, generate a new one and save it to the database.
if (empty($user['2fa_secret'])) {
    $secret = $ga->createSecret();
    $update_stmt = $conn->prepare("UPDATE users SET 2fa_secret = ? WHERE id = ?");
    $update_stmt->bind_param("si", $secret, $user_id);
    $update_stmt->execute();
    $_SESSION['temp_2fa_secret'] = $secret;
} else {
    // If a secret already exists, use that one.
    $secret = $user['2fa_secret'];
    $_SESSION['temp_2fa_secret'] = $secret;
}

// Generate the QR code URL.
$qrCodeUrl = $ga->getQRCodeGoogleUrl('YourAppName', $secret);

// Handle the verification form submission.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['2fa_code'];

    // Verify the provided code.
    if ($ga->verifyCode($secret, $code)) {
        // If the code is correct, set the final user session and clear temporary sessions.
        $_SESSION['user_id'] = $user_id;
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_2fa_secret']);
        
        $_SESSION['message'] = "2FA setup and login successful!";
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
    <title>2FA Setup</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Set Up Two-Factor Authentication</h2>
        <p>Please scan the QR code with your authenticator app (e.g., Google Authenticator) or enter the secret key manually.</p>
        <?php if ($error): ?>
            <div class="message error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <div style="text-align: center; margin-top: 20px;">
            <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code">
            <p style="margin-top: 10px;">Secret Key: <strong><?php echo $secret; ?></strong></p>
        </div>
        
        <form action="2fa_setup.php" method="POST" style="margin-top: 20px;">
            <div class="form-group">
                <label for="2fa_code">Enter the 6-digit code from your app here:</label>
                <input type="text" id="2fa_code" name="2fa_code" required>
            </div>
            <button type="submit">Verify and Complete Setup</button>
        </form>
    </div>
</body>
</html>

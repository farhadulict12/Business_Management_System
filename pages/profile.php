<?php
// Include the header file first. This handles session_start() and the login check.
include '../includes/header.php';
// At this point, the session is active and we know the user is logged in.
$user_id = $_SESSION['user_id'];

// Include the database configuration file.
// Make sure this file does NOT have session_start() in it.
include '../includes/config.php';

$message = '';

// Fetch user data
$sql_user = "SELECT name, email, profile_pic FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();
$stmt_user->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
    <div class="container">
        <main class="main-content">
            <h1>My Profile</h1>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="profile-info-container">
                <div class="profile-pic-container">
                    <img src="../public/assets/images/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture">
                </div>
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <a href="settings.php" class="button">Go to Settings</a>
            </div>
            
        </main>
    </div>
</body>
</html>
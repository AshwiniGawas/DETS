<?php
session_start();
require_once 'DETS_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: DETS_login_page.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: DETS_login_page.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cloudflare.com">
    <title>User Profile</title>
    <style>
        html, body { height: 100%; margin: 0; font-family: Arial, sans-serif; }
        body { background-image: url("DETS_signup_page_image.jpeg"); background-size: 100% 100%; background-position: center; background-repeat: no-repeat; display: flex; justify-content: center; align-items: center; }
        .profile-card { width: 400px; padding: 25px; border-radius: 15px; background: rgb(255, 255, 255); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2); text-align: center; }
        .avatar { font-size: 4em; color: #ffb347; margin-bottom: 15px; }
        h2 { color: #1a1a1a; margin-bottom: 20px; }
        .info-group { text-align: left; margin-bottom: 15px; background: rgba(0,0,0,0.03); padding: 10px 15px; border-radius: 10px; }
        .info-label { font-size: 0.85em; color: #666; font-weight: bold; }
        .info-value { font-size: 1.1em; color: #333; margin-top: 3px; }
        .btn { display: inline-block; width: 92%; padding: 10px; margin-top: 10px; border-radius: 20px; background: linear-gradient(90deg, #f7c948, #ffb347); color: black; font-weight: bold; text-decoration: none; transition: 0.3s; text-align: center;}
        .btn:hover { background: linear-gradient(90deg, #ffb347, #f7c948); transform: scale(1.03); }
        .logout-link { display: inline-block; margin-top: 15px; color: #ff4d4d; text-decoration: none; font-size: 0.9em; font-weight: bold; }
        .logout-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="profile-card">
    <i class="fa-solid fa-circle-user avatar"></i>
    <h2>Your Profile</h2>

    <div class="info-group">
        <div class="info-label"><i class="fa-solid fa-user"></i> Username</div>
        <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
    </div>

    <div class="info-group">
        <div class="info-label"><i class="fa-solid fa-envelope"></i> Email Address</div>
        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
    </div>

    <div class="info-group">
        <div class="info-label"><i class="fa-solid fa-calendar-days"></i> Account Created</div>
        <div class="info-value"><?php echo htmlspecialchars(date("F j, Y", strtotime($user['created_at']))); ?></div>
    </div>

    <a href="DETS_dashboard.php" class="btn">Go to Dashboard</a>
    <br>
    <a href="DETS_logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

</body>
</html>

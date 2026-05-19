<?php
session_start();
require_once 'DETS_db.php';

/* =========================================
   CHECK LOGIN
========================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: DETTS_login_page.php");
    exit();
}

/* =========================================
   GET CURRENT USER DATA
========================================= */

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT username, email, created_at
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

$stmt->close();

/* =========================================
   IF USER NOT FOUND
========================================= */

if (!$user) {

    session_destroy();

    header("Location: DETS_login_page.php");

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>User Profile</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, sans-serif;
}

body{

    height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

    background-image:url("DETS_signup_page_image.jpeg");

    background-size:100% 100%;
    background-position:center;
    background-repeat:no-repeat;
}

/* PROFILE CARD */

.profile-card{

    width:420px;

    background:white;

    padding:30px;

    border-radius:20px;

    box-shadow:0 10px 25px rgba(0,0,0,0.2);

    text-align:center;
}

/* PROFILE ICON */

.avatar{

    font-size:90px;

    color:#3b82f6;

    margin-bottom:15px;
}

h2{

    color:#1e293b;

    margin-bottom:25px;
}

/* INFO BOX */

.info-group{

    text-align:left;

    background:#f8fafc;

    padding:15px;

    border-radius:12px;

    margin-bottom:15px;
}

.info-label{

    font-size:14px;

    color:#64748b;

    margin-bottom:5px;

    font-weight:bold;
}

.info-value{

    font-size:18px;

    color:#111827;
}

/* BUTTONS */

.btn{

    display:block;

    width:100%;

    padding:12px;

    margin-top:15px;

    border-radius:12px;

    text-decoration:none;

    font-weight:bold;

    transition:0.3s;
}

/* DASHBOARD BUTTON */

.dashboard-btn{

    background:#2563eb;

    color:white;
}

.dashboard-btn:hover{

    background:#1d4ed8;
}

/* LOGOUT BUTTON */

.logout-btn{

    background:#dc2626;

    color:white;
}

.logout-btn:hover{

    background:#b91c1c;
}

/* RESPONSIVE */

@media(max-width:500px){

    .profile-card{

        width:90%;
    }
}

</style>

</head>

<body>

<div class="profile-card">

    <!-- PROFILE ICON -->

    <i class="fa-solid fa-circle-user avatar"></i>

    <h2>
        Welcome,
        <?php echo htmlspecialchars($user['username']); ?>
    </h2>

    <!-- USERNAME -->

    <div class="info-group">

        <div class="info-label">
            <i class="fa-solid fa-user"></i>
            Username
        </div>

        <div class="info-value">

            <?php
            echo htmlspecialchars($user['username']);
            ?>

        </div>

    </div>

    <!-- EMAIL -->

    <div class="info-group">

        <div class="info-label">
            <i class="fa-solid fa-envelope"></i>
            Email Address
        </div>

        <div class="info-value">

            <?php
            echo htmlspecialchars($user['email']);
            ?>

        </div>

    </div>

    <!-- ACCOUNT CREATED -->

    <div class="info-group">

        <div class="info-label">
            <i class="fa-solid fa-calendar-days"></i>
            Account Created
        </div>

        <div class="info-value">

            <?php
            echo date(
                "F j, Y",
                strtotime($user['created_at'])
            );
            ?>

        </div>

    </div>

    <!-- DASHBOARD BUTTON -->

    <a href="DETS_dashboard.php"
       class="btn dashboard-btn">

       <i class="fa-solid fa-chart-line"></i>
       Go To Dashboard

    </a>

    <!-- LOGOUT BUTTON -->

    <a href="DETS_logout.php"
       class="btn logout-btn">

       <i class="fa-solid fa-right-from-bracket"></i>
       Logout

    </a>

</div>

</body>
</html>
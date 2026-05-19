<?php

session_start();
/* REMOVE USER SESSION ONLY */
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
/* STORE POPUP MESSAGE */
$_SESSION['success'] = "Logged Out Successfully!";
/* REDIRECT */
header("Location: DETS_dashboard.php");
exit();

?>
<?php

session_start();

/* CLEAR ALL SESSION DATA */
$_SESSION = [];

/* DESTROY SESSION */
session_destroy();

/* START NEW SESSION FOR MESSAGE */
session_start();
$_SESSION['success'] = "Logged Out Successfully!";

/* REDIRECT TO DASHBOARD */
header("Location: DETS_dashboard.php");

exit();
?>
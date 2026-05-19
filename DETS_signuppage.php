<?php

session_start();

require_once 'DETS_db.php';

/* =========================================
   REDIRECT IF ALREADY LOGGED IN
========================================= */

if(isset($_SESSION['user_id'])){

    header("Location: DETS_dashboard.php");
    exit();
}

/* =========================================
   VARIABLES
========================================= */

$username_val = "";
$email_val    = "";

$errors = [];

/* =========================================
   FORM SUBMIT
========================================= */

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $username_val =
    trim($_POST['username'] ?? '');

    $email_val =
    trim($_POST['email'] ?? '');

    $create_password =
    trim($_POST['create-password'] ?? '');

    $confirm_password =
    trim($_POST['confirm-password'] ?? '');

    /* =========================================
       USERNAME VALIDATION
    ========================================= */

    if(empty($username_val)){

        $errors['username'] =
        "Username is required";
    }
    elseif(strlen($username_val) < 3){

        $errors['username'] =
        "Username must be at least 3 characters";
    }

    /* =========================================
       EMAIL VALIDATION
    ========================================= */

    if(empty($email_val)){

        $errors['email'] =
        "Email is required";
    }
    elseif(!filter_var(
        $email_val,
        FILTER_VALIDATE_EMAIL
    )){

        $errors['email'] =
        "Invalid email format";
    }

    /* =========================================
       PASSWORD VALIDATION
    ========================================= */

    if(empty($create_password)){

        $errors['create-password'] =
        "Password is required";
    }
    elseif(strlen($create_password) < 6){

        $errors['create-password'] =
        "Password must be at least 6 characters";
    }

    /* =========================================
       CONFIRM PASSWORD
    ========================================= */

    if($confirm_password != $create_password){

        $errors['confirm-password'] =
        "Passwords do not match";
    }

    /* =========================================
       CHECK EXISTING USER
    ========================================= */

    if(empty($errors)){

        $checkUser =
        $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            OR email = ?
        ");

        $checkUser->bind_param(
            "ss",
            $username_val,
            $email_val
        );

        $checkUser->execute();

        $checkUser->store_result();

        if($checkUser->num_rows > 0){

            $errors['username'] =
            "Username or Email already exists";

            $checkUser->close();
        }
        else{

            $checkUser->close();

            /* =========================================
               HASH PASSWORD
            ========================================= */

            $hashed_password =
            password_hash(
                $create_password,
                PASSWORD_DEFAULT
            );

            /* =========================================
               INSERT USER
            ========================================= */

            $insert =
            $conn->prepare("
                INSERT INTO users
                (
                    username,
                    email,
                    password
                )
                VALUES
                (
                    ?,
                    ?,
                    ?
                )
            ");

            $insert->bind_param(
                "sss",
                $username_val,
                $email_val,
                $hashed_password
            );

            if($insert->execute()){

                /* =========================================
                   CREATE SESSION
                ========================================= */

                $_SESSION['user_id'] =
                $insert->insert_id;

                $_SESSION['user_name'] =
                $username_val;

                $_SESSION['success'] =
                "Registration Successful!";

                $insert->close();

                header(
                    "Location: DETS_dashboard.php"
                );

                exit();
            }
            else{

                $errors['db'] =
                "Registration failed";

                $insert->close();
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Sign Up</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

html, body {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background-image: url("DETS_signup_page_image.jpeg");
            background-size: 100% 100% ;
            background-position: center;
            background-repeat: no-repeat;

            display: flex;
            justify-content: center;
            align-items: center;
        }

        form {
            width: 400px;
            padding: 25px;
            border-radius: 15px;

            background: rgb(255, 255, 255);
            backdrop-filter: blur(12px);

            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .logo {
        display: block;
        margin: 0 auto 10px auto;
        width: 100px;   
        height: 100px;
}

        h2 {
            text-align: center;
            color: #1a1a1a;
        }

        label {
            color: #333;
            font-weight: 500;
        }

        input {
            width: 95%;
            padding: 10px;
            margin-bottom: 12px;

            border-radius: 20px;
            border: 1px solid rgba(0, 0, 0, 0.2);

            background: rgba(255, 255, 255, 0.73);
            outline: none;
        }

        input:focus {
            border-color: #6a8dff;
            box-shadow: 0 0 5px rgba(106, 141, 255, 0.5);
        }

        .btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 20px;

            background: linear-gradient(90deg, #f7c948, #ffb347);
            color: black;
            font-weight: bold;
            cursor: pointer;

            transition: 0.3s;
        }

        .btn:hover {
            background: linear-gradient(90deg, #ffb347, #f7c948);
            transform: scale(1.03);
        }

        .error {
            color: #ff4d4d;
            font-size: 0.85em;
        }

        a {
            text-decoration: none;
            color: #4a6cff;
            font-weight: 500;
        }

        p {
            text-align: center;
        }

    </style>

</head>

<body>

<form method="POST">

    <img src="DETS_logo_image.jpeg"
         class="logo">

    <h2><b>Daily Expense Tracking System</b></h2>

    <div class="error">

        <?php
        echo $errors['db'] ?? '';
        ?>

    </div>

    <!-- USERNAME -->

    <label>
        <i class="fa-solid fa-user"></i>
        Username
    </label>

    <input
        type="text"
        name="username"

        value="<?php
        echo htmlspecialchars(
            $username_val
        );
        ?>"
    >

    <div class="error">

        <?php
        echo $errors['username'] ?? '';
        ?>

    </div>

    <!-- EMAIL -->

    <label>
        <i class="fa-solid fa-envelope"></i>
        Email
    </label>

    <input
        type="text"
        name="email"

        value="<?php
        echo htmlspecialchars(
            $email_val
        );
        ?>"
    >

    <div class="error">

        <?php
        echo $errors['email'] ?? '';
        ?>

    </div>

    <!-- PASSWORD -->

    <label>
        <i class="fa-solid fa-lock"></i>
        Create Password
    </label>

    <input
        type="password"
        name="create-password"
    >

    <div class="error">

        <?php
        echo $errors['create-password'] ?? '';
        ?>

    </div>

    <!-- CONFIRM PASSWORD -->

    <label>
        <i class="fa-solid fa-lock"></i>
        Confirm Password
    </label>

    <input
        type="password"
        name="confirm-password"
    >

    <div class="error">

        <?php
        echo $errors['confirm-password'] ?? '';
        ?>

    </div>

    <!-- BUTTON -->

    <input
        type="submit"
        value="Sign Up"
        class="btn"
    >

    <p>

        Already have an account?

        <a href="DETS_login_page.php">

            Login

        </a>

    </p>

</form>

</body>

</html>
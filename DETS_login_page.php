<?php
session_start();
require_once 'DETS_db.php';

/* =========================
   REDIRECT IF ALREADY LOGIN
========================= */

if(isset($_SESSION['user_id'])){
    header("Location: DETS_dashboard.php");
    exit();
}

$login_input = "";
$email_val = "";
$errors = [];

/* =========================
   LOGIN FORM SUBMIT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login_input = trim($_POST['username'] ?? '');
    $email_val   = trim($_POST['email'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    /* =========================
       VALIDATION
    ========================= */

    if (empty($login_input)) {
        $errors['username'] = "Username is required";
    }

    if (empty($email_val)) {
        $errors['email'] = "Email is required";
    } 
    elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($password)) {
        $errors['password'] = "Password is required";
    }

    /* =========================
       LOGIN CHECK
    ========================= */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT id, username, password
            FROM users
            WHERE username = ? AND email = ?
        ");

        $stmt->bind_param(
            "ss",
            $login_input,
            $email_val
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {

            if (password_verify($password, $user['password'])) {

                /* =========================
                   STORE SESSION
                ========================= */

                $_SESSION['user_id'] = $user['id'];

                $_SESSION['user_name'] = $user['username'];

                $_SESSION['success'] = "Login Successful!";

                $stmt->close();

                header("Location: DETS_dashboard.php");

                exit();
            }
        }

        $errors['login'] =
        "Invalid username, email, or password.";

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Login</title>

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

    <!-- LOGIN ERROR -->

    <div class="error">
        <?php echo $errors['login'] ?? ''; ?>
    </div>

    <!-- USERNAME -->

    <label>
        <i class="fa-solid fa-user"></i>
        Username
    </label>

    <input
        type="text"
        name="username"
        value="<?php echo htmlspecialchars($login_input); ?>"
    >

    <div class="error">
        <?php echo $errors['username'] ?? ''; ?>
    </div>

    <!-- EMAIL -->

    <label>
        <i class="fa-solid fa-envelope"></i>
        Email
    </label>

    <input
        type="text"
        name="email"
        value="<?php echo htmlspecialchars($email_val); ?>"
    >

    <div class="error">
        <?php echo $errors['email'] ?? ''; ?>
    </div>

    <!-- PASSWORD -->

    <label>
        <i class="fa-solid fa-lock"></i>
        Password
    </label>

    <input
        type="password"
        name="password"
    >

    <div class="error">
        <?php echo $errors['password'] ?? ''; ?>
    </div>

    <!-- LOGIN BUTTON -->

    <input
        class="btn"
        type="submit"
        value="Login"
    >

    <p>
        Don't have an account?

        <a href="DETS_signuppage.php">
            SignUp
        </a>
    </p>

</form>

</body>
</html>
<?php
session_start();
require_once 'DETS_db.php';

$username_val = "";
$email_val = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_val = trim($_POST['username'] ?? '');
    $email_val = trim($_POST['email'] ?? '');
    $create_password = trim($_POST['create-password'] ?? '');
    $confirm_password = trim($_POST['confirm-password'] ?? '');

    if (empty($username_val)) {
        $errors['username'] = "Username is required";
    }

    if (empty($email_val)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (empty($create_password)) {
        $errors['create-password'] = "Password is required";
    } elseif (strlen($create_password) < 6) {
        $errors['create-password'] = "Password must be at least 6 characters long.";
    }

    if ($confirm_password !== $create_password) {
        $errors['confirm-password'] = "Passwords do not match!";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_val, $email_val);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors['username'] = "Username or Email already taken.";
            $stmt->close();
        } else {
            $stmt->close();
            $hashed_password = password_hash($create_password, PASSWORD_DEFAULT);
            
            $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username_val, $email_val, $hashed_password);
            
            if ($insert->execute()) {
                /* STORE SESSION */
                $_SESSION['user_id'] = $insert->insert_id;
                /* STORE USERNAME */
                $_SESSION['user_name'] = $username_val;
                /* SUCCESS POPUP MESSAGE */
                $_SESSION['success'] = "Registration Successful!";
                $insert->close();
                /* REDIRECT */
                header("Location: DETS_dashboard.php");
                exit();
} else {
                $errors['db'] = "Registration failed. Try again.";
                $insert->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <title>SignUp</title>
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
    <img src="DETS_logo_image.jpeg" class="logo">
    <h2><b>Daily Expense Tracking System</b></h2>
    
    <div class="error"><?php echo $errors['db'] ?? '' ?></div>

    <label><i class="fa-solid fa-user"></i> Username:</label><br>
    <input type="text" name="username" value="<?php echo htmlspecialchars($username_val); ?>">
    <div class="error"><?php echo $errors['username'] ?? '' ?></div>

    <label><i class="fa-solid fa-envelope"></i> Email:</label><br>
    <input type="text" name="email" value="<?php echo htmlspecialchars($email_val); ?>">
    <div class="error"><?php echo $errors['email'] ?? '' ?></div>

    <label><i class="fa-solid fa-lock"></i> Create Password:</label><br>
    <input type="password" name="create-password">
    <div class="error"><?php echo $errors['create-password'] ?? '' ?></div>

    <label><i class="fa-solid fa-lock"></i> Confirm Password:</label><br>
    <input type="password" name="confirm-password">
    <div class="error"><?php echo $errors['confirm-password'] ?? '' ?></div>

    <input class="btn" type="submit" value="SignUp">
    <p>Already have an account? <a href="DETS_login_page.php">Login</a></p>
</form>

</body>
</html>
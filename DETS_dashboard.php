<?php

session_start();

include 'DETS_db.php';

/* =========================================
   CHECK LOGIN
========================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: DETS_login_page.php");
    exit();

}

/* =========================================
   USER ID
========================================= */

$user_id = $_SESSION['user_id'];

/* =========================================
   CURRENT MONTH
========================================= */

$currentMonth = date('m');
$currentYear  = date('Y');

/* =========================================
   TOTAL AMOUNT OF CURRENT MONTH
========================================= */

$stmt1 = $conn->prepare("
    SELECT SUM(amount) AS total
    FROM expenses
    WHERE user_id = ?
    AND MONTH(expense_date) = ?
    AND YEAR(expense_date) = ?
");

$stmt1->bind_param("iii", $user_id, $currentMonth, $currentYear);

$stmt1->execute();

$totalExpenseQuery = $stmt1->get_result();

$totalExpense =
$totalExpenseQuery->fetch_assoc()['total'] ?? 0;

/* =========================================
   TOTAL EXPENSE COUNT OF CURRENT MONTH
========================================= */

$stmt2 = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM expenses
    WHERE user_id = ?
    AND MONTH(expense_date) = ?
    AND YEAR(expense_date) = ?
");

$stmt2->bind_param("iii", $user_id, $currentMonth, $currentYear);

$stmt2->execute();

$totalExpensesQuery = $stmt2->get_result();

$totalExpenses =
$totalExpensesQuery->fetch_assoc()['total'] ?? 0;

/* =========================================
   TOTAL CATEGORIES OF CURRENT MONTH
========================================= */

$stmt3 = $conn->prepare("
    SELECT COUNT(DISTINCT category) AS total
    FROM expenses
    WHERE user_id = ?
    AND MONTH(expense_date) = ?
    AND YEAR(expense_date) = ?
");

$stmt3->bind_param("iii", $user_id, $currentMonth, $currentYear);

$stmt3->execute();

$categoriesQuery = $stmt3->get_result();

$categories =
$categoriesQuery->fetch_assoc()['total'] ?? 0;

/* =========================================
   RECENT 10 EXPENSES
========================================= */

$stmt4 = $conn->prepare("
    SELECT *
    FROM expenses
    WHERE user_id = ?
    AND MONTH(expense_date) = ?
    AND YEAR(expense_date) = ?
    ORDER BY id DESC
    LIMIT 10
");

$stmt4->bind_param("iii", $user_id, $currentMonth, $currentYear);

$stmt4->execute();

$latest = $stmt4->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>DETS Dashboard</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', sans-serif;
}

body{
    display:flex;
    background:#f4f7fe;
    min-height:100vh;
}

/* SIDEBAR */

.sidebar{
    width:250px;
    background:#1e293b;
    color:white;
    padding:25px 20px;
    position:fixed;
    height:100%;
}

.sidebar h2{
    text-align:center;
    margin-bottom:40px;
    font-size:28px;
    letter-spacing:1px;
}

.sidebar a{
    display:block;
    color:#cbd5e1;
    text-decoration:none;
    padding:14px 16px;
    margin-bottom:12px;
    border-radius:10px;
    transition:0.3s;
    font-size:16px;
}

.sidebar a i{
    margin-right:10px;
}

.sidebar a:hover{
    background:#3b82f6;
    color:white;
}

/* MAIN CONTENT */

.main{
    margin-left:250px;
    width:calc(100% - 250px);
    padding:30px;
}

/* TOPBAR */

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.topbar h1{
    font-size:32px;
    color:#1e293b;
}

/* USER ACTIONS */

.user-actions{
    display:flex;
    align-items:center;
    gap:12px;
}

.profile-icon{
    font-size:32px;
    text-decoration:none;
    color:#1e293b;
}

.auth-btn{
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
    transition:0.3s;
}

.btn-logout{
    background:#dc2626;
    color:white;
}

.auth-btn:hover{
    opacity:0.85;
}

/* HERO SECTION */

.hero-section{
    background:linear-gradient(135deg,#3b82f6,#6366f1);
    border-radius:24px;
    padding:35px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:35px;
    color:white;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,0.1);
}

.hero-content{
    max-width:55%;
}

.hero-content h2{
    font-size:38px;
    margin-bottom:18px;
    line-height:1.3;
}

.hero-content p{
    font-size:18px;
    opacity:0.95;
    line-height:1.7;
}

.hero-image img{
    width:320px;
    animation:floatImage 3s ease-in-out infinite;
}

@keyframes floatImage{

    0%{
        transform:translateY(0px);
    }

    50%{
        transform:translateY(-10px);
    }

    100%{
        transform:translateY(0px);
    }
}

/* CARDS */

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:35px;
}

.card{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    transition:0.3s;
}

.card:hover{
    transform:translateY(-5px);
}

.card h3{
    color:#64748b;
    font-size:16px;
    margin-bottom:12px;
}

.card h1{
    color:#111827;
    font-size:34px;
}

/* TABLE */

.table-container{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.table-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.table-header h2{
    color:#1e293b;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#eff6ff;
    color:#1e293b;
    padding:14px;
    text-align:left;
}

td{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
}

tr:hover{
    background:#f9fafb;
}

/* CATEGORY BADGE */

.badge{
    background:#dbeafe;
    color:#2563eb;
    padding:6px 12px;
    border-radius:20px;
    font-size:14px;
}

/* NO DATA */

.no-data{
    text-align:center;
    padding:20px;
    color:#64748b;
    font-size:18px;
}

/* POPUP */

.popup{
    position:fixed;
    top:30px;
    right:30px;
    background:#10b981;
    color:white;
    padding:16px 24px;
    border-radius:12px;
    font-weight:600;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
    z-index:9999;
    animation:slideIn 0.5s ease;
}

@keyframes slideIn{

    from{
        transform:translateX(100%);
        opacity:0;
    }

    to{
        transform:translateX(0);
        opacity:1;
    }
}

/* RESPONSIVE */

@media(max-width:768px){

    body{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        height:auto;
        position:relative;
    }

    .main{
        margin-left:0;
        width:100%;
    }

    .hero-section{
        flex-direction:column;
        text-align:center;
    }

    .hero-content{
        max-width:100%;
        margin-bottom:20px;
    }

    .hero-content h2{
        font-size:28px;
    }

    .hero-image img{
        width:220px;
    }

    .topbar{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
    }
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <h2>DETS</h2>

    <a href="DETS_dashboard.php">
        <i class="fa-solid fa-chart-line"></i>
        Dashboard
    </a>

    <a href="DETS_expense_page.php">
        <i class="fa-solid fa-wallet"></i>
        Expenses
    </a>

    <a href="DETS_chart_page.php">
        <i class="fa-solid fa-chart-pie"></i>
        Charts
    </a>

    <a href="DETS_report_page.php">
        <i class="fa-solid fa-file-lines"></i>
        Reports
    </a>

</div>

<!-- MAIN CONTENT -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <h1>
            Dashboard -
            <?php echo date("F Y"); ?>
        </h1>

        <div class="user-actions">

            <a href="DETS_profile_page.php"
               class="profile-icon"
               title="View Profile">

               <i class="fa-solid fa-circle-user"></i>

            </a>

            <a href="DETS_logout.php"
               class="auth-btn btn-logout">

               Logout

            </a>

        </div>

    </div>

    <!-- HERO SECTION -->

    <div class="hero-section">

        <div class="hero-content">

            <h2>
                Welcome,
                <?php echo htmlspecialchars($_SESSION['user_name']); ?> 💰
            </h2>

            <p>
                “A budget is telling your money where to go
                instead of wondering where it went.”
            </p>

        </div>

        <div class="hero-image">

            <img src="eta.png" alt="Expense Tracker">

        </div>

    </div>

    <!-- CARDS -->

    <div class="cards">

        <div class="card">

            <h3>
                Total Expenses This Month
            </h3>

            <h1>
                <?php echo $totalExpenses; ?>
            </h1>

        </div>

        <div class="card">

            <h3>
                Total Amount This Month
            </h3>

            <h1>
                ₹<?php echo number_format($totalExpense,2); ?>
            </h1>

        </div>

        <div class="card">

            <h3>
                Categories This Month
            </h3>

            <h1>
                <?php echo $categories; ?>
            </h1>

        </div>

    </div>

    <!-- RECENT EXPENSES -->

    <div class="table-container">

        <div class="table-header">

            <h2>
                10 Recent Expenses Of Current Month
            </h2>

        </div>

        <table>

            <tr>

                <th>Title</th>
                <th>Amount</th>
                <th>Category</th>
                <th>Date</th>

            </tr>

            <?php if($latest->num_rows > 0) { ?>

            <?php while($row = $latest->fetch_assoc()) { ?>

            <tr>

                <td>
                    <?php
                    echo htmlspecialchars($row['title']);
                    ?>
                </td>

                <td>
                    ₹<?php
                    echo number_format(
                        $row['amount'],
                        2
                    );
                    ?>
                </td>

                <td>

                    <span class="badge">

                    <?php
                    echo htmlspecialchars(
                        $row['category']
                    );
                    ?>

                    </span>

                </td>

                <td>
                    <?php
                    echo $row['expense_date'];
                    ?>
                </td>

            </tr>

            <?php } ?>

            <?php } else { ?>

            <tr>

                <td colspan="4"
                class="no-data">

                    No Expenses Found

                </td>

            </tr>

            <?php } ?>

        </table>

    </div>

</div>

<!-- POPUP -->

<?php if(isset($_SESSION['success'])): ?>

<div id="popup" class="popup">

    <?php

    echo $_SESSION['success'];

    unset($_SESSION['success']);

    ?>

</div>

<?php endif; ?>

<script>

setTimeout(() => {

    const popup =
    document.getElementById("popup");

    if(popup){

        popup.style.display = "none";
    }

},3000);

</script>

</body>
</html>
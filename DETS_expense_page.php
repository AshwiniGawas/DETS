<?php
session_start();

include 'DETS_db.php';

// ADD EXPENSE
if(isset($_POST['add'])) {

    $title = $_POST['title'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['expense_date'];

    $sql = "INSERT INTO expenses(title, amount, category, expense_date)
            VALUES('$title','$amount','$category','$date')";

    $conn->query($sql);
    /* SUCCESS POPUP */
    $_SESSION['success'] = "Expense Added Successfully!";
    header("Location: DETS_expense_page.php");
    exit();
    }

// DELETE EXPENSE
if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $conn->query("DELETE FROM expenses WHERE id=$id");
    /* SUCCESS POPUP */
    $_SESSION['success'] = "Expense Deleted Successfully!";
    header("Location: DETS_expense_page.php");
    exit();
    }

// UPDATE EXPENSE
if(isset($_POST['update'])) {

    $id = $_POST['id'];
    $title = $_POST['title'];
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['expense_date'];

    $sql = "UPDATE expenses SET
            title='$title',
            amount='$amount',
            category='$category',
            expense_date='$date'
            WHERE id=$id";

    $conn->query($sql);

    header("Location:DETS_expense_page.php");
    exit();
}

// SET BUDGET
if(isset($_POST['set_budget'])) {

    $budget = $_POST['budget'];

    $conn->query("DELETE FROM budgets");

    $conn->query("INSERT INTO budgets(monthly_budget)
                VALUES('$budget')");
    /* SUCCESS POPUP */
$_SESSION['success'] = "Budget Saved Successfully!";
}

/* CURRENT MONTH */

$currentMonth = date('m');
$currentYear  = date('Y');

/* GET CURRENT MONTHLY BUDGET */

$budgetData = $conn->query("
    SELECT monthly_budget
    FROM budgets
    ORDER BY id DESC
    LIMIT 1
");

$budgetRow = $budgetData->fetch_assoc();

$currentBudget = $budgetRow['monthly_budget'] ?? 0;

/* GET CURRENT MONTH TOTAL EXPENSE */

$totalData = $conn->query("
    SELECT SUM(amount) AS total
    FROM expenses
    WHERE MONTH(expense_date) = '$currentMonth'
    AND YEAR(expense_date) = '$currentYear'
");

$totalRow = $totalData->fetch_assoc();

$totalExpense = $totalRow['total'] ?? 0;

/* REMAINING BALANCE */

$remaining = $currentBudget - $totalExpense;

// FETCH EXPENSES
$result = $conn->query("SELECT * FROM expenses ORDER BY expense_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Expense Tracker</title>

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

/* MAIN */

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

.user-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    }

.profile-icon {
    font-size: 32px;
    text-decoration: none;
    }

.auth-btn {
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    }

.btn-login {
    background-color: transparent;
    border: 1px solid #007bff;
    color: #007bff;
    }

.btn-register {
    background-color: #007bff;
    border: 1px solid #007bff;
    color: white;
    }

.btn-logout {
    background-color: #dc3545;
    border: 1px solid #dc3545;
    color: white;
    }

.auth-btn:hover {
    opacity: 0.85;
    }

/* BUDGET CARDS */

.budget-cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.budget-card{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.budget-card h3{
    color:#64748b;
    margin-bottom:10px;
}

.budget-card h1{
    font-size:30px;
    color:#111827;
}

.danger{
    background:#fee2e2;
    color:#dc2626;
    padding:16px 20px;
    border-left:6px solid #ef4444;
    border-radius:12px;
    margin-bottom:25px;
    font-weight:600;
    font-size:16px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

/* FORM */

.form-container{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
    margin-bottom:30px;
}

.form-container h2{
    margin-bottom:20px;
    color:#1e293b;
}

.form-grid{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}

.form-grid input{
    width:220px;
    height:50px;
}

.budget-form input{
    width:180px;
}

.form-grid button{
    width:auto;
    min-width:140px;
}

button{
    background:#3b82f6;
    color:white;
    border:none;
    padding:14px;
    border-radius:10px;
    cursor:pointer;
    font-size:15px;
    font-weight:600;
    transition:0.3s;
}

button:hover{
    background:#2563eb;
}

/* TABLE */

.table-container{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.table-container h2{
    margin-bottom:20px;
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

.category-badge{
    background:#dbeafe;
    color:#2563eb;
    padding:6px 12px;
    border-radius:20px;
    font-size:14px;
}

.delete-btn{
    background:#ef4444;
    color:white;
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
}

.delete-btn:hover{
    background:#dc2626;
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

    .topbar{
        flex-direction:column;
        gap:15px;
        align-items:flex-start;
    }
}

/* POPUP OVERLAY */

#popup-overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.45);
    display:flex;
    justify-content:center;
    align-items:center;
    z-index:9999;
    animation:fadeIn 0.4s ease;
}

/* POPUP BOX */

#popup-box{
    width:260px;
    background:white;
    border-radius:18px;
    padding:20px 18px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,0.25);
    animation:popupScale 0.5s ease;
}

/* IMAGE */

.popup-image{
    width:60px;
    margin-bottom:15px;
    animation:bounce 1.5s infinite;
}

/* TITLE */

.popup-title{
    color:#10b981;
    font-size:22px;
    margin-bottom:10px;
}

/* MESSAGE */

.popup-message{
    font-size:14px;
    color:#374151;
    line-height:1.6;
}

/* ANIMATIONS */

@keyframes popupScale{

    0%{
        transform:scale(0.5);
        opacity:0;
    }

    100%{
        transform:scale(1);
        opacity:1;
    }
}

@keyframes bounce{

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

@keyframes fadeIn{

    from{
        opacity:0;
    }

    to{
        opacity:1;
    }
}

</style>
</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

    <h2>DETS</h2>

    <a href="DETS_dashboard.php">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>

    <a href="DETS_expense_page.php">
        <i class="fa-solid fa-wallet"></i> Expenses
    </a>

    <a href="DETS_chart_page.php">
        <i class="fa-solid fa-chart-pie"></i> Charts
    </a>

    <a href="DETS_report_page.php">
        <i class="fa-solid fa-file-lines"></i> Reports
    </a>

</div>

<!-- MAIN -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <h1>Expense Tracker</h1>

        <div class="user-actions">
        <!-- Conditional Display Based on Login Status -->
        <?php if(isset($_SESSION['user_id'])): ?>
            <!-- Shown ONLY when user is logged in -->
            <a href="DETS_profile_page.php" class="profile-icon" title="View Profile"><i class="fa-solid fa-circle-user"></i></a>
            <a href="DETS_logout.php" class="auth-btn btn-logout">Logout</a>
        <?php else: ?>
            <!-- Shown ONLY when user is guest / logged out -->
            <a href="DETS_login_page.php" class="auth-btn btn-login">Login</a>
            <a href="DETS_signuppage.php" class="auth-btn btn-register">Register</a>
        <?php endif; ?>
        </div>

    </div>

    <!-- BUDGET SECTION -->

    <div class="budget-cards">

        <div class="budget-card">
            <h3>Monthly Budget</h3>
            <h1>₹<?php echo number_format($currentBudget,2); ?></h1>
        </div>

        <div class="budget-card">
            <h3>Monthly Expenses</h3>
            <h1>₹<?php echo number_format($totalExpense,2); ?></h1>
        </div>

        <div class="budget-card">
            <h3>Remaining Monthly Balance</h3>
            <h1>₹<?php echo number_format($remaining,2); ?></h1>
        </div>

    </div>

    <!-- BUDGET FORM -->

    <div class="form-container">

    <h2>Set Monthly Budget</h2>

    <form method="POST" class="budget-form">

        <div class="form-grid">

            <input type="number"
                step="0.01"
                name="budget"
                placeholder="Enter Budget"
                required>

            <button type="submit" name="set_budget">
                Save Budget
            </button>

        </div>

        </form>

    </div>

    <!-- BUDGET WARNING -->

    <?php if($remaining < 0){ ?>

    <div class="danger">

        ⚠ Budget Limit Exceeded!
        Your expenses are higher than your monthly budget.

    </div>

<?php } ?>

    <!-- ADD EXPENSE -->

    <div class="form-container">

    <h2>Add Expense</h2>

    <form method="POST">

        <div class="form-grid">

            <input type="text"
                name="title"
                placeholder="Expense Title"
                required>

            <input type="number"
                step="0.01"
                name="amount"
                placeholder="Amount"
                required>

            <input type="text"
                name="category"
                placeholder="Category"
                required>

            <input type="date"
                name="expense_date"
                required>

            <button type="submit" name="add">
                Add Expense
            </button>

        </div>

    </form>

</div>

    <!-- TABLE -->

    <div class="table-container">

        <h2>Expense List</h2>

        <table>

            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Amount</th>
                <th>Category</th>
                <th>Date</th>
                <th>Action</th>
            </tr>

            <?php while($row = $result->fetch_assoc()) { ?>

            <tr>

                <td><?php echo $row['id']; ?></td>

                <td>
                    <?php echo htmlspecialchars($row['title']); ?>
                </td>

                <td>
                    ₹<?php echo number_format($row['amount'],2); ?>
                </td>

                <td>
                    <span class="category-badge">
                        <?php echo htmlspecialchars($row['category']); ?>
                    </span>
                </td>

                <td><?php echo $row['expense_date']; ?></td>

                <td>

                    <a class="delete-btn"
                    href="DETS_expense_page.php?delete=<?php echo $row['id']; ?>">

                    Delete

                    </a>

                </td>

            </tr>

            <?php } ?>

        </table>

    </div>

</div>

<!-- POPUP MESSAGE -->

<?php if(isset($_SESSION['success'])): ?>

<div id="popup-overlay">

    <div id="popup-box">

        <img src="https://cdn-icons-png.flaticon.com/512/845/845646.png"
            class="popup-image">

        <h2 class="popup-title">
            Success
        </h2>

        <p class="popup-message">

            <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>

        </p>

    </div>

</div>

<?php endif; ?>

<script>

setTimeout(() => {

    const popup = document.getElementById("popup-overlay");

    if(popup){

        popup.style.opacity = "0";

        setTimeout(() => {

            popup.style.display = "none";

        }, 500);
    }

}, 2500);

</script>

</body>
</html>
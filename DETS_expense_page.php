<?php
session_start();
include 'DETS_db.php';

/* CHECK LOGIN */

if (!isset($_SESSION['user_id'])) {
    header("Location: DETS_login_page.php");
    exit();
}
$user_id = $_SESSION['user_id'];

/* ADD EXPENSE */

if(isset($_POST['add'])) {
    $title = trim($_POST['title']);
    $amount = trim($_POST['amount']);
    $category = trim($_POST['category']);
    $date = trim($_POST['expense_date']);

    /* VALIDATION */

    if(empty($title)){
        $_SESSION['error'] =
        "Expense title cannot be empty!";
        header("Location: DETS_expense_page.php");
        exit();
    }

    if(empty($category)){
        $_SESSION['error'] =
        "Category cannot be empty!";
        header("Location: DETS_expense_page.php");
        exit();
    }

    if($amount <= 0){
        $_SESSION['error'] ="Invalid Amount!";
        header("Location: DETS_expense_page.php");
        exit();
    }

    if($date > date('Y-m-d')){
        $_SESSION['error'] = "Future dates are not allowed!";
        header("Location: DETS_expense_page.php");
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO expenses
        (user_id, title, amount, category, expense_date)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isdss",
        $user_id,
        $title,
        $amount,
        $category,
        $date
    );

    if($stmt->execute()) {
        $_SESSION['success'] =
        "Expense Added Successfully!";
    }

    $stmt->close();
    header("Location: DETS_expense_page.php");
    exit();
}

/* DELETE EXPENSE */

if(isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM expenses
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $id,
        $user_id
    );

    if($stmt->execute()) {
        $_SESSION['success'] = "Expense Deleted Successfully!";
    }
    $stmt->close();
    header("Location: DETS_expense_page.php");
    exit();
}

/* SET BUDGET */

if(isset($_POST['set_budget'])) {
    $budget = trim($_POST['budget']);

    /* VALIDATION */

    if($budget <= 0){
        $_SESSION['error'] = "Invalid Budget Amount!";
        header("Location: DETS_expense_page.php");
        exit();
    }

    /* DELETE OLD BUDGET */

    $deleteStmt = $conn->prepare("
        DELETE FROM budgets
        WHERE user_id = ?
    ");

    $deleteStmt->bind_param(
        "i",
        $user_id
    );

    $deleteStmt->execute();
    $deleteStmt->close();

    /* INSERT NEW BUDGET */

    $insertStmt = $conn->prepare("
        INSERT INTO budgets
        (user_id, monthly_budget)
        VALUES (?, ?)
    ");

    $insertStmt->bind_param(
        "id",
        $user_id,
        $budget
    );

    if($insertStmt->execute()) {
        $_SESSION['success'] =
        "Budget Added Successfully!";
    }
    $insertStmt->close();
    header("Location: DETS_expense_page.php");
    exit();
}

/* CURRENT MONTH */

$currentMonth = date('m');
$currentYear = date('Y');

/* GET USER BUDGET */

$budgetStmt = $conn->prepare("
    SELECT monthly_budget
    FROM budgets
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$budgetStmt->bind_param("i", $user_id);
$budgetStmt->execute();

$budgetResult = $budgetStmt->get_result();
$budgetRow = $budgetResult->fetch_assoc();
$currentBudget = $budgetRow['monthly_budget'] ?? 0;

$budgetStmt->close();

/* GET TOTAL EXPENSE */

$totalStmt = $conn->prepare("
    SELECT SUM(amount) AS total
    FROM expenses
    WHERE user_id = ?
    AND MONTH(expense_date) = ?
    AND YEAR(expense_date) = ?
");

$totalStmt->bind_param(
    "iii",
    $user_id,
    $currentMonth,
    $currentYear
);

$totalStmt->execute();

$totalResult =
$totalStmt->get_result();

$totalRow =
$totalResult->fetch_assoc();

$totalExpense =
$totalRow['total'] ?? 0;

$totalStmt->close();

/* REMAINING BALANCE */

$remaining =
$currentBudget - $totalExpense;

/* FETCH EXPENSES */

$expenseStmt = $conn->prepare("
    SELECT *
    FROM expenses
    WHERE user_id = ?
    ORDER BY expense_date DESC
");

$expenseStmt->bind_param("i", $user_id);
$expenseStmt->execute();

$result =
$expenseStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">
<title>Expense Tracker</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
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
}

.sidebar a{
    display:block;
    color:#cbd5e1;
    text-decoration:none;
    padding:14px 16px;
    margin-bottom:12px;
    border-radius:10px;
    transition:0.3s;
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
    padding:10px 16px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
}

.btn-logout{
    background:#ef4444;
    color:white;
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

/* WARNING */

.danger{
    background:#fee2e2;
    color:#dc2626;
    padding:16px;
    border-radius:12px;
    margin-bottom:25px;
    font-weight:600;
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
    gap:12px;
    flex-wrap:wrap;
}

.form-grid input{
    padding:12px;
    border:1px solid #d1d5db;
    border-radius:10px;
    min-width:220px;
}

button{
    background:#3b82f6;
    color:white;
    border:none;
    padding:12px 20px;
    border-radius:10px;
    cursor:pointer;
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
    overflow-x:auto;
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

/* POPUP */

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
}

#popup-box{
    width:260px;
    background:white;
    border-radius:16px;
    padding:20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,0.2);
}

.popup-icon{
    font-size:50px;
    margin-bottom:10px;
}

.success-icon{
    color:#10b981;
}

.error-icon{
    color:#ef4444;
}

.popup-title{
    font-size:22px;
    margin-bottom:8px;
}

.popup-message{
    font-size:15px;
    color:#374151;
}

/* RESPONSIVE */

@media(max-width:768px){
    body{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        position:relative;
        height:auto;
    }

    .main{
        margin-left:0;
        width:100%;
    }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>DETS</h2>
    <a href="DETS_dashboard.php">  <i class="fa-solid fa-chart-line"></i> Dashboard </a>
    <a href="DETS_expense_page.php"> <i class="fa-solid fa-wallet"></i> Expenses </a>
    <a href="DETS_chart_page.php"> <i class="fa-solid fa-chart-pie"></i> Charts </a>
    <a href="DETS_report_page.php"> <i class="fa-solid fa-file-lines"></i> Reports </a>
</div>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <h1>Expense Tracker</h1>
        <div class="user-actions">
            <a href="DETS_profile_page.php" class="profile-icon"> <i class="fa-solid fa-circle-user"></i> </a>
            <a href="DETS_logout.php" class="auth-btn btn-logout"> Logout </a>
        </div>
    </div>

    <!-- BUDGET CARDS -->
    <div class="budget-cards">
        <div class="budget-card"> <h3>Monthly Budget</h3>
            <h1>₹<?php echo number_format($currentBudget,2); ?> </h1>
        </div>
        <div class="budget-card"><h3>Monthly Expenses</h3> <h1>₹<?php echo number_format($totalExpense,2); ?></h1></div>
        <div class="budget-card">
            <h3>Remaining Balance</h3>
            <h1>₹<?php echo number_format($remaining,2); ?> </h1>
        </div>
    </div>

    <!-- WARNING -->
    <?php if($remaining < 0){ ?> <div class="danger"> ⚠ Budget Exceeded! </div> <?php } ?>

    <!-- SET BUDGET -->
    <div class="form-container">
        <h2>Set Monthly Budget</h2>
        <form method="POST">
            <div class="form-grid">
                <input type="number"
                       step="0.01"
                       name="budget"
                       placeholder="Enter Budget"
                       required>
                <button type="submit" name="set_budget"> Save Budget </button>
            </div>
        </form>
    </div>

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
                       max="<?php echo date('Y-m-d'); ?>"
                       required>

                <button type="submit" name="add"> Add Expense </button>
            </div>
        </form>
    </div>

    <!-- EXPENSE TABLE -->
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
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td> ₹<?php echo number_format($row['amount'],2); ?></td>

                <td>
                    <span class="category-badge">
                    <?php echo htmlspecialchars($row['category']); ?>
                    </span>
                </td>

                <td> <?php echo $row['expense_date']; ?> </td>
                <td>
                    <a class="delete-btn" href="DETS_expense_page.php?delete=<?php echo $row['id']; ?>"
                       onclick="return confirm('Delete this expense?')"> Delete </a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

<!-- SUCCESS POPUP -->
<?php if(isset($_SESSION['success'])): ?>
<div id="popup-overlay">
    <div id="popup-box">
        <div class="popup-icon success-icon"> <i class="fa-solid fa-circle-check"></i> </div>
        <h2 class="popup-title"> Success </h2>
        <p class="popup-message">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </p>
    </div>
</div>
<?php endif; ?>

<!-- ERROR POPUP -->
<?php if(isset($_SESSION['error'])): ?>
<div id="popup-overlay">
    <div id="popup-box">
        <div class="popup-icon error-icon"> <i class="fa-solid fa-circle-xmark"></i> </div>
        <h2 class="popup-title">Error</h2>
        <p class="popup-message">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </p>
    </div>
</div>
<?php endif; ?>

<script>

setTimeout(() => {
    const popup =
    document.getElementById("popup-overlay");
    if(popup) { popup.style.display = "none"; }
},2500);

</script>
</body>
</html>

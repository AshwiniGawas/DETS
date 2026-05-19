<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'DETS_db.php';

/* =========================
   REPORT TYPE
========================= */

$reportType = $_GET['report_type'] ?? 'monthly';

$selectedMonth = $_GET['month'] ?? date('m');

$selectedWeek = $_GET['week'] ?? 1;

/* =========================
   GET ALL CATEGORIES
========================= */

$categoryQuery = $conn->query("
    SELECT DISTINCT category
    FROM expenses
");

$categories = [];

while($cat = $categoryQuery->fetch_assoc()){

    $categories[] = $cat['category'];
}

/* =========================
   WEEK CONDITIONS
========================= */

if($selectedWeek == 1){

    $weekCondition = "DAY(expense_date) BETWEEN 1 AND 7";
}
elseif($selectedWeek == 2){

    $weekCondition = "DAY(expense_date) BETWEEN 8 AND 14";
}
elseif($selectedWeek == 3){

    $weekCondition = "DAY(expense_date) BETWEEN 15 AND 21";
}
else{

    $weekCondition = "DAY(expense_date) BETWEEN 22 AND 31";
}

/* =========================
   VARIABLES
========================= */

$highestCategory = "";
$highestAmount = 0;

$monthlyData = [];
$monthlyTotals = [];

$weeklyData = [];
$weeklyTotals = [];

$weekTotals = [];

$weekDays = [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday"
];

/* =========================
   MONTHLY REPORT
========================= */

if($reportType == "monthly"){

    for($week=1;$week<=4;$week++){

        if($week == 1){
            $condition = "DAY(expense_date) BETWEEN 1 AND 7";
        }
        elseif($week == 2){
            $condition = "DAY(expense_date) BETWEEN 8 AND 14";
        }
        elseif($week == 3){
            $condition = "DAY(expense_date) BETWEEN 15 AND 21";
        }
        else{
            $condition = "DAY(expense_date) BETWEEN 22 AND 31";
        }

        foreach($categories as $category){

            $query = $conn->query("
                SELECT SUM(amount) AS total
                FROM expenses
                WHERE MONTH(expense_date) = '$selectedMonth'
                AND $condition
                AND category = '$category'
            ");

            $row = $query->fetch_assoc();

            $amount = $row['total'] ?? 0;

            $monthlyData[$week][$category] = $amount;

            if(!isset($monthlyTotals[$category])){
                $monthlyTotals[$category] = 0;
            }

            $monthlyTotals[$category] += $amount;
        }

        $weekTotals[$week] =
        array_sum($monthlyData[$week]);
    }

    /* HIGHEST CATEGORY */

    $highestQuery = $conn->query("
        SELECT category,
        SUM(amount) AS total
        FROM expenses
        WHERE MONTH(expense_date) = '$selectedMonth'
        GROUP BY category
        ORDER BY total DESC
        LIMIT 1
    ");

    if($highestQuery->num_rows > 0){

        $highest = $highestQuery->fetch_assoc();

        $highestCategory = $highest['category'];
        $highestAmount = $highest['total'];
    }
}

/* =========================
   WEEKLY REPORT
========================= */

if($reportType == "weekly"){

    foreach($weekDays as $day){

        foreach($categories as $category){

            $query = $conn->query("
                SELECT SUM(amount) AS total
                FROM expenses
                WHERE MONTH(expense_date) = '$selectedMonth'
                AND $weekCondition
                AND DAYNAME(expense_date) = '$day'
                AND category = '$category'
            ");

            $row = $query->fetch_assoc();

            $amount = $row['total'] ?? 0;

            $weeklyData[$day][$category] = $amount;

            if(!isset($weeklyTotals[$category])){
                $weeklyTotals[$category] = 0;
            }

            $weeklyTotals[$category] += $amount;
        }
    }

    /* HIGHEST CATEGORY */

    $highestQuery = $conn->query("
        SELECT category,
        SUM(amount) AS total
        FROM expenses
        WHERE MONTH(expense_date) = '$selectedMonth'
        AND $weekCondition
        GROUP BY category
        ORDER BY total DESC
        LIMIT 1
    ");

    if($highestQuery->num_rows > 0){

        $highest = $highestQuery->fetch_assoc();

        $highestCategory = $highest['category'];
        $highestAmount = $highest['total'];
    }
}

/* =========================
   INSIGHTS
========================= */

$insights = [];

if($highestCategory != ""){

    $insights[] =
    "You spent most on $highestCategory.";
}

if(!empty($weekTotals)){

    $highestWeek =
    array_keys($weekTotals,max($weekTotals))[0];

    $insights[] =
    "Week $highestWeek had the highest expenses.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Expense Reports</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
    transition:0.3s;
}

.btn-login{
    background:white;
    border:1px solid #3b82f6;
    color:#3b82f6;
}

.btn-register{
    background:#3b82f6;
    border:1px solid #3b82f6;
    color:white;
}

.btn-logout{
    background:#ef4444;
    border:1px solid #ef4444;
    color:white;
}

.auth-btn:hover{
    opacity:0.85;
}

/* FILTER */

.filter-box{
    background:white;
    padding:25px;
    border-radius:18px;
    margin-bottom:30px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.filter-grid{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    align-items:center;
}

select{
    padding:12px;
    border-radius:10px;
    border:1px solid #d1d5db;
    min-width:180px;
}

button{
    background:#3b82f6;
    color:white;
    border:none;
    padding:12px 20px;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
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

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#eff6ff;
    color:#1e293b;
    padding:14px;
    text-align:center;
}

td{
    padding:14px;
    border-bottom:1px solid #e5e7eb;
    text-align:center;
}

tr:hover{
    background:#f9fafb;
}

.total-row{
    background:#dbeafe;
    font-weight:bold;
}

/* HEATMAP */

.low{
    background:#dcfce7;
}

.medium{
    background:#fde68a;
}

.high{
    background:#fecaca;
}

/* HIGHEST CATEGORY COLUMN */

.highlight-column{
    background:#bfdbfe !important;
    font-weight:bold;
}

/* INSIGHTS */

.insight-box{
    margin-top:25px;
    background:white;
    padding:20px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.insight-box h3{
    margin-bottom:15px;
}

.insight-item{
    background:#eff6ff;
    padding:14px;
    border-left:5px solid #3b82f6;
    margin-bottom:12px;
    border-radius:10px;
    font-weight:600;
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

    .topbar{
        flex-direction:column;
        gap:15px;
        align-items:flex-start;
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

<!-- MAIN -->

<div class="main">

<div class="topbar">

<h1>Expense Reports</h1>

<div class="user-actions">

<?php if(isset($_SESSION['user_id'])): ?>

<a href="DETS_profile_page.php"
class="profile-icon">

<i class="fa-solid fa-circle-user"></i>

</a>

<a href="DETS_logout.php"
class="auth-btn btn-logout">

Logout

</a>

<?php else: ?>

<a href="DETS_login_page.php"
class="auth-btn btn-login">

Login

</a>

<a href="DETS_signuppage.php"
class="auth-btn btn-register">

Register

</a>

<?php endif; ?>

</div>

</div>

<!-- FILTER -->

<div class="filter-box">

<form method="GET">

<div class="filter-grid">

<select name="report_type"
onchange="toggleWeek(this.value)">

<option value="monthly"
<?php if($reportType=="monthly") echo "selected"; ?>>
Monthly Report
</option>

<option value="weekly"
<?php if($reportType=="weekly") echo "selected"; ?>>
Weekly Report
</option>

</select>

<select name="month">

<?php for($m=1;$m<=12;$m++){ ?>

<option value="<?php echo $m; ?>"
<?php if($selectedMonth==$m) echo "selected"; ?>>

<?php echo date("F",mktime(0,0,0,$m,1)); ?>

</option>

<?php } ?>

</select>

<select name="week"
id="weekBox"
<?php
if($reportType=="monthly")
echo "style='display:none;'";
?>>

<option value="1">Week 1</option>
<option value="2">Week 2</option>
<option value="3">Week 3</option>
<option value="4">Week 4</option>

</select>

<button type="submit">
Generate Report
</button>

</div>

</form>

</div>

<!-- TABLE -->

<div class="table-container">

<table>

<tr>

<th>

<?php
echo ($reportType=="monthly")
? "Week"
: "Day";
?>

</th>

<?php foreach($categories as $category){ ?>

<th class="<?php
if($category==$highestCategory)
echo 'highlight-column';
?>">

<?php echo $category; ?>

</th>

<?php } ?>

<th class="total-row">Total</th>

</tr>

<!-- MONTHLY REPORT -->

<?php if($reportType=="monthly"){ ?>

<?php for($week=1;$week<=4;$week++){ ?>

<tr>

<td>Week <?php echo $week; ?></td>

<?php

$rowTotal = 0;

foreach($categories as $category){

$amount =
$monthlyData[$week][$category];

$rowTotal += $amount;

$class = "low";

if($amount > 5000){
$class = "high";
}
elseif($amount > 2000){
$class = "medium";
}

?>

<td class="<?php echo $class; ?> <?php
if($category==$highestCategory)
echo 'highlight-column';
?>">

₹<?php echo number_format($amount,2); ?>

</td>

<?php } ?>

<td class="total-row">

₹<?php echo number_format($rowTotal,2); ?>

</td>

</tr>

<?php } ?>

<?php } ?>

<!-- WEEKLY REPORT -->

<?php if($reportType=="weekly"){ ?>

<?php foreach($weekDays as $day){ ?>

<tr>

<td><?php echo $day; ?></td>

<?php

$rowTotal = 0;

foreach($categories as $category){

$amount =
$weeklyData[$day][$category];

$rowTotal += $amount;

$class = "low";

if($amount > 3000){
$class = "high";
}
elseif($amount > 1000){
$class = "medium";
}

?>

<td class="<?php echo $class; ?> <?php
if($category==$highestCategory)
echo 'highlight-column';
?>">

₹<?php echo number_format($amount,2); ?>

</td>

<?php } ?>

<td class="total-row">

₹<?php echo number_format($rowTotal,2); ?>

</td>

</tr>

<?php } ?>

<?php } ?>

<!-- TOTAL ROW -->

<tr class="total-row">

<td>Total</td>

<?php

$grandTotal = 0;

foreach($categories as $category){

$total =
($reportType=="monthly")
? $monthlyTotals[$category]
: $weeklyTotals[$category];

$grandTotal += $total;

?>

<td>

₹<?php echo number_format($total,2); ?>

</td>

<?php } ?>

<td>

₹<?php echo number_format($grandTotal,2); ?>

</td>

</tr>

</table>

</div>

<!-- INSIGHTS -->

<div class="insight-box">

<h3>

<i class="fa-solid fa-lightbulb"></i>
Expense Insights

</h3>

<?php foreach($insights as $msg){ ?>

<div class="insight-item">

<?php echo $msg; ?>

</div>

<?php } ?>

</div>

</div>

<script>

function toggleWeek(value){

let weekBox =
document.getElementById('weekBox');

if(value == "weekly"){

weekBox.style.display = "block";

}else{

weekBox.style.display = "none";
}
}

</script>

</body>
</html>
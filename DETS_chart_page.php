<?php
session_start();

include 'DETS_db.php';

$activeTab = "totalTab";

if(isset($_GET['month'])){
    $activeTab = "monthlyTab";
}

if(isset($_GET['week_month']) || isset($_GET['week'])){
    $activeTab = "weeklyTab";
}

/* TOTAL EXPENSE CHART DATA */

$totalChart = $conn->query("
    SELECT category, SUM(amount) AS total
    FROM expenses
    GROUP BY category
");

$totalCategories = [];
$totalAmounts = [];

while($row = $totalChart->fetch_assoc()){

    $totalCategories[] = $row['category'];
    $totalAmounts[] = $row['total'];
}

/* MONTHLY FILTER */

$selectedMonth = $_GET['month'] ?? date('m');

/* MONTHLY CHART DATA */

$monthlyChart = $conn->query("
    SELECT category, SUM(amount) AS total
    FROM expenses
    WHERE MONTH(expense_date) = '$selectedMonth'
    GROUP BY category
");

$monthlyCategories = [];
$monthlyAmounts = [];

while($row = $monthlyChart->fetch_assoc()){

    $monthlyCategories[] = $row['category'];
    $monthlyAmounts[] = $row['total'];
}

/* WEEKLY FILTER */

$selectedWeekMonth =
$_GET['week_month'] ?? date('m');

$selectedWeek =
$_GET['week'] ?? 1;

/* WEEK CONDITIONS */

if($selectedWeek == 1){

    $condition =
    "DAY(expense_date) BETWEEN 1 AND 7";

}
elseif($selectedWeek == 2){

    $condition =
    "DAY(expense_date) BETWEEN 8 AND 14";

}
elseif($selectedWeek == 3){

    $condition =
    "DAY(expense_date) BETWEEN 15 AND 21";

}
else{

    $condition =
    "DAY(expense_date) BETWEEN 22 AND 31";
}

/* WEEKLY CHART DATA */

$weeklyChart = $conn->query("
    SELECT category, SUM(amount) AS total
    FROM expenses
    WHERE MONTH(expense_date) = '$selectedWeekMonth'
    AND $condition
    GROUP BY category
");

$weeklyCategories = [];
$weeklyAmounts = [];

while($row = $weeklyChart->fetch_assoc()){

    $weeklyCategories[] = $row['category'];
    $weeklyAmounts[] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Expense Charts</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    padding:14px;
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

/* TABS */

.tabs{
    display:flex;
    gap:15px;
    margin-bottom:30px;
}

.tab-btn{
    padding:12px 24px;
    border:none;
    border-radius:10px;
    background:#dbeafe;
    color:#1e293b;
    font-weight:600;
    cursor:pointer;
    transition:0.3s;
}

.tab-btn.active{
    background:#3b82f6;
    color:white;
}

/* TAB CONTENT */

.tab-content{
    display:none;
}

.tab-content.active{
    display:block;
}

/* FILTERS */

.filter-box{
    background:white;
    padding:20px;
    border-radius:18px;
    margin-bottom:25px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.filter-box form{
    display:flex;
    gap:15px;
    align-items:center;
    flex-wrap:wrap;
}

select{
    padding:10px 14px;
    border-radius:10px;
    border:1px solid #ccc;
}

button{
    background:#3b82f6;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    cursor:pointer;
}

/* CHART GRID */

.chart-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(450px,1fr));
    gap:25px;
}

.chart-card{
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.chart-card h2{
    margin-bottom:20px;
    color:#1e293b;
}

.chart-container{
    height:350px;
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

    .chart-grid{
        grid-template-columns:1fr;
    }

    .tabs{
        flex-direction:column;
    }
}

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

    <div class="topbar">
        <h1>Expense Charts</h1>
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

    <!-- TABS -->

    <div class="tabs">

    <button class="tab-btn <?php if($activeTab=='totalTab') echo 'active'; ?>"
    onclick="showTab('totalTab',this)">
    Total Expense Charts
    </button>

    <button class="tab-btn <?php if($activeTab=='monthlyTab') echo 'active'; ?>"
    onclick="showTab('monthlyTab',this)">
    Monthly Expense Charts
    </button>

    <button class="tab-btn <?php if($activeTab=='weeklyTab') echo 'active'; ?>"
    onclick="showTab('weeklyTab',this)">
    Weekly Expense Charts
    </button>

    </div>

    <!-- =========================
        TOTAL TAB
    ========================== -->

    <div id="totalTab" class="tab-content <?php if($activeTab=='totalTab') echo 'active'; ?>">

        <div class="chart-grid">

            <div class="chart-card">
                <h2>Total Expense Pie Chart</h2>
                <div class="chart-container">
                    <canvas id="totalPie"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2>Total Expense Bar Chart</h2>
                <div class="chart-container">
                    <canvas id="totalBar"></canvas>
                </div>
            </div>

        </div>

    </div>

    <!-- =========================
         MONTHLY TAB
    ========================== -->

    <div id="monthlyTab" class="tab-content <?php if($activeTab=='monthlyTab') echo 'active'; ?>">
        <div class="filter-box">

            <form method="GET">

                <select name="month">

                    <?php
                    for($m=1;$m<=12;$m++){
                    ?>

                    <option value="<?php echo $m; ?>"
                    <?php if($selectedMonth==$m) echo "selected"; ?>>

                    <?php echo date("F", mktime(0,0,0,$m,1)); ?>

                    </option>

                    <?php } ?>

                </select>

                <button type="submit">
                    Show Monthly Charts
                </button>

            </form>

        </div>

        <div class="chart-grid">

            <div class="chart-card">
                <h2>Monthly Pie Chart</h2>
                <div class="chart-container">
                    <canvas id="monthlyPie"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2>Monthly Bar Chart</h2>
                <div class="chart-container">
                    <canvas id="monthlyBar"></canvas>
                </div>
            </div>

        </div>

    </div>

    <!-- =========================
        WEEKLY TAB
    ========================== -->

    <div id="weeklyTab" class="tab-content <?php if($activeTab=='weeklyTab') echo 'active'; ?>">
        <div class="filter-box">

            <form method="GET">

                <select name="week_month">

                    <?php
                    for($m=1;$m<=12;$m++){
                    ?>

                    <option value="<?php echo $m; ?>"
                    <?php if($selectedWeekMonth==$m) echo "selected"; ?>>

                    <?php echo date("F", mktime(0,0,0,$m,1)); ?>

                    </option>

                    <?php } ?>

                </select>

                <select name="week">

                    <option value="1">Week 1</option>
                    <option value="2">Week 2</option>
                    <option value="3">Week 3</option>
                    <option value="4">Week 4</option>

                </select>

                <button type="submit">
                    Show Weekly Charts
                </button>

            </form>

        </div>

        <div class="chart-grid">

            <div class="chart-card">
                <h2>Weekly Pie Chart</h2>
                <div class="chart-container">
                    <canvas id="weeklyPie"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h2>Weekly Bar Chart</h2>
                <div class="chart-container">
                    <canvas id="weeklyBar"></canvas>
                </div>
            </div>

        </div>

    </div>

</div>

<script>

/* TAB FUNCTION */

function showTab(tabId,btn){

    document.querySelectorAll('.tab-content')
    .forEach(tab => tab.classList.remove('active'));

    document.querySelectorAll('.tab-btn')
    .forEach(button => button.classList.remove('active'));

    document.getElementById(tabId)
    .classList.add('active');

    btn.classList.add('active');
}

/* COMMON COLORS */

const colors = [
    '#3b82f6',
    '#10b981',
    '#f59e0b',
    '#ef4444',
    '#8b5cf6',
    '#14b8a6'
];

/* TOTAL */

const totalLabels =
<?php echo json_encode($totalCategories); ?>;

const totalData =
<?php echo json_encode($totalAmounts); ?>;

/* MONTHLY */

const monthlyLabels =
<?php echo json_encode($monthlyCategories); ?>;

const monthlyData =
<?php echo json_encode($monthlyAmounts); ?>;

/* WEEKLY */

const weeklyLabels =
<?php echo json_encode($weeklyCategories); ?>;

const weeklyData =
<?php echo json_encode($weeklyAmounts); ?>;

/* CREATE CHART */

function createPie(id,labels,data){

    new Chart(document.getElementById(id),{

        type:'pie',

        data:{
            labels:labels,
            datasets:[{
                data:data,
                backgroundColor:colors
            }]
        },

        options:{
            responsive:true,
            maintainAspectRatio:false
        }
    });
}

function createBar(id,labels,data){

    new Chart(document.getElementById(id),{

        type:'bar',

        data:{
            labels:labels,
            datasets:[{
                label:'Expense Amount',
                data:data,
                backgroundColor:'#3b82f6',
                borderRadius:8
            }]
        },

        options:{
            responsive:true,
            maintainAspectRatio:false,
            scales:{
                y:{
                    beginAtZero:true
                }
            }
        }
    });
}

/* TOTAL */

createPie('totalPie',totalLabels,totalData);
createBar('totalBar',totalLabels,totalData);

/* MONTHLY */

createPie('monthlyPie',monthlyLabels,monthlyData);
createBar('monthlyBar',monthlyLabels,monthlyData);

/* WEEKLY */

createPie('weeklyPie',weeklyLabels,weeklyData);
createBar('weeklyBar',weeklyLabels,weeklyData);

</script>

</body>
</html>
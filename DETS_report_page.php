<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'DETS_db.php';

if (!isset($_SESSION['user_id'])) {

    header("Location: DETS_login_page.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$reportType = $_GET['report_type'] ?? 'monthly';

$selectedMonth = $_GET['month'] ?? date('m');

$selectedYear = date('Y');

$selectedWeek = $_GET['week'] ?? 1;

$categories = [];

$categoryStmt = $conn->prepare("
    SELECT DISTINCT category
    FROM expenses
    WHERE user_id = ?
    ORDER BY category ASC
");

$categoryStmt->bind_param("i", $user_id);

$categoryStmt->execute();

$categoryResult = $categoryStmt->get_result();

while($cat = $categoryResult->fetch_assoc()){

    $categories[] = $cat['category'];
}

$categoryStmt->close();

switch($selectedWeek){

    case 1:
        $weekCondition =
        "DAY(expense_date) BETWEEN 1 AND 7";
        break;

    case 2:
        $weekCondition =
        "DAY(expense_date) BETWEEN 8 AND 14";
        break;

    case 3:
        $weekCondition =
        "DAY(expense_date) BETWEEN 15 AND 21";
        break;

    default:
        $weekCondition =
        "DAY(expense_date) BETWEEN 22 AND 31";
        break;
}

$highestCategory = "";
$highestAmount = 0;

$highestRow = "";

$monthlyData = [];
$monthlyTotals = [];

$weeklyData = [];
$weeklyTotals = [];

$weekTotals = [];

$dayTotals = [];

$insights = [];

$weekDays = [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday"
];

foreach($categories as $category){

    $monthlyTotals[$category] = 0;
    $weeklyTotals[$category] = 0;
}

if($reportType == "monthly"){

    for($week = 1; $week <= 4; $week++){

        switch($week){

            case 1:
                $condition =
                "DAY(expense_date) BETWEEN 1 AND 7";
                break;

            case 2:
                $condition =
                "DAY(expense_date) BETWEEN 8 AND 14";
                break;

            case 3:
                $condition =
                "DAY(expense_date) BETWEEN 15 AND 21";
                break;

            default:
                $condition =
                "DAY(expense_date) BETWEEN 22 AND 31";
                break;
        }

        $monthlyData[$week] = [];

        foreach($categories as $category){

            $stmt = $conn->prepare("
                SELECT SUM(amount) AS total
                FROM expenses
                WHERE user_id = ?
                AND MONTH(expense_date) = ?
                AND YEAR(expense_date) = ?
                AND $condition
                AND category = ?
            ");

            $stmt->bind_param(
                "iiis",
                $user_id,
                $selectedMonth,
                $selectedYear,
                $category
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $row = $result->fetch_assoc();

            $amount = $row['total'] ?? 0;

            $monthlyData[$week][$category] = $amount;

            $monthlyTotals[$category] += $amount;

            $stmt->close();
        }

        $weekTotals[$week] =
        array_sum($monthlyData[$week]);
    }

    if(!empty($weekTotals)){

        $maxWeekAmount = max($weekTotals);

        if($maxWeekAmount > 0){

            $highestRow =
            array_keys(
                $weekTotals,
                $maxWeekAmount
            )[0];
        }
    }

    $highestStmt = $conn->prepare("
        SELECT category,
        SUM(amount) AS total
        FROM expenses
        WHERE user_id = ?
        AND MONTH(expense_date) = ?
        AND YEAR(expense_date) = ?
        GROUP BY category
        ORDER BY total DESC
        LIMIT 1
    ");

    $highestStmt->bind_param(
        "iii",
        $user_id,
        $selectedMonth,
        $selectedYear
    );

    $highestStmt->execute();

    $highestResult =
    $highestStmt->get_result();

    if($highestResult->num_rows > 0){

        $highest =
        $highestResult->fetch_assoc();

        $highestCategory =
        $highest['category'];

        $highestAmount =
        $highest['total'];
    }

    $highestStmt->close();
}

if($reportType == "weekly"){

    foreach($weekDays as $day){

        $weeklyData[$day] = [];

        foreach($categories as $category){

            $stmt = $conn->prepare("
                SELECT SUM(amount) AS total
                FROM expenses
                WHERE user_id = ?
                AND MONTH(expense_date) = ?
                AND YEAR(expense_date) = ?
                AND $weekCondition
                AND DAYNAME(expense_date) = ?
                AND category = ?
            ");

            $stmt->bind_param(
                "iiiss",
                $user_id,
                $selectedMonth,
                $selectedYear,
                $day,
                $category
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $row = $result->fetch_assoc();

            $amount = $row['total'] ?? 0;

            $weeklyData[$day][$category] = $amount;

            $weeklyTotals[$category] += $amount;

            $stmt->close();
        }

        $dayTotals[$day] =
        array_sum($weeklyData[$day]);
    }

    if(!empty($dayTotals)){

        $maxDayAmount = max($dayTotals);

        if($maxDayAmount > 0){

            $highestRow =
            array_keys(
                $dayTotals,
                $maxDayAmount
            )[0];
        }
    }

    $highestStmt = $conn->prepare("
        SELECT category,
        SUM(amount) AS total
        FROM expenses
        WHERE user_id = ?
        AND MONTH(expense_date) = ?
        AND YEAR(expense_date) = ?
        AND $weekCondition
        GROUP BY category
        ORDER BY total DESC
        LIMIT 1
    ");

    $highestStmt->bind_param(
        "iii",
        $user_id,
        $selectedMonth,
        $selectedYear
    );

    $highestStmt->execute();

    $highestResult =
    $highestStmt->get_result();

    if($highestResult->num_rows > 0){

        $highest =
        $highestResult->fetch_assoc();

        $highestCategory =
        $highest['category'];

        $highestAmount =
        $highest['total'];
    }

    $highestStmt->close();
}

$hasExpenses = false;

if($reportType == "monthly"){

    foreach($monthlyTotals as $total){

        if($total > 0){

            $hasExpenses = true;
            break;
        }
    }
}

if($reportType == "weekly"){

    foreach($weeklyTotals as $total){

        if($total > 0){

            $hasExpenses = true;
            break;
        }
    }
}

if($hasExpenses){

    if($highestCategory != ""){

        $insights[] =
        "You spent the most on " .
        $highestCategory .
        " (₹" .
        number_format($highestAmount,2) .
        ").";
    }

    if($reportType == "monthly" && $highestRow != ""){

        $insights[] =
        "Week " .
        $highestRow .
        " had the highest expenses.";
    }

    if($reportType == "weekly" && $highestRow != ""){

        $insights[] =
        $highestRow .
        " had the highest expenses.";
    }
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
href="DETS_style.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>

.highlight-column{
    background:#bfdbfe !important;
    color:#1e293b;
    font-weight:bold;
}

.highlight-row td{
    background:#dbeafe !important;
    font-weight:bold;
}

.low{
    background:#dcfce7;
}

.medium{
    background:#fde68a;
}

.high{
    background:#fecaca;
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

    <!-- TOPBAR -->

    <div class="topbar">

        <h1>Expense Reports</h1>

        <div class="user-actions">

            <a href="DETS_profile_page.php"
               class="profile-icon">

               <i class="fa-solid fa-circle-user"></i>

            </a>

            <a href="DETS_logout.php"
               class="auth-btn btn-logout">

               Logout

            </a>

        </div>

    </div>

    <!-- FILTER -->

    <div class="form-container">

        <h2>Generate Report</h2>

        <form method="GET">

            <div class="form-grid">

                <select name="report_type"
                        onchange="toggleWeek(this.value)">

                    <option value="monthly"
                    <?php
                    if($reportType=="monthly")
                    echo "selected";
                    ?>>
                    Monthly Report
                    </option>

                    <option value="weekly"
                    <?php
                    if($reportType=="weekly")
                    echo "selected";
                    ?>>
                    Weekly Report
                    </option>

                </select>

                <select name="month">

                    <?php for($m=1;$m<=12;$m++){ ?>

                    <option value="<?php echo $m; ?>"
                    <?php
                    if($selectedMonth==$m)
                    echo "selected";
                    ?>>

                    <?php
                    echo date(
                        "F",
                        mktime(0,0,0,$m,1)
                    );
                    ?>

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

    <!-- NO DATA -->

    <?php if(empty($categories)){ ?>

    <div class="danger">

        No expenses found.
        Add expenses first to view reports.

    </div>

    <?php } ?>

    <!-- REPORT TABLE -->

    <?php if(!empty($categories)){ ?>

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

                    <?php
                    echo htmlspecialchars($category);
                    ?>

                </th>

                <?php } ?>

                <th class="total-row">
                    Total
                </th>

            </tr>

            <!-- MONTHLY REPORT -->

            <?php if($reportType=="monthly"){ ?>

            <?php for($week=1;$week<=4;$week++){ ?>

            <tr class="<?php
            if($highestRow == $week)
            echo 'highlight-row';
            ?>">

                <td>

                    Week <?php echo $week; ?>

                </td>

                <?php

                $rowTotal = 0;

                foreach($categories as $category){

                    $amount =
                    $monthlyData[$week][$category] ?? 0;

                    $rowTotal += $amount;

                    $class = "low";

                    if($amount > 5000){

                        $class = "high";

                    }elseif($amount > 2000){

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

            <tr class="<?php
            if($highestRow == $day)
            echo 'highlight-row';
            ?>">

                <td>

                    <?php echo $day; ?>

                </td>

                <?php

                $rowTotal = 0;

                foreach($categories as $category){

                    $amount =
                    $weeklyData[$day][$category] ?? 0;

                    $rowTotal += $amount;

                    $class = "low";

                    if($amount > 3000){

                        $class = "high";

                    }elseif($amount > 1000){

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
                    ? ($monthlyTotals[$category] ?? 0)
                    : ($weeklyTotals[$category] ?? 0);

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

    <?php } ?>

    <!-- INSIGHTS -->

    <div class="insight-box">

        <h3>

            <i class="fa-solid fa-lightbulb"></i>
            Expense Insights

        </h3>

        <?php if(!empty($insights)){ ?>

            <?php foreach($insights as $msg){ ?>

            <div class="insight-item">

                <?php echo $msg; ?>

            </div>

            <?php } ?>

        <?php } else { ?>

            <div class="insight-item">

                No expense insights available yet.

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

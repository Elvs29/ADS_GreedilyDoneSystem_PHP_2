<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get Month and Year for Calendar
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Date logic
$first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
$number_of_days = date('t', $first_day_of_month);
$date_components = getdate($first_day_of_month);
$month_name = $date_components['month'];
$day_of_week = $date_components['wday']; // 0 (Sunday) to 6 (Saturday)

// Prev/Next month logic
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month == 0) {
    $prev_month = 12;
    $prev_year--;
}
$next_month = $month + 1;
$next_year = $year;
if ($next_month == 13) {
    $next_month = 1;
    $next_year++;
}

// FETCH TASKS FOR THIS MONTH
$start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($number_of_days, 2, '0', STR_PAD_LEFT);

try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND status = 'Pending' AND due_date BETWEEN ? AND ? ORDER BY due_date ASC, FIELD(priority, 'High', 'Medium', 'Low') ASC");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the absolute next task for the banner (Greedy Suggestion)
    $stmt_suggest = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND status = 'Pending' ORDER BY due_date ASC, FIELD(priority, 'High', 'Medium', 'Low') ASC LIMIT 1");
    $stmt_suggest->execute([$user_id]);
    $suggested_task = $stmt_suggest->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching tasks: " . $e->getMessage());
}

// Map tasks to days
$calendar_tasks = [];
foreach ($tasks as $task) {
    $d = date('j', strtotime($task['due_date']));
    $calendar_tasks[$d][] = $task;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Calendar | GreedilyDone</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="dashboard-body">

    <aside class="sidebar">
        <div class="brand">
            <img src="GD_LOGO.png" alt="Logo" class="brand-logo-img">
            <h2 class="brand-text"><span>Greedily</span>Done</h2>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i> Overview</a>
            <a href="task.php" class="nav-item"><i class="fa-solid fa-list-check"></i> My Tasks</a>
            <a href="schedule.php" class="nav-item active"><i class="fa-solid fa-calendar-days"></i> Schedule</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-note-sticky"></i> Notes</a>
            
            <div class="nav-divider"></div>
            
            <a href="#" class="nav-item"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="logout.php" class="nav-item logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-area">
        <header class="top-header">
            <div class="welcome-text">
                <h1>Visual Schedule</h1>
                <p>Plan your month with the Greedy Strategy.</p>
            </div>
            
            <div class="header-actions">
                <button class="btn-primary" onclick="window.location.href='task.php'">
                    <i class="fa-solid fa-plus"></i> New Task
                </button>
            </div>
        </header>

        <!-- Greedy Suggestion Banner -->
        <?php if ($suggested_task): ?>
        <div class="greedy-suggestion-card" style="max-width: 100%;">
            <div class="greedy-icon-box">
                <i class="fa-solid fa-bullseye"></i>
            </div>
            <div class="greedy-content">
                <h3>Recommended Next Priority</h3>
                <p><?php echo htmlspecialchars($suggested_task['title']); ?> (Due: <?php echo date('M d', strtotime($suggested_task['due_date'])); ?>)</p>
            </div>
            <div class="greedy-badge">
                <i class="fa-solid fa-bolt"></i> Earliest Deadline
            </div>
        </div>
        <?php endif; ?>

        <div class="calendar-wrapper">
            <div class="calendar-header">
                <h2><?php echo $month_name . " " . $year; ?></h2>
                <div class="calendar-nav">
                    <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="action-btn"><i class="fa-solid fa-chevron-left"></i></a>
                    <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn-save" style="padding: 5px 15px; text-decoration: none; font-size: 0.8rem;">Today</a>
                    <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="action-btn"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="calendar-grid">
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>

                <?php
                // Empty cells before the first day
                for ($i = 0; $i < $day_of_week; $i++) {
                    echo '<div class="calendar-cell other-month"></div>';
                }

                // Days of the month
                for ($current_day = 1; $current_day <= $number_of_days; $current_day++) {
                    $is_today = ($current_day == date('j') && $month == date('n') && $year == date('Y'));
                    echo '<div class="calendar-cell ' . ($is_today ? 'today' : '') . '">';
                    echo '<span class="cell-date">' . $current_day . '</span>';
                    
                    if (isset($calendar_tasks[$current_day])) {
                        echo '<div class="calendar-tasks">';
                        foreach ($calendar_tasks[$current_day] as $task) {
                            $p_class = strtolower($task['priority']);
                            echo '<div class="calendar-task-item ' . $p_class . '" title="' . htmlspecialchars($task['title']) . '">';
                            echo htmlspecialchars($task['title']);
                            echo '</div>';
                        }
                        if (count($calendar_tasks[$current_day]) > 1) {
                            echo '<div class="conflict-dot" title="Multiple tasks scheduled"></div>';
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }

                // Empty cells after the last day
                $total_cells = $day_of_week + $number_of_days;
                $remaining_cells = (7 - ($total_cells % 7)) % 7;
                for ($i = 0; $i < $remaining_cells; $i++) {
                    echo '<div class="calendar-cell other-month"></div>';
                }
                ?>
            </div>
        </div>
    </main>

</body>
</html>

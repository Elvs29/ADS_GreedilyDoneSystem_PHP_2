<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Calendar Logic
$month = date('m');
$year = date('Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day = date('w', strtotime("$year-$month-01")); // 0 (Sun) to 6 (Sat)
$month_name = date('F Y');

// Safe greeting logic
$full_name = $_SESSION['full_name'] ?? 'User';
$first_name = explode(' ', trim($full_name))[0];
$user_id = $_SESSION['user_id'];

// --- DATABASE QUERIES ---
try {
    // 1. Fetch Stats
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total, 
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
    FROM tasks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_tasks = $stats['total'] ?? 0;
    $completed_tasks = $stats['completed'] ?? 0;
    $pending_tasks = $stats['pending'] ?? 0;
    $completion_rate = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100) : 0;

    // 2. Due Soon Tasks (Next 48 hours)
    $stmt = $pdo->prepare("SELECT * FROM tasks 
        WHERE user_id = ? AND status = 'Pending' 
        AND due_date <= DATE_ADD(NOW(), INTERVAL 2 DAY) 
        ORDER BY due_date ASC LIMIT 2");
    $stmt->execute([$user_id]);
    $due_soon = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Activity Log (Fetch from the new activity_log table)
    $stmt = $pdo->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
    $stmt->execute([$user_id]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Pinned Tasks (Max 3)
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND is_pinned = 1 LIMIT 3");
    $stmt->execute([$user_id]);
    $pinned_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// HANDLE TASK ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_task'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    
    $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, category, due_date, priority) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $category, $due_date, $priority]);

    // Log Activity
    $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, task_title) VALUES (?, 'Added', ?)");
    $log->execute([$user_id, $title]);

    header("Location: dashboard.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | GreedilyDone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="dashboard-style.css">
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <img src="GD_LOGO.png" alt="Logo" class="brand-logo-img">
            <h2 class="brand-text"><span>Greedily</span>Done</h2>
        </div>
        
        <nav class="nav-menu">
            <a href="#" class="nav-item active"><i class="fa-solid fa-house"></i> Overview</a>
            <a href="task.php" class="nav-item"><i class="fa-solid fa-list-check"></i> My Tasks</a>
            <a href="schedule.php" class="nav-item"><i class="fa-solid fa-calendar-days"></i> Schedule</a>
            <a href="#" class="nav-item"><i class="fa-solid fa-note-sticky"></i> Notes</a>
            
            <div class="nav-divider"></div>
            
            <a href="#" class="nav-item"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="logout.php" class="nav-item logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-area">
        <header class="top-header">
            <div class="welcome-text">
                <h1>Hello, <?php echo htmlspecialchars($first_name); ?>!</h1>
                <p>Ready to crush your goals today?</p>
            </div>
            
            <div class="header-actions">
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="taskSearch" placeholder="Search tasks, schedules...">
                </div> 

                <button class="btn-primary">
                    <i class="fa-solid fa-plus"></i> New Task
                </button>

                <div class="notification-wrapper">
                    <i class="fa-solid fa-bell"></i>
                    <span class="notification-dot"></span>
                </div>
            </div>
        </header>

        <div class="stats-horizontal">
            <div class="stat-card-h card-completed">
                <div class="stat-content">
                    <span class="stat-label">Tasks Completed</span>
                    <h2 class="stat-number"><?php echo str_pad($completed_tasks, 2, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>

            <div class="stat-card-h card-new">
                <div class="stat-content">
                    <span class="stat-label">Pending Tasks</span>
                    <h2 class="stat-number"><?php echo str_pad($pending_tasks, 2, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            </div>

            <div class="stat-card-h card-projects">
                <div class="stat-content">
                    <span class="stat-label">Total Tasks</span>
                    <h2 class="stat-number"><?php echo str_pad($total_tasks, 2, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <!-- MAIN CONTENT AREA (LEFT) -->
            <div class="main-tasks-section">
                <div class="urgent-tasks-wrapper">
                    <h3 class="sub-title"><i class="fa-solid fa-clock-rotate-left"></i> Due Soon</h3>
                    <div class="urgent-cards-container">
                        <?php if (empty($due_soon)): ?>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-left: 10px;">No urgent tasks.</p>
                        <?php else: ?>
                            <?php foreach ($due_soon as $task): ?>
                                <?php 
                                    $diff = strtotime($task['due_date']) - time();
                                    $hours_left = floor($diff / 3600);
                                    $urgent_label = ($hours_left < 24) ? 'Urgent' : 'Soon';
                                ?>
                                <div class="urgent-card <?php echo strtolower($task['category']); ?>">
                                    <div class="urgent-info">
                                        <span class="tag"><?php echo $task['category']; ?></span>
                                        <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                                        <p><?php echo ($hours_left > 0) ? "In $hours_left Hours" : "Past Due"; ?></p>
                                    </div>
                                    <div class="urgent-status <?php echo strtolower($urgent_label); ?>"><?php echo $urgent_label; ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity in the old Task List position -->
                <div class="task-board-card">
                    <div class="board-header">
                        <h2 class="section-title"><i class="fa-solid fa-bolt" style="color: #f1c40f; margin-right: 10px;"></i> Recent Activity</h2>
                        <span class="task-count">Latest History</span>
                    </div>

                    <div class="activity-log-main" style="padding: 10px 0;">
                        <?php if (empty($recent_activity)): ?>
                            <p style="padding: 20px; text-align: center; color: var(--text-muted);">No activity recorded yet.</p>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <?php 
                                    $color = 'var(--primary-green)';
                                    
                                    if ($activity['action'] == 'Completed') {
                                        $color = '#2ecc71';
                                    } elseif ($activity['action'] == 'Deleted') {
                                        $color = '#e74c3c';
                                    } elseif ($activity['action'] == 'Reopened') {
                                        $color = '#f39c12';
                                    }
                                ?>
                                <div class="activity-item-main" style="display: flex; align-items: flex-start; gap: 15px; padding: 18px 25px; border-bottom: 1px solid #f8f9fa; transition: 0.2s;">
                                    <div class="activity-details">
                                        <p style="font-weight: 600; font-size: 0.95rem; color: var(--text-dark); margin-bottom: 4px;">
                                            <span style="color: <?php echo $color; ?>; font-weight: 700;"><?php echo $activity['action']; ?>:</span> 
                                            <?php echo htmlspecialchars($activity['task_title']); ?>
                                        </p>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <span style="font-size: 11px; color: var(--text-muted);"><i class="fa-regular fa-clock"></i> <?php echo date('M d, h:i A', strtotime($activity['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR AREA (RIGHT) -->
            <aside class="side-panel">
                <!-- 1. Quick Pinned at the top -->
                <div class="side-card">
                    <h3 style="font-size: 16px; margin-bottom: 15px;"><i class="fa-solid fa-thumbtack"></i> Quick Pinned</h3>
                    <div class="pinned-tasks-container" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php if (!empty($pinned_tasks)): ?>
                            <?php foreach ($pinned_tasks as $pinned): ?>
                                <div class="mini-card" style="background: var(--white); padding: 10px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #eee;">
                                    <p style="font-size: 13px; margin: 0; font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($pinned['title']); ?></p>
                                    <i class="fa-solid fa-thumbtack" style="color: var(--primary-green); font-size: 12px;"></i>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-align: center; padding: 10px;">No tasks pinned.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Calendar in the middle -->
                <div class="calendar-section card"> 
                    <div class="calendar-header">
                        <h4><?php echo $month_name; ?></h4>
                        <div class="cal-nav">
                            <i class="fa-solid fa-chevron-left"></i> <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </div>
                    <div class="calendar-days">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div class="calendar-dates">
                        <?php
                        for ($i = 0; $i < $first_day; $i++) { echo '<span class="empty"></span>'; }
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $today = (date('j') == $day) ? 'active' : '';
                            echo "<span class='$today'>$day</span>";
                        }
                        ?>
                    </div>
                </div>

                <!-- 3. Daily Progress at the bottom -->
                <div class="side-card progress-card">
                    <h3 style="font-size: 16px; margin-bottom: 15px;">Daily Progress</h3>
                    <div class="circular-progress" style="width: 100px; height: 100px; border-radius: 50%; background: conic-gradient(var(--primary-green) <?php echo ($completion_rate * 3.6); ?>deg, #f0f0f0 0deg); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                        <span style="font-weight: bold; font-size: 18px; color: var(--primary-green);"><?php echo $completion_rate; ?>%</span>
                    </div>
                    <p style="text-align: center; color: #718093; font-size: 13px;"><?php echo $completed_tasks; ?> out of <?php echo $total_tasks; ?> tasks done</p>
                </div>
            </aside>

        </div> 
    </main>

    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-circle-plus"></i> Create New Task</h2>
                <span class="close-btn">&times;</span>
            </div>
            <form action="dashboard.php" method="POST">
                <div class="input-group">
                    <label>Task Name</label>
                    <input type="text" name="title" placeholder="e.g. Study for Database Exam" required>
                </div>
                
                <div class="input-row">
                    <div class="input-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="Academic">Academic</option>
                            <option value="Business">Business</option>
                            <option value="Office">Office</option>
                            <option value="Personal">Personal</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Deadline</label>
                        <input type="date" name="due_date" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Priority Level</label>
                    <select name="priority" class="priority-select" required>
                        <option value="High">High Priority</option>
                        <option value="Medium" selected>Medium Priority</option>
                        <option value="Low">Low Priority</option>
                    </select>
                </div>

                <button type="submit" name="add_task" class="btn-save">Save Task</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content confirmation-modal">
            <div class="modal-header">
                <h2><i class="fa-solid fa-circle-exclamation" style="color: #d63031;"></i> Confirm Delete</h2>
                <span class="close-delete">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this task? This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="btn-cancel" id="cancelDelete" style="flex: 1; padding: 10px; border-radius: 8px; border: 1.5px solid #eee; background: #fff; cursor: pointer;">Cancel</button>
                <button class="btn-delete-confirm" id="confirmDelete" style="flex: 1; padding: 10px; border-radius: 8px; border: none; background: #d63031; color: white; font-weight: 600; cursor: pointer;">Delete Task</button>
            </div>
        </div>
    </div>

    <script>
    // Search Filtering
    document.getElementById('taskSearch').addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const activities = document.querySelectorAll('.activity-item-main');
        activities.forEach(item => {
            const text = item.innerText.toLowerCase();
            item.style.display = text.includes(filter) ? 'flex' : 'none';
        });
    });

    // Modal Control
    const modal = document.getElementById("taskModal");
    const newTaskBtn = document.querySelector(".btn-primary");
    const closeBtn = document.querySelector(".close-btn");

    newTaskBtn.onclick = () => modal.style.display = "block";
    closeBtn.onclick = () => modal.style.display = "none";
    window.onclick = (event) => { if (event.target == modal) modal.style.display = "none"; }
    </script>
</body>
</html>
<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// KUNIN ANG MGA TASKS MULA SA DATABASE
try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY status DESC, due_date ASC, FIELD(priority, 'High', 'Medium', 'Low') ASC");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching tasks: " . $e->getMessage());
}

// HANDLE TASK ADDITION (Temporary logic in same file or fix add_task.php later)
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

    header("Location: task.php");
    exit;
}

// GREEDY LOGIC: Find the most urgent pending task
$suggested_task = null;
$pending_tasks_list = array_filter($tasks, fn($t) => $t['status'] == 'Pending');
if (!empty($pending_tasks_list)) {
    $suggested_task = reset($pending_tasks_list);
}

// Calculate Stats for UI consistency
$total_count = count($tasks);
$pending_count = count($pending_tasks_list);
$completed_count = count(array_filter($tasks, fn($t) => $t['status'] == 'Completed'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <a href="task.php" class="nav-item active"><i class="fa-solid fa-list-check"></i> My Tasks</a>
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
                <h1>Manage Your Tasks</h1>
                <p>Stay organized and crush your daily goals.</p>
            </div>
            
            <div class="header-actions">
                <button class="btn-primary" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> New Task
                </button>
            </div>
        </header>

        <!-- Task Stats Overview -->
        <div class="stats-horizontal">
            <div class="stat-card-h card-completed">
                <div class="stat-content">
                    <span class="stat-label">Tasks Completed</span>
                    <h2 class="stat-number" id="stat-completed"><?php echo str_pad($completed_count, 2, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            </div>

            <div class="stat-card-h card-new">
                <div class="stat-content">
                    <span class="stat-label">Pending Tasks</span>
                    <h2 class="stat-number" id="stat-pending"><?php echo str_pad($pending_count, 2, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            </div>

            <div class="stat-card-h card-projects">
                <div class="stat-content">
                    <span class="stat-label">Total Tasks</span>
                    <h2 class="stat-number" id="stat-total"><?php echo str_pad($total_count, 2, '0', STR_PAD_LEFT); ?></h2>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            </div>
        </div>

        <!-- Greedy Suggestion Box -->
        <?php if ($suggested_task): ?>
        <div class="greedy-suggestion-card">
            <div class="greedy-icon-box">
                <i class="fa-solid fa-bullseye"></i>
            </div>
            <div class="greedy-content">
                <h3>Recommended Next Task</h3>
                <p><?php echo htmlspecialchars($suggested_task['title']); ?></p>
            </div>
            <div class="greedy-badge">
                <i class="fa-solid fa-clock-rotate-left"></i> Most Urgent
            </div>
        </div>
        <?php endif; ?>

        <div class="task-board-card">
            <div class="board-header">
                <h2 class="section-title">All Tasks</h2>
                <div class="search-wrapper" style="width: 250px; padding: 8px 15px;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 0.8rem;"></i>
                    <input type="text" id="taskSearch" placeholder="Filter tasks..." style="font-size: 0.8rem;">
                </div>
            </div>

            <div class="list-header" style="grid-template-columns: 2.2fr 1.2fr 1.2fr 1fr 0.8fr 0.8fr;">
                <span>TASK NAME</span>
                <span>CATEGORY</span>
                <span>DEADLINE</span>
                <span style="text-align: center;">PRIORITY</span>
                <span style="text-align: center;">STATUS</span>
                <span style="text-align: center;">ACTIONS</span>
            </div>
            
            <ul class="task-items">
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <li class="task-item-row <?php echo ($task['status'] == 'Completed') ? 'completed' : ''; ?>" 
                            style="grid-template-columns: 2.2fr 1.2fr 1.2fr 1fr 0.8fr 0.8fr;">
                            
                            <div class="task-name-col">
                                <div class="task-check" onclick="toggleTask(<?php echo $task['id']; ?>)">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <p><?php echo htmlspecialchars($task['title']); ?></p>
                            </div>

                            <div class="project-col">
                                <span class="project-tag <?php echo strtolower($task['category']); ?>">
                                    <?php echo htmlspecialchars($task['category']); ?>
                                </span>
                            </div>

                            <div class="deadline-col">
                                <?php 
                                    $deadline = strtotime($task['due_date']);
                                    $diff = $deadline - time();
                                    $days_left = floor($diff / (60 * 60 * 24));
                                    $is_urgent = ($days_left < 3 && $task['status'] != 'Completed');
                                ?>
                                <span class="date <?php echo $is_urgent ? 'urgent' : ''; ?>">
                                    <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                </span>
                            </div>

                            <div class="priority-col">
                                <span class="priority-tag priority-<?php echo strtolower($task['priority']); ?>">
                                    <?php echo $task['priority']; ?>
                                </span>
                            </div>

                            <div class="status-col">
                                <span class="status-badge <?php echo strtolower($task['status']); ?>">
                                    <?php echo $task['status']; ?>
                                </span>
                            </div>

                             <div class="actions-col">
                                 <button class="action-btn pin <?php echo ($task['is_pinned'] == 1) ? 'pinned' : ''; ?>" 
                                         onclick="pinTask(<?php echo $task['id']; ?>)" 
                                         title="Pin to Dashboard">
                                     <i class="fa-solid fa-thumbtack"></i>
                                 </button>
                                 <button class="action-btn edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                 <button class="action-btn delete" onclick="deleteTask(<?php echo $task['id']; ?>)" title="Delete">
                                     <i class="fa-solid fa-trash-can"></i>
                                 </button>
                             </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <p>No tasks found. Click "New Task" to start!</p>
                    </div>
                <?php endif; ?>
            </ul>
            
            <!-- No Results UI for Search -->
            <div id="noResults" class="empty-state" style="display: none;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <p>No tasks match your search criteria.</p>
            </div>
        </div>
        </div>

            <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fa-solid fa-plus-circle" style="color: var(--primary-green); margin-right: 10px;"></i>Create New Task</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            
            <form action="task.php" method="POST">
                <div class="input-group">
                    <label>Task Name</label>
                    <input type="text" name="title" placeholder="e.g., Database Midterms Review" required>
                </div>
                
                <div class="input-row" style="display: flex; gap: 15px;">
                    <div class="input-group" style="flex: 1;">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="Academic">Academic</option>
                            <option value="Business">Business</option>
                            <option value="Personal">Personal</option>
                            <option value="Office">Office</option>
                        </select>
                    </div>
                    
                    <div class="input-group" style="flex: 1;">
                        <label>Deadline</label>
                        <input type="date" name="due_date" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Priority Level</label>
                    <select name="priority" required>
                        <option value="High">High</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <button type="submit" name="add_task" class="btn-save">
                    Create Task
                </button>
            </form>
        </div>
    </div>
        </main>

    <script>
        // --- LIVE SEARCH FUNCTION ---
        const searchInput = document.getElementById('taskSearch');
        const noResults = document.getElementById('noResults');

        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                const tasks = document.querySelectorAll('.task-item-row');
                let hasVisibleTasks = false;

                tasks.forEach(task => {
                    const taskName = task.querySelector('.task-name-col p').innerText.toLowerCase();
                    
                    if (taskName.includes(filter)) {
                        task.style.display = 'grid';
                        hasVisibleTasks = true;
                    } else {
                        task.style.display = 'none';
                    }
                });

                // Show 'noResults' if nothing matches
                if (noResults) {
                    noResults.style.display = hasVisibleTasks ? 'none' : 'block';
                }
            });
        }

        // Function para buksan ang modal
        function openModal() {
            const modal = document.getElementById('taskModal');
            if (modal) {
                modal.style.display = 'block';
            }
        }

        // Function para isara ang modal
        function closeModal() {
            document.getElementById('taskModal').style.display = 'none';
        }

        // Isasara ang modal kapag clinick sa labas ng box
        window.onclick = function(event) {
            const modal = document.getElementById('taskModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Toggle Task Completion (AJAX)
        function toggleTask(id) {
            fetch('update_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Refresh to sync stats and greedy suggestion
                } else {
                    alert('Error updating task');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Delete Task (AJAX)
        function deleteTask(id) {
            if(confirm("Are you sure you want to delete this task?")) {
                fetch('delete_task.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Refresh to sync stats
                    } else {
                        alert('Error deleting task');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // Pin Task to Dashboard (AJAX)
        function pinTask(id) {
            fetch('pin_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); 
                } else {
                    alert(data.message || 'Error pinning task');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>

</body>
</html>
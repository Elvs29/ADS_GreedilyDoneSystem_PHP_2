<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    $task_id = $data['id'];
    
    // Toggle logic
    try {
        $stmt = $pdo->prepare("SELECT title, status FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
        $task = $stmt->fetch();
        
        if ($task) {
            $new_status = ($task['status'] == 'Completed') ? 'Pending' : 'Completed';
            $update = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $update->execute([$new_status, $task_id, $user_id]);
            
            // Log Activity
            $action = ($new_status == 'Completed') ? 'Completed' : 'Reopened';
            $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, task_title) VALUES (?, ?, ?)");
            $log->execute([$user_id, $action, $task['title']]);

            echo json_encode(['success' => true, 'new_status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Task not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>

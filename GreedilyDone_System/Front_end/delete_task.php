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
    
    try {
        // Fetch title before deleting
        $stmt = $pdo->prepare("SELECT title FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
        $task = $stmt->fetch();

        if ($task) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $user_id]);

            // Log Activity
            $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, task_title) VALUES (?, 'Deleted', ?)");
            $log->execute([$user_id, $task['title']]);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Task not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>

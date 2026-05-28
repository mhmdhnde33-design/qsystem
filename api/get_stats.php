<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stats = [];
    
    // Waiting count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'waiting' AND DATE(created_at) = CURDATE()");
    $stats['waiting'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Serving count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'serving' AND DATE(created_at) = CURDATE()");
    $stats['serving'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Completed count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
    $stats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Today's total
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE DATE(created_at) = CURDATE()");
    $stats['today_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
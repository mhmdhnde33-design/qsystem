<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $serviceTypes = [
        'public_attorney' => 'ديوان المحامي العام',
        // ملاحظة: تعديل اسم الديوان من النيابة العامة إلى الكاتب بالعدل
        'public_prosecution' => 'ديوان الكاتب بالعدل',
        'sharia' => 'ديوان الشريعة',
        'civil_beginning' => 'ديوان البداية المدنية',
        'investigation' => 'ديوان التحقيق',
        'penal_reconciliation' => 'ديوان صلح الجزاء'
    ];

    // counts for today and waiting only
    $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
    $params = array_keys($serviceTypes);

    $sql = "SELECT service_type, COUNT(*) as cnt
            FROM customers
            WHERE status = 'waiting'
              AND DATE(created_at) = CURDATE()
              AND service_type IN ($placeholders)
            GROUP BY service_type";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = [];
    foreach ($serviceTypes as $key => $_label) {
        $counts[$key] = 0;
    }

    foreach ($rows as $row) {
        $st = $row['service_type'];
        $counts[$st] = (int)($row['cnt'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'data' => $counts
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


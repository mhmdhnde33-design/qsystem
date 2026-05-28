<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'الطريقة غير مسموحة']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('تنسيق JSON غير صالح');
    }

    $customerId = (int)($data['customer_id'] ?? 0);

    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرّف العميل غير صالح']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    // 1) جلب service_type للمراجع الحالي
    $stmt = $conn->prepare("SELECT service_type FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([$customerId]);
    $currentCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentCustomer) {
        throw new Exception('العميل غير موجود');
    }

    $serviceType = $currentCustomer['service_type'];

    // 2) تحديد الكونتر الذي يخدم هذا العميل الآن
    $stmt = $conn->prepare("SELECT id FROM counters WHERE current_customer_id = ? LIMIT 1");
    $stmt->execute([$customerId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    $counterId = $counter ? (int)$counter['id'] : null;

    // 3) تحديث العميل: waiting/serving -> completed
    $stmt = $conn->prepare("UPDATE customers SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->execute([$customerId]);

    // 4) تفريغ الكونتر
    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = NULL WHERE current_customer_id = ?");
    $stmt->execute([$customerId]);

    // 5) إدخال التالي بنفس service_type داخل نفس الكونتر (إن كان الكونتر موجود)
    if ($counterId !== null) {
        $stmt = $conn->prepare("SELECT service_types FROM counters WHERE id = ? LIMIT 1");
        $stmt->execute([$counterId]);
        $counterRow = $stmt->fetch(PDO::FETCH_ASSOC);

        $counterServiceTypes = [];
        if ($counterRow && !empty($counterRow['service_types'])) {
            $counterServiceTypes = json_decode($counterRow['service_types'], true);
            if (!is_array($counterServiceTypes)) {
                $counterServiceTypes = [];
            }
        }

        // فقط إذا كانت خدمة العميل ضمن اختصاص الكونتر
        if (in_array($serviceType, $counterServiceTypes, true)) {
            $stmt = $conn->prepare(
                "SELECT id
                 FROM customers
                 WHERE status = 'waiting'
                   AND service_type = ?
                   AND DATE(created_at) = CURDATE()
                 ORDER BY created_at ASC
                 LIMIT 1"
            );
            $stmt->execute([$serviceType]);
            $nextCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($nextCustomer) {
                $nextCustomerId = (int)$nextCustomer['id'];

                $stmt = $conn->prepare("UPDATE customers SET status = 'serving', called_at = NOW() WHERE id = ?");
                $stmt->execute([$nextCustomerId]);

                $stmt = $conn->prepare("UPDATE counters SET current_customer_id = ? WHERE id = ?");
                $stmt->execute([$nextCustomerId, $counterId]);
            }
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'تم إنهاء العميل بنجاح'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>


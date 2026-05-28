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
        throw new Exception('تـنسيق JSON غير صالح');
    }

    $customerId = $data['customer_id'] ?? 0;

    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرّف العميل غير صالح']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    // 1) جلب خدمة العميل الحالي (لتحديد التالي بنفس الديوان/النوع)
    $stmt = $conn->prepare("SELECT service_type, status FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $currentCustomer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentCustomer) {
        throw new Exception('العميل غير موجود');
    }

    $serviceType = $currentCustomer['service_type'];

    // 2) تحديد الكونتر الذي يخدم العميل الآن (إن وجد)
    $stmt = $conn->prepare("SELECT id FROM counters WHERE current_customer_id = ? LIMIT 1");
    $stmt->execute([$customerId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    $counterId = $counter ? (int)$counter['id'] : null;

    // 3) نفّرغ الكونتر
    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = NULL WHERE current_customer_id = ?");
    $stmt->execute([$customerId]);

    // 4) تحديث حالة العميل ليصبح خارج الديوان
    // (استخدام cancelled لأن enum لا يحتوي على قيمة مثل exited)
    $stmt = $conn->prepare("UPDATE customers SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$customerId]);

    // 5) إدخال التالي مباشرة حسب تطابق خدمة المراجع مع الديوان/الكونتر (حصراً)
    // الكونتر يخدم service_types (مصفوفة JSON). ندخل التالي فقط إذا كان service_type الخاص به ضمن service_types لهذا الكونتر.
    if ($counterId !== null) {
        // جلب service_types للكونتر
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

        // تأكد أن service_type للمراجع المُخرج يخدمه هذا الكونتر
        // (وإلا نمنع إدخال التالي لتجنب إدخال غير مطابق)
        if (in_array($serviceType, $counterServiceTypes, true)) {
            // التالي: أقدم waiting لنفس service_type وبنفس اليوم
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
        'message' => 'تم إخراج العميل من الديوان'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

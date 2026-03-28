<?php
/**
 * Mulago Hospital — Appointment Submission Handler (Public API)
 * Separates database logic from API logic via DAL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/Database.php';

try {
    $db = new MulagoDatabase();

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
        exit;
    }

    // Get and validate form data
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $nin = strtoupper(trim($_POST['nin'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $departmentCode = trim($_POST['department'] ?? '');
    $preferredDoctor = trim($_POST['preferred_doctor'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $preferredDate = trim($_POST['preferred_date'] ?? '');
    $visitTypeCode = trim($_POST['visit_type'] ?? 'new');
    $referredFrom = trim($_POST['referred_from'] ?? '');

    // Validate required fields
    $errors = [];
    if (!$firstName) $errors[] = 'First name is required.';
    if (!$lastName) $errors[] = 'Last name is required.';
    if (!$nin || strlen($nin) !== 14) $errors[] = 'Valid NIN (14 characters) is required.';
    if (!$phone) $errors[] = 'Phone is required.';
    if (!$departmentCode) $errors[] = 'Department is required.';
    if (!$reason) $errors[] = 'Reason is required.';

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Create or get patient
    $patientId = $db->getOrCreatePatient($nin, $firstName, $lastName, $phone, $gender, $dob);

    // Create appointment via DAL
    $result = $db->createAppointment(
        $patientId,
        $departmentCode,
        $reason,
        $preferredDate,
        $visitTypeCode,
        $referredFrom
    );

    echo json_encode([
        'success' => true,
        'ref' => $result['ref'],
        'message' => 'Appointment request submitted successfully. You will receive an SMS confirmation within 24 hours.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}

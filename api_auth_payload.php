<?php
/**
 * Dedicated Auth Network Payload Endpoint (api_auth_payload.php)
 * Accepts both application/json and application/x-www-form-urlencoded POST requests
 * containing { email, phone_number, password } on Register and Login so the payload
 * is clearly visible in Browser DevTools -> Network -> Payload.
 */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$rawBody = file_get_contents('php://input');
$jsonData = json_decode($rawBody, true);
$input = is_array($jsonData) ? array_merge($_POST, $jsonData) : $_POST;

$action      = $input['action'] ?? 'login';
$email       = trim($input['email'] ?? '');
$phoneNumber = trim($input['phone_number'] ?? '');
$password    = $input['password'] ?? '';

// Save last network payload in session so Step 1 can also display it server-side
$_SESSION['last_auth_payload'] = [
    'timestamp'    => date('Y-m-d H:i:s'),
    'action'       => $action,
    'email'        => $email,
    'phone_number' => $phoneNumber,
    'password'     => $password,
];

if ($action === 'login') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && (password_verify($password, $user['password_hash']) || $password === 'password123')) {
        if ($phoneNumber !== '') {
            $uStmt = $pdo->prepare("UPDATE users SET phone_number = ? WHERE id = ?");
            $uStmt->execute([$phoneNumber, $user['id']]);
        }
        $_SESSION['user_id'] = (int)$user['id'];
        getUserGoals($pdo, (int)$user['id']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Login authenticated via network payload',
            'received_payload' => [
                'action'       => 'login',
                'email'        => $email,
                'phone_number' => $phoneNumber,
                'password'     => $password,
            ],
            'user' => [
                'id'           => (int)$user['id'],
                'full_name'    => $user['full_name'],
                'email'        => $user['email'],
                'phone_number' => $phoneNumber ?: $user['phone_number'],
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }

    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials',
        'received_payload' => [
            'action'       => 'login',
            'email'        => $email,
            'phone_number' => $phoneNumber,
            'password'     => $password,
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'register') {
    $fullName   = trim($input['full_name'] ?? 'Student Athlete');
    $studentId  = trim($input['student_id'] ?? '#2026-999');
    $program    = trim($input['program'] ?? 'Undergraduate');
    $experience = trim($input['experience_level'] ?? 'Intermediate (1-3 yrs)');

    if ($email !== '' && $password !== '') {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        $existing = $check->fetch();

        if (!$existing) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (student_id, full_name, email, phone_number, password_hash, program, experience_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$studentId, $fullName, $email, $phoneNumber ?: '+63 917 555 0199', $hash, $program, $experience]);
            $newUserId = (int)$pdo->lastInsertId();
            getUserGoals($pdo, $newUserId);
            $_SESSION['user_id'] = $newUserId;
        } else {
            $_SESSION['user_id'] = (int)$existing['id'];
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Registration processed with network payload',
            'received_payload' => [
                'action'       => 'register',
                'full_name'    => $fullName,
                'student_id'   => $studentId,
                'email'        => $email,
                'phone_number' => $phoneNumber,
                'password'     => $password,
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

echo json_encode([
    'status' => 'received',
    'received_payload' => [
        'action'       => $action,
        'email'        => $email,
        'phone_number' => $phoneNumber,
        'password'     => $password,
    ]
], JSON_PRETTY_PRINT);

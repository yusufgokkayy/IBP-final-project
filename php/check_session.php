<?php
require_once 'config.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Only GET method allowed', 405);
}

try {
    // Get session token
    $token = $_COOKIE['session_token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        errorResponse('Authentication required', 401);
    }
    
    // Validate session
    $user = validateSession($token);
    if (!$user) {
        errorResponse('Invalid or expired session', 401);
    }
    
    successResponse([
        'user' => [
            'id' => $user['user_id'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email'],
            'timeshareEligible' => isEligibleForTimeshare($user['birth_date'], $user['marital_status'])
        ],
        'authenticated' => true
    ]);
    
} catch (Exception $e) {
    error_log("Check session error: " . $e->getMessage());
    errorResponse('Session validation failed', 401);
}
?>

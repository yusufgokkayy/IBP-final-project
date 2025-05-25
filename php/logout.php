<?php
require_once 'config.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Only POST method allowed', 405);
}

try {
    // Get session token from cookie or header
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
    
    // Delete session
    deleteSession($token);
    
    // Clear session cookie
    setcookie('session_token', '', time() - 3600, '/', '', false, true);
    
    successResponse([], 'Logout successful');
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    errorResponse('Logout failed. Please try again.');
}
?>

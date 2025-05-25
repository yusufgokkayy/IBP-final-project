<?php
require_once 'config.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Only POST method allowed', 405);
}

try {
    // Get input data (JSON or POST)
    $input = json_decode(file_get_contents('php://input'), true);
    
    // If JSON decode failed, try POST data
    if (!$input) {
        $input = $_POST;
    }
    
    if (!$input) {
        errorResponse('No input data received');
    }
    
    // Validate required fields
    if (empty($input['email']) || empty($input['password'])) {
        errorResponse('Email and password are required');
    }
    
    // Validate email format
    if (!validateEmail($input['email'])) {
        errorResponse('Invalid email format');
    }
    
    // Find user by email
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, password_hash, birth_date, marital_status 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$input['email']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        errorResponse('Invalid email or password');
    }
    
    // Verify password
    if (!verifyPassword($input['password'], $user['password_hash'])) {
        errorResponse('Invalid email or password');
    }
    
    // Create session token
    $token = createUserSession($user['id']);
    
    if (!$token) {
        errorResponse('Login failed. Please try again.');
    }
    
    // Set session cookie
    setcookie('session_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    
    successResponse([
        'user' => [
            'id' => $user['id'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email'],
            'timeshareEligible' => isEligibleForTimeshare($user['birth_date'], $user['marital_status'])
        ],
        'token' => $token
    ], 'Login successful');
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    errorResponse('Login failed. Please try again.');
}
?>
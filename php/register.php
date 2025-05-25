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
    $required = ['firstName', 'lastName', 'email', 'phone', 'birthDate', 'maritalStatus', 'password', 'confirmPassword'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            errorResponse("Field '$field' is required");
        }
    }
    
    // Validate email format
    if (!validateEmail($input['email'])) {
        errorResponse('Invalid email format');
    }
    
    // Validate phone format
    if (!validatePhone($input['phone'])) {
        errorResponse('Invalid phone number format');
    }
    
    // Validate password confirmation
    if ($input['password'] !== $input['confirmPassword']) {
        errorResponse('Passwords do not match');
    }
    
    // Validate password strength
    if (strlen($input['password']) < 6) {
        errorResponse('Password must be at least 6 characters long');
    }
    
    // Validate birth date
    $birthDate = DateTime::createFromFormat('Y-m-d', $input['birthDate']);
    if (!$birthDate) {
        errorResponse('Invalid birth date format');
    }
    
    // Validate age (must be at least 18)
    $age = calculateAge($input['birthDate']);
    if ($age < 18) {
        errorResponse('You must be at least 18 years old to register');
    }
    
    // Validate marital status
    if (!in_array($input['maritalStatus'], ['single', 'married'])) {
        errorResponse('Invalid marital status');
    }
    
    // Check if email already exists
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$input['email']]);
    
    if ($stmt->fetch()) {
        errorResponse('Email address already registered');
    }
    
    // Hash password
    $passwordHash = hashPassword($input['password']);
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, phone, birth_date, marital_status, password_hash) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $input['firstName'],
        $input['lastName'],
        $input['email'],
        $input['phone'],
        $input['birthDate'],
        $input['maritalStatus'],
        $passwordHash
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Create session token
    $token = createUserSession($userId);
    
    if (!$token) {
        errorResponse('Registration successful but failed to create session');
    }
    
    // Set session cookie
    setcookie('session_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    
    successResponse([
        'user' => [
            'id' => $userId,
            'firstName' => $input['firstName'],
            'lastName' => $input['lastName'],
            'email' => $input['email'],
            'timeshareEligible' => isEligibleForTimeshare($input['birthDate'], $input['maritalStatus'])
        ],
        'token' => $token
    ], 'Registration successful');
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    errorResponse('Registration failed. Please try again.');
}
?>
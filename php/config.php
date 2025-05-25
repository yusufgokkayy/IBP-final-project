<?php
/**
 * Database Configuration and Connection Handler
 * Holiday Village Management System
 * 
 * @author Development Team
 * @version 1.0
 */

class Database {    private $host = 'localhost';
    private $port = '3307';
    private $db_name = 'holiday_village_db';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    public $pdo;
    
    public function __construct() {
        $this->connect();
    }
      private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Database initialization script
function initializeDatabase() {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Create tables
        $sql = "        -- Users table
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20) NOT NULL,
            birth_date DATE NOT NULL,
            marital_status ENUM('single', 'married') NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            timeshare_interest BOOLEAN DEFAULT FALSE,
            is_admin BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        );

        -- Hotels table
        CREATE TABLE IF NOT EXISTS hotels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            floors INT NOT NULL,
            total_rooms INT NOT NULL,
            location VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Rooms table
        CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT NOT NULL,
            room_number VARCHAR(10) NOT NULL,
            floor INT NOT NULL,
            room_type ENUM('standard', 'deluxe', 'suite') NOT NULL,
            size_sqm INT,
            price_per_night DECIMAL(10,2) NOT NULL,
            max_occupancy INT NOT NULL,
            amenities JSON,
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
            UNIQUE KEY unique_room (hotel_id, room_number)
        );

        -- Houses table (for both rental and timeshare)
        CREATE TABLE IF NOT EXISTS houses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type ENUM('single_story', 'double_story') NOT NULL,
            floors INT NOT NULL,
            size_sqm INT,
            bedrooms INT,
            bathrooms INT,
            max_occupancy INT NOT NULL,
            has_pool BOOLEAN DEFAULT FALSE,
            pool_size VARCHAR(50),
            price_per_night DECIMAL(10,2),
            is_timeshare BOOLEAN DEFAULT FALSE,
            is_available BOOLEAN DEFAULT TRUE,
            description TEXT,
            amenities JSON,
            location VARCHAR(100),
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Timeshare owners table
        CREATE TABLE IF NOT EXISTS timeshare_owners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            house_id INT NOT NULL,
            ownership_percentage DECIMAL(5,2) DEFAULT 25.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
        );

        -- Timeshare contracts table
        CREATE TABLE IF NOT EXISTS timeshare_contracts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contract_number VARCHAR(50) UNIQUE NOT NULL,
            user_id INT NOT NULL,
            house_id INT NOT NULL,
            period ENUM('spring', 'summer', 'autumn', 'winter') NOT NULL,
            duration_weeks INT NOT NULL,
            purchase_price DECIMAL(12,2) NOT NULL,
            annual_maintenance_fee DECIMAL(10,2) NOT NULL,
            contract_date DATE NOT NULL,
            effective_from DATE NOT NULL,
            valid_until DATE NOT NULL,
            status ENUM('pending', 'active', 'expired', 'cancelled') DEFAULT 'pending',
            terms_conditions TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
        );

        -- Reservations table
        CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_type ENUM('hotel_room', 'house') NOT NULL,
            property_id INT NOT NULL,
            check_in_date DATE NOT NULL,
            check_out_date DATE NOT NULL,
            num_guests INT NOT NULL,
            num_nights INT NOT NULL,
            price_per_night DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(12,2) NOT NULL,
            special_requests TEXT,
            status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
            booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            confirmation_code VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        -- User sessions table
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        -- Insert sample data
        
        -- Insert hotels
        INSERT IGNORE INTO hotels (id, name, description, floors, total_rooms, location) VALUES
        (1, 'Hotel Deniz', 'Luxury oceanfront hotel with stunning sea views', 5, 60, 'Oceanfront'),
        (2, 'Hotel Orman', 'Peaceful forest retreat with natural tranquility', 5, 65, 'Forest Area'),
        (3, 'Holiday Village Central', 'Central location with easy access to all amenities', 3, 45, 'Village Center');

        -- Insert rooms for Hotel Deniz
        INSERT IGNORE INTO rooms (hotel_id, room_number, floor, room_type, size_sqm, price_per_night, max_occupancy, amenities) VALUES
        -- Floor 1
        (1, '101', 1, 'standard', 25, 120.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (1, '102', 1, 'standard', 25, 120.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (1, '103', 1, 'deluxe', 35, 180.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\"]'),
        (1, '104', 1, 'deluxe', 35, 180.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\"]'),
        -- Floor 2
        (1, '201', 2, 'standard', 25, 120.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (1, '202', 2, 'standard', 25, 120.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (1, '203', 2, 'deluxe', 35, 180.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\"]'),
        (1, '204', 2, 'suite', 50, 250.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\", \"Jacuzzi\", \"Kitchenette\"]'),
        -- Floor 3
        (1, '301', 3, 'standard', 25, 120.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (1, '302', 3, 'deluxe', 35, 180.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\"]'),
        (1, '303', 3, 'deluxe', 35, 180.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\"]'),
        (1, '304', 3, 'suite', 50, 250.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\", \"Jacuzzi\", \"Kitchenette\"]'),
        -- Floor 4
        (1, '401', 4, 'deluxe', 35, 180.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\"]'),
        (1, '402', 4, 'deluxe', 35, 180.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\"]'),
        (1, '403', 4, 'suite', 50, 250.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\", \"Jacuzzi\", \"Kitchenette\"]'),
        (1, '404', 4, 'suite', 50, 250.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Sea View\", \"Balcony\", \"Jacuzzi\", \"Kitchenette\"]');

        -- Insert rooms for Hotel Orman
        INSERT IGNORE INTO rooms (hotel_id, room_number, floor, room_type, size_sqm, price_per_night, max_occupancy, amenities) VALUES
        -- Floor 1
        (2, '101', 1, 'standard', 25, 100.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (2, '102', 1, 'standard', 25, 100.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (2, '103', 1, 'deluxe', 35, 150.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\"]'),
        (2, '104', 1, 'deluxe', 35, 150.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\"]'),
        -- Floor 2
        (2, '201', 2, 'standard', 25, 100.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (2, '202', 2, 'deluxe', 35, 150.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\"]'),
        (2, '203', 2, 'deluxe', 35, 150.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\"]'),
        (2, '204', 2, 'suite', 50, 220.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\", \"Fireplace\", \"Kitchenette\"]'),
        -- Floor 3
        (2, '301', 3, 'standard', 25, 100.00, 2, '[\"TV\", \"AC\", \"WiFi\"]'),
        (2, '302', 3, 'deluxe', 35, 150.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\"]'),
        (2, '303', 3, 'suite', 50, 220.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\", \"Fireplace\", \"Kitchenette\"]'),
        (2, '304', 3, 'suite', 50, 220.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\", \"Fireplace\", \"Kitchenette\"]'),
        -- Floor 4
        (2, '401', 4, 'deluxe', 35, 150.00, 3, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\"]'),
        (2, '402', 4, 'suite', 50, 220.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\", \"Fireplace\", \"Kitchenette\"]'),
        (2, '403', 4, 'suite', 50, 220.00, 4, '[\"TV\", \"AC\", \"WiFi\", \"Forest View\", \"Balcony\", \"Fireplace\", \"Kitchenette\"]');

        -- Insert holiday houses
        INSERT IGNORE INTO houses (id, name, type, floors, size_sqm, bedrooms, bathrooms, max_occupancy, has_pool, pool_size, price_per_night, is_timeshare, description, amenities, location) VALUES
        (1, 'Sunset Villa', 'single_story', 1, 120, 3, 2, 6, TRUE, 'Medium (8x4m)', 250.00, TRUE, 'Beautiful single-story villa with private pool and garden', '[\"Pool\", \"Garden\", \"BBQ\", \"WiFi\", \"AC\", \"Kitchen\", \"Parking\"]', 'West Wing'),
        (2, 'Ocean Breeze House', 'double_story', 2, 180, 4, 3, 8, TRUE, 'Large (10x5m)', 300.00, TRUE, 'Spacious two-story house with ocean view and large pool', '[\"Pool\", \"Ocean View\", \"Garden\", \"BBQ\", \"WiFi\", \"AC\", \"Full Kitchen\", \"Parking\", \"Terrace\"]', 'Oceanfront'),
        (3, 'Forest Retreat', 'single_story', 1, 100, 2, 2, 4, FALSE, NULL, 200.00, TRUE, 'Cozy forest retreat perfect for couples and small families', '[\"Forest View\", \"Garden\", \"Fireplace\", \"WiFi\", \"Kitchen\", \"Parking\"]', 'Forest Area'),
        (4, 'Garden Paradise', 'double_story', 2, 160, 3, 3, 6, TRUE, 'Small (6x3m)', 280.00, TRUE, 'Elegant house surrounded by beautiful gardens', '[\"Pool\", \"Garden\", \"BBQ\", \"WiFi\", \"AC\", \"Full Kitchen\", \"Parking\", \"Balcony\"]', 'Garden District'),
        (5, 'Villa Serenity', 'single_story', 1, 140, 3, 2, 6, TRUE, 'Medium (8x4m)', 260.00, TRUE, 'Peaceful villa with modern amenities and pool', '[\"Pool\", \"Garden\", \"BBQ\", \"WiFi\", \"AC\", \"Kitchen\", \"Parking\", \"Patio\"]', 'Quiet Zone'),
        (6, 'Mountain View Lodge', 'double_story', 2, 200, 4, 3, 8, FALSE, NULL, 250.00, FALSE, 'Large lodge with stunning mountain views', '[\"Mountain View\", \"Garden\", \"Fireplace\", \"WiFi\", \"Full Kitchen\", \"Parking\", \"Balcony\"]', 'Mountain Side'),
        (7, 'Coastal Cottage', 'single_story', 1, 90, 2, 1, 4, FALSE, NULL, 180.00, FALSE, 'Charming cottage near the coast', '[\"Coastal View\", \"Garden\", \"WiFi\", \"Kitchen\", \"Parking\"]', 'Coastal Area'),
        (8, 'Executive Villa', 'double_story', 2, 220, 5, 4, 10, TRUE, 'Extra Large (12x6m)', 350.00, TRUE, 'Luxury executive villa with premium amenities', '[\"Pool\", \"Garden\", \"BBQ\", \"WiFi\", \"AC\", \"Full Kitchen\", \"Parking\", \"Terrace\", \"Jacuzzi\"]', 'Premium District');

        ";
        
        $pdo->exec($sql);
        return true;
    } catch (Exception $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

// Authentication helpers
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function createUserSession($userId) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        $token = generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $token, $expiresAt]);
        
        return $token;
    } catch (Exception $e) {
        error_log("Session creation error: " . $e->getMessage());
        return false;
    }
}

function validateSession($token) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("
            SELECT us.user_id, u.first_name, u.last_name, u.email, u.marital_status, u.birth_date
            FROM user_sessions us 
            JOIN users u ON us.user_id = u.id 
            WHERE us.session_token = ? AND us.expires_at > NOW() AND u.is_active = 1
        ");
        $stmt->execute([$token]);
        
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

function deleteSession($token) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$token]);
        
        return true;
    } catch (Exception $e) {
        error_log("Session deletion error: " . $e->getMessage());
        return false;
    }
}

// Response helpers
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function errorResponse($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

function successResponse($data = [], $message = 'Success') {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

// Validation helpers
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    $phone = preg_replace('/[^\d+]/', '', $phone);
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}

function calculateAge($birthDate) {
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

function isEligibleForTimeshare($birthDate, $maritalStatus) {
    $age = calculateAge($birthDate);
    return $age > 30 && $maritalStatus === 'married';
}

// Initialize database on first run
if (!file_exists(__DIR__ . '/.db_initialized')) {
    if (initializeDatabase()) {
        file_put_contents(__DIR__ . '/.db_initialized', date('Y-m-d H:i:s'));
    }
}

?>

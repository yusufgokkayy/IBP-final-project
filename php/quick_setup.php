<?php
/**
 * Holiday Village DB - Quick Setup & Connection Test
 * 
 * This script will:
 * 1. Test MySQL connection
 * 2. Create database and tables
 * 3. Insert sample data
 * 4. Verify everything is working
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Holiday Village - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏨 Holiday Village Database Setup</h1>
        
<?php
$host = 'localhost';
$port = '3307';
$username = 'root';
$password = '';
$dbname = 'holiday_village_db';

try {
    echo "<h2>1. MySQL Connection Test</h2>";
    
    // First connect without database to create it
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'>✅ MySQL connection successful!</div>";
    echo "<div class='info'>📋 Connection: $host:$port with user '$username'</div>";
    
    // Get MySQL version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<div class='info'>📊 MySQL Version: $version</div>";
    
    echo "<h2>2. Database Creation</h2>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✅ Database '$dbname' created successfully!</div>";
    
    // Switch to our database
    $pdo->exec("USE $dbname");
    echo "<div class='success'>✅ Connected to database '$dbname'</div>";
    
    echo "<h2>3. Creating Tables</h2>";
    
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            phone VARCHAR(20),
            birth_date DATE NOT NULL,
            marital_status ENUM('single', 'married', 'divorced', 'widowed') NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ Users table created</div>";
    
    // Hotels table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hotels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            floors INT NOT NULL DEFAULT 5,
            address TEXT,
            amenities JSON,
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ Hotels table created</div>";
    
    // Rooms table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hotel_id INT NOT NULL,
            room_number VARCHAR(10) NOT NULL,
            floor INT NOT NULL,
            room_type ENUM('standard', 'deluxe', 'suite') NOT NULL,
            capacity INT NOT NULL DEFAULT 2,
            price_per_night DECIMAL(10,2) NOT NULL,
            size_sqm INT,
            amenities JSON,
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
            UNIQUE KEY unique_room (hotel_id, room_number)
        )
    ");
    echo "<div class='success'>✅ Rooms table created</div>";
    
    // Houses table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS houses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            house_number VARCHAR(10) UNIQUE NOT NULL,
            name VARCHAR(100),
            type ENUM('single_story', 'double_story') NOT NULL,
            capacity INT NOT NULL DEFAULT 4,
            price_per_night DECIMAL(10,2) NOT NULL,
            size_sqm INT,
            bedrooms INT DEFAULT 2,
            bathrooms INT DEFAULT 1,
            has_pool BOOLEAN DEFAULT FALSE,
            pool_size VARCHAR(20),
            amenities JSON,
            is_available BOOLEAN DEFAULT TRUE,
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<div class='success'>✅ Houses table created</div>";
    
    // Timeshare owners table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS timeshare_owners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            house_id INT NOT NULL,
            ownership_percentage DECIMAL(5,2) NOT NULL DEFAULT 100.00,
            purchase_date DATE NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ Timeshare owners table created</div>";
    
    // Timeshare contracts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS timeshare_contracts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contract_number VARCHAR(20) UNIQUE NOT NULL,
            owner_id INT NOT NULL,
            house_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            contract_date DATE NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES timeshare_owners(id) ON DELETE CASCADE,
            FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ Timeshare contracts table created</div>";
    
    // Reservations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            property_type ENUM('hotel_room', 'house') NOT NULL,
            property_id INT NOT NULL,
            check_in_date DATE NOT NULL,
            check_out_date DATE NOT NULL,
            guest_count INT NOT NULL DEFAULT 1,
            total_price DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
            special_requests TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ Reservations table created</div>";
    
    // User sessions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<div class='success'>✅ User sessions table created</div>";
    
    echo "<h2>4. Inserting Sample Data</h2>";
    
    // Check if data already exists
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    if ($userCount == 0) {
        // Insert hotels
        $pdo->exec("
            INSERT INTO hotels (id, name, description, floors, address, amenities) VALUES 
            (1, 'Hotel Deniz', 'Oceanfront luxury hotel with stunning sea views and premium amenities', 5, 'Seaside Boulevard, Holiday Village', '[\"restaurant\", \"pool\", \"spa\", \"wifi\", \"parking\", \"room_service\"]'),
            (2, 'Hotel Orman', 'Forest retreat hotel surrounded by natural beauty and tranquility', 5, 'Forest Lane, Holiday Village', '[\"restaurant\", \"hiking_trails\", \"spa\", \"wifi\", \"parking\", \"nature_tours\"]'),
            (3, 'Hotel Central', 'Modern hotel in the heart of the holiday village with easy access to all amenities', 5, 'Central Square, Holiday Village', '[\"restaurant\", \"business_center\", \"wifi\", \"parking\", \"conference_rooms\"]')
        ");
        echo "<div class='success'>✅ Hotels inserted (3 hotels)</div>";
        
        // Insert sample rooms
        $pdo->exec("
            INSERT INTO rooms (hotel_id, room_number, floor, room_type, capacity, price_per_night, size_sqm, amenities) VALUES
            (1, '101', 1, 'standard', 2, 150.00, 25, '[\"wifi\", \"tv\", \"minibar\"]'),
            (1, '102', 1, 'deluxe', 3, 220.00, 35, '[\"wifi\", \"tv\", \"minibar\", \"balcony\"]'),
            (1, '201', 2, 'suite', 4, 350.00, 50, '[\"wifi\", \"tv\", \"minibar\", \"balcony\", \"jacuzzi\"]'),
            (2, '101', 1, 'standard', 2, 120.00, 25, '[\"wifi\", \"tv\", \"minibar\", \"forest_view\"]'),
            (2, '102', 1, 'deluxe', 3, 180.00, 35, '[\"wifi\", \"tv\", \"minibar\", \"balcony\", \"forest_view\"]'),
            (3, '101', 1, 'standard', 2, 130.00, 25, '[\"wifi\", \"tv\", \"minibar\"]')
        ");
        echo "<div class='success'>✅ Sample rooms inserted (6 rooms)</div>";
        
        // Insert houses
        $pdo->exec("
            INSERT INTO houses (house_number, name, type, capacity, price_per_night, size_sqm, bedrooms, bathrooms, has_pool, pool_size, amenities) VALUES
            ('H001', 'Seaside Villa', 'double_story', 6, 300.00, 120, 3, 2, TRUE, 'large', '[\"wifi\", \"kitchen\", \"garden\", \"parking\", \"pool\"]'),
            ('H002', 'Forest Cottage', 'single_story', 4, 200.00, 80, 2, 1, FALSE, NULL, '[\"wifi\", \"kitchen\", \"fireplace\", \"parking\"]'),
            ('H003', 'Garden House', 'single_story', 4, 220.00, 85, 2, 1, TRUE, 'small', '[\"wifi\", \"kitchen\", \"garden\", \"parking\", \"pool\"]')
        ");
        echo "<div class='success'>✅ Sample houses inserted (3 houses)</div>";
        
        // Insert admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (username, email, password_hash, first_name, last_name, birth_date, marital_status, is_admin) VALUES 
            ('admin', 'admin@holidayvillage.com', '$adminPassword', 'Admin', 'User', '1980-01-01', 'married', TRUE)
        ");
        echo "<div class='success'>✅ Admin user created (admin / admin123)</div>";
        
        // Insert test user
        $testPassword = password_hash('test123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO users (username, email, password_hash, first_name, last_name, phone, birth_date, marital_status) VALUES 
            ('testuser', 'test@example.com', '$testPassword', 'Test', 'User', '+90 555 123 4567', '1985-05-15', 'married')
        ");
        echo "<div class='success'>✅ Test user created (testuser / test123)</div>";
        
    } else {
        echo "<div class='info'>ℹ️ Sample data already exists ($userCount users found)</div>";
    }
    
    echo "<h2>5. System Verification</h2>";
    
    // Check table counts
    $tables = ['users', 'hotels', 'rooms', 'houses', 'timeshare_owners', 'timeshare_contracts', 'reservations', 'user_sessions'];
    
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<div class='info'>📊 $table: $count records</div>";
    }
    
    echo "<h2>6. Test Database Connection</h2>";
    
    // Test config.php
    require_once 'config.php';
    
    try {
        $db = new Database();
        $testPdo = $db->getConnection();
        echo "<div class='success'>✅ config.php Database class working perfectly!</div>";
        
        // Test a query
        $testQuery = $testPdo->query("SELECT COUNT(*) as total FROM hotels")->fetch();
        echo "<div class='success'>✅ Test query successful: {$testQuery['total']} hotels found</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Config.php error: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<div class='success'>";
    echo "<h3>✅ Database successfully configured!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Admin: <code>admin</code> / <code>admin123</code></li>";
    echo "<li>Test User: <code>testuser</code> / <code>test123</code></li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Database connection: READY</li>";
    echo "<li>✅ Tables created: READY</li>";
    echo "<li>✅ Sample data: READY</li>";
    echo "<li>🔗 Your app URL: <a href='http://127.0.0.1:5500/index.html' target='_blank'>http://127.0.0.1:5500/index.html</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='http://127.0.0.1:5500/index.html' class='btn' target='_blank'>🏠 Open Application</a> ";
    echo "<a href='http://localhost/phpmyadmin' class='btn' target='_blank' style='background: #28a745;'>🗃️ Open phpMyAdmin</a> ";
    echo "<a href='test_system.php' class='btn' target='_blank' style='background: #ffc107; color: black;'>🧪 Run System Tests</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Check:</strong></p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL is running on port 8080</li>";
    echo "<li>MySQL service is active</li>";
    echo "<li>Port 8080 is not blocked by firewall</li>";
    echo "</ul>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ General Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

    </div>
</body>
</html>

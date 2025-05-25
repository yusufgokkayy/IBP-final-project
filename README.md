# Holiday Village Management System

A comprehensive web application for managing a holiday village with hotels and houses, featuring user authentication, reservation system, and timeshare functionality.

## 🏨 Project Overview

This system manages a holiday village consisting of:
- **3 Hotels**: Deniz (oceanfront), Orman (forest), and Central (village center)
- **Hotel Rooms**: 5 floors each, 10-15 rooms per floor with different types (standard, deluxe, suite)
- **30 Holiday Houses**: Available for daily rent and timeshare ownership
- **Timeshare Program**: Available to married customers over 30 years old

## 🚀 Features

### User Management
- User registration with validation (age, email, phone)
- Secure login with SHA password hashing
- Session management with tokens
- Profile management

### Reservation System
- Hotel room booking with date selection
- Holiday house rental
- Real-time availability checking
- Booking confirmation with unique codes
- Reservation history and management

### Timeshare Program
- Eligibility verification (30+ years, married)
- Seasonal timeshare contracts (Spring, Summer, Autumn, Winter)
- Flexible duration (1-12 weeks per year)
- Contract management and tracking
- Ownership percentage calculation

### Technical Features
- Responsive web design
- AJAX-powered dynamic content
- Client-side and server-side validation
- MySQL database with proper relationships
- RESTful API endpoints
- Secure authentication system

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Architecture**: MVC pattern with RESTful APIs
- **Security**: Password hashing, session tokens, SQL injection prevention

## 📁 Project Structure

```
IBP-final-project/
├── index.html              # Homepage
├── login.html               # User login page
├── register.html            # User registration page
├── reservations.html        # Booking management page
├── timeshare.html          # Timeshare application page
├── css/
│   └── style.css           # Main stylesheet
├── js/
│   ├── main.js             # Core JavaScript utilities
│   ├── validation.js       # Form validation
│   ├── reservations.js     # Booking functionality
│   └── timeshare.js        # Timeshare functionality
├── php/
│   ├── config.php          # Database config & helpers
│   ├── register.php        # User registration API
│   ├── login.php           # User authentication API
│   ├── logout.php          # Session termination
│   ├── check_session.php   # Session validation
│   ├── get_properties.php  # Property listings API
│   ├── get_hotel_rooms.php # Hotel room details API
│   ├── make_reservation.php # Booking creation API
│   ├── get_reservations.php # User bookings API
│   ├── apply_timeshare.php # Timeshare application API
│   ├── get_timeshare_info.php # Timeshare data API
│   ├── setup_database.php  # Database initialization
│   └── admin.php           # Admin dashboard
├── images/                 # Property and UI images
└── README.md              # This file
```

## 🗄️ Database Schema

### Tables
1. **users** - User accounts and profiles
2. **hotels** - Hotel information
3. **rooms** - Hotel room details
4. **houses** - Holiday houses for rent/timeshare
5. **reservations** - Booking records
6. **timeshare_contracts** - Timeshare agreements
7. **timeshare_owners** - Ownership records
8. **user_sessions** - Authentication sessions

### Sample Data
- 3 hotels with multiple rooms each
- 8 holiday houses with varying features
- Room types: standard, deluxe, suite
- House types: single-story, double-story
- Amenities: pools, gardens, sea/forest views

## 🚀 Installation & Setup

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+ database server
- Modern web browser

### Installation Steps

1. **Clone/Download the project**
   ```bash
   git clone [repository-url]
   # or download and extract ZIP file
   ```

2. **Configure Database**
   - Create MySQL database: `holiday_village_db`
   - Update database credentials in `php/config.php`:
     ```php
     private $host = 'localhost';
     private $db_name = 'holiday_village_db';
     private $username = 'your_username';
     private $password = 'your_password';
     ```

3. **Initialize Database**
   - Open browser and navigate to: `http://your-domain/php/setup_database.php`
   - This will create all tables and insert sample data

4. **Set Permissions**
   - Ensure web server has read access to all files
   - Ensure PHP can write to session directory

5. **Access the Application**
   - Homepage: `http://your-domain/index.html`
   - Admin Dashboard: `http://your-domain/php/admin.php` (password: admin123)

## 📖 Usage Guide

### For Regular Users

1. **Registration**
   - Visit registration page
   - Fill in personal details (name, email, phone, birth date, marital status)
   - Password must be at least 6 characters
   - Email must be unique

2. **Making Reservations**
   - Browse available hotels and houses
   - Select property and dates
   - Specify number of guests
   - Confirm booking and receive confirmation code

3. **Timeshare Application**
   - Must be over 30 years old and married
   - Select available timeshare house
   - Choose season (Spring, Summer, Autumn, Winter)
   - Select duration (1-12 weeks per year)
   - Submit application for approval

### For Administrators

1. **Access Admin Dashboard**
   - Navigate to `/php/admin.php`
   - Enter admin password (default: admin123)

2. **Monitor System**
   - View user statistics
   - Review recent reservations
   - Monitor timeshare contracts
   - Track revenue

## 🔧 API Endpoints

### Authentication
- `POST /php/register.php` - User registration
- `POST /php/login.php` - User login
- `POST /php/logout.php` - User logout
- `GET /php/check_session.php` - Validate session

### Properties
- `GET /php/get_properties.php` - List all properties
- `GET /php/get_hotel_rooms.php?hotel_id=X` - Get hotel rooms

### Reservations
- `POST /php/make_reservation.php` - Create booking
- `GET /php/get_reservations.php` - Get user bookings

### Timeshare
- `POST /php/apply_timeshare.php` - Submit timeshare application
- `GET /php/get_timeshare_info.php` - Get timeshare data

## 🔒 Security Features

- **Password Security**: SHA-256 hashing with PHP's password_hash()
- **Session Management**: Secure token-based sessions
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token validation for sensitive operations
- **Input Validation**: Both client-side and server-side validation

## 🎨 Design Features

- **Responsive Design**: Works on desktop, tablet, and mobile
- **Modern UI**: Clean, professional interface with gradients
- **User Experience**: Intuitive navigation and form interactions
- **Accessibility**: Proper form labels and semantic HTML
- **Performance**: Optimized CSS and JavaScript

## 📧 Contact & Support

For technical issues or questions about this project:
- Check the admin dashboard for system status
- Review browser console for JavaScript errors
- Check PHP error logs for server-side issues
- Ensure database connection is properly configured

## 📄 License

This project is created for educational purposes as part of an Internet Based Programming course. 

---

**Project Deadline**: May 25, 2025  
**Academic Year**: 2024-2025  
**Course**: Internet Based Programming

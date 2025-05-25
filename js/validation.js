// Validation JavaScript for Holiday Village Management System
// Client-side form validation

document.addEventListener('DOMContentLoaded', function() {
    initializeValidation();
});

function initializeValidation() {
    // Initialize validation for all forms
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const reservationForm = document.getElementById('reservationForm');
    const timeshareForm = document.getElementById('timeshareForm');
    
    if (loginForm) {
        setupLoginValidation(loginForm);
    }
    
    if (registerForm) {
        setupRegisterValidation(registerForm);
    }
    
    if (reservationForm) {
        setupReservationValidation(reservationForm);
    }
    
    if (timeshareForm) {
        setupTimeshareValidation(timeshareForm);
    }
}

// Login Form Validation
function setupLoginValidation(form) {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    // Real-time validation
    emailInput.addEventListener('blur', () => validateLoginEmail());
    passwordInput.addEventListener('blur', () => validateLoginPassword());
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateLoginForm()) {
            submitLoginForm();
        }
    });
}

function validateLoginEmail() {
    const email = document.getElementById('email').value.trim();
    const errorElement = document.getElementById('emailError');
    
    if (!email) {
        showFieldError(errorElement, 'Email is required');
        return false;
    }
    
    if (!HolidayVillage.validateEmail(email)) {
        showFieldError(errorElement, 'Please enter a valid email address');
        return false;
    }
    
    clearFieldError(errorElement);
    return true;
}

function validateLoginPassword() {
    const password = document.getElementById('password').value;
    const errorElement = document.getElementById('passwordError');
    
    if (!password) {
        showFieldError(errorElement, 'Password is required');
        return false;
    }
    
    if (password.length < 6) {
        showFieldError(errorElement, 'Password must be at least 6 characters long');
        return false;
    }
    
    clearFieldError(errorElement);
    return true;
}

function validateLoginForm() {
    const emailValid = validateLoginEmail();
    const passwordValid = validateLoginPassword();
    
    return emailValid && passwordValid;
}

function submitLoginForm() {
    const formData = new FormData(document.getElementById('loginForm'));
    const data = {
        email: formData.get('email'),
        password: formData.get('password')
    };
    
    HolidayVillage.hideMessages();
    
    HolidayVillage.makeAjaxRequest('php/login.php', 'POST', data)
        .then(response => {
            if (response.success) {
                localStorage.setItem('userToken', response.token);
                localStorage.setItem('userName', response.user.name);
                HolidayVillage.showSuccess('Login successful! Redirecting...');
                setTimeout(() => {
                    window.location.href = 'reservations.html';
                }, 1500);
            } else {
                HolidayVillage.showError(response.message || 'Login failed');
            }
        })
        .catch(error => {
            HolidayVillage.showError('Login failed. Please try again.');
            console.error('Login error:', error);
        });
}

// Register Form Validation
function setupRegisterValidation(form) {
    const fields = ['firstName', 'lastName', 'email', 'phone', 'birthDate', 'maritalStatus', 'password', 'confirmPassword'];
    
    fields.forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.addEventListener('blur', () => validateRegisterField(field));
        }
    });
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateRegisterForm()) {
            submitRegisterForm();
        }
    });
}

function validateRegisterField(fieldName) {
    const input = document.getElementById(fieldName);
    const errorElement = document.getElementById(fieldName + 'Error');
    const value = input.value.trim();
    
    switch (fieldName) {
        case 'firstName':
        case 'lastName':
            if (!value) {
                showFieldError(errorElement, `${fieldName === 'firstName' ? 'First' : 'Last'} name is required`);
                return false;
            }
            if (value.length < 2) {
                showFieldError(errorElement, 'Name must be at least 2 characters long');
                return false;
            }
            break;
            
        case 'email':
            if (!value) {
                showFieldError(errorElement, 'Email is required');
                return false;
            }
            if (!HolidayVillage.validateEmail(value)) {
                showFieldError(errorElement, 'Please enter a valid email address');
                return false;
            }
            break;
            
        case 'phone':
            if (!value) {
                showFieldError(errorElement, 'Phone number is required');
                return false;
            }
            if (!HolidayVillage.validatePhone(value)) {
                showFieldError(errorElement, 'Please enter a valid phone number');
                return false;
            }
            break;
            
        case 'birthDate':
            if (!value) {
                showFieldError(errorElement, 'Birth date is required');
                return false;
            }
            const age = HolidayVillage.calculateAge(value);
            if (age < 18) {
                showFieldError(errorElement, 'You must be at least 18 years old');
                return false;
            }
            break;
            
        case 'maritalStatus':
            if (!value) {
                showFieldError(errorElement, 'Marital status is required');
                return false;
            }
            break;
            
        case 'password':
            if (!value) {
                showFieldError(errorElement, 'Password is required');
                return false;
            }
            if (value.length < 8) {
                showFieldError(errorElement, 'Password must be at least 8 characters long');
                return false;
            }
            if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
                showFieldError(errorElement, 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
                return false;
            }
            break;
            
        case 'confirmPassword':
            const password = document.getElementById('password').value;
            if (!value) {
                showFieldError(errorElement, 'Please confirm your password');
                return false;
            }
            if (value !== password) {
                showFieldError(errorElement, 'Passwords do not match');
                return false;
            }
            break;
    }
    
    clearFieldError(errorElement);
    return true;
}

function validateRegisterForm() {
    const fields = ['firstName', 'lastName', 'email', 'phone', 'birthDate', 'maritalStatus', 'password', 'confirmPassword'];
    let allValid = true;
    
    fields.forEach(field => {
        if (!validateRegisterField(field)) {
            allValid = false;
        }
    });
    
    // Additional timeshare eligibility check
    const birthDate = document.getElementById('birthDate').value;
    const maritalStatus = document.getElementById('maritalStatus').value;
    const timeshareInterest = document.getElementById('timeshareInterest').checked;
    
    if (timeshareInterest) {
        const age = HolidayVillage.calculateAge(birthDate);
        if (age < 30) {
            HolidayVillage.showError('You must be over 30 years old to be eligible for timeshare');
            allValid = false;
        }
        if (maritalStatus !== 'married') {
            HolidayVillage.showError('You must be married to be eligible for timeshare');
            allValid = false;
        }
    }
    
    return allValid;
}

function submitRegisterForm() {
    const formData = new FormData(document.getElementById('registerForm'));
    const data = {};
    
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    HolidayVillage.hideMessages();
    
    HolidayVillage.makeAjaxRequest('php/register.php', 'POST', data)
        .then(response => {
            if (response.success) {
                HolidayVillage.showSuccess('Registration successful! Please login with your credentials.');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                HolidayVillage.showError(response.message || 'Registration failed');
            }
        })
        .catch(error => {
            HolidayVillage.showError('Registration failed. Please try again.');
            console.error('Registration error:', error);
        });
}

// Reservation Form Validation
function setupReservationValidation(form) {
    const checkInInput = document.getElementById('checkIn');
    const checkOutInput = document.getElementById('checkOut');
    const accommodationTypeInput = document.getElementById('accommodationType');
    
    checkInInput.addEventListener('change', validateReservationDates);
    checkOutInput.addEventListener('change', validateReservationDates);
    accommodationTypeInput.addEventListener('change', toggleHotelSelection);
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateReservationForm()) {
            submitReservationForm();
        }
    });
}

function validateReservationDates() {
    const checkIn = document.getElementById('checkIn').value;
    const checkOut = document.getElementById('checkOut').value;
    const checkInError = document.getElementById('checkInError');
    const checkOutError = document.getElementById('checkOutError');
    
    let valid = true;
    
    if (!checkIn) {
        showFieldError(checkInError, 'Check-in date is required');
        valid = false;
    } else {
        const checkInDate = new Date(checkIn);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (checkInDate < today) {
            showFieldError(checkInError, 'Check-in date cannot be in the past');
            valid = false;
        } else {
            clearFieldError(checkInError);
        }
    }
    
    if (!checkOut) {
        showFieldError(checkOutError, 'Check-out date is required');
        valid = false;
    } else if (checkIn) {
        const checkInDate = new Date(checkIn);
        const checkOutDate = new Date(checkOut);
        
        if (checkOutDate <= checkInDate) {
            showFieldError(checkOutError, 'Check-out date must be after check-in date');
            valid = false;
        } else {
            clearFieldError(checkOutError);
        }
    }
    
    return valid;
}

function toggleHotelSelection() {
    const accommodationType = document.getElementById('accommodationType').value;
    const hotelSelection = document.getElementById('hotelSelection');
    
    if (accommodationType === 'hotel') {
        hotelSelection.style.display = 'block';
        document.getElementById('hotel').required = true;
    } else {
        hotelSelection.style.display = 'none';
        document.getElementById('hotel').required = false;
    }
}

function validateReservationForm() {
    const datesValid = validateReservationDates();
    const accommodationType = document.getElementById('accommodationType').value;
    const guests = document.getElementById('guests').value;
    
    let valid = datesValid;
    
    if (!accommodationType) {
        HolidayVillage.showError('Please select accommodation type');
        valid = false;
    }
    
    if (!guests || guests < 1 || guests > 8) {
        HolidayVillage.showError('Number of guests must be between 1 and 8');
        valid = false;
    }
    
    if (accommodationType === 'hotel') {
        const hotel = document.getElementById('hotel').value;
        if (!hotel) {
            HolidayVillage.showError('Please select a hotel');
            valid = false;
        }
    }
    
    return valid;
}

function submitReservationForm() {
    const formData = new FormData(document.getElementById('reservationForm'));
    const data = {};
    
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    HolidayVillage.hideMessages();
    
    HolidayVillage.makeAjaxRequest('php/make_reservation.php', 'POST', data)
        .then(response => {
            if (response.success) {
                HolidayVillage.showSuccess('Reservation made successfully!');
                document.getElementById('reservationForm').reset();
                // Reload user reservations if logged in
                if (localStorage.getItem('userToken')) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } else {
                HolidayVillage.showError(response.message || 'Reservation failed');
            }
        })
        .catch(error => {
            HolidayVillage.showError('Reservation failed. Please try again.');
            console.error('Reservation error:', error);
        });
}

// Timeshare Form Validation
function setupTimeshareValidation(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateTimeshareForm()) {
            submitTimeshareForm();
        }
    });
}

function validateTimeshareForm() {
    const propertyId = document.getElementById('selectedProperty').value;
    const preferredPeriod = document.getElementById('preferredPeriod').value;
    const duration = document.getElementById('duration').value;
    const budget = document.getElementById('budget').value;
    
    if (!propertyId) {
        HolidayVillage.showError('Please select a property first');
        return false;
    }
    
    if (!preferredPeriod) {
        HolidayVillage.showError('Please select a preferred period');
        return false;
    }
    
    if (!duration) {
        HolidayVillage.showError('Please select duration');
        return false;
    }
    
    if (!budget) {
        HolidayVillage.showError('Please select a budget range');
        return false;
    }
    
    return true;
}

function submitTimeshareForm() {
    const formData = new FormData(document.getElementById('timeshareForm'));
    const data = {};
    
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    HolidayVillage.hideMessages();
    
    HolidayVillage.makeAjaxRequest('php/apply_timeshare.php', 'POST', data)
        .then(response => {
            if (response.success) {
                HolidayVillage.showSuccess('Timeshare application submitted successfully!');
                document.getElementById('timeshareForm').reset();
                document.getElementById('timeshareApplication').style.display = 'none';
            } else {
                HolidayVillage.showError(response.message || 'Application failed');
            }
        })
        .catch(error => {
            HolidayVillage.showError('Application failed. Please try again.');
            console.error('Timeshare application error:', error);
        });
}

// Utility functions for field validation
function showFieldError(errorElement, message) {
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearFieldError(errorElement) {
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
}

// Export validation functions
window.Validation = {
    validateEmail: function(email) {
        return window.HolidayVillage.validateEmail(email);
    },
    validatePhone: function(phone) {
        return window.HolidayVillage.validatePhone(phone);
    },
    showFieldError,
    clearFieldError
};

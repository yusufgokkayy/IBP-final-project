/**
 * Holiday Village Management System - Core JavaScript
 */

// Global HolidayVillage object
window.HolidayVillage = {
    // Configuration
    config: {
        apiBaseUrl: 'http://localhost:8000/php/',
        dateFormat: 'YYYY-MM-DD'
    },
    
    // Utility functions
    validateEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    validatePhone: function(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        const cleanPhone = phone.replace(/[^\d+]/g, '');
        return phoneRegex.test(cleanPhone);
    },
    
    formatDate: function(date) {
        return new Date(date).toISOString().split('T')[0];
    },
    
    calculateAge: function(birthDate) {
        const birth = new Date(birthDate);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        
        return age;
    },
    
    // AJAX request method
    makeAjaxRequest: function(url, method = 'GET', data = null) {
        return new Promise((resolve, reject) => {
            // If URL doesn't start with http, prepend the base URL
            if (!url.startsWith('http')) {
                url = this.config.apiBaseUrl + url.replace(/^\/?(php\/)?/, '');
            }
            
            const xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            // Add authentication token if available
            const userToken = localStorage.getItem('userToken');
            if (userToken) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + userToken);
            }
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            resolve(xhr.responseText);
                        }
                    } else {
                        reject({
                            status: xhr.status,
                            message: xhr.responseText
                        });
                    }
                }
            };
            
            xhr.onerror = function() {
                reject({
                    status: 0,
                    message: 'Network error occurred'
                });
            };
            
            if (data) {
                xhr.send(JSON.stringify(data));
            } else {
                xhr.send();
            }
        });
    },
    
    // Message display methods
    showSuccess: function(message) {
        this.showMessage(message, 'success');
    },
    
    showError: function(message) {
        this.showMessage(message, 'error');
    },
    
    showInfo: function(message) {
        this.showMessage(message, 'info');
    },
    
    showMessage: function(message, type = 'info') {
        // Remove existing messages
        this.hideMessages();
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.innerHTML = `
            <span class="message-text">${message}</span>
            <button class="message-close" onclick="HolidayVillage.hideMessages()">&times;</button>
        `;
        
        // Add to page
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(messageDiv, container.firstChild);
        
        // Auto-hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(() => {
                this.hideMessages();
            }, 5000);
        }
    },
    
    hideMessages: function() {
        const messages = document.querySelectorAll('.message');
        messages.forEach(msg => msg.remove());
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    checkUserSession();
    initializeNavigation();
    initializeDateInputs();
}

function checkUserSession() {
    const userToken = localStorage.getItem('userToken');
    const userName = localStorage.getItem('userName');
    
    if (userToken && userName) {
        updateNavigationForLoggedInUser(userName);
    }
}

function updateNavigationForLoggedInUser(userName) {
    const nav = document.querySelector('.nav');
    if (nav) {
        const userMenu = document.createElement('div');
        userMenu.className = 'user-menu';
        userMenu.innerHTML = `
            <span class="user-greeting">Welcome, ${userName}</span>
            <a href="#" class="nav-link" onclick="logout()">Logout</a>
        `;
        nav.appendChild(userMenu);
    }
}

function initializeNavigation() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
}

function initializeDateInputs() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        if (input.name === 'checkIn' || input.name === 'checkOut' || 
            input.id === 'checkIn' || input.id === 'checkOut' ||
            input.classList.contains('booking-date')) {
            input.min = today;
        }
        
        if (input.name === 'birthDate' || input.id === 'birthDate' ||
            input.classList.contains('birth-date')) {
            const maxDate = today;
            const minDate = new Date();
            minDate.setFullYear(minDate.getFullYear() - 100);
            const minDateString = minDate.toISOString().split('T')[0];
            
            input.max = maxDate;
            input.min = minDateString;
        }
    });
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('userToken');
        localStorage.removeItem('userName');
        window.location.href = 'index.html';
    }
}
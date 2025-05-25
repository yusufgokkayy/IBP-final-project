// Reservations JavaScript - AJAX functionality for reservation management
// This file handles all reservation-related AJAX calls and dynamic content

document.addEventListener('DOMContentLoaded', function() {
    initializeReservations();
});

function initializeReservations() {
    const checkAvailabilityBtn = document.getElementById('checkAvailability');
    if (checkAvailabilityBtn) {
        checkAvailabilityBtn.addEventListener('click', checkAvailability);
    }
    
    // Load hotels for dropdown
    loadHotels();
    
    // Setup accommodation type change handler
    const accommodationTypeSelect = document.getElementById('accommodationType');
    if (accommodationTypeSelect) {
        accommodationTypeSelect.addEventListener('change', function() {
            const hotelSelection = document.getElementById('hotelSelection');
            if (this.value === 'hotel') {
                hotelSelection.style.display = 'block';
            } else {
                hotelSelection.style.display = 'none';
            }
        });
    }
    
    // Load user reservations if logged in
    if (localStorage.getItem('userToken')) {
        loadUserReservations();
    }
}

function loadHotels() {
    HolidayVillage.makeAjaxRequest('get_hotels.php')
        .then(response => {
            console.log('Hotels response:', response);
            
            let hotels = [];
            if (response.success && response.data && Array.isArray(response.data)) {
                hotels = response.data;
            } else if (response.success && Array.isArray(response)) {
                hotels = response;
            } else if (response.data) {
                hotels = Object.values(response.data);
            } else {
                // Fallback: sayısal index'li objeler
                hotels = Object.keys(response).filter(key => !isNaN(key)).map(key => response[key]);
            }
            
            console.log('Processed hotels:', hotels);
            
            const hotelSelect = document.getElementById('hotel');
            if (hotelSelect && hotels.length > 0) {
                // Clear existing options except the first one
                hotelSelect.innerHTML = '<option value="">Select Hotel</option>';
                
                hotels.forEach(hotel => {
                    const option = document.createElement('option');
                    option.value = hotel.id;
                    option.textContent = hotel.name;
                    hotelSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading hotels:', error);
        });
}

function checkAvailability() {
    const checkInDate = document.getElementById('check_in_date');
    const checkOutDate = document.getElementById('check_out_date');
    
    // Bugünün tarihini al (saat bilgisi olmadan)
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Eğer tarih seçilmemişse, bugünün tarihini varsayılan yap
    if (!checkInDate.value) {
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        checkInDate.value = tomorrow.toISOString().split('T')[0];
    }
    
    if (!checkOutDate.value) {
        const dayAfterTomorrow = new Date(today);
        dayAfterTomorrow.setDate(dayAfterTomorrow.getDate() + 2);
        checkOutDate.value = dayAfterTomorrow.toISOString().split('T')[0];
    }
    
    // Seçilen tarihleri kontrol et
    const selectedCheckIn = new Date(checkInDate.value);
    const selectedCheckOut = new Date(checkOutDate.value);
    
    // Tarih validasyonu
    if (selectedCheckIn < today) {
        alert('Giriş tarihi bugünden önceki bir tarih olamaz!');
        checkInDate.value = new Date(today.getTime() + 24*60*60*1000).toISOString().split('T')[0];
        return;
    }
    
    if (selectedCheckOut <= selectedCheckIn) {
        alert('Çıkış tarihi giriş tarihinden sonra olmalıdır!');
        const newCheckOut = new Date(selectedCheckIn);
        newCheckOut.setDate(newCheckOut.getDate() + 1);
        checkOutDate.value = newCheckOut.toISOString().split('T')[0];
        return;
    }
    
    console.log('Check-in date:', checkInDate.value);
    console.log('Check-out date:', checkOutDate.value);
    
    // AJAX isteği gönder
    makeAjaxRequest('/check_availability.php', {
        property_type: 'hotel_room',
        property_id: currentHotelId,
        check_in_date: checkInDate.value,
        check_out_date: checkOutDate.value
    })
    .then(response => {
        console.log('Availability response:', response);
        if (response.success) {
            displayAvailableRooms(response.available || []);
        } else {
            console.error('Availability check failed:', response.message);
            displayAvailableRooms([]);
        }
    })
    .catch(error => {
        console.error('Error checking room availability:', error);
        displayAvailableRooms([]);
    });
}

function displayAvailabilityResults(response) {
    const resultsContainer = document.getElementById('availabilityResults');
    const makeReservationBtn = document.getElementById('makeReservation');
    
    if (!response.success) {
        HolidayVillage.showError(response.message || 'No availability found');
        resultsContainer.style.display = 'none';
        makeReservationBtn.style.display = 'none';
        return;
    }
    
    if (!response.available || response.available.length === 0) {
        resultsContainer.innerHTML = `
            <div class="no-availability">
                <h4><i class="fas fa-exclamation-triangle"></i> No Availability</h4>
                <p>Sorry, no rooms or houses are available for your selected dates and criteria.</p>
                <p>Please try different dates or accommodation type.</p>
            </div>
        `;
        resultsContainer.style.display = 'block';
        makeReservationBtn.style.display = 'none';
        return;
    }
    
    let html = '<h4><i class="fas fa-check-circle"></i> Available Options</h4>';
    html += '<div class="available-options">';
    
    response.available.forEach((option, index) => {
        const nights = calculateNights(
            document.getElementById('checkIn').value,
            document.getElementById('checkOut').value
        );
        const totalPrice = option.pricing ? option.pricing.totalPrice : (option.price_per_night * nights);
        
        // Determine property name and type
        const propertyName = option.hotel_name ? `${option.hotel_name} - Room ${option.room_number}` : option.name;
        const propertyType = option.room_number ? 'hotel_room' : 'house';
        const displayType = option.room_type || option.type || 'Standard';
        
        html += `
            <div class="availability-option" data-option-id="${option.id}">
                <input type="radio" id="option_${index}" name="selectedOption" 
                       value="${option.id}" 
                       data-property-type="${propertyType}"
                       data-price="${option.price_per_night}">
                <label for="option_${index}" class="option-label">
                    <div class="option-details">
                        <h5>${propertyName}</h5>
                        <p class="option-type">${displayType}</p>
                        <p class="option-description">${option.description || ''}</p>
                        <div class="option-features">
                            <span class="feature-tag">Capacity: ${option.capacity} guests</span>
                            ${option.size_sqm ? `<span class="feature-tag">${option.size_sqm}m²</span>` : ''}
                            ${option.has_pool ? '<span class="feature-tag">Pool</span>' : ''}
                        </div>
                        <div class="option-pricing">
                            <span class="price-per-night">$${option.price_per_night}/night</span>
                            <span class="total-price">Total: $${totalPrice} (${nights} nights)</span>
                        </div>
                    </div>
                </label>
            </div>
        `;
    });
    
    html += '</div>';
    
    resultsContainer.innerHTML = html;
    resultsContainer.style.display = 'block';
    
    // Add event listeners to radio buttons
    const radioButtons = resultsContainer.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                makeReservationBtn.style.display = 'block';
                
                // Store selected property info for reservation
                makeReservationBtn.dataset.propertyId = this.value;
                makeReservationBtn.dataset.propertyType = this.dataset.propertyType;
            }
        });
    });
}

function calculateNights(checkIn, checkOut) {
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    const timeDiff = checkOutDate - checkInDate;
    return Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
}

function loadUserReservations() {
    HolidayVillage.makeAjaxRequest('php/get_user_reservations.php')
        .then(response => {
            if (response.success) {
                displayUserReservations(response.reservations);
            }
        })
        .catch(error => {
            console.error('Error loading user reservations:', error);
        });
}

function displayUserReservations(reservations) {
    const reservationsList = document.getElementById('reservationsList');
    if (!reservationsList) return;
    
    if (!reservations || reservations.length === 0) {
        reservationsList.innerHTML = `
            <div class="no-reservations">
                <i class="fas fa-calendar-times"></i>
                <p>You don't have any reservations yet.</p>
                <a href="#reservation-section" class="btn btn-primary">Make Your First Reservation</a>
            </div>
        `;
        return;
    }
    
    let html = '';
    reservations.forEach(reservation => {
        const statusClass = getStatusClass(reservation.status);
        const canCancel = canCancelReservation(reservation);
        const canModify = canModifyReservation(reservation);
        
        html += `
            <div class="reservation-card ${statusClass}">
                <div class="reservation-header">
                    <h4>Reservation #${reservation.id}</h4>
                    <span class="status-badge status-${reservation.status}">${reservation.status.toUpperCase()}</span>
                </div>
                <div class="reservation-details">
                    <div class="detail-row">
                        <span class="detail-label">Property:</span>
                        <span class="detail-value">${reservation.property_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Dates:</span>
                        <span class="detail-value">${HolidayVillage.formatDate(reservation.check_in)} - ${HolidayVillage.formatDate(reservation.check_out)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Guests:</span>
                        <span class="detail-value">${reservation.guests} people</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total:</span>
                        <span class="detail-value total-price">${HolidayVillage.formatCurrency(reservation.total_price)}</span>
                    </div>
                    ${reservation.special_requests ? `
                    <div class="detail-row">
                        <span class="detail-label">Special Requests:</span>
                        <span class="detail-value">${reservation.special_requests}</span>
                    </div>
                    ` : ''}
                </div>
                <div class="reservation-actions">
                    ${canModify ? `<button onclick="modifyReservation(${reservation.id})" class="btn btn-secondary btn-sm">Modify</button>` : ''}
                    ${canCancel ? `<button onclick="cancelReservation(${reservation.id})" class="btn btn-danger btn-sm">Cancel</button>` : ''}
                    <button onclick="viewReservationDetails(${reservation.id})" class="btn btn-primary btn-sm">View Details</button>
                </div>
            </div>
        `;
    });
    
    reservationsList.innerHTML = html;
}

function getStatusClass(status) {
    switch (status.toLowerCase()) {
        case 'confirmed': return 'status-confirmed';
        case 'pending': return 'status-pending';
        case 'cancelled': return 'status-cancelled';
        case 'completed': return 'status-completed';
        default: return '';
    }
}

function canCancelReservation(reservation) {
    const checkInDate = new Date(reservation.check_in);
    const today = new Date();
    const daysDiff = Math.ceil((checkInDate - today) / (1000 * 60 * 60 * 24));
    
    // Can cancel if check-in is more than 24 hours away and status is confirmed or pending
    return daysDiff > 1 && ['confirmed', 'pending'].includes(reservation.status.toLowerCase());
}

function canModifyReservation(reservation) {
    const checkInDate = new Date(reservation.check_in);
    const today = new Date();
    const daysDiff = Math.ceil((checkInDate - today) / (1000 * 60 * 60 * 24));
    
    // Can modify if check-in is more than 48 hours away and status is confirmed or pending
    return daysDiff > 2 && ['confirmed', 'pending'].includes(reservation.status.toLowerCase());
}

function cancelReservation(reservationId) {
    if (confirm('Are you sure you want to cancel this reservation? This action cannot be undone.')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        HolidayVillage.makeAjaxRequest('php/cancel_reservation.php', 'POST', { reservationId })
            .then(response => {
                if (response.success) {
                    HolidayVillage.showSuccess('Reservation cancelled successfully');
                    loadUserReservations(); // Reload reservations
                } else {
                    HolidayVillage.showError(response.message || 'Failed to cancel reservation');
                }
            })
            .catch(error => {
                HolidayVillage.showError('Error cancelling reservation. Please try again.');
                console.error('Cancel reservation error:', error);
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
    }
}

function modifyReservation(reservationId) {
    // This would typically show a modal or redirect to an edit form
    HolidayVillage.showMessage('Modification feature will be available soon. Please contact support for changes.', 'info');
    console.log('Modify reservation:', reservationId);
}

function viewReservationDetails(reservationId) {
    HolidayVillage.makeAjaxRequest(`php/get_reservation_details.php?id=${reservationId}`)
        .then(response => {
            if (response.success) {
                showReservationDetailsModal(response.reservation);
            } else {
                HolidayVillage.showError('Failed to load reservation details');
            }
        })
        .catch(error => {
            HolidayVillage.showError('Error loading reservation details');
            console.error('View reservation details error:', error);
        });
}

function showReservationDetailsModal(reservation) {
    // Create modal HTML
    const modalHTML = `
        <div class="modal-overlay" id="reservationModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Reservation Details #${reservation.id}</h3>
                    <button class="modal-close" onclick="closeReservationModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Property Information</h4>
                        <p><strong>Name:</strong> ${reservation.property_name}</p>
                        <p><strong>Type:</strong> ${reservation.property_type}</p>
                        <p><strong>Location:</strong> ${reservation.property_location}</p>
                    </div>
                    <div class="detail-section">
                        <h4>Booking Details</h4>
                        <p><strong>Check-in:</strong> ${HolidayVillage.formatDate(reservation.check_in)}</p>
                        <p><strong>Check-out:</strong> ${HolidayVillage.formatDate(reservation.check_out)}</p>
                        <p><strong>Guests:</strong> ${reservation.guests}</p>
                        <p><strong>Status:</strong> ${reservation.status}</p>
                    </div>
                    <div class="detail-section">
                        <h4>Pricing</h4>
                        <p><strong>Rate per night:</strong> ${HolidayVillage.formatCurrency(reservation.price_per_night)}</p>
                        <p><strong>Number of nights:</strong> ${reservation.nights}</p>
                        <p><strong>Subtotal:</strong> ${HolidayVillage.formatCurrency(reservation.subtotal)}</p>
                        <p><strong>Taxes & Fees:</strong> ${HolidayVillage.formatCurrency(reservation.taxes)}</p>
                        <p><strong>Total:</strong> ${HolidayVillage.formatCurrency(reservation.total_price)}</p>
                    </div>
                    ${reservation.special_requests ? `
                    <div class="detail-section">
                        <h4>Special Requests</h4>
                        <p>${reservation.special_requests}</p>
                    </div>
                    ` : ''}
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeReservationModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add modal styles if not already present
    if (!document.getElementById('modalStyles')) {
        const modalStyles = document.createElement('style');
        modalStyles.id = 'modalStyles';
        modalStyles.textContent = `
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            }
            .modal-content {
                background: white;
                border-radius: 10px;
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
            }
            .modal-header {
                padding: 1.5rem;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .modal-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
            }
            .modal-body {
                padding: 1.5rem;
            }
            .modal-footer {
                padding: 1.5rem;
                border-top: 1px solid #eee;
                text-align: right;
            }
            .detail-section {
                margin-bottom: 1.5rem;
            }
            .detail-section h4 {
                color: #2c3e50;
                margin-bottom: 0.5rem;
            }
        `;
        document.head.appendChild(modalStyles);
    }
}

function closeReservationModal() {
    const modal = document.getElementById('reservationModal');
    if (modal) {
        modal.remove();
    }
}

// Export functions for global use
window.ReservationManager = {
    checkAvailability,
    cancelReservation,
    modifyReservation,
    viewReservationDetails
};

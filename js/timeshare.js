// Timeshare JavaScript - AJAX functionality for timeshare management
// This file handles all timeshare-related AJAX calls and dynamic content

document.addEventListener('DOMContentLoaded', function() {
    initializeTimeshare();
});

function initializeTimeshare() {
    loadTimeshareProperties();
    
    // Show application form and contracts if user is logged in
    if (localStorage.getItem('userToken')) {
        loadUserTimeshares();
        checkTimeshareEligibility();
    }
}

function loadTimeshareProperties() {
    HolidayVillage.makeAjaxRequest('php/get_timeshare_properties.php')
        .then(response => {
            if (response.success) {
                displayTimeshareProperties(response.properties);
            } else {
                console.error('Failed to load timeshare properties');
            }
        })
        .catch(error => {
            console.error('Error loading timeshare properties:', error);
        });
}

function displayTimeshareProperties(properties) {
    const timeshareList = document.getElementById('timeshareList');
    if (!timeshareList) return;
    
    if (!properties || properties.length === 0) {
        timeshareList.innerHTML = `
            <div class="no-properties">
                <i class="fas fa-home"></i>
                <p>No timeshare properties are currently available.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    properties.forEach(property => {
        const features = property.features || [];
        const hasPool = features.includes('pool');
        const isAvailable = property.availability_status === 'available';
        
        html += `
            <div class="timeshare-card ${!isAvailable ? 'unavailable' : ''}" data-property-id="${property.id}">
                <div class="property-image">
                    <img src="${property.image_url || 'images/default-house.jpg'}" alt="${property.name}" onerror="this.src='images/default-house.jpg'">
                    ${hasPool ? '<div class="pool-badge"><i class="fas fa-swimming-pool"></i> Pool</div>' : ''}
                    ${!isAvailable ? '<div class="unavailable-badge">Not Available</div>' : ''}
                </div>
                <div class="timeshare-card-content">
                    <h4>${property.name}</h4>
                    <p class="property-type">${property.type} - ${property.floors} ${property.floors === 1 ? 'Floor' : 'Floors'}</p>
                    <p class="property-description">${property.description}</p>
                    
                    <div class="property-features">
                        <h5>Features:</h5>
                        <ul class="timeshare-features">
                            ${features.map(feature => `<li><i class="fas fa-check"></i> ${feature}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="property-pricing">
                        <div class="price-range">
                            <span class="price-label">Starting from:</span>
                            <span class="price-value">${HolidayVillage.formatCurrency(property.min_price)}</span>
                        </div>
                        <div class="available-periods">
                            <span class="periods-label">Available periods:</span>
                            <div class="periods-list">
                                ${property.available_periods ? property.available_periods.map(period => 
                                    `<span class="period-tag">${period}</span>`
                                ).join('') : '<span class="period-tag">Contact for details</span>'}
                            </div>
                        </div>
                    </div>
                    
                    <div class="property-actions">
                        ${isAvailable ? 
                            `<button onclick="selectProperty(${property.id}, '${property.name}')" class="btn btn-primary">
                                <i class="fas fa-hand-pointer"></i> Select This Property
                            </button>` :
                            `<button class="btn btn-secondary" disabled>
                                <i class="fas fa-clock"></i> Currently Unavailable
                            </button>`
                        }
                        <button onclick="viewPropertyDetails(${property.id})" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> More Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    timeshareList.innerHTML = html;
}

function selectProperty(propertyId, propertyName) {
    // Check if user is logged in
    if (!localStorage.getItem('userToken')) {
        HolidayVillage.showError('Please login to apply for timeshare');
        return;
    }
    
    // Check eligibility first
    checkTimeshareEligibility().then(eligible => {
        if (!eligible) {
            HolidayVillage.showError('You are not eligible for timeshare. You must be over 30 years old and married.');
            return;
        }
        
        // Set selected property
        document.getElementById('selectedProperty').value = propertyId;
        
        // Show application form
        const applicationSection = document.getElementById('timeshareApplication');
        if (applicationSection) {
            applicationSection.style.display = 'block';
            applicationSection.scrollIntoView({ behavior: 'smooth' });
            
            // Update form title
            const formTitle = applicationSection.querySelector('h3');
            if (formTitle) {
                formTitle.innerHTML = `<i class="fas fa-file-contract"></i> Apply for ${propertyName}`;
            }
        }
    });
}

function checkTimeshareEligibility() {
    return HolidayVillage.makeAjaxRequest('php/check_timeshare_eligibility.php')
        .then(response => {
            return response.success && response.eligible;
        })
        .catch(error => {
            console.error('Error checking eligibility:', error);
            return false;
        });
}

function viewPropertyDetails(propertyId) {
    HolidayVillage.makeAjaxRequest(`php/get_property_details.php?id=${propertyId}`)
        .then(response => {
            if (response.success) {
                showPropertyDetailsModal(response.property);
            } else {
                HolidayVillage.showError('Failed to load property details');
            }
        })
        .catch(error => {
            HolidayVillage.showError('Error loading property details');
            console.error('Property details error:', error);
        });
}

function showPropertyDetailsModal(property) {
    const modalHTML = `
        <div class="modal-overlay" id="propertyModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>${property.name}</h3>
                    <button class="modal-close" onclick="closePropertyModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="property-gallery">
                        <img src="${property.image_url || 'images/default-house.jpg'}" alt="${property.name}" class="main-image">
                        ${property.gallery ? property.gallery.map(img => 
                            `<img src="${img}" alt="${property.name}" class="gallery-image">`
                        ).join('') : ''}
                    </div>
                    
                    <div class="detail-section">
                        <h4>Property Information</h4>
                        <p><strong>Type:</strong> ${property.type}</p>
                        <p><strong>Floors:</strong> ${property.floors}</p>
                        <p><strong>Location:</strong> ${property.location}</p>
                        <p><strong>Size:</strong> ${property.size} sq ft</p>
                        <p>${property.detailed_description}</p>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Features & Amenities</h4>
                        <div class="features-grid">
                            ${property.features ? property.features.map(feature => 
                                `<div class="feature-item">
                                    <i class="fas fa-check"></i> ${feature}
                                </div>`
                            ).join('') : '<p>No specific features listed</p>'}
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Pricing & Availability</h4>
                        <div class="pricing-grid">
                            <div class="pricing-item">
                                <span class="pricing-label">1 Week:</span>
                                <span class="pricing-value">${HolidayVillage.formatCurrency(property.pricing?.week_1 || property.min_price)}</span>
                            </div>
                            <div class="pricing-item">
                                <span class="pricing-label">2 Weeks:</span>
                                <span class="pricing-value">${HolidayVillage.formatCurrency(property.pricing?.week_2 || property.min_price * 1.8)}</span>
                            </div>
                            <div class="pricing-item">
                                <span class="pricing-label">4 Weeks:</span>
                                <span class="pricing-value">${HolidayVillage.formatCurrency(property.pricing?.week_4 || property.min_price * 3.5)}</span>
                            </div>
                            <div class="pricing-item">
                                <span class="pricing-label">8 Weeks:</span>
                                <span class="pricing-value">${HolidayVillage.formatCurrency(property.pricing?.week_8 || property.min_price * 6.5)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Available Periods</h4>
                        <div class="periods-grid">
                            ${property.available_periods ? property.available_periods.map(period => 
                                `<div class="period-item ${period.available ? 'available' : 'unavailable'}">
                                    <span class="period-name">${period.name}</span>
                                    <span class="period-status">${period.available ? 'Available' : 'Booked'}</span>
                                </div>`
                            ).join('') : '<p>Contact for period availability</p>'}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    ${property.availability_status === 'available' ? 
                        `<button onclick="selectProperty(${property.id}, '${property.name}')" class="btn btn-primary">
                            Apply for This Property
                        </button>` : ''
                    }
                    <button class="btn btn-secondary" onclick="closePropertyModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Add modal styles if not already present
    if (!document.getElementById('propertyModalStyles')) {
        const modalStyles = document.createElement('style');
        modalStyles.id = 'propertyModalStyles';
        modalStyles.textContent = `
            .property-gallery {
                margin-bottom: 1.5rem;
            }
            .main-image {
                width: 100%;
                height: 200px;
                object-fit: cover;
                border-radius: 8px;
                margin-bottom: 1rem;
            }
            .gallery-image {
                width: 80px;
                height: 60px;
                object-fit: cover;
                border-radius: 4px;
                margin-right: 0.5rem;
                cursor: pointer;
            }
            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 0.5rem;
            }
            .feature-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .feature-item i {
                color: #00b894;
            }
            .pricing-grid, .periods-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            .pricing-item, .period-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .period-item.available {
                background: #d4edda;
                border-left: 3px solid #28a745;
            }
            .period-item.unavailable {
                background: #f8d7da;
                border-left: 3px solid #dc3545;
            }
        `;
        document.head.appendChild(modalStyles);
    }
}

function closePropertyModal() {
    const modal = document.getElementById('propertyModal');
    if (modal) {
        modal.remove();
    }
}

function loadUserTimeshares() {
    HolidayVillage.makeAjaxRequest('php/get_user_timeshares.php')
        .then(response => {
            if (response.success) {
                displayUserTimeshares(response.contracts);
            }
        })
        .catch(error => {
            console.error('Error loading user timeshares:', error);
        });
}

function displayUserTimeshares(contracts) {
    const contractsList = document.getElementById('contractsList');
    if (!contractsList) return;
    
    if (!contracts || contracts.length === 0) {
        contractsList.innerHTML = `
            <div class="no-contracts">
                <i class="fas fa-file-contract"></i>
                <p>You don't have any timeshare contracts yet.</p>
                <a href="#timeshare-properties" class="btn btn-primary">Explore Properties</a>
            </div>
        `;
        return;
    }
    
    let html = '';
    contracts.forEach(contract => {
        const statusClass = getContractStatusClass(contract.status);
        
        html += `
            <div class="contract-card ${statusClass}">
                <div class="contract-header">
                    <h4>Contract #${contract.contract_number}</h4>
                    <span class="status-badge status-${contract.status}">${contract.status.toUpperCase()}</span>
                </div>
                <div class="contract-details">
                    <div class="detail-row">
                        <span class="detail-label">Property:</span>
                        <span class="detail-value">${contract.property_name}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Period:</span>
                        <span class="detail-value">${contract.period}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value">${contract.duration} weeks</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contract Date:</span>
                        <span class="detail-value">${HolidayVillage.formatDate(contract.contract_date)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Investment:</span>
                        <span class="detail-value total-price">${HolidayVillage.formatCurrency(contract.total_amount)}</span>
                    </div>
                </div>
                <div class="contract-actions">
                    <button onclick="viewContractDetails(${contract.id})" class="btn btn-primary btn-sm">View Contract</button>
                    ${contract.status === 'active' ? `
                        <button onclick="scheduleStay(${contract.id})" class="btn btn-secondary btn-sm">Schedule Stay</button>
                    ` : ''}
                    <button onclick="downloadContract(${contract.id})" class="btn btn-secondary btn-sm">Download PDF</button>
                </div>
            </div>
        `;
    });
    
    contractsList.innerHTML = html;
}

function getContractStatusClass(status) {
    switch (status.toLowerCase()) {
        case 'active': return 'status-active';
        case 'pending': return 'status-pending';
        case 'expired': return 'status-expired';
        case 'cancelled': return 'status-cancelled';
        default: return '';
    }
}

function viewContractDetails(contractId) {
    HolidayVillage.makeAjaxRequest(`php/get_contract_details.php?id=${contractId}`)
        .then(response => {
            if (response.success) {
                showContractDetailsModal(response.contract);
            } else {
                HolidayVillage.showError('Failed to load contract details');
            }
        })
        .catch(error => {
            HolidayVillage.showError('Error loading contract details');
            console.error('Contract details error:', error);
        });
}

function showContractDetailsModal(contract) {
    const modalHTML = `
        <div class="modal-overlay" id="contractModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Contract Details #${contract.contract_number}</h3>
                    <button class="modal-close" onclick="closeContractModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="detail-section">
                        <h4>Contract Information</h4>
                        <p><strong>Contract Number:</strong> ${contract.contract_number}</p>
                        <p><strong>Status:</strong> ${contract.status}</p>
                        <p><strong>Contract Date:</strong> ${HolidayVillage.formatDate(contract.contract_date)}</p>
                        <p><strong>Effective From:</strong> ${HolidayVillage.formatDate(contract.effective_from)}</p>
                        <p><strong>Valid Until:</strong> ${HolidayVillage.formatDate(contract.valid_until)}</p>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Property Details</h4>
                        <p><strong>Property:</strong> ${contract.property_name}</p>
                        <p><strong>Type:</strong> ${contract.property_type}</p>
                        <p><strong>Location:</strong> ${contract.property_location}</p>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Timeshare Terms</h4>
                        <p><strong>Period:</strong> ${contract.period}</p>
                        <p><strong>Duration:</strong> ${contract.duration} weeks per year</p>
                        <p><strong>Maximum Guests:</strong> ${contract.max_guests}</p>
                    </div>
                    
                    <div class="detail-section">
                        <h4>Financial Details</h4>
                        <p><strong>Purchase Price:</strong> ${HolidayVillage.formatCurrency(contract.purchase_price)}</p>
                        <p><strong>Annual Maintenance Fee:</strong> ${HolidayVillage.formatCurrency(contract.annual_fee)}</p>
                        <p><strong>Total Investment:</strong> ${HolidayVillage.formatCurrency(contract.total_amount)}</p>
                    </div>
                    
                    ${contract.usage_history && contract.usage_history.length > 0 ? `
                    <div class="detail-section">
                        <h4>Usage History</h4>
                        <div class="usage-list">
                            ${contract.usage_history.map(usage => `
                                <div class="usage-item">
                                    <span class="usage-date">${HolidayVillage.formatDate(usage.start_date)} - ${HolidayVillage.formatDate(usage.end_date)}</span>
                                    <span class="usage-guests">${usage.guests} guests</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                </div>
                <div class="modal-footer">
                    <button onclick="downloadContract(${contract.id})" class="btn btn-primary">Download PDF</button>
                    <button class="btn btn-secondary" onclick="closeContractModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function closeContractModal() {
    const modal = document.getElementById('contractModal');
    if (modal) {
        modal.remove();
    }
}

function scheduleStay(contractId) {
    HolidayVillage.showMessage('Stay scheduling feature will be available soon. Please contact support to schedule your stay.', 'info');
    console.log('Schedule stay for contract:', contractId);
}

function downloadContract(contractId) {
    const link = document.createElement('a');
    link.href = `php/download_contract.php?id=${contractId}`;
    link.download = `contract_${contractId}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Export functions for global use
window.TimeshareManager = {
    selectProperty,
    viewPropertyDetails,
    viewContractDetails,
    scheduleStay,
    downloadContract
};

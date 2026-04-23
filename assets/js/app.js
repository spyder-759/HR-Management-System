// HR Management System JavaScript

// Global variables
let sidebarOpen = false;
let currentModal = null;

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize dropdowns
    initializeDropdowns();
    
    // Initialize file uploads
    initializeFileUploads();
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize auto-save for forms
    initializeAutoSave();
    
    // Initialize keyboard shortcuts
    initializeKeyboardShortcuts();
    
    // Initialize notifications
    initializeNotifications();
    
    // Show loading animation
    hideLoadingScreen();
}

// Loading screen
function showLoadingScreen() {
    const loadingScreen = document.createElement('div');
    loadingScreen.id = 'loadingScreen';
    loadingScreen.className = 'loading-screen';
    loadingScreen.innerHTML = `
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Loading HR Management System...</p>
        </div>
    `;
    document.body.appendChild(loadingScreen);
}

function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen) {
        loadingScreen.style.opacity = '0';
        setTimeout(() => {
            loadingScreen.remove();
        }, 300);
    }
}

// Sidebar functionality
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebarOpen = !sidebarOpen;
    
    if (sidebarOpen) {
        sidebar.classList.add('show');
        document.body.classList.add('sidebar-open');
    } else {
        sidebar.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }
}

// Close sidebar on outside click
document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.header-left button');
    
    if (sidebarOpen && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        toggleSidebar();
    }
});

// Modal functionality
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        currentModal = modal;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Focus first input
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

function closeModal(modalId) {
    if (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    } else if (currentModal) {
        currentModal.classList.remove('show');
        currentModal = null;
    }
    document.body.style.overflow = '';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && currentModal) {
        closeModal();
    }
});

// Close modal on backdrop click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal();
    }
});

// Toast notifications
function showToast(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = getToastIcon(type);
    toast.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Auto remove
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }
    }, duration);
}

function getToastIcon(type) {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

// Form validation
function initializeFormValidations() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(input);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(input);
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    clearFieldError(field);
    
    if (required && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }
    
    if (value) {
        switch (type) {
            case 'email':
                if (!isValidEmail(value)) {
                    showFieldError(field, 'Please enter a valid email address');
                    return false;
                }
                break;
            case 'tel':
                if (!isValidPhone(value)) {
                    showFieldError(field, 'Please enter a valid phone number');
                    return false;
                }
                break;
            case 'number':
                const min = parseFloat(field.min);
                const max = parseFloat(field.max);
                const num = parseFloat(value);
                
                if (!isNaN(min) && num < min) {
                    showFieldError(field, `Value must be at least ${min}`);
                    return false;
                }
                
                if (!isNaN(max) && num > max) {
                    showFieldError(field, `Value must be at most ${max}`);
                    return false;
                }
                break;
        }
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    let errorElement = field.parentNode.querySelector('.field-error');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('error');
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[\d\s\-\+\(\)]+$/.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

// File upload handling
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            handleFileSelect(e.target);
        });
    });
}

function handleFileSelect(input) {
    const files = input.files;
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if (files.length > 0) {
        const file = files[0];
        
        if (file.size > maxSize) {
            showToast('File size must be less than 5MB', 'error');
            input.value = '';
            return;
        }
        
        if (!allowedTypes.includes(file.type)) {
            showToast('Only PDF, DOC, and DOCX files are allowed', 'error');
            input.value = '';
            return;
        }
        
        // Show file info
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <i class="fas fa-file"></i>
            <span>${file.name}</span>
            <span>(${formatFileSize(file.size)})</span>
        `;
        
        // Remove existing file info
        const existing = input.parentNode.querySelector('.file-info');
        if (existing) {
            existing.remove();
        }
        
        input.parentNode.appendChild(fileInfo);
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Auto-save functionality
function initializeAutoSave() {
    const forms = document.querySelectorAll('form[data-autosave]');
    
    forms.forEach(form => {
        let saveTimer;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(() => {
                    autoSaveForm(form);
                }, 2000);
            });
        });
    });
}

function autoSaveForm(form) {
    const formData = new FormData(form);
    const saveUrl = form.dataset.autosave;
    
    // Show saving indicator
    showSavingIndicator(form);
    
    fetch(saveUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Draft saved automatically', 'success', 2000);
        }
    })
    .catch(error => {
        console.error('Auto-save failed:', error);
    })
    .finally(() => {
        hideSavingIndicator(form);
    });
}

function showSavingIndicator(form) {
    const indicator = document.createElement('div');
    indicator.className = 'auto-save-indicator';
    indicator.innerHTML = '<i class="fas fa-save"></i> Saving...';
    form.appendChild(indicator);
}

function hideSavingIndicator(form) {
    const indicator = form.querySelector('.auto-save-indicator');
    if (indicator) {
        indicator.remove();
    }
}

// Keyboard shortcuts
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            focusSearchInput();
        }
        
        // Ctrl/Cmd + N for new item
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            const addButton = document.querySelector('.btn-primary');
            if (addButton && addButton.textContent.includes('Add')) {
                addButton.click();
            }
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

function focusSearchInput() {
    const searchInput = document.querySelector('input[type="search"], input[placeholder*="Search"]');
    if (searchInput) {
        searchInput.focus();
        searchInput.select();
    }
}

// Tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            showTooltip(this);
        });
        
        element.addEventListener('mouseleave', function() {
            hideTooltip(this);
        });
    });
}

function showTooltip(element) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip-popup';
    tooltip.textContent = element.dataset.tooltip;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    
    element.tooltip = tooltip;
}

function hideTooltip(element) {
    if (element.tooltip) {
        element.tooltip.remove();
        element.tooltip = null;
    }
}

// Dropdowns
function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleDropdown(dropdown);
            });
        }
    });
    
    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            closeAllDropdowns();
        }
    });
}

function toggleDropdown(dropdown) {
    const isOpen = dropdown.classList.contains('show');
    closeAllDropdowns();
    
    if (!isOpen) {
        dropdown.classList.add('show');
    }
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown.show').forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}

// Notifications
function initializeNotifications() {
    // Check for server-sent notifications
    const eventSource = new EventSource('/api/notifications');
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        showToast(data.message, data.type);
    };
    
    eventSource.onerror = function() {
        console.log('Notification stream closed');
    };
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions for global use
window.HRMS = {
    toggleSidebar,
    showModal,
    closeModal,
    showToast,
    validateForm,
    debounce,
    throttle
};

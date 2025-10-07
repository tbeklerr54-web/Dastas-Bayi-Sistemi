/**
 * Dastas Bayi Sistemi - Main JavaScript
 */

(function($) {
    'use strict';

    // Global variables
    window.DastasSystem = {
        ajaxUrl: dastas_ajax.ajax_url,
        nonce: dastas_ajax.nonce,
        currentUser: null,
        notifications: [],
        
        // Initialize the system
        init: function() {
            this.bindEvents();
            this.initModals();
            this.initNotifications();
            this.initFormValidation();
            this.loadUserSession();
            console.log('Dastas System initialized');
        },
        
        // Bind all event handlers
        bindEvents: function() {
            // Login form
            if (typeof this.handleLogin === 'function') {
                $(document).on('submit', '#dastas-login-form', this.handleLogin.bind(this));
            }
            
            // Order form
            if (typeof this.handleOrderSubmit === 'function') {
                $(document).on('submit', '#dastas-order-form', this.handleOrderSubmit.bind(this));
            }
            
            // Product management
            if (typeof this.addProductRow === 'function') {
                $(document).on('click', '.add-product-btn', this.addProductRow.bind(this));
            }
            if (typeof this.removeProductRow === 'function') {
                $(document).on('click', '.remove-product-btn', this.removeProductRow.bind(this));
            }
            
            // Order list actions
            if (typeof this.viewOrderDetails === 'function') {
                $(document).on('click', '.view-order-btn', this.viewOrderDetails.bind(this));
            }
            if (typeof this.deleteOrder === 'function') {
                $(document).on('click', '.delete-order-btn', this.deleteOrder.bind(this));
            }
            
            // Notifications
            if (typeof this.markNotificationRead === 'function') {
                $(document).on('click', '.mark-read-btn', this.markNotificationRead.bind(this));
            }
            
            // Profile actions
            if (typeof this.updateProfile === 'function') {
                $(document).on('submit', '#profile-form', this.updateProfile.bind(this));
            }
            if (typeof this.changePassword === 'function') {
                $(document).on('submit', '#password-form', this.changePassword.bind(this));
            }
            
            // Modal controls
            if (typeof this.closeModal === 'function') {
                $(document).on('click', '.modal-close, .modal-overlay', this.closeModal.bind(this));
            }
            
            // Keyboard shortcuts
            if (typeof this.handleKeyboardShortcuts === 'function') {
                $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
            }
        },
        
        // Handle login form submission
        handleLogin: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.text();
            
            // Show loading state
            this.setLoadingState(submitBtn, 'Giriş yapılıyor...');
            
            const formData = {
                action: 'dastas_login',
                bayi_kodu: form.find('#bayi_kodu').val(),
                sifre: form.find('#sifre').val(),
                nonce: this.nonce
            };
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Giriş başarılı! Yönlendiriliyorsunuz...', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        this.showNotification(response.data || 'Giriş başarısız', 'error');
                        this.resetLoadingState(submitBtn, originalText);
                    }
                },
                error: () => {
                    this.showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                    this.resetLoadingState(submitBtn, originalText);
                }
            });
        },
        
        // Handle order form submission
        handleOrderSubmit: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const products = this.collectProductData();
            
            if (products.length === 0) {
                this.showNotification('En az bir ürün eklemelisiniz.', 'warning');
                return;
            }
            
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.text();
            
            this.setLoadingState(submitBtn, 'Sipariş kaydediliyor...');
            
            const formData = {
                action: 'dastas_yeni_siparis_handler',
                products: products,
                nonce: this.nonce
            };
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Sipariş başarıyla kaydedildi!', 'success');
                        this.resetOrderForm();
                        
                        // Redirect to orders page after a delay
                        setTimeout(() => {
                            window.location.href = '/siparislerim/';
                        }, 2000);
                    } else {
                        this.showNotification(response.data || 'Sipariş kaydedilemedi', 'error');
                    }
                    this.resetLoadingState(submitBtn, originalText);
                },
                error: () => {
                    this.showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                    this.resetLoadingState(submitBtn, originalText);
                }
            });
        },
        
        // Add product row to order form
        addProductRow: function() {
            const productTemplate = this.getProductRowTemplate();
            $('.products-container').append(productTemplate);
            this.updateProductNumbers();
            this.calculateTotals();
        },
        
        // Remove product row
        removeProductRow: function(e) {
            const row = $(e.target).closest('.product-row');
            row.fadeOut(300, function() {
                $(this).remove();
                DastasSystem.updateProductNumbers();
                DastasSystem.calculateTotals();
            });
        },
        
        // Collect product data from form
        collectProductData: function() {
            const products = [];
            
            $('.product-row').each(function() {
                const row = $(this);
                const product = {
                    agac_turu: row.find('.agac-turu').val(),
                    kalinlik: row.find('.kalinlik').val(),
                    en: row.find('.en').val(),
                    boy: row.find('.boy').val(),
                    tutkal: row.find('.tutkal').val(),
                    kalite: row.find('.kalite').val(),
                    kaplama: row.find('.kaplama').val(),
                    desen: row.find('.desen').val(),
                    adet: parseInt(row.find('.adet').val()) || 1
                };
                
                // Validate product data
                if (product.agac_turu && product.kalinlik && product.en && product.boy) {
                    // Calculate m³
                    product.m3 = this.calculateM3(product);
                    products.push(product);
                }
            });
            
            return products;
        },
        
        // Calculate m³ for a product
        calculateM3: function(product) {
            const en = parseFloat(product.en) || 0;
            const boy = parseFloat(product.boy) || 0;
            const kalinlik = parseFloat(product.kalinlik) || 0;
            const adet = parseInt(product.adet) || 1;
            
            // Convert mm to m and calculate volume
            const volume = (en / 1000) * (boy / 1000) * (kalinlik / 1000) * adet;
            return Math.round(volume * 1000) / 1000; // Round to 3 decimal places
        },
        
        // Update product numbers in form
        updateProductNumbers: function() {
            $('.product-row').each(function(index) {
                $(this).find('.product-number').text(index + 1);
            });
        },
        
        // Calculate and display totals
        calculateTotals: function() {
            let totalProducts = 0;
            let totalM3 = 0;
            
            $('.product-row').each(function() {
                const row = $(this);
                const adet = parseInt(row.find('.adet').val()) || 0;
                const m3 = DastasSystem.calculateM3({
                    en: row.find('.en').val(),
                    boy: row.find('.boy').val(),
                    kalinlik: row.find('.kalinlik').val(),
                    adet: adet
                });
                
                totalProducts += adet;
                totalM3 += m3;
                
                // Update row total
                row.find('.row-total').text(m3.toFixed(3) + ' m³');
            });
            
            // Update form totals
            $('.total-products').text(totalProducts);
            $('.total-m3').text(totalM3.toFixed(3));
        },
        
        // Get product row template
        getProductRowTemplate: function() {
            return `
                <div class="product-row fade-in">
                    <div class="product-header">
                        <h4>Ürün <span class="product-number">1</span></h4>
                        <button type="button" class="btn btn-danger btn-small remove-product-btn">✕ Kaldır</button>
                    </div>
                    
                    <div class="product-form-grid">
                        <div class="form-group">
                            <label class="form-label">Ağaç Türü *</label>
                            <select class="form-control agac-turu" required>
                                <option value="">Seçiniz</option>
                                <option value="Kayın">Kayın</option>
                                <option value="Kavak">Kavak</option>
                                <option value="Ladin">Ladin</option>
                                <option value="Çam">Çam</option>
                                <option value="Sarıçam">Sarıçam</option>
                                <option value="Karaçam">Karaçam</option>
                                <option value="Huş">Huş</option>
                                <option value="Kızılağaç">Kızılağaç</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Kalınlık (mm) *</label>
                            <select class="form-control kalinlik" required>
                                <option value="">Seçiniz</option>
                                <option value="3">3mm</option>
                                <option value="4">4mm</option>
                                <option value="5">5mm</option>
                                <option value="6">6mm</option>
                                <option value="8">8mm</option>
                                <option value="9">9mm</option>
                                <option value="12">12mm</option>
                                <option value="15">15mm</option>
                                <option value="18">18mm</option>
                                <option value="20">20mm</option>
                                <option value="22">22mm</option>
                                <option value="25">25mm</option>
                                <option value="30">30mm</option>
                                <option value="35">35mm</option>
                                <option value="40">40mm</option>
                                <option value="50">50mm</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">En (mm) *</label>
                            <select class="form-control en" required>
                                <option value="">Seçiniz</option>
                                <option value="170">170mm</option>
                                <option value="125">125mm</option>
                                <option value="122">122mm</option>
                                <option value="150">150mm</option>
                                <option value="152.5">152.5mm</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Boy (mm) *</label>
                            <select class="form-control boy" required>
                                <option value="">Seçiniz</option>
                                <option value="220">220mm</option>
                                <option value="250">250mm</option>
                                <option value="244">244mm</option>
                                <option value="300">300mm</option>
                                <option value="152.5">152.5mm</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tutkal Türü</label>
                            <select class="form-control tutkal">
                                <option value="">Seçiniz</option>
                                <option value="Muf">Muf</option>
                                <option value="Beton">Beton</option>
                                <option value="Marin">Marin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Kalite</label>
                            <select class="form-control kalite">
                                <option value="">Seçiniz</option>
                                <option value="BB/BB">BB/BB</option>
                                <option value="BB/CP">BB/CP</option>
                                <option value="CP/CP">CP/CP</option>
                                <option value="CP/C">CP/C</option>
                                <option value="C/C">C/C</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Kaplama</label>
                            <select class="form-control kaplama">
                                <option value="">Seçiniz</option>
                                <option value="Filmli">Filmli</option>
                                <option value="Petek Desen">Petek Desen</option>
                                <option value="Tırtık Desen">Tırtık Desen</option>
                                <option value="Arpa Desen">Arpa Desen</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Desen</label>
                            <select class="form-control desen">
                                <option value="">Seçiniz</option>
                                <option value="Suyuna">Suyuna</option>
                                <option value="Sokrasına">Sokrasına</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Adet *</label>
                            <input type="number" class="form-control adet" min="1" value="1" required>
                        </div>
                    </div>
                    
                    <div class="product-total">
                        <strong>Toplam: <span class="row-total">0 m³</span></strong>
                    </div>
                </div>
            `;
        },
        
        // Reset order form
        resetOrderForm: function() {
            $('.products-container').empty();
            this.addProductRow();
            this.calculateTotals();
        },
        
        // View order details
        viewOrderDetails: function(e) {
            const orderNumber = $(e.target).data('order-number');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_get_order_details',
                    order_number: orderNumber,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showOrderModal(response.data);
                    } else {
                        this.showNotification('Sipariş detayları yüklenemedi', 'error');
                    }
                }
            });
        },
        
        // Delete order
        deleteOrder: function(e) {
            const orderNumber = $(e.target).data('order-number');
            
            if (!confirm('Bu siparişi silmek istediğinizden emin misiniz?')) {
                return;
            }
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_delete_order',
                    order_number: orderNumber,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Sipariş silindi', 'success');
                        $(e.target).closest('.order-item').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        this.showNotification('Sipariş silinemedi', 'error');
                    }
                }
            });
        },
        
        // Mark notification as read
        markNotificationRead: function(e) {
            const notificationId = $(e.target).data('notification-id');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_mark_notification_read',
                    notification_id: notificationId,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $(e.target).closest('.notification-item').removeClass('unread');
                        $(e.target).remove();
                        this.updateNotificationBadge();
                    }
                }
            });
        },
        
        // Initialize modals
        initModals: function() {
            // Close modal when clicking outside
            $(document).on('click', '.modal', function(e) {
                if (e.target === this) {
                    DastasSystem.closeModal();
                }
            });
            
            // Close modal with escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    DastasSystem.closeModal();
                }
            });
        },
        
        // Show modal
        showModal: function(content, title = '') {
            const modal = $(`
                <div class="modal fade-in">
                    <div class="modal-content slide-up">
                        <div class="modal-header">
                            <h3 class="modal-title">${title}</h3>
                            <span class="close modal-close">&times;</span>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.show();
        },
        
        // Close modal
        closeModal: function() {
            $('.modal').fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        // Show order details modal
        showOrderModal: function(orderData) {
            const content = this.generateOrderDetailsHTML(orderData);
            this.showModal(content, `Sipariş Detayları - ${orderData.siparis_no}`);
        },
        
        // Generate order details HTML
        generateOrderDetailsHTML: function(order) {
            let html = `
                <div class="order-details">
                    <div class="order-info">
                        <h4>Sipariş Bilgileri</h4>
                        <p><strong>Sipariş No:</strong> ${order.siparis_no}</p>
                        <p><strong>Tarih:</strong> ${order.siparis_tarihi}</p>
                        <p><strong>Durum:</strong> <span class="badge durum-${order.durum}">${order.durum}</span></p>
                    </div>
                    
                    <div class="products-list">
                        <h4>Ürünler</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ağaç Türü</th>
                                    <th>Kalınlık</th>
                                    <th>Boyutlar</th>
                                    <th>Adet</th>
                                    <th>m³</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            order.products.forEach(product => {
                html += `
                    <tr>
                        <td>${product.agac_turu}</td>
                        <td>${product.kalinlik}mm</td>
                        <td>${product.en}x${product.boy}mm</td>
                        <td>${product.adet}</td>
                        <td>${product.m3}</td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            
            return html;
        },
        
        // Initialize notifications
        initNotifications: function() {
            this.loadNotifications();
            
            // Check for new notifications every 30 seconds
            setInterval(() => {
                this.checkNewNotifications();
            }, 30000);
        },
        
        // Load notifications
        loadNotifications: function() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_get_notifications',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.notifications = response.data;
                        this.updateNotificationBadge();
                    }
                }
            });
        },
        
        // Check for new notifications
        checkNewNotifications: function() {
            // This would typically check for notifications newer than the last check
            this.loadNotifications();
        },
        
        // Update notification badge
        updateNotificationBadge: function() {
            const unreadCount = this.notifications.filter(n => !n.okundu).length;
            const badge = $('.notification-badge');
            
            if (unreadCount > 0) {
                if (badge.length === 0) {
                    $('.notifications-header h4').append(`<span class="notification-badge">${unreadCount}</span>`);
                } else {
                    badge.text(unreadCount);
                }
            } else {
                badge.remove();
            }
        },
        
        // Initialize form validation
        initFormValidation: function() {
            // Real-time validation
            $(document).on('input change', '.form-control', function() {
                DastasSystem.validateField($(this));
            });
            
            // Calculate totals when product data changes
            $(document).on('input change', '.product-row .form-control', function() {
                DastasSystem.calculateTotals();
            });
        },
        
        // Validate individual field
        validateField: function(field) {
            const value = field.val();
            const isRequired = field.prop('required');
            
            // Remove existing validation classes
            field.removeClass('is-valid is-invalid');
            
            if (isRequired && !value) {
                field.addClass('is-invalid');
                return false;
            } else if (value) {
                field.addClass('is-valid');
                return true;
            }
            
            return true;
        },
        
        // Show notification
        showNotification: function(message, type = 'info', duration = 5000) {
            const notification = $(`
                <div class="notification notification-${type} fade-in">
                    <div class="notification-content">
                        <span class="notification-message">${message}</span>
                        <button class="notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            // Add to page
            if ($('.notifications-container').length === 0) {
                $('body').append('<div class="notifications-container"></div>');
            }
            
            $('.notifications-container').append(notification);
            
            // Auto remove after duration
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, duration);
            
            // Manual close
            notification.find('.notification-close').on('click', function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },
        
        // Set loading state
        setLoadingState: function(button, text) {
            button.prop('disabled', true)
                  .html(`<span class="spinner"></span> ${text}`)
                  .addClass('loading');
        },
        
        // Reset loading state
        resetLoadingState: function(button, originalText) {
            button.prop('disabled', false)
                  .html(originalText)
                  .removeClass('loading');
        },
        
        // Load user session
        loadUserSession: function() {
            // This would load current user data and session info
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_get_user_session',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.currentUser = response.data;
                    }
                }
            });
        },
        
        // Handle keyboard shortcuts
        handleKeyboardShortcuts: function(e) {
            // Ctrl/Cmd + S to save form
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
                e.preventDefault();
                const form = $('form:visible').first();
                if (form.length) {
                    form.submit();
                }
            }
            
            // Escape to close modals
            if (e.keyCode === 27) {
                this.closeModal();
            }
        },
        
        // Utility functions
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(amount);
        },
        
        formatDate: function(date) {
            return new Intl.DateTimeFormat('tr-TR').format(new Date(date));
        },
        
        debounce: function(func, wait) {
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
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.DastasSystem.init();
    });

})(jQuery);
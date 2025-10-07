/**
 * Dastas Bayi v2 - Safe JavaScript (Ohio Theme Compatible)
 * Bu dosya Ohio teması ile JavaScript çakışmalarını önler
 */

(function() {
    'use strict';
    
    // Safe jQuery namespace
    var $dastas = jQuery.noConflict(true);
    
    // Dastas global namespace
    window.DastasSafe = {
        $: $dastas,
        version: '2.1.0',
        isInitialized: false,
        config: {
            selectorScope: '.dastas-plugin-scope',
            eventNamespace: 'dastas',
            debugMode: false
        },
        modules: {},
        
        /**
         * Ana başlatma fonksiyonu
         */
        init: function() {
            if (this.isInitialized) {
                this.log('Dastas already initialized');
                return;
            }
            
            this.log('Initializing Dastas Safe Mode...');
            
            // Event listener'ları safe mode'da ekle
            this.initEventListeners();
            
            // Ohio tema uyumluluğunu kontrol et
            this.checkThemeCompatibility();
            
            // Sadece Dastas elementlerini hedefle
            this.initDastasElements();
            
            // Event bubbling'i önle
            this.preventEventBubbling();
            
            // Modülleri yükle
            this.loadModules();
            
            this.isInitialized = true;
            this.log('Dastas Safe Mode initialized successfully');
        },
        
        /**
         * Event listener'ları safe mode'da ekle
         */
        initEventListeners: function() {
            var self = this;
            
            // Document ready
            this.$(document).ready(function() {
                self.onDocumentReady();
            });
            
            // Window load
            this.$(window).on('load', function() {
                self.onWindowLoad();
            });
            
            // Window resize (throttled)
            this.$(window).on('resize', this.throttle(function() {
                self.onWindowResize();
            }, 250));
        },
        
        /**
         * Document ready handler
         */
        onDocumentReady: function() {
            this.log('Document ready');
            this.initFormHandlers();
            this.initModalHandlers();
            this.initOrderWizard();
        },
        
        /**
         * Window load handler
         */
        onWindowLoad: function() {
            this.log('Window loaded');
            this.initLazyLoading();
        },
        
        /**
         * Window resize handler
         */
        onWindowResize: function() {
            this.log('Window resized');
            this.adjustResponsiveElements();
        },
        
        /**
         * Ohio tema uyumluluğunu kontrol et
         */
        checkThemeCompatibility: function() {
            var currentTheme = this.$('body').attr('class') || '';
            
            if (currentTheme.indexOf('ohio') !== -1) {
                this.log('Ohio theme detected, applying compatibility measures');
                this.applyOhioCompatibility();
            }
            
            // ACF varlığını kontrol et
            if (typeof acf !== 'undefined') {
                this.log('ACF detected, applying ACF compatibility');
                this.applyACFCompatibility();
            }
        },
        
        /**
         * Ohio tema uyumluluğu uygula
         */
        applyOhioCompatibility: function() {
            // Ohio tema event'lerini override etme
            var originalOhioInit = window.ohio_init;
            if (typeof originalOhioInit === 'function') {
                window.ohio_init = function() {
                    originalOhioInit.apply(this, arguments);
                    // Ohio init'ten sonra Dastas event'lerini yeniden bağla
                    DastasSafe.rebindEvents();
                };
            }
            
            // Ohio ACF field'larını bypass et
            this.$('.dastas-plugin-scope').on('acf/setup_fields', function(e) {
                e.stopPropagation();
                DastasSafe.log('Blocked ACF setup_fields for Dastas scope');
            });
        },
        
        /**
         * ACF uyumluluğu uygula
         */
        applyACFCompatibility: function() {
            // ACF field validator'larını Dastas scope'undan hariç tut
            if (typeof acf !== 'undefined' && acf.add_filter) {
                acf.add_filter('validation_complete', function(json, form) {
                    if (form.closest('.dastas-plugin-scope').length > 0) {
                        return json; // Dastas formları için ACF validation'ı bypass et
                    }
                    return json;
                });
            }
        },
        
        /**
         * Sadece Dastas elementlerini hedefle
         */
        initDastasElements: function() {
            var self = this;
            var $scope = this.$(this.config.selectorScope);
            
            if ($scope.length === 0) {
                this.log('No Dastas scope found');
                return;
            }
            
            $scope.each(function() {
                var $currentScope = self.$(this);
                self.log('Initializing Dastas scope:', $currentScope);
                
                // Scope içindeki elementleri işle
                self.processScope($currentScope);
            });
        },
        
        /**
         * Scope içindeki elementleri işle
         */
        processScope: function($scope) {
            this.log('Processing scope:', $scope);
            
            // Form elementlerini işle
            this.processForms($scope);
            
            // Button'ları işle
            this.processButtons($scope);
            
            // Modal'ları işle
            this.processModals($scope);
            
            // Table'ları işle
            this.processTables($scope);
        },
        
        /**
         * Form'ları işle
         */
        processForms: function($scope) {
            var self = this;
            
            $scope.find('form').each(function() {
                var $form = self.$(this);
                
                // Önceki event handler'ları temizle
                $form.off('.' + self.config.eventNamespace);
                
                // Yeni event handler'ları ekle
                $form.on('submit.' + self.config.eventNamespace, function(e) {
                    return self.handleFormSubmit(e, $form);
                });
                
                // Form validation
                $form.on('input.' + self.config.eventNamespace + ' change.' + self.config.eventNamespace, 'input, select, textarea', function(e) {
                    self.handleFieldValidation(e, self.$(this));
                });
            });
        },
        
        /**
         * Button'ları işle
         */
        processButtons: function($scope) {
            var self = this;
            
            $scope.find('.btn').each(function() {
                var $btn = self.$(this);
                
                // Önceki event handler'ları temizle
                $btn.off('.' + self.config.eventNamespace);
                
                // Yeni event handler'ları ekle
                $btn.on('click.' + self.config.eventNamespace, function(e) {
                    return self.handleButtonClick(e, $btn);
                });
            });
        },
        
        /**
         * Modal'ları işle
         */
        processModals: function($scope) {
            var self = this;
            
            $scope.find('.modal').each(function() {
                var $modal = self.$(this);
                
                // Close button
                $modal.find('.close').off('.' + self.config.eventNamespace).on('click.' + self.config.eventNamespace, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.closeModal($modal);
                });
                
                // Backdrop click
                $modal.off('.' + self.config.eventNamespace).on('click.' + self.config.eventNamespace, function(e) {
                    if (e.target === this) {
                        self.closeModal($modal);
                    }
                });
            });
        },
        
        /**
         * Table'ları işle
         */
        processTables: function($scope) {
            var self = this;
            
            $scope.find('.table').each(function() {
                var $table = self.$(this);
                
                // Responsive table wrapper ekle
                if (!$table.parent().hasClass('table-responsive')) {
                    $table.wrap('<div class="table-responsive"></div>');
                }
                
                // Sort functionality
                $table.find('th[data-sort]').off('.' + self.config.eventNamespace).on('click.' + self.config.eventNamespace, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.handleTableSort(self.$(this), $table);
                });
            });
        },
        
        /**
         * Event bubbling'i önle
         */
        preventEventBubbling: function() {
            var self = this;
            
            // Ana scope elementlerinde event bubbling'i durdur
            this.$(this.config.selectorScope).on('click.' + this.config.eventNamespace, function(e) {
                e.stopPropagation();
                self.log('Event bubbling stopped for Dastas scope');
            });
            
            // Form submit'lerinde de bubbling'i durdur
            this.$(this.config.selectorScope).on('submit.' + this.config.eventNamespace, function(e) {
                e.stopPropagation();
                self.log('Form submit bubbling stopped for Dastas scope');
            });
        },
        
        /**
         * Event handler'ları yeniden bağla
         */
        rebindEvents: function() {
            this.log('Rebinding events...');
            this.initDastasElements();
        },
        
        /**
         * Form submit handler
         */
        handleFormSubmit: function(e, $form) {
            e.stopPropagation();
            
            this.log('Form submit:', $form);
            
            // Form validation
            if (!this.validateForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Loading state ekle
            this.setFormLoading($form, true);
            
            // AJAX form ise
            if ($form.hasClass('ajax-form')) {
                e.preventDefault();
                this.submitFormAjax($form);
                return false;
            }
            
            return true;
        },
        
        /**
         * Button click handler
         */
        handleButtonClick: function(e, $btn) {
            e.stopPropagation();
            
            this.log('Button click:', $btn);
            
            // Disabled button'a tıklanmasını önle
            if ($btn.hasClass('disabled') || $btn.prop('disabled')) {
                e.preventDefault();
                return false;
            }
            
            // Modal trigger
            var modalTarget = $btn.data('modal-target');
            if (modalTarget) {
                e.preventDefault();
                this.openModal(modalTarget);
                return false;
            }
            
            // Loading state
            if ($btn.hasClass('btn-loading')) {
                this.setButtonLoading($btn, true);
            }
            
            return true;
        },
        
        /**
         * Field validation handler
         */
        handleFieldValidation: function(e, $field) {
            this.log('Field validation:', $field);
            
            var value = $field.val();
            var isValid = true;
            var errorMessage = '';
            
            // Required field check
            if ($field.prop('required') && !value.trim()) {
                isValid = false;
                errorMessage = 'Bu alan zorunludur.';
            }
            
            // Email validation
            if ($field.attr('type') === 'email' && value && !this.isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Geçerli bir e-posta adresi girin.';
            }
            
            // Phone validation
            if ($field.hasClass('phone') && value && !this.isValidPhone(value)) {
                isValid = false;
                errorMessage = 'Geçerli bir telefon numarası girin.';
            }
            
            this.setFieldValidation($field, isValid, errorMessage);
        },
        
        /**
         * Table sort handler
         */
        handleTableSort: function($th, $table) {
            this.log('Table sort:', $th);
            
            var sortKey = $th.data('sort');
            var sortOrder = $th.hasClass('sort-asc') ? 'desc' : 'asc';
            
            // Sort classes'ları temizle
            $table.find('th').removeClass('sort-asc sort-desc');
            $th.addClass('sort-' + sortOrder);
            
            // Table'ı sırala
            this.sortTable($table, sortKey, sortOrder);
        },
        
        /**
         * Form validation
         */
        validateForm: function($form) {
            var isValid = true;
            var self = this;
            
            $form.find('input, select, textarea').each(function() {
                var $field = self.$(this);
                self.handleFieldValidation({}, $field);
                
                if ($field.hasClass('is-invalid')) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        /**
         * Field validation state ayarla
         */
        setFieldValidation: function($field, isValid, errorMessage) {
            $field.removeClass('is-valid is-invalid');
            
            var $errorDiv = $field.next('.invalid-feedback');
            if ($errorDiv.length === 0) {
                $errorDiv = this.$('<div class="invalid-feedback"></div>');
                $field.after($errorDiv);
            }
            
            if (isValid) {
                $field.addClass('is-valid');
                $errorDiv.hide();
            } else {
                $field.addClass('is-invalid');
                $errorDiv.text(errorMessage).show();
            }
        },
        
        /**
         * Form loading state
         */
        setFormLoading: function($form, isLoading) {
            var $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
            
            if (isLoading) {
                $submitBtn.addClass('loading').prop('disabled', true);
                $form.addClass('form-loading');
            } else {
                $submitBtn.removeClass('loading').prop('disabled', false);
                $form.removeClass('form-loading');
            }
        },
        
        /**
         * Button loading state
         */
        setButtonLoading: function($btn, isLoading) {
            if (isLoading) {
                $btn.addClass('loading').prop('disabled', true);
                if (!$btn.find('.spinner').length) {
                    $btn.prepend('<span class="spinner"></span>');
                }
            } else {
                $btn.removeClass('loading').prop('disabled', false);
                $btn.find('.spinner').remove();
            }
        },
        
        /**
         * Modal aç
         */
        openModal: function(modalSelector) {
            var $modal = this.$(modalSelector);
            if ($modal.length === 0) return;
            
            $modal.addClass('show').css('display', 'block');
            this.$('body').addClass('modal-open');
            
            // Focus modal'a yönelt
            $modal.focus();
            
            this.log('Modal opened:', modalSelector);
        },
        
        /**
         * Modal kapat
         */
        closeModal: function($modal) {
            $modal.removeClass('show').css('display', 'none');
            this.$('body').removeClass('modal-open');
            
            this.log('Modal closed:', $modal);
        },
        
        /**
         * AJAX form submit
         */
        submitFormAjax: function($form) {
            var self = this;
            var formData = new FormData($form[0]);
            
            this.$.ajax({
                url: $form.attr('action') || window.dastas_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    self.handleAjaxSuccess(response, $form);
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, status, error, $form);
                },
                complete: function() {
                    self.setFormLoading($form, false);
                }
            });
        },
        
        /**
         * AJAX success handler
         */
        handleAjaxSuccess: function(response, $form) {
            this.log('AJAX success:', response);
            
            if (response.success) {
                this.showAlert('success', response.data.message || 'İşlem başarılı!');
                
                // Form'u reset et
                if (!response.data.keep_form) {
                    $form[0].reset();
                }
                
                // Redirect varsa
                if (response.data.redirect) {
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1500);
                }
            } else {
                this.showAlert('error', response.data.message || 'Bir hata oluştu!');
            }
        },
        
        /**
         * AJAX error handler
         */
        handleAjaxError: function(xhr, status, error, $form) {
            this.log('AJAX error:', error);
            this.showAlert('error', 'Bağlantı hatası! Lütfen tekrar deneyin.');
        },
        
        /**
         * Alert göster
         */
        showAlert: function(type, message) {
            var alertClass = 'alert-' + type;
            var $alert = this.$('<div class="alert ' + alertClass + '">' + message + '</div>');
            
            // Alert container'ı bul veya oluştur
            var $container = this.$(this.config.selectorScope).first();
            if ($container.length === 0) {
                $container = this.$('body');
            }
            
            $container.prepend($alert);
            
            // Auto hide
            setTimeout(function() {
                $alert.fadeOut(function() {
                    $alert.remove();
                });
            }, 5000);
        },
        
        /**
         * Table sıralama
         */
        sortTable: function($table, sortKey, sortOrder) {
            var $tbody = $table.find('tbody');
            var $rows = $tbody.find('tr').toArray();
            
            $rows.sort(function(a, b) {
                var aVal = this.$(a).find('[data-sort-value]').data('sort-value') || this.$(a).find('td').eq(0).text();
                var bVal = this.$(b).find('[data-sort-value]').data('sort-value') || this.$(b).find('td').eq(0).text();
                
                if (sortOrder === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            }.bind(this));
            
            $tbody.empty().append($rows);
        },
        
        /**
         * Lazy loading başlat
         */
        initLazyLoading: function() {
            var self = this;
            
            // Intersection Observer destekliyorsa kullan
            if ('IntersectionObserver' in window) {
                var lazyImageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var lazyImage = entry.target;
                            lazyImage.src = lazyImage.dataset.src;
                            lazyImage.classList.remove('lazy');
                            lazyImageObserver.unobserve(lazyImage);
                        }
                    });
                });
                
                this.$(this.config.selectorScope + ' img.lazy').each(function() {
                    lazyImageObserver.observe(this);
                });
            }
        },
        
        /**
         * Responsive elementleri ayarla
         */
        adjustResponsiveElements: function() {
            var windowWidth = this.$(window).width();
            
            // Mobile view
            if (windowWidth < 768) {
                this.$(this.config.selectorScope + ' .table').addClass('table-mobile');
            } else {
                this.$(this.config.selectorScope + ' .table').removeClass('table-mobile');
            }
        },
        
        /**
         * Modül yükle
         */
        loadModule: function(name, module) {
            this.modules[name] = module;
            this.log('Module loaded:', name);
        },
        
        /**
         * Modül getir
         */
        getModule: function(name) {
            return this.modules[name];
        },
        
        /**
         * Tüm modülleri yükle
         */
        loadModules: function() {
            // Order wizard modülü
            if (typeof DastasOrderWizard !== 'undefined') {
                this.loadModule('orderWizard', DastasOrderWizard);
            }
            
            // Dashboard modülü
            if (typeof DastasDashboard !== 'undefined') {
                this.loadModule('dashboard', DastasDashboard);
            }
        },
        
        /**
         * Order wizard başlat
         */
        initOrderWizard: function() {
            var $wizard = this.$(this.config.selectorScope + ' .order-wizard');
            if ($wizard.length > 0 && this.modules.orderWizard) {
                this.modules.orderWizard.init($wizard);
            }
        },
        
        /**
         * Form handler'ları başlat
         */
        initFormHandlers: function() {
            // Login form
            this.initLoginForm();
            
            // Order form
            this.initOrderForm();
            
            // Profile form
            this.initProfileForm();
        },
        
        /**
         * Login form başlat
         */
        initLoginForm: function() {
            var self = this;
            var $loginForm = this.$(this.config.selectorScope + ' .login-form');
            
            if ($loginForm.length === 0) return;
            
            $loginForm.addClass('ajax-form');
            
            // Remember me functionality
            $loginForm.find('input[name="remember"]').on('change', function() {
                var isChecked = self.$(this).is(':checked');
                localStorage.setItem('dastas_remember_user', isChecked);
            });
        },
        
        /**
         * Order form başlat
         */
        initOrderForm: function() {
            var $orderForm = this.$(this.config.selectorScope + ' .order-form');
            
            if ($orderForm.length === 0) return;
            
            $orderForm.addClass('ajax-form');
            
            // Auto-calculate totals
            this.initOrderCalculations($orderForm);
        },
        
        /**
         * Profile form başlat
         */
        initProfileForm: function() {
            var $profileForm = this.$(this.config.selectorScope + ' .profile-form');
            
            if ($profileForm.length === 0) return;
            
            $profileForm.addClass('ajax-form');
        },
        
        /**
         * Order calculations başlat
         */
        initOrderCalculations: function($form) {
            var self = this;
            
            $form.on('input change', '.quantity-input, .product-select', function() {
                self.calculateOrderTotal($form);
            });
        },
        
        /**
         * Order total hesapla
         */
        calculateOrderTotal: function($form) {
            var total = 0;
            var self = this;
            
            $form.find('.order-item').each(function() {
                var $item = self.$(this);
                var quantity = parseInt($item.find('.quantity-input').val()) || 0;
                var price = parseFloat($item.find('.price').data('price')) || 0;
                
                var itemTotal = quantity * price;
                $item.find('.item-total').text(self.formatPrice(itemTotal));
                
                total += itemTotal;
            });
            
            $form.find('.order-total').text(this.formatPrice(total));
        },
        
        /**
         * Modal handler'ları başlat
         */
        initModalHandlers: function() {
            // Keyboard navigation
            this.$(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    DastasSafe.$('.modal.show').each(function() {
                        DastasSafe.closeModal(DastasSafe.$(this));
                    });
                }
            });
        },
        
        /**
         * Utility fonksiyonlar
         */
        
        // Email validation
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        // Phone validation
        isValidPhone: function(phone) {
            var phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
            return phoneRegex.test(phone);
        },
        
        // Price formatting
        formatPrice: function(price) {
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY'
            }).format(price);
        },
        
        // Throttle function
        throttle: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Debounce function
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Logging
        log: function() {
            if (this.config.debugMode && console && console.log) {
                console.log.apply(console, ['[Dastas]'].concat(Array.prototype.slice.call(arguments)));
            }
        }
    };
    
    // Custom event dispatcher
    window.DastasSafe.Events = {
        FORM_SUBMIT: 'dastas:form:submit',
        MODAL_OPEN: 'dastas:modal:open',
        MODAL_CLOSE: 'dastas:modal:close',
        WIZARD_NEXT: 'dastas:wizard:next',
        WIZARD_PREV: 'dastas:wizard:prev',
        ORDER_UPDATE: 'dastas:order:update',
        
        dispatch: function(element, eventType, data) {
            if (!element || !element.closest('.dastas-plugin-scope')) {
                return;
            }
            
            var event = new CustomEvent(eventType, {
                detail: data || {},
                bubbles: false, // Ohio tema event'lerine karışmasın
                cancelable: true
            });
            
            element.dispatchEvent(event);
            DastasSafe.log('Event dispatched:', eventType, data);
        },
        
        listen: function(eventType, callback) {
            DastasSafe.$(document).on(eventType, function(e) {
                if (e.target.closest('.dastas-plugin-scope')) {
                    callback(e);
                }
            });
        }
    };
    
    // Document ready'de başlat
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.DastasSafe.init();
        });
    } else {
        window.DastasSafe.init();
    }
    
    // Global error handler
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.indexOf('dastas') !== -1) {
            DastasSafe.log('Dastas error caught:', e.message, e.filename, e.lineno);
        }
    });
    
})();
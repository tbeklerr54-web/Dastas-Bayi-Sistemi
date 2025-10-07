/* Dastas Order Wizard JavaScript */

(function($) {
    'use strict';
    
    const DastasOrderWizard = {
        currentStep: 1,
    totalSteps: 7,
    productData: {},
        productList: [],
    autoAdvanceTimer: null,
    autoAdvanceLock: false,
        
        init: function() {
            this.initializeEvents();
            this.updateStepDisplay();
            this.showStep(1);
                this.ensureModalStyles();
            // Ensure submit button state reflects current product list
            this.updateSubmitAvailability();
        },

        // Accessibility enhancements: set roles/ids and keyboard handling
        enhanceAccessibility: function() {
            // Ensure each accordion header/content pair has proper ARIA attributes
            $('.accordion-header').each(function() {
                const $header = $(this);
                const $content = $header.next('.accordion-content');
                const step = $header.data('step');

                // Ensure content has an id
                if (!$content.attr('id')) {
                    $content.attr('id', 'dastas-accordion-content-step-' + step);
                }

                $header.attr({
                    'role': 'button',
                    'tabindex': 0,
                    'aria-controls': $content.attr('id'),
                    'aria-expanded': ($header.hasClass('active') ? 'true' : 'false')
                });
                $content.attr('aria-hidden', ($header.hasClass('active') ? 'false' : 'true'));
            });
        },

            ensureModalStyles: function() {
                if ($('#dastas-modal-styles').length) return;
                const css = `
                    #dastas-popup-modal { position: fixed; inset: 0; display: none; z-index: 9999; }
                    #dastas-popup-modal .dastas-modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.4); }
                    #dastas-popup-modal .dastas-modal-content { position: absolute; left: 50%; top: 20%; transform: translateX(-50%); background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 6px 24px rgba(0,0,0,0.2); min-width: 260px; max-width: 90%; }
                    #dastas-popup-modal .dastas-modal-close { position: absolute; right: 8px; top: 6px; background: transparent; border: none; font-size: 18px; cursor: pointer; }
                    #dastas-popup-modal .dastas-modal-body { padding: 8px 4px; font-size: 15px; }
                `;
                $('<style id="dastas-modal-styles">'+css+'</style>').appendTo('head');
            },
        
        initializeEvents: function() {
            // Step navigation
            $('.wizard-step').on('click', this.handleStepClick.bind(this));
            
            // Accordion navigation
            $(document).on('click', '.accordion-header', this.handleAccordionClick.bind(this));
            // Keyboard support for accordion headers (Enter / Space)
            $(document).on('keydown', '.accordion-header', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(e.currentTarget).trigger('click');
                }
            });
            
            // Form field changes
            $(document).on('change', '#agac_cinsi, #kalinlik, #ebat1, #ebat2, #miktar, #tutkal, #kalite, #kaplama, #desen', this.handleFormChange.bind(this));
            
            // Specific auto-advance listeners removed — delegated handler above covers these fields
            
            // Navigation buttons (delegation to support dynamic markup)
            $(document).on('click', '.btn-next', (e) => {
                e.preventDefault();
                this.nextStep();
            });
            $(document).on('click', '.btn-prev', (e) => {
                e.preventDefault();
                this.prevStep();
            });
            $(document).on('click', '.btn-add', (e) => {
                e.preventDefault();
                this.addProduct();
            });
            $(document).on('click', '.btn-submit', (e) => {
                e.preventDefault();
                this.submitOrder();
            });
            
            // Product list actions
            $(document).on('click', '.remove-product', this.removeProduct.bind(this));
            $(document).on('click', '.edit-product', this.editProduct.bind(this));
            
            // Clear actions
            $(document).on('click', '#clear-form', this.clearForm.bind(this));
            $(document).on('click', '#clear-all', this.clearAll.bind(this));
            
            // Real-time validation
            $('select[required], input[required]').on('change', this.validateField.bind(this));

            // Initialize ARIA roles and keyboard accessibility
            this.enhanceAccessibility();
        },
        
        handleStepClick: function(e) {
            const stepNumber = parseInt($(e.currentTarget).data('step'));
            
            // Allow going back to completed steps
            if (stepNumber <= this.currentStep) {
                this.showStep(stepNumber);
            }
        },
        
        handleAccordionClick: function(e) {
            const $header = $(e.currentTarget);
            const stepNumber = parseInt($header.data('step'));
            this.showStep(stepNumber);
        },
        
        handleFormChange: function() {
            this.calculateVolume();
            this.validateCurrentStep();
            this.checkAutoAdvance();
        },
        
        checkAutoAdvance: function() {
            // Otomatik adım geçişi - step 7'ye kadar
            if (this.currentStep >= 7) return;

            // Clear any pending auto-advance to avoid multiple advances caused by
            // multiple change events firing in quick succession.
            if (this.autoAdvanceTimer) {
                clearTimeout(this.autoAdvanceTimer);
                this.autoAdvanceTimer = null;
            }

            const step = this.currentStep;
            let shouldAdvance = false;

            if (step === 1 && $('#agac_cinsi').val()) {
                shouldAdvance = true;
            } else if (step === 2 && $('#kalinlik').val()) {
                shouldAdvance = true;
            } else if (step === 3 && $('#ebat1').val() && $('#ebat2').val()) {
                shouldAdvance = true;
            } else if (step === 4 && $('#tutkal').val()) {
                shouldAdvance = true;
            }

            if (shouldAdvance) {
                // If a recent auto-advance just happened, don't chain another
                if (this.autoAdvanceLock) return;

                // Reserve a lock immediately so other rapid change events don't schedule another advance
                this.autoAdvanceLock = true;

                // Debounced advance: schedule one advance and replace any previous one
                this.autoAdvanceTimer = setTimeout(() => {
                    // Re-check validity for the step we were on before advancing
                    if (this.isStepValid(step) && this.currentStep === step) {
                        // Perform the advance
                        this.showStep(step + 1);
                    }

                    // Release the lock shortly after the advance to allow future auto-advances
                    setTimeout(() => {
                        this.autoAdvanceLock = false;
                    }, 350);

                    this.autoAdvanceTimer = null;
                }, 250);
            }
        },
        
        // Optional fields removed - no toggle logic required
        
        showStep: function(stepNumber) {
            this.currentStep = stepNumber;
            
            // Update step indicators
            this.updateStepDisplay();
            
            // Show corresponding accordion
            $('.accordion-header').removeClass('active');
            $('.accordion-content').removeClass('active').hide();
            
            const $targetHeader = $(`.accordion-header[data-step="${stepNumber}"]`);
            const $targetContent = $targetHeader.next('.accordion-content');
            
            $targetHeader.addClass('active');
            $targetContent.addClass('active').show().attr('aria-hidden', 'false');

            // Update aria-expanded on headers and aria-hidden on all contents
            $('.accordion-header').each(function() {
                const $h = $(this);
                const $c = $h.next('.accordion-content');
                $h.attr('aria-expanded', $h.hasClass('active') ? 'true' : 'false');
                $c.attr('aria-hidden', $h.hasClass('active') ? 'false' : 'true');
            });
            
            // Smooth scroll to active section
            if ($targetHeader.length > 0) {
                setTimeout(() => {
                    const headerTop = $targetHeader.offset();
                    if (headerTop) {
                        $('html, body').animate({
                            scrollTop: headerTop.top - 100
                        }, 400);
                    }
                }, 100);
            }
            
            // Update navigation buttons
            this.updateNavigationButtons();
            
            // Focus first input in current step
            setTimeout(() => {
                $targetContent.find('select:first, input:first').focus();
            }, 200);
        },
        
        updateStepDisplay: function() {
            $('.wizard-step').each((index, element) => {
                const $step = $(element);
                const stepNumber = parseInt($step.data('step'));
                
                $step.removeClass('active completed');
                
                if (stepNumber === this.currentStep) {
                    $step.addClass('active');
                } else if (stepNumber < this.currentStep) {
                    $step.addClass('completed');
                }
            });
        },
        
        nextStep: function() {
            if (this.validateCurrentStep()) {
                if (this.currentStep < this.totalSteps) {
                    this.showStep(this.currentStep + 1);
                }
            }
        },
        
        prevStep: function() {
            if (this.currentStep > 1) {
                this.showStep(this.currentStep - 1);
            }
        },
        
        validateCurrentStep: function() {
            let isValid = true;
            const $currentContent = $(`.accordion-content[data-step="${this.currentStep}"]`);
            
            // Remove previous error states
            $currentContent.find('.form-group').removeClass('error success');
            
            // Validate required fields in current step
            $currentContent.find('select[required], input[required]').each((index, element) => {
                const $field = $(element);
                const $group = $field.closest('.form-group');
                
                if (!$field.val() || $field.val().trim() === '') {
                    $group.addClass('error');
                    isValid = false;
                } else {
                    $group.addClass('success');
                }
            });
            
            // Update step status
            const $stepHeader = $(`.accordion-header[data-step="${this.currentStep}"]`);
            const $wizardStep = $(`.wizard-step[data-step="${this.currentStep}"]`);
            
            if (isValid) {
                $stepHeader.addClass('completed');
                $wizardStep.addClass('completed');
            } else {
                $stepHeader.removeClass('completed');
                $wizardStep.removeClass('completed');
            }
            
            // updateNavigationButtons çağrısını kaldırıyoruz - sonsuz döngü engelleme
            return isValid;
        },

        updateSubmitAvailability: function() {
            const $submit = $('.btn-submit');
            if ($submit.length) {
                $submit.prop('disabled', this.productList.length === 0);
            }
        },
        
        validateField: function(e) {
            const $field = $(e.target);
            const $group = $field.closest('.form-group');
            
            $group.removeClass('error success');
            
            if (!$field.val() || $field.val().trim() === '') {
                $group.addClass('error');
            } else {
                $group.addClass('success');
            }
            
            this.calculateVolume();
        },
        
        updateNavigationButtons: function() {
            // Use scoped buttons inside current accordion content
            const $currentContent = $(`.accordion-content[data-step="${this.currentStep}"]`);
            const $prevBtn = $currentContent.find('.btn-prev');
            const $nextBtn = $currentContent.find('.btn-next');
            const $addBtn = $currentContent.find('.btn-add');

            // Previous button visibility (if exists in current step)
            if ($prevBtn.length) {
                $prevBtn.toggle(this.currentStep > 1);
            }

            // Current step validation - tek seferlik
            const isCurrentStepValid = this.isStepValid(this.currentStep);

            // Next / Add button
            if ($nextBtn.length) {
                $nextBtn.prop('disabled', !isCurrentStepValid);
            }
            if ($addBtn.length) {
                $addBtn.prop('disabled', !isCurrentStepValid);
            }
        },
        
        isStepValid: function(stepNumber) {
            // Validasyon logic - döngü yapmaz
            if (stepNumber === 1) {
                return !!$('#agac_cinsi').val();
            } else if (stepNumber === 2) {
                return !!$('#kalinlik').val();
            } else if (stepNumber === 3) {
                return !!$('#ebat1').val() && !!$('#ebat2').val();
            } else if (stepNumber === 4) {
                return !!$('#tutkal').val();
            } else if (stepNumber === 5) {
                return true; // Kalite, kaplama, desen opsiyonel
            } else if (stepNumber === 6) {
                return !!$('#miktar').val() && parseInt($('#miktar').val()) > 0;
            } else if (stepNumber === 7) {
                return true; // Sipariş listesi her zaman geçerli
            }
            return false;
        },
        
        calculateVolume: function() {
            const kalinlik = parseFloat($('#kalinlik').val()) || 0;
            const ebat1 = parseFloat($('#ebat1').val()) || 0;
            const ebat2 = parseFloat($('#ebat2').val()) || 0;
            const miktar = parseInt($('#miktar').val()) || 0;
            
            if (kalinlik && ebat1 && ebat2 && miktar) {
                const m3PerPiece = (ebat1 / 100) * (ebat2 / 100) * (kalinlik / 1000);
                const totalM3 = m3PerPiece * miktar;
                $('#volume-result').text(totalM3.toFixed(3));
                
                // Update summary
                this.updateProductSummary({
                    kalinlik, ebat1, ebat2, miktar, m3: totalM3
                });
            } else {
                $('#volume-result').text('0.000');
            }
        },
        
        updateProductSummary: function(data) {
            $('#summary-agac').text($('#agac_cinsi option:selected').text() || '-');
            $('#summary-kalinlik').text(data.kalinlik ? data.kalinlik + ' mm' : '-');
            $('#summary-ebat').text(data.ebat1 && data.ebat2 ? data.ebat1 + ' x ' + data.ebat2 + ' cm' : '-');
            $('#summary-tutkal').text($('#tutkal option:selected').text() || '-');
            $('#summary-miktar').text(data.miktar ? data.miktar + ' adet' : '-');
            $('#summary-volume').text(data.m3 ? data.m3.toFixed(3) + ' m³' : '-');
        },
        
        addProduct: function() {
            if (!this.validateCurrentStep()) {
                this.showPopup('Lütfen tüm zorunlu alanları doldurun.');
                return;
            }
            
            const product = this.collectProductData();
            
            // Add to product list
            this.productList.push(product);
            this.updateProductList();
            this.clearForm();
            this.showStep(1); // Start over for next product
            
            // Show popup confirmation
            this.showPopup('Ürün başarıyla eklendi!');
        },

        showPopup: function(message) {
            // Simple modal implementation
            let $modal = $('#dastas-popup-modal');
            if ($modal.length === 0) {
                $modal = $(`
                    <div id="dastas-popup-modal" class="dastas-modal" style="display:none;">
                        <div class="dastas-modal-backdrop"></div>
                        <div class="dastas-modal-content">
                            <button class="dastas-modal-close">×</button>
                            <div class="dastas-modal-body"></div>
                        </div>
                    </div>
                `);
                $('body').append($modal);
                // close handler
                $modal.on('click', '.dastas-modal-close, .dastas-modal-backdrop', function() {
                    $modal.fadeOut(200);
                });
            }

            $modal.find('.dastas-modal-body').html('<p>'+message+'</p>');
            $modal.fadeIn(200);
            // Auto close after 2s
            setTimeout(() => $modal.fadeOut(200), 2000);
        },
        
        collectProductData: function() {
            return {
                id: Date.now(),
                agac_cinsi: $('#agac_cinsi').val(),
                agac_cinsi_text: $('#agac_cinsi option:selected').text(),
                kalinlik: $('#kalinlik').val(),
                ebat1: $('#ebat1').val(),
                ebat2: $('#ebat2').val(),
                tutkal: $('#tutkal').val(),
                tutkal_text: $('#tutkal option:selected').text(),
                miktar: $('#miktar').val(),
                kalite: $('#kalite').val(),
                kaplama: $('#kaplama').val(),
                desen: $('#desen').val(),
                // Note field removed per request
                m3: parseFloat($('#volume-result').text())
            };
        },
        
        updateProductList: function() {
            const $container = $('#product-list-container');
            
            if (this.productList.length === 0) {
                $container.html('<div class="alert alert-info">Henüz ürün eklenmedi.</div>');
                $('#order-summary, #order-actions').hide();
                return;
            }
            
            let html = '';
            let totalM3 = 0;
            
            this.productList.forEach((product, index) => {
                totalM3 += product.m3;
                
                html += `
                    <div class="product-item">
                        <div class="product-header">
                            <h5>Ürün ${index + 1}</h5>
                            <div>
                                <button class="btn btn-sm btn-secondary edit-product" data-id="${product.id}">Düzenle</button>
                                <button class="remove-product" data-id="${product.id}">&times;</button>
                            </div>
                        </div>
                        <div class="product-details">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="detail-label">Ağaç Cinsi:</span>
                                    <span class="detail-value">${product.agac_cinsi_text}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Kalınlık:</span>
                                    <span class="detail-value">${product.kalinlik} mm</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Ebat:</span>
                                    <span class="detail-value">${product.ebat1} x ${product.ebat2} cm</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tutkal:</span>
                                    <span class="detail-value">${product.tutkal_text}</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Miktar:</span>
                                    <span class="detail-value">${product.miktar} adet</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">m³:</span>
                                    <span class="detail-value">${product.m3.toFixed(3)} m³</span>
                                </div>
                `;
                
                if (product.kalite || product.kaplama || product.desen) {
                    if (product.kalite) {
                        html += `
                            <div class="detail-item">
                                <span class="detail-label">Kalite:</span>
                                <span class="detail-value">${product.kalite}</span>
                            </div>
                        `;
                    }
                    if (product.kaplama) {
                        html += `
                            <div class="detail-item">
                                <span class="detail-label">Kaplama:</span>
                                <span class="detail-value">${product.kaplama}</span>
                            </div>
                        `;
                    }
                    if (product.desen) {
                        html += `
                            <div class="detail-item">
                                <span class="detail-label">Desen:</span>
                                <span class="detail-value">${product.desen}</span>
                            </div>
                        `;
                    }
                }
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
            });
            // Update submit availability after rendering list
            this.updateSubmitAvailability();

            $container.html(html);
            
            // Update totals
            $('#total-products').text(this.productList.length);
            $('#total-volume').text(totalM3.toFixed(3));
            
            $('#order-summary, #order-actions').show();
        },
        
        removeProduct: function(e) {
            const productId = parseInt($(e.target).data('id'));
            this.productList = this.productList.filter(p => p.id !== productId);
            this.updateProductList();
            this.showPopup('Ürün silindi.');
        },
        
        editProduct: function(e) {
            const productId = parseInt($(e.target).data('id'));
            const product = this.productList.find(p => p.id === productId);
            
            if (product) {
                // Fill form with product data
                $('#agac_cinsi').val(product.agac_cinsi);
                $('#kalinlik').val(product.kalinlik);
                $('#ebat1').val(product.ebat1);
                $('#ebat2').val(product.ebat2);
                $('#tutkal').val(product.tutkal);
                $('#miktar').val(product.miktar);
                $('#kalite').val(product.kalite);
                $('#kaplama').val(product.kaplama);
                $('#desen').val(product.desen);
                // urun_notu removed per request
                
                // Optional fields removed - fill directly
                
                // Remove from list (will be re-added when form is submitted)
                this.productList = this.productList.filter(p => p.id !== productId);
                this.updateProductList();
                
                // Go to first step
                this.showStep(1);
                this.calculateVolume();
                
                    this.showPopup('Ürün düzenleme moduna geçildi.');
            }
        },
        
        clearForm: function() {
            $('#order-form')[0].reset();
            $('#volume-result').text('0.000');
            // Optional fields removed; ensure any related UI cleared
            $('.form-group').removeClass('error success');
            $('.accordion-header').removeClass('completed');
            $('.wizard-step').removeClass('completed');
            this.updateProductSummary({});
        },
        
        clearAll: function() {
            if (this.productList.length === 0) {
                this.showMessage('Temizlenecek ürün yok.', 'info');
                return;
            }
            
            if (confirm('Tüm ürünleri ve formu temizlemek istediğinizden emin misiniz?')) {
                this.productList = [];
                this.updateProductList();
                this.clearForm();
                this.showStep(1);
                    this.showPopup('Tüm veriler temizlendi.');
            }
        },
        
        submitOrder: function() {
            if (this.productList.length === 0) {
                this.showPopup('En az bir ürün eklemelisiniz!');
                return;
            }
            
            const formData = {
                action: 'dastas_yeni_siparis',
                nonce: dastas_ajax.nonce,
                products: JSON.stringify(this.productList),
                // order_notes removed per request
            };
            
            const $submitBtn = $('.btn-submit');
            $submitBtn.prop('disabled', true).text('Gönderiliyor...');

            this.showPopup('Sipariş gönderiliyor...');

            $.post(dastas_ajax.ajax_url, formData)
                .done((response) => {
                    if (response.success) {
                        this.showPopup(response.data.message || 'Sipariş başarıyla kaydedildi!');
                        
                        // Clear everything
                        this.productList = [];
                        this.updateProductList();
                        this.clearForm();
                        this.showStep(1);
                        
                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.location.href = dastas_ajax.site_url + '/siparislerim/';
                        }, 3000);
                    } else {
                        this.showPopup('Hata: ' + (response.data?.message || 'Bilinmeyen hata'));
                    }
                })
                .fail(() => {
                    this.showPopup('Bağlantı hatası oluştu!');
                })
                .always(() => {
                    $submitBtn.prop('disabled', false).text('Siparişi Gönder');
                });
        },
        
        showMessage: function(message, type) {
            // Deprecated: route to popup for consistent UX
            this.showPopup(message);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#order-wizard-container').length) {
            DastasOrderWizard.init();
        }
    });
    
    // Global access
    window.DastasOrderWizard = DastasOrderWizard;
    
})(jQuery);
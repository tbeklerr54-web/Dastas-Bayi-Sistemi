/**
 * Dastas Yeni Sipariş Wizard - Sıfırdan Yeniden Yazıldı
 * Version: 2.0.3
 */

(function($) {
    'use strict';

    const OrderWizard = {
        currentStep: 1,
        totalSteps: 7,
        productList: [],
        
        init: function() {
            console.log('🚀 Order Wizard başlatılıyor...');
            this.bindEvents();
            this.showStep(1);
            this.updatePreview();
        },
        
        bindEvents: function() {
            // Navigation buttons
            $(document).on('click', '.btn-next', this.nextStep.bind(this));
            $(document).on('click', '.btn-prev', this.prevStep.bind(this));
            
            // Product actions
            $(document).on('click', '.btn-add', this.addProduct.bind(this));
            $(document).on('click', '.btn-submit', this.submitOrder.bind(this));
            $(document).on('click', '.btn-clear-all', this.clearAll.bind(this));
            
            // Product list actions
            $(document).on('click', '.remove-product', this.removeProduct.bind(this));
            $(document).on('click', '.edit-product', this.editProduct.bind(this));
            
            // Form field changes
            $(document).on('change', '#agac_cinsi, #kalinlik, #ebat1, #ebat2, #tutkal, #kalite, #kaplama, #desen, #miktar', this.handleFieldChange.bind(this));
            
            // Progress step clicks
            $(document).on('click', '.step', this.handleStepClick.bind(this));
        },
        
        handleFieldChange: function() {
            this.calculateVolume();
            this.updatePreview();
            this.checkAutoAdvance();
        },
        
        checkAutoAdvance: function() {
            // Auto advance logic
            if (this.currentStep === 1 && $('#agac_cinsi').val()) {
                setTimeout(() => this.nextStep(), 500);
            } else if (this.currentStep === 2 && $('#kalinlik').val()) {
                setTimeout(() => this.nextStep(), 500);
            } else if (this.currentStep === 3 && $('#ebat1').val() && $('#ebat2').val()) {
                setTimeout(() => this.nextStep(), 500);
            } else if (this.currentStep === 4 && $('#tutkal').val()) {
                setTimeout(() => this.nextStep(), 500);
            }
        },
        
        handleStepClick: function(e) {
            const stepNumber = parseInt($(e.currentTarget).data('step'));
            if (stepNumber <= this.currentStep || this.canGoToStep(stepNumber)) {
                this.showStep(stepNumber);
            }
        },
        
        canGoToStep: function(stepNumber) {
            // Check if previous steps are valid
            for (let i = 1; i < stepNumber; i++) {
                if (!this.isStepValid(i)) {
                    return false;
                }
            }
            return true;
        },
        
        isStepValid: function(stepNumber) {
            switch (stepNumber) {
                case 1: return !!$('#agac_cinsi').val();
                case 2: return !!$('#kalinlik').val();
                case 3: return !!$('#ebat1').val() && !!$('#ebat2').val();
                case 4: return !!$('#tutkal').val();
                case 5: return true; // Optional fields
                case 6: return !!$('#miktar').val() && parseInt($('#miktar').val()) > 0;
                case 7: return true; // Order step
                default: return false;
            }
        },
        
        showStep: function(stepNumber) {
            this.currentStep = stepNumber;
            
            // Update progress steps
            $('.step').removeClass('active completed');
            $('.step').each(function() {
                const step = parseInt($(this).data('step'));
                if (step < stepNumber) {
                    $(this).addClass('completed');
                } else if (step === stepNumber) {
                    $(this).addClass('active');
                }
            });
            
            // Show/hide step content
            $('.step-content').removeClass('active');
            $(`.step-content[data-step="${stepNumber}"]`).addClass('active');
            
            // Update navigation buttons
            this.updateNavigation();
            
            // Removed scroll to top - keep user's position
        },
        
        updateNavigation: function() {
            const $prev = $('.btn-prev');
            const $next = $('.btn-next');
            
            // Previous button
            if (this.currentStep > 1) {
                $prev.show();
            } else {
                $prev.hide();
            }
            
            // Next button
            if (this.currentStep < 6) {
                $next.show().prop('disabled', !this.isStepValid(this.currentStep));
            } else {
                $next.hide();
            }
        },
        
        nextStep: function() {
            if (this.isStepValid(this.currentStep) && this.currentStep < this.totalSteps) {
                this.showStep(this.currentStep + 1);
            } else {
                this.showMessage('Lütfen gerekli alanları doldurun!', 'warning');
            }
        },
        
        prevStep: function() {
            if (this.currentStep > 1) {
                this.showStep(this.currentStep - 1);
            }
        },
        
        calculateVolume: function() {
            const kalinlik = parseFloat($('#kalinlik').val()) || 0;
            const ebat1 = parseFloat($('#ebat1').val()) || 0;
            const ebat2 = parseFloat($('#ebat2').val()) || 0;
            const miktar = parseInt($('#miktar').val()) || 0;
            
            if (kalinlik && ebat1 && ebat2 && miktar) {
                // m³ = (ebat1/100) * (ebat2/100) * (kalinlik/1000) * miktar
                const m3PerPiece = (ebat1 / 100) * (ebat2 / 100) * (kalinlik / 1000);
                const totalM3 = m3PerPiece * miktar;
                const roundedM3 = parseFloat(totalM3.toFixed(3));
                $('#preview-volume').text(roundedM3.toFixed(3) + ' m³');
                return roundedM3;
            } else {
                $('#preview-volume').text('0.000 m³');
                return 0;
            }
        },
        
        updatePreview: function() {
            $('#preview-agac').text($('#agac_cinsi option:selected').text() || '-');
            $('#preview-kalinlik').text($('#kalinlik').val() ? $('#kalinlik').val() + ' mm' : '-');
            $('#preview-ebat').text(($('#ebat1').val() && $('#ebat2').val()) ? $('#ebat1').val() + ' x ' + $('#ebat2').val() + ' cm' : '-');
            $('#preview-tutkal').text($('#tutkal option:selected').text() || '-');
            $('#preview-miktar').text($('#miktar').val() ? $('#miktar').val() + ' adet' : '-');
            
            this.calculateVolume();
        },
        
        addProduct: function() {
            if (!this.isStepValid(6)) {
                this.showMessage('Lütfen tüm gerekli alanları doldurun!', 'error');
                return;
            }
            
            const product = {
                id: Date.now(),
                agac_cinsi: $('#agac_cinsi').val(),
                agac_cinsi_text: $('#agac_cinsi option:selected').text(),
                kalinlik: $('#kalinlik').val(),
                ebat1: $('#ebat1').val(),
                ebat2: $('#ebat2').val(),
                tutkal: $('#tutkal').val(),
                tutkal_text: $('#tutkal option:selected').text(),
                kalite: $('#kalite').val(),
                kaplama: $('#kaplama').val(),
                desen: $('#desen').val(),
                miktar: $('#miktar').val(),
                m3: parseFloat(this.calculateVolume().toFixed(3))
            };
            
            this.productList.push(product);
            this.updateProductList();
            this.clearForm();
            
            // Show success message first
            this.showMessage('✅ Ürün başarıyla eklendi!', 'success');
            
            // Ask if user wants to add more products
            this.showAddMoreProductDialog();
        },
        
        updateProductList: function() {
            const $container = $('#product-list');
            
            if (this.productList.length === 0) {
                $container.html(`
                    <div class="no-products">
                        <p>🛒 Henüz ürün eklenmedi.</p>
                        <p>Ürün eklemek için yukarıdaki adımları tamamlayın.</p>
                    </div>
                `);
                $('#order-summary, #order-notes-section, #order-actions').hide();
                return;
            }
            
            let html = '';
            let totalM3 = 0;
            
            this.productList.forEach((product, index) => {
                totalM3 += parseFloat(product.m3);
                
                html += `
                    <div class="product-item" data-id="${product.id}">
                        <div class="product-header">
                            <span class="product-number">#${index + 1}</span>
                            <h4>${product.agac_cinsi_text} - ${product.kalinlik}mm</h4>
                            <div class="product-actions">
                                <button class="btn-icon edit-product" data-id="${product.id}" title="Düzenle">✏️</button>
                                <button class="btn-icon remove-product" data-id="${product.id}" title="Sil">🗑️</button>
                            </div>
                        </div>
                        <div class="product-details">
                            <div class="detail-row">
                                <span><strong>Ebat:</strong> ${product.ebat1} x ${product.ebat2} cm</span>
                                <span><strong>Tutkal:</strong> ${product.tutkal_text}</span>
                            </div>
                            <div class="detail-row">
                                <span><strong>Adet:</strong> ${product.miktar}</span>
                                <span><strong>m³:</strong> ${parseFloat(product.m3).toFixed(3)}</span>
                            </div>
                `;
                
                if (product.kalite || product.kaplama || product.desen) {
                    html += '<div class="detail-row">';
                    if (product.kalite) html += `<span><strong>Kalite:</strong> ${product.kalite}</span>`;
                    if (product.kaplama) html += `<span><strong>Kaplama:</strong> ${product.kaplama}</span>`;
                    if (product.desen) html += `<span><strong>Desen:</strong> ${product.desen}</span>`;
                    html += '</div>';
                }
                
                html += `
                        </div>
                    </div>
                `;
            });
            
            $container.html(html);
            
            // Update totals
            $('#total-products').text(this.productList.length);
            $('#total-volume').text(totalM3.toFixed(3) + ' m³');
            
            // Show order sections
            $('#order-summary, #order-notes-section, #order-actions').show();
            
            // Enable submit button if products exist
            $('.btn-submit').prop('disabled', false);
        },
        
        removeProduct: function(e) {
            const productId = parseInt($(e.target).data('id'));
            this.productList = this.productList.filter(p => p.id !== productId);
            this.updateProductList();
            this.showMessage('Ürün silindi.', 'info');
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
                $('#kalite').val(product.kalite);
                $('#kaplama').val(product.kaplama);
                $('#desen').val(product.desen);
                $('#miktar').val(product.miktar);
                
                // Remove from list
                this.productList = this.productList.filter(p => p.id !== productId);
                this.updateProductList();
                
                // Go to first step
                this.showStep(1);
                this.updatePreview();
                this.showMessage('Ürün düzenleme moduna geçildi.', 'info');
            }
        },
        
        clearForm: function() {
            $('#order-form')[0].reset();
            this.updatePreview();
        },
        
        clearAll: function() {
            if (this.productList.length === 0) {
                this.showMessage('Temizlenecek ürün yok.', 'info');
                return;
            }
            
            if (confirm('Tüm ürünleri temizlemek istediğinizden emin misiniz?')) {
                this.productList = [];
                this.updateProductList();
                this.clearForm();
                this.showStep(1);
                this.showMessage('Tüm veriler temizlendi.', 'info');
            }
        },
        
        submitOrder: function() {
            if (this.productList.length === 0) {
                this.showMessage('En az bir ürün eklemelisiniz!', 'error');
                return;
            }
            
            const $btn = $('.btn-submit');
            $btn.prop('disabled', true).text('Gönderiliyor...');
            
            const productsJson = JSON.stringify(this.productList);
            const formData = {
                action: 'dastas_yeni_siparis',
                nonce: dastas_ajax.nonce,
                products: productsJson,
                order_notes: $('#order_notes').val() || ''
            };
            
            $.post(dastas_ajax.ajax_url, formData)
                .done((response) => {
                    if (response.success) {
                        this.showSuccess(response.data.message || 'Sipariş başarıyla kaydedildi!');
                        
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
                        this.showMessage('Hata: ' + (response.data?.message || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(() => {
                    this.showMessage('Bağlantı hatası oluştu!', 'error');
                })
                .always(() => {
                    $btn.prop('disabled', false).text('🚀 Siparişi Gönder');
                });
        },
        
        showAddMoreProductDialog: function() {
            // Create dialog HTML
            const dialogHTML = `
                <div id="add-more-dialog" class="modal" style="display: flex;">
                    <div class="modal-content dialog-content">
                        <div class="dialog-header">
                            <h3>🛒 Ürün Eklendi</h3>
                        </div>
                        <div class="dialog-body">
                            <p>Ürününüz başarıyla listeye eklendi.</p>
                            <p><strong>Başka ürün eklemek istiyor musunuz?</strong></p>
                            
                            <div class="dialog-actions">
                                <button type="button" class="btn btn-success btn-add-more">
                                    ✅ Evet, Başka Ürün Ekle
                                </button>
                                <button type="button" class="btn btn-primary btn-finish-order">
                                    � Sipariş Listesini Gör
                                </button>
                                <button type="button" class="btn btn-warning btn-send-order">
                                    🚀 Hemen Sipariş Gönder
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing dialog if any
            $('#add-more-dialog').remove();
            
            // Add to body
            $('body').append(dialogHTML);
            
            // Handle button clicks
            const self = this;
            $('#add-more-dialog .btn-add-more').on('click', () => {
                $('#add-more-dialog').remove();
                self.showStep(1); // Go back to step 1 for new product
            });
            
            $('#add-more-dialog .btn-finish-order').on('click', () => {
                $('#add-more-dialog').remove();
                self.showStep(7); // Go to final step (order summary)
            });
            
            $('#add-more-dialog .btn-send-order').on('click', () => {
                $('#add-more-dialog').remove();
                self.submitOrder(); // Send order immediately
            });
            
            // Close on overlay click
            $('#add-more-dialog').on('click', function(e) {
                if (e.target === this) {
                    $(this).remove();
                }
            });
        },

        showMessage: function(message, type = 'info') {
            // Create notification
            const notification = $(`
                <div class="notification notification-${type}">
                    <span>${message}</span>
                    <button class="notification-close">×</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => notification.fadeOut(() => notification.remove()), 3000);
            
            // Manual close
            notification.on('click', '.notification-close', () => {
                notification.fadeOut(() => notification.remove());
            });
        },
        
        showSuccess: function(message) {
            $('#success-message').text(message);
            $('#success-modal').fadeIn();
            
            setTimeout(() => {
                $('#success-modal').fadeOut();
            }, 3000);
        }
    };

    // Initialize when document ready
    $(document).ready(function() {
        if ($('#dastas-order-wizard').length) {
            OrderWizard.init();
        }
    });

    // Global access
    window.OrderWizard = OrderWizard;

})(jQuery);
/**
 * Dastas Bayi Sistemi - Modern JavaScript
 * WordPress jQuery uyumlu versiyon
 */

// WordPress'in jQuery'sini kullan ve çakışmaları önle
jQuery(document).ready(function($) {
    'use strict';

    // Plugin ana sınıfı
    const DastasAuth = {
        
        init: function() {
            this.bindEvents();
            this.initFormToggle();
            this.initFormValidation();
        },

        bindEvents: function() {
            // Login form submit
            $(document).on('submit', '#dastas-login-form', this.handleLogin);
            
            // Reset form submit
            $(document).on('submit', '#dastas-reset-form', this.handlePasswordReset);
            
            // Form toggle buttons
            $(document).on('click', '[data-toggle]', this.toggleForms);
            
            // Input focus effects
            $(document).on('focus', '.form-group input', this.inputFocus);
            $(document).on('blur', '.form-group input', this.inputBlur);
        },

        initFormToggle: function() {
            // Form toggle işlemleri
            $('.reset-form').hide();
        },

        initFormValidation: function() {
            // Form validasyon işlemleri
        },

        handleLogin: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $form.find('.login-btn');
            const $message = $('#login-message');
            
            // AJAX objesi kontrolü
            if (typeof dastas_ajax === 'undefined') {
                DastasAuth.showMessage($message, 'Sistem hatası: AJAX yapılandırması bulunamadı!', 'error');
                console.error('dastas_ajax objesi tanımlanmamış');
                return;
            }
            
            if (!dastas_ajax.ajax_url || !dastas_ajax.nonce) {
                DastasAuth.showMessage($message, 'Sistem hatası: AJAX yapılandırması eksik!', 'error');
                console.error('dastas_ajax:', dastas_ajax);
                return;
            }
            
            // Validation
            const kullaniciAdi = $('#kullanici_adi').val().trim();
            const sifre = $('#sifre').val();
            
            if (!kullaniciAdi || !sifre) {
                DastasAuth.showMessage($message, 'Lütfen tüm alanları doldurun.', 'error');
                return;
            }
            
            if (sifre.length < 6) {
                DastasAuth.showMessage($message, 'Şifre en az 6 karakter olmalıdır.', 'error');
                return;
            }
            
            // Loading state
            DastasAuth.setButtonLoading($button, true);
            $message.empty();
            
            const formData = {
                action: 'dastas_login',
                kullanici_adi: kullaniciAdi,
                sifre: sifre,
                nonce: dastas_ajax.nonce
            };
            
            $.ajax({
                url: dastas_ajax.ajax_url,
                type: 'POST',
                data: formData,
                timeout: 10000,
                beforeSend: function() {
                    console.log('AJAX İsteği gönderiliyor:', {
                        url: dastas_ajax.ajax_url,
                        data: formData
                    });
                },
                success: function(response) {
                    console.log('AJAX Yanıtı:', response);
                    DastasAuth.setButtonLoading($button, false);
                    
                    if (response.success) {
                        DastasAuth.showMessage($message, response.data.message, 'success');
                        
                        // Başarılı giriş animasyonu
                        $form.addClass('success-animation');
                        
                        setTimeout(function() {
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        DastasAuth.showMessage($message, response.data.message, 'error');
                        // Form shake animasyonu
                        $form.addClass('shake-animation');
                        setTimeout(() => $form.removeClass('shake-animation'), 500);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Hatası:', {
                        status: status,
                        error: error,
                        xhr: xhr,
                        responseText: xhr.responseText
                    });
                    
                    DastasAuth.setButtonLoading($button, false);
                    let errorMessage = 'Bağlantı hatası! Lütfen tekrar deneyin.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'İstek zaman aşımına uğradı. Lütfen tekrar deneyin.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Ağ bağlantısı yok veya sunucu yanıt vermiyor.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'AJAX endpoint bulunamadı (404).';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Sunucu hatası (500).';
                    } else if (xhr.responseText) {
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            if (errorData.data && errorData.data.message) {
                                errorMessage = errorData.data.message;
                            }
                        } catch (e) {
                            console.error('JSON parse hatası:', e);
                        }
                    }
                    
                    DastasAuth.showMessage($message, errorMessage, 'error');
                }
            });
        },

        handlePasswordReset: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $button = $form.find('.reset-btn');
            const $message = $('#reset-message');
            
            // Validation
            const bayiKodu = $('#reset_bayi_kodu').val().trim().toUpperCase();
            const kullaniciAdi = $('#reset_kullanici_adi').val().trim().toLowerCase();
            
            if (!bayiKodu || !kullaniciAdi) {
                DastasAuth.showMessage($message, 'Lütfen tüm alanları doldurun.', 'error');
                return;
            }
            
            // Loading state
            DastasAuth.setButtonLoading($button, true);
            $message.empty();
            
            const formData = {
                action: 'dastas_reset_password',
                bayi_kodu: bayiKodu,
                kullanici_adi: kullaniciAdi,
                nonce: dastas_ajax.nonce
            };
            
            $.ajax({
                url: dastas_ajax.ajax_url,
                type: 'POST',
                data: formData,
                timeout: 10000,
                success: function(response) {
                    DastasAuth.setButtonLoading($button, false);
                    
                    if (response.success) {
                        DastasAuth.showMessage($message, response.data.message, 'success');
                        
                        // Form temizle
                        $form[0].reset();
                        
                        setTimeout(function() {
                            $('.reset-form').fadeOut(300, function() {
                                $('.login-form').fadeIn(300);
                            });
                        }, 2000);
                    } else {
                        DastasAuth.showMessage($message, response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    DastasAuth.setButtonLoading($button, false);
                    let errorMessage = 'Bağlantı hatası! Lütfen tekrar deneyin.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'İstek zaman aşımına uğradı. Lütfen tekrar deneyin.';
                    }
                    
                    DastasAuth.showMessage($message, errorMessage, 'error');
                }
            });
        },

        showMessage: function($container, message, type) {
            if (!$container.length) return;
            
            $container.html(`
                <div class="alert alert-${type}" style="animation: slideInDown 0.3s ease-out;">
                    ${message}
                </div>
            `);
            
            // Auto hide after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    $container.find('.alert').fadeOut();
                }, 5000);
            }
        },

        setButtonLoading: function($button, loading) {
            if (!$button.length) return;
            
            if (loading) {
                $button.prop('disabled', true)
                    .addClass('loading')
                    .data('original-text', $button.text())
                    .html('<span class="spinner"></span> Gönderiliyor...');
            } else {
                $button.prop('disabled', false)
                    .removeClass('loading')
                    .text($button.data('original-text') || 'Gönder');
            }
        },

        inputFocus: function() {
            $(this).parent().addClass('focused');
        },

        inputBlur: function() {
            if (!$(this).val()) {
                $(this).parent().removeClass('focused');
            }
        },

        toggleForms: function(e) {
            e.preventDefault();
            const target = $(this).data('toggle');
            
            if (target === 'reset-form') {
                $('.login-form').slideUp(300, function() {
                    $('.reset-form').slideDown(300);
                });
            } else {
                $('.reset-form').slideUp(300, function() {
                    $('.login-form').slideDown(300);
                });
            }
        }
    };

    // DOM ready - DastasAuth'ı başlat
    DastasAuth.init();

    // Global erişim için
    window.DastasAuth = DastasAuth;

    // Eski sipariş kodları için ek event handler'lar
    // Siparişi Tamamla butonu
    $('#siparisi-tamamla-btn').on('click', function() {
        // Önce mevcut ürünü listeye ekle
        if (typeof validateCurrentProduct === 'function' && validateCurrentProduct()) {
            if (typeof addProductToList === 'function') {
                addProductToList();
            }
        }
        
        // Sonra siparişi gönder
        if (typeof urunListesi !== 'undefined' && urunListesi.length > 0) {
            $('#siparis-form').submit();
        } else {
            alert('En az bir ürün eklemelisiniz!');
        }
    });

    // Şifremi Unuttum linkine tıklama
    $('#forgot-password-link').on('click', function(e) {
        e.preventDefault();
        $('#login-form-container').hide();
        $('#forgot-password-container').show();
    });
    
    // Geri Dön butonuna tıklama
    $('#back-to-login').on('click', function(e) {
        e.preventDefault();
        $('#forgot-password-container').hide();
        $('#login-form-container').show();
    });

    // Input error stillerini temizle
    $('#siparis-form input, #siparis-form select').on('focus', function() {
        $(this).removeClass('error');
    });
    
    // Success Popup Notification fonksiyonu
    function showSuccessPopup(message, orderNo, productCount) {
        // Mevcut popup'ları kaldır
        $('.success-popup').remove();
        
        const popup = $(`
            <div class="success-popup">
                <div class="popup-header">
                    <div class="popup-icon">✓</div>
                    <h4 class="popup-title">Sipariş Başarıyla Oluşturuldu!</h4>
                    <button class="popup-close">&times;</button>
                </div>
                <div class="popup-content">
                    ${message}
                </div>
                <div class="popup-order-info">
                    <div><strong>Sipariş Numaranız:</strong></div>
                    <div class="popup-order-no">${orderNo}</div>
                    ${productCount ? `<div style="margin-top:8px;"><strong>Toplam Ürün:</strong> ${productCount} çeşit</div>` : ''}
                </div>
                <div class="popup-actions">
                    <a href="${window.location.origin}/siparislerim/" class="popup-btn primary">Siparişlerim</a>
                    <button class="popup-btn new-order">Yeni Sipariş</button>
                </div>
                <div class="popup-progress">
                    <div class="popup-progress-bar"></div>
                </div>
            </div>
        `);
        
        $('body').append(popup);
        
        // Animasyon
        setTimeout(() => popup.addClass('show'), 10);
        
        // 5 saniye sonra otomatik kapat
        setTimeout(() => {
            popup.addClass('hide');
            setTimeout(() => popup.remove(), 400);
        }, 5000);
        
        // Kapatma butonları
        popup.find('.popup-close').on('click', function() {
            popup.addClass('hide');
            setTimeout(() => popup.remove(), 400);
        });
        
        // Yeni sipariş butonu
        popup.find('.new-order').on('click', function() {
            location.reload(); // Formu sıfırla
        });
    }
    
    // Multi-order başarı mesajı için global fonksiyon
    window.showOrderSuccessPopup = showSuccessPopup;

    // Eğer URL'de success parametresi varsa popup göster
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'order') {
        const orderNo = urlParams.get('order_no');
        const productCount = urlParams.get('product_count');
        if (orderNo) {
            showSuccessPopup('Siparişiniz başarıyla alındı ve işleme konuldu.', orderNo, productCount);
        }
    }

});
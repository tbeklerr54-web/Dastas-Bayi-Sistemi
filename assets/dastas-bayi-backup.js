jQuery(document).ready(function($) {
    
    // Siparişi Tamamla butonu
    $('#siparisi-tamamla-btn').on('click', function() {
        // Önce mevcut ürünü listeye ekle
        if (validateCurrentProduct()) {
            addProductToList();
        }
        
        // Sonra siparişi gönder
        if (urunListesi.length > 0) {
            $('#siparis-form').submit();
        } else {
            alert('En az bir ürün eklemelisiniz!');
        }
    });
    
    // Login form submit
/**
 * Dastas Bayi Sistemi - Modern JavaScript
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
            // Form geçiş animasyonları
            $('[data-toggle]').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('toggle');
                
                if (target === 'reset-form') {
                    $('.login-form').fadeOut(300, function() {
                        $('.reset-form').fadeIn(300);
                    });
                } else {
                    $('.reset-form').fadeOut(300, function() {
                        $('.login-form').fadeIn(300);
                    });
                }
            });
        },

        initFormValidation: function() {
            // Real-time validation
            $(document).on('input', '.form-group input', function() {
                const $input = $(this);
                const $group = $input.closest('.form-group');
                
                // Remove previous validation classes
                $group.removeClass('has-error has-success');
                
                if ($input.val().length > 0) {
                    if ($input.attr('type') === 'password' && $input.val().length < 6) {
                        $group.addClass('has-error');
                    } else {
                        $group.addClass('has-success');
                    }
                }
            });
        },

        inputFocus: function(e) {
            const $input = $(e.target);
            const $group = $input.closest('.form-group');
            $group.addClass('focused');
        },

        inputBlur: function(e) {
            const $input = $(e.target);
            const $group = $input.closest('.form-group');
            $group.removeClass('focused');
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
                    DastasAuth.showMessage($message, 'Bağlantı hatası! Lütfen tekrar deneyin.', 'error');
                }
            });
        },

        setButtonLoading: function($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $button.data('original-text', $button.text()).text('İşleniyor...');
            } else {
                $button.removeClass('loading').prop('disabled', false);
                if ($button.data('original-text')) {
                    $button.text($button.data('original-text'));
                }
            }
        },

        showMessage: function($container, message, type) {
            const messageHtml = `<div class="message ${type}">${message}</div>`;
            $container.html(messageHtml).hide().slideDown(300);
            
            // Auto hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    $container.slideUp(300, () => $container.empty());
                }, 5000);
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

    // DOM ready
    $(document).ready(function() {
        DastasAuth.init();
    });

    // Global erişim
    window.DastasAuth = DastasAuth;

    // Eski jQuery kodları için ek event handler'lar
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
        $('#dastas-forgot-message').html('');
        $('#dastas-forgot-password-form')[0].reset();
    });
    
    // Şifre sıfırlama formu
    $('#dastas-forgot-password-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#dastas-forgot-message');
        var $submitBtn = $form.find('button[type="submit"]');
        
        var yeniSifre = $('#forgot_yeni_sifre').val();
        var sifreTekrar = $('#forgot_sifre_tekrar').val();
        
        // Client-side validasyon
        if (yeniSifre !== sifreTekrar) {
            $message.html('<div class="alert alert-danger">Şifreler eşleşmiyor!</div>');
            return;
        }
        
        if (yeniSifre.length < 6) {
            $message.html('<div class="alert alert-danger">Şifre en az 6 karakter olmalıdır!</div>');
            return;
        }
        
        $submitBtn.prop('disabled', true).text('Şifre sıfırlanıyor...');
        $message.html('');
        
        $.ajax({
            url: dastas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dastas_bayi_forgot_password',
                bayi_kodu: $('#forgot_bayi_kodu').val(),
                kullanici_adi: $('#forgot_kullanici_adi').val(),
                yeni_sifre: yeniSifre,
                sifre_tekrar: sifreTekrar,
                nonce: dastas_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<div class="alert alert-success">' + response.data.message + '</div>');
                    setTimeout(function() {
                        $('#back-to-login').trigger('click');
                    }, 2000);
                } else {
                    $message.html('<div class="alert alert-danger">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $message.html('<div class="alert alert-danger">Bir hata oluştu, lütfen tekrar deneyin.</div>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Şifreyi Sıfırla');
            }
        });
    });
    
    // Şifre eşleşme kontrolü
    $('#forgot_sifre_tekrar').on('input', function() {
        var yeniSifre = $('#forgot_yeni_sifre').val();
        var sifreTekrar = $(this).val();
        var $message = $('#dastas-forgot-message');
        
        if (sifreTekrar && yeniSifre !== sifreTekrar) {
            $message.html('<div class="alert alert-warning">Şifreler eşleşmiyor!</div>');
        } else if (sifreTekrar && yeniSifre === sifreTekrar) {
            $message.html('<div class="alert alert-success">Şifreler eşleşiyor!</div>');
        } else {
            $message.html('');
        }
    });
    
    // Bayi kodu otomatik büyük harf
    $('#forgot_bayi_kodu').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Kullanıcı adı otomatik küçük harf
    $('#forgot_kullanici_adi').on('input', function() {
        $(this).val($(this).val().toLowerCase());
    });
    
    // Tab geçişleri (Hesap sayfası için)
    $('.tab-btn').on('click', function() {
        var targetTab = $(this).data('tab');
        
        // Tab butonlarını güncelle
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Tab içeriklerini güncelle
        $('.tab-content').removeClass('active');
        $('#' + targetTab + '-tab').addClass('active');
    });
    
    // Profil güncelleme formu
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#profile-message');
        var $submitBtn = $form.find('button[type="submit"]');
        
        $submitBtn.prop('disabled', true).text('Güncelleniyor...');
        $message.html('');
        
        $.ajax({
            url: dastas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dastas_update_profile',
                bayi_adi: $('#bayi_adi').val(),
                telefon: $('#telefon').val(),
                eposta: $('#eposta').val(),
                nonce: dastas_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<div class="alert alert-success">' + response.data.message + '</div>');
                } else {
                    $message.html('<div class="alert alert-danger">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $message.html('<div class="alert alert-danger">Bir hata oluştu, lütfen tekrar deneyin.</div>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Bilgileri Güncelle');
            }
        });
    });
    
    // Şifre değiştirme formu
    $('#password-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#password-message');
        var $submitBtn = $form.find('button[type="submit"]');
        
        var currentPassword = $('#current_password').val();
        var newPassword = $('#new_password').val();
        var confirmPassword = $('#confirm_password').val();
        
        // Şifre doğrulama
        if (newPassword.length < 6) {
            $message.html('<div class="alert alert-danger">Yeni şifre en az 6 karakter olmalıdır.</div>');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            $message.html('<div class="alert alert-danger">Yeni şifreler eşleşmiyor!</div>');
            return false;
        }
        
        $submitBtn.prop('disabled', true).text('Şifre Değiştiriliyor...');
        $message.html('');
        
        $.ajax({
            url: dastas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dastas_change_password',
                mevcut_sifre: currentPassword,
                yeni_sifre: newPassword,
                nonce: dastas_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<div class="alert alert-success">' + response.data.message + '</div>');
                    $form[0].reset(); // Formu temizle
                } else {
                    $message.html('<div class="alert alert-danger">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $message.html('<div class="alert alert-danger">Bir hata oluştu, lütfen tekrar deneyin.</div>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Şifreyi Değiştir');
            }
        });
    });
    
    // Şifre eşleşme kontrolü (Hesap sayfası)
    $('#confirm_password').on('input', function() {
        var newPassword = $('#new_password').val();
        var confirmPassword = $(this).val();
        var $message = $('#password-match-message');
        
        if (confirmPassword && newPassword !== confirmPassword) {
            if ($message.length === 0) {
                $('#password-message').html('<div class="alert alert-warning">Şifreler eşleşmiyor!</div>');
            } else {
                $message.html('<div class="alert alert-warning">Şifreler eşleşmiyor!</div>');
            }
        } else if (confirmPassword && newPassword === confirmPassword) {
            if ($message.length === 0) {
                $('#password-message').html('<div class="alert alert-success">Şifreler eşleşiyor!</div>');
            } else {
                $message.html('<div class="alert alert-success">Şifreler eşleşiyor!</div>');
            }
        } else {
            if ($message.length === 0) {
                $('#password-message').html('');
            } else {
                $message.html('');
            }
        }
    });
    
    // Sipariş formu
    $('#dastas-siparis-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#siparis-message');
        var $submitBtn = $form.find('button[type="submit"]');
        
        $submitBtn.prop('disabled', true).text('Kaydediliyor...');
        $message.html('');
        
        // Burada AJAX sipariş kaydetme yapılacak
        // Şimdilik sadece başarı mesajı gösterelim
        setTimeout(function() {
            $message.html('<div class="alert alert-success">Siparişiniz başarıyla kaydedildi!</div>');
            $submitBtn.prop('disabled', false).text('Siparişi Kaydet');
        }, 1000);
    });
    
    // Ürün ekleme butonu
    $('#add-product-btn').on('click', function() {
        // Burada ürün ekleme modalı veya formu açılacak
        alert('Ürün ekleme özelliği yakında eklenecek!');
    });
    
    // Logout
    $(document).on('click', '.dastas-logout', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: dastas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dastas_bayi_logout',
                nonce: dastas_ajax.nonce
            },
            success: function(response) {
                location.reload();
            }
        });
    });
    
    // Sipariş Wizard İşlevselliği
    var currentStep = 1;
    var totalSteps = 4;
    var urunListesi = []; // Çoklu ürün listesi
    var editingIndex = -1; // Düzenlenen ürün indexi
    
    // Step navigation
    $('#next-step').on('click', function() {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                currentStep++;
                updateWizardStep();
            }
        }
    });
    
    $('#prev-step').on('click', function() {
        if (currentStep > 1) {
            currentStep--;
            updateWizardStep();
        }
    });
    
    // Ürün ekleme
    $('#urun-ekle-btn').on('click', function() {
        if (validateCurrentProduct()) {
            addProductToList();
        }
    });
    
    // Form submission - Çoklu ürün
    $('#siparis-form').on('submit', function(e) {
        e.preventDefault();
        
        if (urunListesi.length === 0) {
            alert('En az bir ürün eklemelisiniz!');
            return;
        }
        
        var $form = $(this);
        var $message = $('#siparis-message');
        var $submitBtn = $('#submit-order');
        
        $submitBtn.prop('disabled', true).text('Çoklu Sipariş Gönderiliyor...');
        $message.html('');
        
        var formData = {
            action: 'dastas_submit_multi_order',
            urun_listesi: JSON.stringify(urunListesi),
            notlar: $('#notlar').val(),
            nonce: dastas_ajax.nonce
        };
        
        $.ajax({
            url: dastas_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Popup göster
                    showSuccessPopup(
                        response.data.message, 
                        response.data.siparis_no, 
                        response.data.success_count || urunListesi.length
                    );
                    
                    // Formu sıfırla
                    $form[0].reset();
                    currentStep = 1;
                    urunListesi = [];
                    editingIndex = -1;
                    updateWizardStep();
                    updateProductList();
                    updateTotalSummary();
                    
                    // Mesaj alanını temizle
                    $message.html('');
                } else {
                    $message.html('<div class="alert alert-danger">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $message.html('<div class="alert alert-danger">Bir hata oluştu, lütfen tekrar deneyin.</div>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Çoklu Siparişi Gönder');
            }
        });
    });
    
    function updateWizardStep() {
        // Step indicators
        $('.step').removeClass('active completed');
        $('.step[data-step="' + currentStep + '"]').addClass('active');
        
        for (var i = 1; i < currentStep; i++) {
            $('.step[data-step="' + i + '"]').addClass('completed');
        }
        
        // Step content
        $('.wizard-step').removeClass('active');
        $('.wizard-step[data-step="' + currentStep + '"]').addClass('active');
        
        // Navigation buttons
        if (currentStep === 1) {
            $('#prev-step').hide();
        } else {
            $('#prev-step').show();
        }
        
        if (currentStep === totalSteps) {
            $('#next-step').hide();
            // Step 4'te submit butonu sadece ürün listesi varsa göster
            if (urunListesi.length > 0) {
                $('#submit-order').show();
            } else {
                $('#submit-order').hide();
            }
        } else {
            $('#next-step').show();
            $('#submit-order').hide();
        }
    }
    
    // Mevcut ürün validasyonu
    function validateCurrentProduct() {
        var isValid = true;
        var errors = [];
        
        if (!$('#agac_cinsi').val()) {
            errors.push('Ağaç cinsi seçiniz');
            isValid = false;
        }
        if (!$('#kalinlik').val()) {
            errors.push('Kalınlık seçiniz');
            isValid = false;
        }
        if (!$('#ebat1').val()) {
            errors.push('Ebat 1 seçiniz');
            isValid = false;
        }
        if (!$('#ebat2').val()) {
            errors.push('Ebat 2 seçiniz');
            isValid = false;
        }
        if (!$('#tutkal').val()) {
            errors.push('Tutkal seçiniz');
            isValid = false;
        }
        if (!$('#miktar').val() || $('#miktar').val() <= 0) {
            errors.push('Geçerli bir miktar giriniz');
            isValid = false;
        }
        
        if (!isValid) {
            alert('Lütfen aşağıdaki zorunlu alanları doldurunuz:\n• ' + errors.join('\n• '));
        }
        
        return isValid;
    }
    
    // Ürünü listeye ekle
    function addProductToList() {
        var urun = {
            agac_cinsi: $('#agac_cinsi').val(),
            kalinlik: parseFloat($('#kalinlik').val()),
            ebat1: parseFloat($('#ebat1').val()),
            ebat2: parseFloat($('#ebat2').val()),
            kalite: $('#kalite').val() || '',
            tutkal: $('#tutkal').val(),
            kaplama: $('#kaplama').val() || '',
            sinif: $('#sinif').val() || '',
            desen: $('#desen').val() || '',
            sertifika: $('#sertifika').val() || '',
            miktar: parseInt($('#miktar').val()),
            m3: calculateM3()
        };
        
        if (editingIndex >= 0) {
            // Düzenleme modu
            urunListesi[editingIndex] = urun;
            editingIndex = -1;
            $('#urun-ekle-btn').text('Ürünü Listeye Ekle').removeClass('editing');
        } else {
            // Yeni ürün ekleme
            urunListesi.push(urun);
        }
        
        // Wizard'ı sıfırla ve yeni ürün için hazırla
        resetWizardForNewProduct();
        
        updateProductList();
        updateTotalSummary();
        updateWizardStep(); // Submit butonunu göster/gizle
    }
    
    // Yeni ürün için wizard'ı sıfırla
    function resetWizardForNewProduct() {
        // Tüm form alanlarını temizle
        $('#agac_cinsi, #kalinlik, #ebat1, #ebat2, #kalite, #tutkal, #kaplama, #sinif, #desen, #sertifika, #miktar').val('');
        
        // 1. adıma dön
        currentStep = 1;
        updateWizardStep();
        
        // m³ hesaplamayı sıfırla
        $('#m3_sonuc').text('0');
        
        // Düzenleme modunu sıfırla
        editingIndex = -1;
        $('#urun-ekle-btn').text('Ürünü Listeye Ekle').removeClass('editing');
    }

    // Ürün listesini güncelle
    function updateProductList() {
        var $container = $('#urun-items');
        
        if (urunListesi.length === 0) {
            $container.html('<div class="empty-products"><p><i class="dashicons dashicons-info"></i> Henüz ürün eklenmedi. Yukarıdaki formu doldurup "Ürünü Listeye Ekle" butonuna tıklayın.</p></div>');
            return;
        }
        
        var html = '';
        urunListesi.forEach(function(urun, index) {
            html += '<div class="urun-item">';
            html += '<button type="button" class="urun-sil" onclick="removeProduct(' + index + ')" title="Ürünü Sil">×</button>';
            html += '<h5>' + urun.agac_cinsi + ' - ' + urun.kalinlik + 'mm - ' + urun.ebat1 + 'x' + urun.ebat2 + 'cm</h5>';
            html += '<div class="urun-detay">';
            
            if (urun.kalite) html += '<span><strong>Kalite:</strong> ' + urun.kalite + '</span>';
            html += '<span><strong>Tutkal:</strong> ' + urun.tutkal + '</span>';
            if (urun.kaplama) html += '<span><strong>Kaplama:</strong> ' + urun.kaplama + '</span>';
            if (urun.sinif) html += '<span><strong>Sınıf:</strong> ' + urun.sinif + '</span>';
            if (urun.desen) html += '<span><strong>Desen:</strong> ' + urun.desen + '</span>';
            if (urun.sertifika) html += '<span><strong>Sertifika:</strong> ' + urun.sertifika + '</span>';
            
            html += '</div>';
            html += '<div class="urun-detay">';
            html += '<span class="urun-miktar"><strong>Miktar:</strong> ' + urun.miktar + ' adet</span>';
            html += '<span class="urun-miktar"><strong>m³:</strong> ' + urun.m3.toFixed(3) + ' m³</span>';
            html += '</div>';
            html += '</div>';
        });
        
        $container.html(html);
    }
    
    // Ürün sil
    function removeProduct(index) {
        if (confirm('Bu ürünü silmek istediğinizden emin misiniz?')) {
            urunListesi.splice(index, 1);
            updateProductList();
            updateTotalSummary();
            updateWizardStep();
        }
    }
    
    // Toplam özeti güncelle
    function updateTotalSummary() {
        var toplamCesit = urunListesi.length;
        var toplamAdet = 0;
        var toplamM3 = 0;
        
        urunListesi.forEach(function(urun) {
            toplamAdet += urun.miktar;
            toplamM3 += urun.m3;
        });
        
        $('#toplam_cesit').text(toplamCesit);
        $('#toplam_adet').text(toplamAdet);
        $('#toplam_m3').text(toplamM3.toFixed(3) + ' m³');
    }
    
    // Global olarak erişilebilir fonksiyonlar
    window.removeProduct = removeProduct;
    
    function validateCurrentStep() {
        // Step 4 için özel validasyon yok - ürün ekleme kendi validasyonunu yapıyor
        if (currentStep === 4) {
            return true;
        }
        
        var isValid = true;
        var $currentStepEl = $('.wizard-step[data-step="' + currentStep + '"]');
        
        $currentStepEl.find('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            alert('Lütfen tüm zorunlu alanları doldurunuz!');
        }
        
        return isValid;
    }
    
    // M³ hesaplama fonksiyonu
    function calculateM3() {
        var ebat1 = parseFloat($('#ebat1').val()) || 0; // cm
        var ebat2 = parseFloat($('#ebat2').val()) || 0; // cm  
        var kalinlik = parseFloat($('#kalinlik').val()) || 0; // mm
        var miktar = parseInt($('#miktar').val()) || 0; // adet
        
        if (ebat1 && ebat2 && kalinlik && miktar) {
            // Ebatlar zaten cm, sadece m'ye çevir (÷100), kalınlık mm'den m'ye (÷1000)
            var m3PerPiece = (ebat1 / 100) * (ebat2 / 100) * (kalinlik / 1000);
            return m3PerPiece * miktar;
        }
        return 0;
    }

    // Siparişlerim sayfası fonksiyonları
    if ($('.dastas-siparislerim').length > 0) {
        // Detayları göster
        $('.detay-goster').on('click', function() {
            var siparisNo = $(this).data('siparis-no');
            var detayDiv = $('#detay-' + siparisNo);
            
            if (detayDiv.is(':visible')) {
                detayDiv.slideUp();
                $(this).text('Detayları Göster');
            } else {
                // AJAX ile detayları yükle
                loadSiparisDetay(siparisNo, detayDiv, $(this));
            }
        });
        
        // Sipariş sil
        $('.siparis-sil').on('click', function() {
            if (confirm('Bu siparişi silmek istediğinizden emin misiniz?')) {
                var siparisNo = $(this).data('siparis-no');
                deleteSiparis(siparisNo);
            }
        });
        
        // Sipariş düzenle
        $('.siparis-duzenle').on('click', function() {
            var siparisNo = $(this).data('siparis-no');
            // Düzenleme sayfasına yönlendir
            window.location.href = ajaxurl.replace('/admin-ajax.php', '') + '/siparis-ekle/?edit=' + siparisNo;
        });
        
        // Modal kapatma
        $('.close, .modal').on('click', function(e) {
            if (e.target === this) {
                $('.modal').hide();
            }
        });
    }
    
    // Sipariş detay yükleme fonksiyonu
    function loadSiparisDetay(siparisNo, detayDiv, button) {
        button.text('Yükleniyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dastas_get_siparis_detay',
                nonce: nonce,
                siparis_no: siparisNo
            },
            success: function(response) {
                if (response.success) {
                    detayDiv.html(response.data.html).slideDown();
                    button.text('Detayları Gizle');
                } else {
                    alert('Detaylar yüklenirken hata oluştu: ' + response.data.message);
                    button.text('Detayları Göster');
                }
            },
            error: function(xhr, status, error) {
                alert('Detaylar yüklenirken hata oluştu.');
                button.text('Detayları Göster');
            }
        });
    }
    
    // Sipariş silme fonksiyonu
    function deleteSiparis(siparisNo) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dastas_delete_siparis',
                nonce: nonce,
                siparis_no: siparisNo
            },
            success: function(response) {
                if (response.success) {
                    // Sipariş kartını kaldır
                    $('[data-siparis-no="' + siparisNo + '"]').fadeOut(300, function() {
                        $(this).remove();
                        
                        // Eğer hiç sipariş kalmadıysa mesaj göster
                        if ($('.siparis-kart').length === 0) {
                            $('.siparis-liste').html('<div class="alert alert-info"><p>Henüz sipariş vermemişsiniz.</p><a href="' + 
                                ajaxurl.replace('/admin-ajax.php', '') + '/siparis-ekle/" class="btn btn-primary">İlk Siparişi Ver</a></div>');
                        }
                    });
                    
                    $('#siparis-message').html('<div class="alert alert-success">' + response.data.message + '</div>');
                } else {
                    alert('Sipariş silinirken hata oluştu: ' + response.data.message);
                }
            },
            error: function() {
                alert('Sipariş silinirken hata oluştu.');
            }
        });
    }
    
    // Form değişikliklerinde m³ hesaplamayı güncelle
    $('#siparis-form input, #siparis-form select').on('change keyup', function() {
        var m3 = calculateM3();
        $('#m3_sonuc').text(m3 ? m3.toFixed(3) : '0');
    });
    
    // Input error stillerini temizle
    $('#siparis-form input, #siparis-form select').on('focus', function() {
        $(this).removeClass('error');
    });
    
    // Success Popup Notification
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
        
        // Popup'ı göster
        setTimeout(() => popup.addClass('show'), 100);
        
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
    
    // Multi-order başarı mesajı için özel fonksiyon
    window.showOrderSuccessPopup = showSuccessPopup;
});

// Sayfa yüklendiğinde çalışacak global fonksiyonlar
jQuery(document).ready(function($) {
    // Eğer URL'de success parametresi varsa popup göster
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'order') {
        const orderNo = urlParams.get('order_no');
        const productCount = urlParams.get('product_count');
        if (orderNo) {
            showOrderSuccessPopup('Siparişiniz başarıyla alındı ve işleme konuldu.', orderNo, productCount);
        }
    }
});

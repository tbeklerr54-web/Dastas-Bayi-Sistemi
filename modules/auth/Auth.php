<?php
/**
 * Kimlik Doğrulama ve Kullanıcı Yönetimi Modülü
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Auth {
    
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->registerHooks();
    }
    
    private function registerHooks() {
        // AJAX hooks
        add_action('wp_ajax_dastas_login', [$this, 'handleLogin']);
        add_action('wp_ajax_nopriv_dastas_login', [$this, 'handleLogin']);
        add_action('wp_ajax_dastas_logout', [$this, 'handleLogout']);
        add_action('wp_ajax_dastas_reset_password', [$this, 'handlePasswordReset']);
        add_action('wp_ajax_dastas_get_profile', [$this, 'handleGetProfile']);
        
        // Nonce almak için
        add_action('wp_ajax_dastas_get_nonce', [$this, 'getNonce']);
        add_action('wp_ajax_nopriv_dastas_get_nonce', [$this, 'getNonce']);
    }
    
    public function renderLoginForm($atts) {
        // Zaten giriş yapılmışsa panel sayfasına yönlendir (JavaScript ile)
        if ($this->isLoggedIn()) {
            return '
            <div class="dastas-redirect-message">
                <p>Zaten giriş yapmışsınız. Yönlendiriliyorsunuz...</p>
                <script>
                setTimeout(function() {
                    window.location.href = "' . home_url('/bayi-panel/') . '";
                }, 1000);
                </script>
            </div>';
        }
        
        // Çıkış işlemi (JavaScript ile)
        if (isset($_GET['logout'])) {
            $this->logout();
            return '
            <div class="dastas-logout-message">
                <p>Çıkış yapıldı. Yönlendiriliyorsunuz...</p>
                <script>
                setTimeout(function() {
                    window.location.href = "' . home_url('/bayi-giris/') . '";
                }, 1000);
                </script>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="dastas-login-container">
            <div class="login-form-wrapper">
                <div class="login-header">
                    <h2>Bayi Girişi</h2>
                    <p>Satış bayisi hesabınızla giriş yapın</p>
                </div>
                
                <form id="dastas-login-form" class="login-form">
                    <div class="form-group">
                        <label for="kullanici_adi">Kullanıcı Adı</label>
                        <input type="text" id="kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı adınızı girin" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="sifre">Şifre</label>
                        <input type="password" id="sifre" name="sifre" placeholder="Şifrenizi girin" required autocomplete="current-password">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="login-btn">
                            <span>Giriş Yap</span>
                        </button>
                    </div>
                    
                    <div id="login-message" class="message-container"></div>
                    
                    <div class="form-footer">
                        <a href="#" class="forgot-password" data-toggle="reset-form">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle; margin-right: 4px;">
                                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1L9 7V9C7.9 9 7 9.9 7 11V22C7 23.1 7.9 24 9 24H15C16.1 24 17 23.1 17 22V11C17 9.9 16.1 9 15 9H21Z"/>
                            </svg>
                            Şifremi Unuttum
                        </a>
                    </div>
                </form>
                
                <!-- Şifre Sıfırlama Formu -->
                <form id="dastas-reset-form" class="reset-form" style="display: none;">
                    <div class="form-header">
                        <h3>Şifre Sıfırlama</h3>
                        <p>Bayi kodu ve kullanıcı adınızı girin, yeni şifreniz oluşturulacak</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="reset_bayi_kodu">Bayi Kodu</label>
                        <input type="text" id="reset_bayi_kodu" name="bayi_kodu" placeholder="Örn: BY001" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group">
                        <label for="reset_kullanici_adi">Kullanıcı Adı</label>
                        <input type="text" id="reset_kullanici_adi" name="kullanici_adi" placeholder="Kullanıcı adınızı girin" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="reset-btn">Şifre Sıfırla</button>
                        <button type="button" class="cancel-btn" data-toggle="login-form">Geri Dön</button>
                    </div>
                    
                    <div id="reset-message" class="message-container"></div>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Form toggle
            $('[data-toggle]').click(function(e) {
                e.preventDefault();
                var target = $(this).data('toggle');
                if (target === 'reset-form') {
                    $('.login-form').hide();
                    $('.reset-form').show();
                } else {
                    $('.reset-form').hide();
                    $('.login-form').show();
                }
            });
            
            // Login form submit
            $('#dastas-login-form').submit(function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'dastas_login',
                    kullanici_adi: $('#kullanici_adi').val(),
                    sifre: $('#sifre').val(),
                    nonce: '<?php echo wp_create_nonce('dastas_nonce'); ?>'
                };
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#login-message').html('<div class="success">' + response.data.message + '</div>');
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            $('#login-message').html('<div class="error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#login-message').html('<div class="error">Bağlantı hatası! Lütfen tekrar deneyin.</div>');
                    }
                });
            });
            
            // Reset form submit
            $('#dastas-reset-form').submit(function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'dastas_reset_password',
                    bayi_kodu: $('#reset_bayi_kodu').val(),
                    kullanici_adi: $('#reset_kullanici_adi').val(),
                    nonce: '<?php echo wp_create_nonce('dastas_nonce'); ?>'
                };
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#reset-message').html('<div class="success">' + response.data.message + '</div>');
                            setTimeout(function() {
                                $('.reset-form').hide();
                                $('.login-form').show();
                            }, 2000);
                        } else {
                            $('#reset-message').html('<div class="error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#reset-message').html('<div class="error">Bağlantı hatası! Lütfen tekrar deneyin.</div>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function renderUserProfile($atts) {
        if (!$this->isLoggedIn()) {
            return '<p>Bu sayfayı görüntülemek için giriş yapmalısınız.</p>';
        }
        
        $bayi_data = $this->getCurrentUser();
        
        ob_start();
        ?>
        <div class="dastas-profile-container">
            <div class="profile-wrapper">
                <div class="profile-header">
                    <h2>Profil Bilgileri</h2>
                    <p>Hesap bilgilerinizi buradan güncelleyebilirsiniz</p>
                </div>
                
                <form id="dastas-profile-form" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bayi_kodu">Bayi Kodu</label>
                            <input type="text" value="<?php echo esc_attr($bayi_data->bayi_kodu); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="kullanici_adi">Kullanıcı Adı</label>
                            <input type="text" value="<?php echo esc_attr($bayi_data->kullanici_adi); ?>" disabled>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bayi_adi">Bayi Adı</label>
                        <input type="text" id="bayi_adi" name="bayi_adi" value="<?php echo esc_attr($bayi_data->bayi_adi); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefon">Telefon</label>
                        <input type="text" id="telefon" name="telefon" value="<?php echo esc_attr($bayi_data->telefon); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="eposta">E-posta</label>
                        <input type="email" id="eposta" name="eposta" value="<?php echo esc_attr($bayi_data->eposta); ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="update-btn">Güncelle</button>
                    </div>
                    
                    <div id="profile-message" class="message"></div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handleLogin() {
        // Debug log
        error_log('Dastas Login: İşlem başladı');
        
        // AJAX işlemi olduğunu kontrol et
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            error_log('Dastas Login: AJAX dışı erişim');
            wp_die('Bu işlem sadece AJAX ile yapılabilir');
        }
        
        // Nonce kontrolü
        if (!check_ajax_referer('dastas_nonce', 'nonce', false)) {
            error_log('Dastas Login: Nonce kontrolü başarısız');
            wp_send_json_error(['message' => 'Güvenlik kontrolü başarısız!']);
            return;
        }
        
        $kullanici_adi = sanitize_text_field($_POST['kullanici_adi']);
        $sifre = $_POST['sifre'];
        
        error_log('Dastas Login: Kullanıcı: ' . $kullanici_adi);
        
        // Validation
        if (empty($kullanici_adi) || empty($sifre)) {
            error_log('Dastas Login: Boş alanlar');
            wp_send_json_error(['message' => 'Kullanıcı adı ve şifre gerekli!']);
            return;
        }
        
        $bayi = $this->db->getBayiByKullaniciAdi($kullanici_adi);
        
        if ($bayi && wp_check_password($sifre, $bayi->sifre)) {
            if ($bayi->aktif != 1) {
                error_log('Dastas Login: Pasif hesap');
                wp_send_json_error(['message' => 'Hesabınız pasif durumda!']);
                return;
            }
            
            error_log('Dastas Login: Başarılı giriş');
            $this->setCurrentUser($bayi);
            wp_send_json_success([
                'message' => 'Giriş başarılı!',
                'redirect' => home_url('/bayi-panel/')
            ]);
        } else {
            error_log('Dastas Login: Hatalı bilgiler');
            wp_send_json_error(['message' => 'Kullanıcı adı veya şifre hatalı!']);
        }
    }
    
    public function handleLogout() {
        $this->logout();
        wp_send_json_success(['message' => 'Çıkış yapıldı.']);
    }
    
    public function handlePasswordReset() {
        check_ajax_referer('dastas_nonce', 'nonce');
        
        $bayi_kodu = strtoupper(sanitize_text_field($_POST['bayi_kodu']));
        $kullanici_adi = strtolower(sanitize_text_field($_POST['kullanici_adi']));
        
        // Yeni şifre oluştur
        $yeni_sifre = wp_generate_password(8, false);
        
        if (strlen($yeni_sifre) < 6) {
            wp_send_json_error(['message' => 'Şifre en az 6 karakter olmalıdır!']);
            return;
        }
        
        $bayi = $this->db->getBayiByKod($bayi_kodu);
        
        if ($bayi && strtolower($bayi->kullanici_adi) === $kullanici_adi) {
            // Şifreyi hash'le ve güncelle
            $hashed_password = wp_hash_password($yeni_sifre);
            
            $result = $this->db->updateBayiSifre($bayi->id, $hashed_password);
            
            if ($result !== false) {
                wp_send_json_success(['message' => 'Şifreniz başarıyla sıfırlandı.']);
            } else {
                wp_send_json_error(['message' => 'Şifre sıfırlanırken hata oluştu!']);
            }
        } else {
            wp_send_json_error(['message' => 'Bayi kodu veya kullanıcı adı hatalı!']);
        }
    }
    
    public function handleGetProfile() {
        if (!$this->isLoggedIn()) {
            wp_send_json_error(['message' => 'Giriş yapmalısınız!']);
            return;
        }
        
        check_ajax_referer('dastas_nonce', 'nonce');
        
        $bayi_id = $_SESSION['dastas_bayi_id'];
        $bayi_adi = sanitize_text_field($_POST['bayi_adi']);
        $telefon = sanitize_text_field($_POST['telefon']);
        $eposta = sanitize_email($_POST['eposta']);
        
        $update_data = [
            'bayi_adi' => $bayi_adi,
            'telefon' => $telefon,
            'eposta' => $eposta
        ];
        
        $result = $this->db->updateBayi($bayi_id, $update_data);
        
        if ($result !== false) {
            $_SESSION['dastas_bayi_adi'] = $bayi_adi;
            wp_send_json_success(['message' => 'Profil başarıyla güncellendi!']);
        } else {
            wp_send_json_error(['message' => 'Profil güncellenirken hata oluştu!']);
        }
    }
    
    public function getNonce() {
        echo wp_create_nonce('dastas_nonce');
        wp_die();
    }
    
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['dastas_bayi_id'])) {
            return false;
        }
        
        // Session timeout kontrolü (2 saat)
        if (isset($_SESSION['dastas_login_time'])) {
            if (time() - $_SESSION['dastas_login_time'] > 7200) {
                $this->logout();
                return false;
            }
        }
        
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $bayi_id = $_SESSION['dastas_bayi_id'];
        return $this->db->getBayiById($bayi_id);
    }
    
    private function setCurrentUser($bayi) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['dastas_bayi_id'] = $bayi->id;
        $_SESSION['dastas_bayi_kodu'] = $bayi->bayi_kodu;
        $_SESSION['dastas_bayi_adi'] = $bayi->bayi_adi;
        $_SESSION['dastas_login_time'] = time();
    }
    
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['dastas_bayi_id'])) {
            unset($_SESSION['dastas_bayi_id']);
            unset($_SESSION['dastas_bayi_adi']);
            unset($_SESSION['dastas_bayi_kodu']);
            unset($_SESSION['dastas_login_time']);
        }
    }
    
    public function renderAccountPage($atts) {
        if (!$this->isLoggedIn()) {
            return '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
                ❌ Bu sayfayı görüntülemek için giriş yapmalısınız.
                <br><a href="' . home_url('/bayi-girisi/') . '" style="color: #721c24; text-decoration: underline;">Giriş yapmak için tıklayın</a>
            </div>';
        }
        
        $bayi_id = $_SESSION['dastas_bayi_id'];
        $bayi = $this->db->getBayiById($bayi_id);
        
        if (!$bayi) {
            return '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
                ❌ Bayi bilgileri bulunamadı.
            </div>';
        }
        
        ob_start();
        ?>
        <div id="dastas-account-page" style="max-width: 800px; margin: 20px auto; padding: 20px; font-family: Arial, sans-serif;">
            <!-- Header -->
            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007cba;">
                <h2 style="margin: 0 0 10px 0; color: #333; font-size: 24px;">👤 Hesap Bilgilerim</h2>
                <p style="margin: 0; color: #666; font-size: 14px;">Bayi hesap bilgilerinizi görüntüleyin ve yönetin</p>
            </div>
            
            <!-- Account Info -->
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <div style="background: #007cba; color: white; padding: 15px;">
                    <h3 style="margin: 0; font-size: 18px;">📋 Bayi Bilgileri</h3>
                </div>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: bold; color: #666; font-size: 12px; margin-bottom: 5px; text-transform: uppercase;">Bayi Adı</label>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #e9ecef;">
                                <?php echo esc_html($bayi->bayi_adi); ?>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: bold; color: #666; font-size: 12px; margin-bottom: 5px; text-transform: uppercase;">Bayi Kodu</label>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #e9ecef;">
                                <?php echo esc_html($bayi->bayi_kodu); ?>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: bold; color: #666; font-size: 12px; margin-bottom: 5px; text-transform: uppercase;">Kullanıcı Adı</label>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #e9ecef;">
                                <?php echo esc_html($bayi->kullanici_adi); ?>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: bold; color: #666; font-size: 12px; margin-bottom: 5px; text-transform: uppercase;">Telefon</label>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #e9ecef;">
                                <?php echo esc_html($bayi->telefon ?: 'Belirtilmemiş'); ?>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: bold; color: #666; font-size: 12px; margin-bottom: 5px; text-transform: uppercase;">E-posta</label>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #e9ecef;">
                                <?php echo esc_html($bayi->eposta ?: 'Belirtilmemiş'); ?>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; font-weight: bold; color: #666; font-size: 12px; margin-bottom: 5px; text-transform: uppercase;">Üyelik Tarihi</label>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #e9ecef;">
                                <?php echo date('d.m.Y', strtotime($bayi->olusturma_tarihi)); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="background: #28a745; color: white; padding: 15px;">
                    <h3 style="margin: 0; font-size: 18px;">🚀 Hızlı İşlemler</h3>
                </div>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="<?php echo home_url('/siparis-ekle/'); ?>" 
                           style="display: block; padding: 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; text-align: center; font-weight: bold;">
                            ➕ Yeni Sipariş Ver
                        </a>
                        <a href="<?php echo home_url('/siparislerim/'); ?>" 
                           style="display: block; padding: 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; text-align: center; font-weight: bold;">
                            📋 Siparişlerim
                        </a>
                        <button onclick="logout()" 
                                style="padding: 15px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                            🚪 Çıkış Yap
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'dastas_logout',
                    nonce: '<?php echo wp_create_nonce('dastas_nonce'); ?>'
                }, function() {
                    window.location.href = '<?php echo home_url('/bayi-girisi/'); ?>';
                });
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
<?php
/**
 * Ana Plugin Sınıfı - Tüm plugin işlevlerini yönetir
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Plugin {
    
    private static $instance = null;
    private $modules = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->defineConstants();
        $this->initThemeCompatibility();
        
        // WordPress ready olduğunda hook'ları ve modülleri yükle
        add_action('init', [$this, 'lateInit'], 1);
    }
    
    public function lateInit() {
        $this->initHooks();
        $this->loadModules();
    }
    
    private function defineConstants() {
        // Sabitler ana plugin dosyasında tanımlı, burada ek sabitler olabilir
        if (!defined('DASTAS_MODULES_DIR')) {
            define('DASTAS_MODULES_DIR', DASTAS_PLUGIN_DIR . 'modules/');
        }
    }
    
    /**
     * Tema uyumluluğu ve hata gizleme
     */
    private function initThemeCompatibility() {
        // PHP 8+ deprecated uyarılarını gizle (sadece production'da)
        if (!defined('WP_DEBUG') || (!WP_DEBUG && version_compare(PHP_VERSION, '8.0.0', '>='))) {
            $current_error_reporting = error_reporting();
            // Dynamic property deprecation uyarılarını gizle
            error_reporting($current_error_reporting & ~E_DEPRECATED);
            
            // Custom error handler for ACF warnings
            set_error_handler(function($errno, $errstr, $errfile = '', $errline = 0) {
                // Sadece ACF Ohio dynamic property uyarılarını gizle
                if ($errno === E_DEPRECATED && 
                    strpos($errstr, 'Creation of dynamic property') !== false && 
                    (strpos($errfile, 'acf') !== false || strpos($errfile, 'ohio') !== false)) {
                    return true; // Suppress the error
                }
                
                // Diğer hataları normal akışına bırak
                return false;
            }, E_DEPRECATED);
        }
        
        // ACF Ohio tema uyumluluğu
        add_action('init', [$this, 'handleThemeCompatibility'], 5);
        
        // Plugin conflict prevention
        add_action('plugins_loaded', [$this, 'checkPluginConflicts'], 1);
        
        // Admin notices için uyumluluk
        add_action('admin_notices', [$this, 'hideDeprecatedNotices'], 999);
    }
    
    /**
     * Admin deprecated notice'ları gizle
     */
    public function hideDeprecatedNotices() {
        // Ohio tema deprecated uyarılarını gizle
        echo '<style>
            .notice.notice-warning p:contains("deprecated"),
            .notice.notice-warning p:contains("Creation of dynamic property"),
            .notice.notice-warning p:contains("acf") {
                display: none !important;
            }
        </style>';
    }
    
    /**
     * Tema uyumluluğu handler
     */
    public function handleThemeCompatibility() {
        $current_theme = get_template();
        
        // Ohio teması için özel uyumluluk
        if ($current_theme === 'ohio') {
            // ACF field uyumluluğu
            add_filter('acf/settings/suppress_filters', '__return_true');
            
            // Ohio tema hooks'larından önce çalış
            add_action('after_setup_theme', [$this, 'ohioThemeCompat'], 5);
        }
    }
    
    /**
     * Ohio tema özel uyumluluğu
     */
    public function ohioThemeCompat() {
        // Dynamic property warnings için error handler
        if (class_exists('acf_field_ohio_code')) {
            add_action('init', function() {
                // ACF Ohio field'ları için error suppression
                $error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
                    // Sadece ACF Ohio dynamic property uyarılarını gizle
                    if ($errno === E_DEPRECATED && 
                        strpos($errstr, 'Creation of dynamic property') !== false && 
                        strpos($errfile, 'ohio-extra/acf_ext/fields/') !== false) {
                        return true; // Suppress the error
                    }
                    return false; // Let other errors pass through
                });
            }, 1);
        }
    }
    
    /**
     * Plugin çakışmalarını kontrol et
     */
    public function checkPluginConflicts() {
        // Çakışan plugin'leri tespit et ve uyar
        $conflicting_plugins = [
            'advanced-custom-fields-pro/acf.php' => 'ACF Pro',
            'custom-post-type-ui/custom-post-type-ui.php' => 'Custom Post Type UI'
        ];
        
        foreach ($conflicting_plugins as $plugin_file => $plugin_name) {
            if (is_plugin_active($plugin_file)) {
                error_log("DASTAS: Potential conflict detected with {$plugin_name}");
            }
        }
    }
    
    private function initHooks() {
        register_activation_hook(DASTAS_PLUGIN_DIR . 'dastas-bayi.php', [$this, 'activate']);
        register_deactivation_hook(DASTAS_PLUGIN_DIR . 'dastas-bayi.php', [$this, 'deactivate']);
        
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }
    
    private function loadModules() {
        // Modüllerin güvenli sırada yüklenmesi için kontrol
        static $modules_loaded = false;
        if ($modules_loaded) {
            return; // Birden fazla yüklemeyi önle
        }
        
        // Database modülü önce yüklenmeli
        $this->loadModule('database');
        
        // Diğer modüller (hata durumunda devam et)
        $modules = ['auth', 'orders', 'dashboard', 'notifications', 'admin', 'analytics'];
        foreach ($modules as $module) {
            $this->loadModule($module);
        }
        
        $modules_loaded = true;
    }
    
    /**
     * Modül yükle
     */
    public function loadModule($name) {
        if (isset($this->modules[$name])) {
            return $this->modules[$name];
        }
        
        // Bellek sızıntısını önlemek için sınır koy
        if (count($this->modules) > 10) {
            error_log("Dastas Plugin: Çok fazla modül yüklendi, güvenlik için durduruldu.");
            return false;
        }
        
        $className = 'Dastas_' . ucfirst($name);
        $filePath = DASTAS_PLUGIN_DIR . 'modules/' . $name . '/' . ucfirst($name) . '.php';
        
        if (!file_exists($filePath)) {
            error_log("Dastas Plugin: Modül dosyası bulunamadı: {$filePath}");
            return false;
        }
        
        try {
            require_once $filePath;
            
            if (class_exists($className)) {
                // Auth modülü için özel constructor
                if ($name === 'auth') {
                    $this->modules[$name] = new $className($this->modules['database']);
                } else {
                    $this->modules[$name] = new $className();
                }
                return $this->modules[$name];
            } else {
                error_log("Dastas Plugin: Sınıf bulunamadı: {$className}");
                return false;
            }
        } catch (Exception $e) {
            error_log("Dastas Plugin: Modül yükleme hatası ({$name}): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Modül getir
     */
    public function getModule($name) {
        return $this->loadModule($name);
    }
    
    public function init() {
        // Session güvenli başlatma
        $this->startSecureSession();
        
        // Shortcode'ları kaydet
        $this->registerShortcodes();
    }
    
    private function startSecureSession() {
        // Sadece frontend'de ve headers gönderilmemişse session başlat
        if (!is_admin() && !session_id() && !headers_sent()) {
            // Session güvenlik ayarları
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');
            
            session_start();
        }
    }
    
    private function registerShortcodes() {
        add_shortcode('bayi-giris', [$this, 'loginShortcode']);
        add_shortcode('bayi-panel', [$this, 'dashboardShortcode']);
        add_shortcode('siparis-ekle', [$this, 'orderFormShortcode']);
        add_shortcode('siparislerim', [$this, 'orderListShortcode']);
        add_shortcode('hesap', [$this, 'accountShortcode']);
    }
    
    public function loginShortcode($atts) {
        return $this->getModule('auth')->renderLoginForm($atts);
    }
    
    public function dashboardShortcode($atts) {
        return $this->getModule('dashboard')->renderDashboard($atts);
    }
    
    public function orderFormShortcode($atts) {
        return $this->getModule('orders')->renderOrderForm($atts);
    }
    
    public function orderListShortcode($atts) {
        return $this->getModule('orders')->renderOrderList($atts);
    }
    
    public function accountShortcode($atts) {
        return $this->getModule('auth')->renderAccountPage($atts);
    }
    
    public function enqueueScripts() {
        global $post;
        $is_order_page = false;
        $is_order_list_page = false;
        
        // Sayfa türü tespiti
        if (is_page() && $post) {
            $content = $post->post_content;
            
            // Sipariş ekleme sayfası
            $is_order_page = (
                strpos($content, '[siparis-ekle]') !== false || 
                strpos($content, 'dastas-order-wizard') !== false ||
                strpos($content, 'order-wizard') !== false ||
                strpos($post->post_name, 'siparis') !== false
            );
            
            // Sipariş listeleme sayfası
            $is_order_list_page = (
                strpos($content, '[siparislerim]') !== false ||
                strpos($content, 'order-list') !== false ||
                strpos($post->post_name, 'siparislerim') !== false
            );
        }
        
        if ($is_order_page) {
            // Order wizard sayfası - YENİ wizard dosyalarını yükle
            wp_enqueue_style('order-wizard-new-css', DASTAS_PLUGIN_URL . 'assets/order-wizard-new.css', [], '2.1.0');
            wp_enqueue_script('order-wizard-new-js', DASTAS_PLUGIN_URL . 'assets/order-wizard-new.js', ['jquery'], '2.1.0', true);
            
            // Order wizard configuration
            wp_localize_script('order-wizard-new-js', 'dastas_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dastas_nonce'),
                'site_url' => home_url()
            ]);
        } elseif ($is_order_list_page) {
            // Order list sayfası - Modern liste stillerini yükle
            wp_enqueue_style('order-list-modern-css', DASTAS_PLUGIN_URL . 'assets/order-list-modern.css', [], '2.1.0');
            wp_enqueue_script('dastas-bayi-js', DASTAS_PLUGIN_URL . 'assets/dastas-bayi.js', ['jquery'], DASTAS_VERSION, true);
            
            // Order list configuration
            wp_localize_script('dastas-bayi-js', 'dastas_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dastas_nonce'),
                'site_url' => home_url()
            ]);
        } else {
            // Diğer sayfalar - normal bayi script'lerini yükle
            wp_enqueue_script('dastas-bayi-js', DASTAS_PLUGIN_URL . 'assets/dastas-bayi.js', ['jquery'], DASTAS_VERSION, true);
            wp_enqueue_style('dastas-bayi-css', DASTAS_PLUGIN_URL . 'assets/dastas-bayi.css', [], DASTAS_VERSION);
            
            // AJAX configuration
            wp_localize_script('dastas-bayi-js', 'dastas_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dastas_nonce'),
                'site_url' => home_url()
            ]);
        }
        
        // Ortak CSS dosyaları (her zaman yükle)
        wp_enqueue_style('dastas-dashboard-css', DASTAS_PLUGIN_URL . 'assets/dastas-dashboard.css', [], DASTAS_VERSION);
        wp_enqueue_style('dastas-dashboard-v2-css', DASTAS_PLUGIN_URL . 'assets/dastas-dashboard-v2.css', ['dastas-dashboard-css'], DASTAS_VERSION);
        wp_enqueue_style('dastas-profile-css', DASTAS_PLUGIN_URL . 'assets/dastas-profile.css', [], DASTAS_VERSION);
        wp_enqueue_style('dastas-animations-css', DASTAS_PLUGIN_URL . 'assets/dastas-animations.css', [], DASTAS_VERSION);
        
        // Main script (global işlevler için)
        wp_enqueue_script('dastas-main', DASTAS_PLUGIN_URL . 'assets/main.js', ['jquery'], DASTAS_VERSION, true);
        wp_enqueue_style('dastas-main', DASTAS_PLUGIN_URL . 'assets/main.css', [], DASTAS_VERSION);
        
        // Tema uyumluluğu CSS'i (her zaman yükle)
        wp_enqueue_style('dastas-theme-compat', DASTAS_PLUGIN_URL . 'assets/theme-compatibility.css', ['dastas-main'], DASTAS_VERSION);
    }
    
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'dastas') === false) {
            return;
        }
        
        wp_enqueue_script('dastas-admin', DASTAS_PLUGIN_URL . 'assets/admin.js', ['jquery'], DASTAS_VERSION, true);
        wp_enqueue_style('dastas-admin', DASTAS_PLUGIN_URL . 'assets/admin.css', [], DASTAS_VERSION);
    }
    
    public function activate() {
        $this->getModule('database')->createTables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Temizlik işlemleri
    }
}

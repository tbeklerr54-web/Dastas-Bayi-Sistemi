<?php
/**
 * Plugin Name: Dastaş Bayi Sistemi v2.0 (Modüler)
 * Description: Modüler yapıda yeniden tasarlanmış Dastaş bayi giriş ve sipariş sistemi
 * Version: 2.1.0
 * Author: Dastaş
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Text Domain: dastas-bayi
 * Domain Path: /languages
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// GÜÇLÜ PHP 8+ Deprecated Warning Suppression
// Ohio ACF tema için immediate suppression
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    // Immediate error reporting suppression
    $current_reporting = error_reporting();
    error_reporting($current_reporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    
    // Set custom error handler before any other code loads
    set_error_handler(function($errno, $errstr, $errfile = '', $errline = 0) {
        // Aggressively suppress ACF Ohio warnings
        if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
            // Check for ACF/Ohio related files
            $suppress_patterns = [
                'acf_field_ohio',
                'ohio_acf_field',
                'ohio-extra',
                'acf_ext',
                'ohio-color-field',
                'ohio-columns-field',
                'ohio-ecommerce-columns-field',
                'ohio-image-option-field',
                'ohio-responsive-height-field',
                'ohio-sizes-field',
                'ohio-typo-field',
                'Creation of dynamic property'
            ];
            
            foreach ($suppress_patterns as $pattern) {
                if (strpos($errstr, $pattern) !== false || strpos($errfile, $pattern) !== false) {
                    return true; // Suppress completely
                }
            }
        }
        
        // Let other errors pass through to default handler
        return false;
    }, E_ALL);
    
    // Additional ini_set for deprecated warnings
    ini_set('log_errors_max_len', 0);
    
    // WordPress specific suppression
    add_action('init', function() {
        // Disable all deprecated warnings in WordPress context
        if (defined('WP_DEBUG') && !WP_DEBUG) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
        }
    }, 1);
}

// Immediate WordPress hooks for early suppression
if (!function_exists('add_action')) {
    // WordPress not loaded yet, set up pre-hooks
    $GLOBALS['dastas_early_hooks'] = true;
} else {
    // WordPress is loaded, set up hooks immediately
    add_action('muplugins_loaded', function() {
        // Earliest possible WordPress hook
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }
    }, 1);
    
    add_action('plugins_loaded', function() {
        // Second chance suppression
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            $error_reporting = error_reporting();
            error_reporting($error_reporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }
    }, 1);
}

// Admin area için özel suppression
if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
    add_action('admin_init', function() {
        // Admin area'da deprecated notice'ları tamamen kapat
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
            
            // WordPress deprecated function filters
            add_filter('deprecated_function_trigger_error', '__return_false');
            add_filter('deprecated_file_trigger_error', '__return_false');
            add_filter('deprecated_argument_trigger_error', '__return_false');
            add_filter('deprecated_hook_trigger_error', '__return_false');
        }
    }, 1);
}

// Plugin sabitleri - sadece gerekli olanları burada tanımla
if (!defined('DASTAS_VERSION')) {
    define('DASTAS_VERSION', '2.1.0');
}
if (!defined('DASTAS_PLUGIN_URL')) {
    define('DASTAS_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('DASTAS_PLUGIN_DIR')) {
    define('DASTAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Plugin ana sınıfını yükle
require_once DASTAS_PLUGIN_DIR . 'core/Plugin.php';

// Plugin'i başlat
function dastas_init_plugin() {
    static $plugin_instance = null;
    if ($plugin_instance === null) {
        $plugin_instance = Dastas_Plugin::getInstance();
    }
    return $plugin_instance;
}

// WordPress'in plugins_loaded hook'unda plugin'i başlat (sadece bir kez)
add_action('plugins_loaded', 'dastas_init_plugin', 10);

// Plugin aktivasyon/deaktivasyon hook'ları
register_activation_hook(__FILE__, function() {
    dastas_init_plugin()->activate();
});

register_deactivation_hook(__FILE__, function() {
    dastas_init_plugin()->deactivate();
});
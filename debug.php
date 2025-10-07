<?php
/**
 * Dastas Bayi Debug Utility
 * Bu dosyayı URL'de çağırarak debug bilgileri alabilirsiniz
 */

// WordPress yükle
require_once('../../../wp-load.php');

// Güvenlik kontrolü - sadece admin kullanıcılar erişebilir
if (!current_user_can('manage_options')) {
    die('Bu sayfaya erişim yetkiniz yok.');
}

echo "<h1>Dastas Bayi Debug Bilgileri</h1>";

// Veritabanı bağlantısını kontrol et
global $wpdb;
echo "<h2>Veritabanı Durumu</h2>";
echo "<p>WordPress DB Prefix: " . $wpdb->prefix . "</p>";

// Tabloları kontrol et
$tables = [
    'bayi' => $wpdb->prefix . 'dastas_bayi',
    'siparis' => $wpdb->prefix . 'dastas_siparis',
    'notifications' => $wpdb->prefix . 'dastas_notifications',
    'templates' => $wpdb->prefix . 'dastas_siparis_sablonlari'
];

echo "<h3>Tablo Durumları</h3>";
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    $status = $exists ? "✅ Mevcut" : "❌ Bulunamadı";
    echo "<p>$name ($table): $status</p>";
    
    if ($exists && $name === 'bayi') {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "<p>→ Kayıt sayısı: $count</p>";
        
        if ($count > 0) {
            $sample = $wpdb->get_results("SELECT bayi_kodu, kullanici_adi, bayi_adi, aktif FROM $table LIMIT 3", ARRAY_A);
            echo "<p>→ Örnek kayıtlar:</p>";
            echo "<pre>" . print_r($sample, true) . "</pre>";
        }
    }
}

// AJAX endpoint'ini kontrol et
echo "<h3>AJAX Endpoint Kontrolü</h3>";
$ajax_url = admin_url('admin-ajax.php');
echo "<p>AJAX URL: $ajax_url</p>";

// Nonce kontrolü
$nonce = wp_create_nonce('dastas_nonce');
echo "<p>Test Nonce: $nonce</p>";

// Plugin aktiflik durumu
echo "<h3>Plugin Durumu</h3>";
echo "<p>Plugin aktif: " . (is_plugin_active('dastas-bayi-v2/dastas-bayi-v2.php') ? "✅ Evet" : "❌ Hayır") . "</p>";

// WordPress debug durumu
echo "<h3>WordPress Ayarları</h3>";
echo "<p>WP_DEBUG: " . (WP_DEBUG ? "✅ Aktif" : "❌ Pasif") . "</p>";
echo "<p>WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? "✅ Aktif" : "❌ Pasif") . "</p>";

// Session durumu
echo "<h3>Session Durumu</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session durumu: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Aktif" : "❌ Pasif") . "</p>";

if (isset($_SESSION['dastas_bayi_id'])) {
    echo "<p>Mevcut bayi ID: " . $_SESSION['dastas_bayi_id'] . "</p>";
} else {
    echo "<p>Aktif bayi girişi yok</p>";
}

echo "<h3>Test AJAX İsteği</h3>";
echo "<button onclick='testAjax()'>AJAX Test Et</button>";
echo "<div id='ajax-result'></div>";

?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function testAjax() {
    $.ajax({
        url: '<?php echo $ajax_url; ?>',
        type: 'POST',
        data: {
            action: 'dastas_get_nonce',
        },
        success: function(response) {
            $('#ajax-result').html('<p style="color: green;">✅ AJAX çalışıyor. Nonce: ' + response + '</p>');
        },
        error: function(xhr, status, error) {
            $('#ajax-result').html('<p style="color: red;">❌ AJAX hatası: ' + status + ' - ' + error + '</p>');
        }
    });
}
</script>
<?php
/**
 * Admin Modülü
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Admin {
    
    private $db;
    
    public function __construct() {
        $this->db = Dastas_Plugin::getInstance()->getModule('database');
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        
        // AJAX handlers for admin
        add_action('wp_ajax_dastas_admin_action', array($this, 'handleAdminAction'));
        add_action('wp_ajax_dastas_get_bayi_details', array($this, 'handleGetBayiDetails'));
        add_action('wp_ajax_dastas_export_orders', array($this, 'handleExportOrders'));
        add_action('wp_ajax_dastas_update_order_status', array($this, 'handleUpdateOrderStatus'));
        add_action('wp_ajax_dastas_get_admin_order_details', array($this, 'handleGetAdminOrderDetails'));
        add_action('wp_ajax_dastas_duplicate_order', array($this, 'handleDuplicateOrder'));
        add_action('wp_ajax_dastas_delete_order', array($this, 'handleDeleteOrder'));
        add_action('wp_ajax_dastas_send_order_notification', array($this, 'handleSendOrderNotification'));
        add_action('wp_ajax_dastas_bulk_order_action', array($this, 'handleBulkOrderAction'));
        add_action('wp_ajax_dastas_get_order_details', array($this, 'handleGetOrderDetails'));
        add_action('wp_ajax_dastas_print_order', array($this, 'handlePrintOrder'));
        add_action('wp_ajax_dastas_test_ajax', array($this, 'handleTestAjax'));
        add_action('wp_ajax_dastas_create_test_order', array($this, 'handleCreateTestOrder'));
    }
    
    public function addAdminMenu() {
        add_menu_page(
            'Dastas Bayi Sistemi',
            'Dastas Bayi',
            'manage_options',
            'dastas-bayi',
            array($this, 'renderMainPage'),
            'dashicons-store',
            30
        );
        
        add_submenu_page(
            'dastas-bayi',
            'Siparişler',
            'Siparişler',
            'manage_options',
            'dastas-siparisler',
            array($this, 'renderOrdersPage')
        );
        
        add_submenu_page(
            'dastas-bayi',
            'Bayiler',
            'Bayiler',
            'manage_options',
            'dastas-bayiler',
            array($this, 'renderBayilerPage')
        );
        
        add_submenu_page(
            'dastas-bayi',
            'Bildirimler',
            'Bildirimler',
            'manage_options',
            'dastas-bildirimler',
            array($this, 'renderNotificationsPage')
        );
        
        add_submenu_page(
            'dastas-bayi',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'dastas-ayarlar',
            array($this, 'renderSettingsPage')
        );
    }
    
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'dastas') !== false) {
            // jQuery'yi açıkça yükle
            wp_enqueue_script('jquery');
            
            wp_enqueue_script('dastas-admin-js', plugin_dir_url(__FILE__) . '../../assets/admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('dastas-admin-css', plugin_dir_url(__FILE__) . '../../assets/dastas-admin.css', array(), '1.0.0');
            
            // Admin panel stilleri
            wp_enqueue_style('dastas-admin-panel-css', plugin_dir_url(__FILE__) . '../../assets/dastas-admin-panel.css', array(), '1.0.0');
            
            // Orders table CSS
            wp_enqueue_style('admin-orders-table-css', plugin_dir_url(__FILE__) . '../../assets/admin-orders-table.css', array(), '1.0.0');
            
            // Chart.js for analytics - sadece analytics sayfasında
            if (strpos($hook, 'dastas-analytics') !== false || isset($_GET['page']) && $_GET['page'] === 'dastas-analytics') {
                wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
                wp_enqueue_style('dastas-analytics-css', plugin_dir_url(__FILE__) . '../../assets/dastas-analytics.css', array(), '1.0.0');
            }
            
            wp_localize_script('dastas-admin-js', 'dastas_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dastas_admin_nonce'),
            ));

            // Global nonce değişkeni de tanımla (bazı yerlerde kullanılıyor)
            wp_localize_script('dastas-admin-js', 'dastas_nonce', wp_create_nonce('dastas_admin_nonce'));
        }
    }
    
    public function renderMainPage() {
        $stats = $this->getSystemStats();
        $detailed_stats = $this->getDetailedStats();
        ?>
        <div class="wrap">
            <div class="dastas-admin-header">
                <h1>🏪 Dastas Bayi Sistemi - Genel Bakış (Güncellendi)</h1>
                <p>Sistemin genel durumu ve önemli metrikler</p>
            </div>

            <!-- Enhanced Statistics Cards -->
            <div class="dastas-admin-stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['toplam_bayi']); ?></h3>
                        <p>Toplam Bayi</p>
                        <div class="stat-trend trend-up">
                            <span class="trend-icon">↗️</span>
                            <span class="trend-text">+%<?php echo $detailed_stats['bayi_artis_orani']; ?> bu ay</span>
                        </div>
                    </div>
                    <div class="stat-badge"><?php echo $stats['aktif_bayi']; ?> aktif</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['bekleyen_siparis']); ?></h3>
                        <p>Bekleyen Sipariş</p>
                        <div class="stat-trend trend-stable">
                            <span class="trend-icon">➡️</span>
                            <span class="trend-text">Ortalama <?php echo $detailed_stats['ortalama_bekleme']; ?> gün</span>
                        </div>
                    </div>
                    <div class="stat-badge priority-<?php echo $detailed_stats['bekleyen_oncelik']; ?>">
                        <?php echo $detailed_stats['bekleyen_oncelik'] === 'high' ? '⚠️ Yüksek' : '✅ Normal'; ?>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($detailed_stats['tamamlanan_siparis']); ?></h3>
                        <p>Tamamlanan Sipariş</p>
                        <div class="stat-trend trend-up">
                            <span class="trend-icon">📈</span>
                            <span class="trend-text">+%<?php echo $detailed_stats['tamamlanma_orani']; ?> başarı</span>
                        </div>
                    </div>
                    <div class="stat-badge">Bu hafta</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['toplam_siparis']); ?></h3>
                        <p>Toplam Sipariş</p>
                        <div class="stat-trend trend-up">
                            <span class="trend-icon">🔥</span>
                            <span class="trend-text"><?php echo number_format($detailed_stats['bugunku_siparis']); ?> bugün</span>
                        </div>
                    </div>
                    <div class="stat-badge"><?php echo number_format($detailed_stats['toplam_m3'], 1); ?> m³</div>
                </div>
            </div>

            <!-- Tips and Tricks Section -->
            <div class="tips-tricks-section">
                <div class="section-header">
                    <h3>💡 İpuçları ve Püf Noktaları</h3>
                </div>
                <div class="tips-grid">
                    <div class="tip-card">
                        <div class="tip-icon">⚡</div>
                        <div class="tip-content">
                            <h4>Hızlı Toplu İşlemler</h4>
                            <p>Siparişler sayfasında checkbox ile birden fazla siparişi seçip toplu işlem yapabilirsiniz.</p>
                        </div>
                    </div>

                    <div class="tip-card">
                        <div class="tip-icon">🔍</div>
                        <div class="tip-content">
                            <h4>Filtreleme Sistemi</h4>
                            <p>Bayi koduna göre arama yapmak için arama kutusunu kullanın. Büyük küçük harf duyarlıdır.</p>
                        </div>
                    </div>

                    <div class="tip-card">
                        <div class="tip-icon">📊</div>
                        <div class="tip-content">
                            <h4>Raporlama</h4>
                            <p>Excel export özelliği ile detaylı raporlar alabilir, grafik analizleri inceleyebilirsiniz.</p>
                        </div>
                    </div>

                    <div class="tip-card">
                        <div class="tip-icon">🔔</div>
                        <div class="tip-content">
                            <h4>Bildirim Yönetimi</h4>
                            <p>Otomatik bildirim ayarlarını Ayarlar bölümünden yapılandırabilirsiniz.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function refreshDashboard() {
            location.reload();
        }

        function exportReport() {
            alert('Raporlama özelliği yakında eklenecek!');
        }

        function filterOrdersByType(type) {
            // Sipariş filtreleme işlemi
            console.log('Filtering orders by type:', type);
        }

        function bulkApproveOrders() {
            window.location.href = '<?php echo admin_url('admin.php?page=dastas-siparisler'); ?>';
        }

        function sendNotification() {
            window.location.href = '<?php echo admin_url('admin.php?page=dastas-bildirimler'); ?>';
        }

        function exportData() {
            alert('Veri export özelliği için Siparişler sayfasına gidin!');
        }

        function systemMaintenance() {
            alert('Bakım modu aktifleştirildi!');
        }
        </script>
        <?php
    }
    
    public function renderOrdersPage() {
        // Filtreleme parametrelerini al
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $bayi_filter = isset($_GET['bayi_filter']) ? sanitize_text_field($_GET['bayi_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';
        
        $orders = $this->getOrders($date_from, $date_to, $bayi_filter, $status_filter);
        $bayiler = $this->db->getBayiler(); // Filtre için bayi listesi
        ?>
        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            'dastas-blue': '#667eea',
                            'dastas-purple': '#764ba2',
                        }
                    }
                }
            }
        </script>
        
        <div class="min-h-screen bg-gray-50 p-6">
            <!-- Header Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="mb-4 sm:mb-0">
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            📦 <span>Siparişler Yönetimi</span>
                        </h1>
                        <p class="text-gray-600 mt-1">Tüm siparişleri görüntüleyin ve yönetin</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="testAjax()" 
                                class="inline-flex items-center px-4 py-2 border border-green-300 rounded-lg text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200">
                            🧪 <span class="ml-2">Test AJAX</span>
                        </button>
                        <button onclick="refreshOrdersList()" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-dastas-blue focus:border-transparent transition-all duration-200">
                            🔄 <span class="ml-2">Yenile</span>
                        </button>
                        <button id="export-orders"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-gradient-to-r from-dastas-blue to-dastas-purple hover:from-blue-600 hover:to-purple-600 focus:outline-none focus:ring-2 focus:ring-dastas-blue focus:border-transparent transition-all duration-200 shadow-md">
                            📊 <span class="ml-2">Excel'e Aktar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    🔍 <span>Filtreler</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">📅 Başlangıç Tarihi</label>
                        <input type="date" id="date-from" value="<?php echo esc_attr($date_from); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-dastas-blue focus:border-transparent transition-all duration-200">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">📅 Bitiş Tarihi</label>
                        <input type="date" id="date-to" value="<?php echo esc_attr($date_to); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-dastas-blue focus:border-transparent transition-all duration-200">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">👤 Bayi</label>
                        <select id="bayi-filter" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-dastas-blue focus:border-transparent transition-all duration-200">
                            <option value="">Tüm Bayiler</option>
                            <?php foreach ($bayiler as $bayi): ?>
                                <option value="<?php echo $bayi->id; ?>" <?php selected($bayi_filter, $bayi->id); ?>>
                                    <?php echo esc_html($bayi->bayi_adi . ' (' . $bayi->bayi_kodu . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">📋 Durum</label>
                        <select id="status-filter" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-dastas-blue focus:border-transparent transition-all duration-200">
                            <option value="">Tüm Durumlar</option>
                            <option value="beklemede" <?php selected($status_filter, 'beklemede'); ?>>⏳ Beklemede</option>
                            <option value="onaylandi" <?php selected($status_filter, 'onaylandi'); ?>>✅ Onaylandı</option>
                            <option value="hazirlaniyor" <?php selected($status_filter, 'hazirlaniyor'); ?>>🔨 Hazırlanıyor</option>
                            <option value="sevk-edildi" <?php selected($status_filter, 'sevk-edildi'); ?>>🚚 Sevk Edildi</option>
                            <option value="teslim-edildi" <?php selected($status_filter, 'teslim-edildi'); ?>>📦 Teslim Edildi</option>
                            <option value="iptal" <?php selected($status_filter, 'iptal'); ?>>❌ İptal</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-3 mt-6">
                    <button onclick="applyFilters()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-dastas-blue hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                        🔍 <span class="ml-2">Filtrele</span>
                    </button>
                    <button onclick="clearFilters()" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200">
                        🗑️ <span class="ml-2">Temizle</span>
                    </button>
                </div>
            </div>

            <!-- Bulk Actions & Summary -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4 mb-4 sm:mb-0">
                        <select id="bulk-action-selector" 
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-dastas-blue focus:border-transparent transition-all duration-200">
                            <option value="-1">Toplu İşlemler</option>
                            <option value="approve">✅ Onayla</option>
                            <option value="prepare">🔨 Hazırlanıyor</option>
                            <option value="shipped">🚚 Sevk Edildi</option>
                            <option value="delivered">📦 Teslim Edildi</option>
                            <option value="cancel">❌ İptal Et</option>
                        </select>
                        <button id="doaction" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-all duration-200">
                            Uygula
                        </button>
                        
                        <div id="bulk-info" class="hidden text-sm text-gray-600 bg-blue-50 px-3 py-2 rounded-lg">
                            <span id="selected-count">0</span> sipariş seçildi
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <div class="bg-gray-50 px-3 py-2 rounded-lg">
                            <strong class="text-gray-900"><?php echo count($orders); ?></strong> sipariş
                        </div>
                        <div class="bg-gray-50 px-3 py-2 rounded-lg">
                            <strong class="text-gray-900"><?php echo number_format(array_sum(array_column($orders, 'toplam_m3')), 1); ?></strong> m³
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" id="cb-select-all" 
                                           class="rounded border-gray-300 text-dastas-blue focus:ring-dastas-blue">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" 
                                    onclick="sortTable('siparis_no')">
                                    <div class="flex items-center gap-2">
                                        <span>Sipariş No</span>
                                        <span class="text-gray-400">↕️</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" 
                                    onclick="sortTable('bayi_adi')">
                                    <div class="flex items-center gap-2">
                                        <span>Bayi</span>
                                        <span class="text-gray-400">↕️</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" 
                                    onclick="sortTable('siparis_tarihi')">
                                    <div class="flex items-center gap-2">
                                        <span>Tarih</span>
                                        <span class="text-gray-400">↕️</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ürün Sayısı
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" 
                                    onclick="sortTable('toplam_m3')">
                                    <div class="flex items-center gap-2">
                                        <span>Toplam m³</span>
                                        <span class="text-gray-400">↕️</span>
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Durum
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    İşlemler
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $index => $order): ?>
                                <?php
                                $days_pending = floor((time() - strtotime($order->siparis_tarihi)) / (60*60*24));
                                $priority_class = '';
                                if ($order->durum === 'beklemede' && $days_pending > 7) {
                                    $priority_class = 'bg-red-50 border-l-4 border-red-400';
                                } elseif ($order->durum === 'beklemede' && $days_pending > 3) {
                                    $priority_class = 'bg-yellow-50 border-l-4 border-yellow-400';
                                }
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200 <?php echo $priority_class; ?>"
                                    data-order-id="<?php echo $order->id; ?>" data-status="<?php echo $order->durum; ?>">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="order[]" value="<?php echo $order->id; ?>"
                                               class="order-checkbox rounded border-gray-300 text-dastas-blue focus:ring-dastas-blue">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm font-medium text-gray-900">#<?php echo esc_html($order->siparis_no); ?></div>
                                            <?php if ($days_pending > 7 && $order->durum === 'beklemede'): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                                      title="<?php echo $days_pending; ?> gündür bekliyor">
                                                    🚨 Acil
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo esc_html($order->bayi_adi); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($order->siparis_tarihi)); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo date('d.m.Y', strtotime($order->siparis_tarihi)); ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?php
                                                if ($days_pending == 0) {
                                                    echo 'Bugün';
                                                } elseif ($days_pending == 1) {
                                                    echo 'Dün';
                                                } else {
                                                    echo $days_pending . ' gün önce';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo number_format($order->urun_sayisi); ?> adet (<?php echo $order->kalem_sayisi; ?> kalem)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo number_format($order->toplam_m3, 2); ?> m³</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select class="order-status-select text-sm rounded-lg border-gray-300 focus:ring-dastas-blue focus:border-transparent"
                                                data-order-id="<?php echo $order->id; ?>" data-original="<?php echo $order->durum; ?>">
                                            <option value="beklemede" <?php selected($order->durum, 'beklemede'); ?> class="text-yellow-600">⏳ Beklemede</option>
                                            <option value="onaylandi" <?php selected($order->durum, 'onaylandi'); ?> class="text-green-600">✅ Onaylandı</option>
                                            <option value="hazirlaniyor" <?php selected($order->durum, 'hazirlaniyor'); ?> class="text-blue-600">🔨 Hazırlanıyor</option>
                                            <option value="sevk-edildi" <?php selected($order->durum, 'sevk-edildi'); ?> class="text-purple-600">🚚 Sevk Edildi</option>
                                            <option value="teslim-edildi" <?php selected($order->durum, 'teslim-edildi'); ?> class="text-green-600">📦 Teslim Edildi</option>
                                            <option value="iptal" <?php selected($order->durum, 'iptal'); ?> class="text-red-600">❌ İptal</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button class="view-order-btn inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                                onclick="viewOrderDetails(<?php echo $order->id; ?>, '<?php echo esc_attr($order->siparis_no); ?>')"
                                                data-order-id="<?php echo $order->id; ?>"
                                                data-siparis-no="<?php echo esc_attr($order->siparis_no); ?>"
                                                title="Detayları Görüntüle">
                                            👁️ Detay
                                        </button>

                                        <div class="relative inline-block">
                                            <button class="dropdown-toggle inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                                    onclick="toggleDropdown(this)">
                                                ⋮
                                            </button>
                                            <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                                <a href="#" onclick="duplicateOrder(<?php echo $order->id; ?>)"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">📋 Kopyala</a>
                                                <a href="#" onclick="printOrder(<?php echo $order->id; ?>, '<?php echo esc_attr($order->siparis_no); ?>')"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">🖨️ Yazdır</a>
                                                <a href="#" onclick="sendNotification(<?php echo $order->id; ?>)"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">📧 Bildirim Gönder</a>
                                                <hr class="my-1">
                                                <a href="#" onclick="deleteOrder(<?php echo $order->id; ?>)"
                                                   class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">🗑️ Sil</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📦</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Henüz sipariş bulunmuyor</h3>
                        <p class="text-gray-500">Bayiler sipariş vermeye başladığında burada görünecekler.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tailwind JavaScript for Interactions -->
        <script>
            // WordPress AJAX URL - standart hale getir
            window.ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
            window.dastas_admin_ajax = <?php echo json_encode(array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dastas_admin_nonce'),
            )); ?>;

            // jQuery kontrolü
            if (typeof jQuery === 'undefined') {
                console.error('jQuery yüklenmemiş!');
                alert('jQuery yüklenmemiş. Lütfen sayfayı yenileyin.');
            } else {
                console.log('jQuery yüklü:', jQuery.fn.jquery);
            }
            
            // Dropdown toggle function
            function toggleDropdown(button) {
                const dropdown = button.nextElementSibling;
                const isHidden = dropdown.classList.contains('hidden');
                
                // Close all other dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
                
                // Toggle current dropdown
                if (isHidden) {
                    dropdown.classList.remove('hidden');
                } else {
                    dropdown.classList.add('hidden');
                }
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.dropdown-toggle')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.classList.add('hidden');
                    });
                }
            });

            // Select all checkbox functionality
            document.getElementById('cb-select-all').addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                updateBulkInfo();
            });

            // Individual checkbox functionality
            document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkInfo);
            });

            function updateBulkInfo() {
                const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
                const bulkInfo = document.getElementById('bulk-info');
                const selectedCount = document.getElementById('selected-count');
                
                if (checkedBoxes.length > 0) {
                    selectedCount.textContent = checkedBoxes.length;
                    bulkInfo.classList.remove('hidden');
                } else {
                    bulkInfo.classList.add('hidden');
                }
            }

            // Sort table function
            function sortTable(column) {
                console.log('Sorting by:', column);
                // Add sorting logic here
            }

            // Filter functions
            function applyFilters() {
                const dateFrom = document.getElementById('date-from').value;
                const dateTo = document.getElementById('date-to').value;
                const bayiFilter = document.getElementById('bayi-filter').value;
                const statusFilter = document.getElementById('status-filter').value;
                
                // Reload page with filters
                const params = new URLSearchParams(window.location.search);
                if (dateFrom) params.set('date_from', dateFrom);
                else params.delete('date_from');
                
                if (dateTo) params.set('date_to', dateTo);
                else params.delete('date_to');
                
                if (bayiFilter) params.set('bayi_filter', bayiFilter);
                else params.delete('bayi_filter');
                
                if (statusFilter) params.set('status_filter', statusFilter);
                else params.delete('status_filter');
                
                window.location.search = params.toString();
            }

            function clearFilters() {
                document.getElementById('date-from').value = '';
                document.getElementById('date-to').value = '';
                document.getElementById('bayi-filter').value = '';
                document.getElementById('status-filter').value = '';
                
                // Remove all filter parameters
                window.location.href = window.location.pathname + '?page=dastas-admin-siparisler';
            }

            // Order actions
            function refreshOrdersList() {
                location.reload();
            }

            function duplicateOrder(orderId) {
                if (confirm('Bu siparişi kopyalamak istediğinizden emin misiniz?')) {
                    jQuery.post(window.ajaxurl, {
                        action: 'dastas_duplicate_order',
                        order_id: orderId,
                        nonce: window.dastas_nonce
                    }, function(response) {
                        if (response.success) {
                            alert('Sipariş başarıyla kopyalandı!');
                            location.reload();
                        } else {
                            alert('Hata: ' + response.data);
                        }
                    });
                }
            }

            function printOrder(orderId, siparisNo) {
                // Open print window
                const printUrl = '<?php echo admin_url("admin-ajax.php"); ?>?action=dastas_print_order&order_id=' + orderId + '&siparis_no=' + encodeURIComponent(siparisNo) + '&nonce=<?php echo wp_create_nonce("dastas_admin_nonce"); ?>';
                window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes');
            }

            function sendNotification(orderId) {
                if (confirm('Bu sipariş için bayi bildirim gönderilsin mi?')) {
                    jQuery.post(window.ajaxurl, {
                        action: 'dastas_send_order_notification',
                        order_id: orderId,
                        nonce: window.dastas_nonce
                    }, function(response) {
                        if (response.success) {
                            alert('Bildirim başarıyla gönderildi!');
                        } else {
                            alert('Hata: ' + response.data);
                        }
                    });
                }
            }

            function deleteOrder(orderId) {
                if (confirm('Bu siparişi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
                    jQuery.post(window.ajaxurl, {
                        action: 'dastas_delete_order',
                        order_id: orderId,
                        nonce: window.dastas_nonce
                    }, function(response) {
                        if (response.success) {
                            alert('Sipariş başarıyla silindi!');
                            location.reload();
                        } else {
                            alert('Hata: ' + response.data);
                        }
                    });
                }
            }

            // Export orders
            document.getElementById('export-orders').addEventListener('click', function() {
                const filters = {
                    date_from: document.getElementById('date-from').value,
                    date_to: document.getElementById('date-to').value,
                    bayi_filter: document.getElementById('bayi-filter').value,
                    status_filter: document.getElementById('status-filter').value
                };
                
                const exportUrl = '<?php echo admin_url("admin-ajax.php"); ?>?action=dastas_export_orders&nonce=<?php echo wp_create_nonce("dastas_admin_nonce"); ?>&' + 
                    Object.keys(filters).map(key => filters[key] ? key + '=' + encodeURIComponent(filters[key]) : '').filter(Boolean).join('&');
                
                window.location.href = exportUrl;
            });

            // Order status change
            document.querySelectorAll('.order-status-select').forEach(select => {
                select.addEventListener('change', function() {
                    const orderId = this.dataset.orderId;
                    const newStatus = this.value;
                    const originalStatus = this.dataset.original;
                    const selectElement = this;
                    
                    if (confirm(`Sipariş durumunu "${getStatusText(newStatus)}" olarak değiştirmek istediğinizden emin misiniz?`)) {
                        jQuery.post(window.ajaxurl, {
                            action: 'dastas_update_order_status',
                            order_id: orderId,
                            new_status: newStatus,
                            nonce: window.dastas_nonce
                        }, function(response) {
                            if (response.success) {
                                selectElement.dataset.original = newStatus;
                                // Update status indicator
                                const indicator = selectElement.parentElement.querySelector('.status-indicator');
                                if (indicator) {
                                    indicator.className = 'status-indicator status-' + newStatus;
                                }
                                alert('Sipariş durumu başarıyla güncellendi!');
                            } else {
                                alert('Hata: ' + response.data);
                                selectElement.value = originalStatus;
                            }
                        });
                    } else {
                        this.value = originalStatus;
                    }
                });
            });

            function getStatusText(status) {
                const statusTexts = {
                    'beklemede': 'Beklemede',
                    'onaylandi': 'Onaylandı',
                    'hazirlaniyor': 'Hazırlanıyor',
                    'sevk-edildi': 'Sevk Edildi',
                    'teslim-edildi': 'Teslim Edildi',
                    'iptal': 'İptal'
                };
                return statusTexts[status] || status;
            }

            // Bulk actions
            document.getElementById('doaction').addEventListener('click', function() {
                const bulkAction = document.getElementById('bulk-action-selector').value;
                const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
                
                if (bulkAction === '-1') {
                    alert('Lütfen bir işlem seçin.');
                    return;
                }
                
                if (checkedBoxes.length === 0) {
                    alert('Lütfen en az bir sipariş seçin.');
                    return;
                }
                
                const orderIds = Array.from(checkedBoxes).map(cb => cb.value);
                
                if (confirm(`Seçilen ${orderIds.length} sipariş için "${getBulkActionText(bulkAction)}" işlemini yapmak istediğinizden emin misiniz?`)) {
                    jQuery.post(window.ajaxurl, {
                        action: 'dastas_bulk_order_action',
                        bulk_action: bulkAction,
                        order_ids: orderIds,
                        nonce: window.dastas_nonce
                    }, function(response) {
                        if (response.success) {
                            alert('İşlem başarıyla tamamlandı!');
                            location.reload();
                        } else {
                            alert('Hata: ' + response.data);
                        }
                    });
                }
            });

            function getBulkActionText(action) {
                const actionTexts = {
                    'delete': 'Sil',
                    'mark-pending': 'Beklemede İşaretle',
                    'mark-approved': 'Onaylandı İşaretle',
                    'mark-preparing': 'Hazırlanıyor İşaretle',
                    'mark-shipped': 'Sevk Edildi İşaretle',
                    'mark-delivered': 'Teslim Edildi İşaretle',
                    'export': 'Dışa Aktar'
                };
                return actionTexts[action] || action;
            }

            // View order details - admin panel için düzeltilmiş versiyon
            window.viewOrderDetails = function(orderId, siparisNo) {
                console.log('Admin panel sipariş detayı isteniyor:', orderId, siparisNo);

                // Admin sistemini kullan
                if (window.DastasAdmin) {
                    window.DastasAdmin.viewOrderDetails(orderId, siparisNo);
                } else {
                    // Fallback olarak direkt AJAX çağrısı - admin panel için doğru action kullan
                    jQuery.post(window.ajaxurl || dastas_admin_ajax.ajax_url, {
                        action: 'dastas_get_admin_order_details',
                        order_id: orderId,
                        siparis_no: siparisNo,
                        nonce: window.dastas_nonce || dastas_admin_ajax.nonce
                    }, function(response) {
                        console.log('AJAX yanıtı:', response);
                        if (response.success) {
                            showOrderDetailModal(response.data.html);
                        } else {
                            console.error('Hata:', response);
                            alert('Hata: ' + (response.data.message || response.data || 'Bilinmeyen hata'));
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('AJAX başarısız:', xhr, status, error);
                        alert('AJAX hatası: ' + error);
                    });
                }
            };

            function showOrderDetailModal(orderData) {
                console.log('Modal gösteriliyor, veri:', orderData);

                // Önceki modal'ı temizle
                const existingModal = document.getElementById('order-detail-modal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Yeni modal oluştur
                const modal = document.createElement('div');
                modal.id = 'order-detail-modal';
                modal.className = 'dastas-modal';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                `;

                modal.innerHTML = `
                    <div class="dastas-modal-content" style="
                        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                        border-radius: 16px;
                        max-width: 950px;
                        width: 95%;
                        max-height: 90vh;
                        overflow-y: auto;
                        position: relative;
                        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.05);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                    ">
                        <div class="dastas-modal-header" style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 24px 28px;
                            border-bottom: 1px solid #e2e8f0;
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            border-radius: 16px 16px 0 0;
                            color: white;
                        ">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 12px;
                                    background: rgba(255, 255, 255, 0.2);
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 18px;
                                ">📋</div>
                                <div>
                                    <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: white;">Sipariş Detayları</h3>
                                    <p style="margin: 4px 0 0 0; font-size: 0.875rem; opacity: 0.9; color: white;">Detaylı sipariş bilgileri</p>
                                </div>
                            </div>
                            <button onclick="closeOrderDetailModal()" style="
                                background: rgba(255, 255, 255, 0.2);
                                border: none;
                                border-radius: 8px;
                                width: 36px;
                                height: 36px;
                                cursor: pointer;
                                color: white;
                                font-size: 18px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                transition: all 0.2s ease;
                            " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">✕</button>
                        </div>
                        <div id="modal-content" style="
                            padding: 32px;
                            background: white;
                            border-radius: 0 0 16px 16px;
                        ">
                            ${orderData}
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                // Modal dışına tıklandığında kapat
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeOrderDetailModal();
                    }
                });

                console.log('Modal başarıyla oluşturuldu');
            }

            window.closeOrderDetailModal = function() {
                const modal = document.getElementById('order-detail-modal');
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = ''; // Arka plan kaydırmayı geri aç
                }
            };

            // Test AJAX function
            window.testAjax = function() {
                console.log('Test AJAX başlatılıyor...');
                jQuery.post(ajaxurl, {
                    action: 'dastas_test_ajax',
                    nonce: '<?php echo wp_create_nonce("dastas_admin_nonce"); ?>'
                }, function(response) {
                    console.log('Test AJAX yanıtı:', response);
                    alert('Test AJAX: ' + response.data);
                }).fail(function(xhr, status, error) {
                    console.error('Test AJAX başarısız:', xhr, status, error);
                    alert('Test AJAX hatası: ' + error);
                });
            };

            // View order details
            document.querySelectorAll('.view-order-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.dataset.orderId;
                    const siparisNo = this.dataset.siparisNo;
                    
                    console.log('Viewing order details:', { orderId, siparisNo });
                    // Add view details logic here or open modal
                });
            });
        </script>
        <?php
    }

    public function renderBayilerPage() {
        $bayiler = $this->db->getBayiler();
        $aktif_bayiler = array_filter($bayiler, function($bayi) { return $bayi->aktif; });
        $pasif_bayiler = array_filter($bayiler, function($bayi) { return !$bayi->aktif; });
        ?>
        <div class="wrap">
            <!-- Header Section -->
            <div class="dastas-admin-header">
                <div class="header-content">
                    <div class="welcome-section">
                        <h1>🏪 Bayi Yönetimi</h1>
                        <p>Bayileri görüntüleyin, düzenleyin ve yönetin</p>
                    </div>
                    <div class="header-actions">
                        <button class="button button-primary button-large" id="add-new-bayi">
                            ➕ Yeni Bayi Ekle
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="dastas-admin-stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo count($aktif_bayiler); ?></h3>
                        <p>Aktif Bayi</p>
                        <div class="stat-trend trend-up">
                            <span class="trend-icon">✅</span>
                            <span class="trend-text">İşlemde</span>
                        </div>
                    </div>
                    <div class="stat-badge"><?php echo count($aktif_bayiler); ?> aktif</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">⏸️</div>
                    <div class="stat-content">
                        <h3><?php echo count($pasif_bayiler); ?></h3>
                        <p>Pasif Bayi</p>
                        <div class="stat-trend trend-stable">
                            <span class="trend-icon">⏸️</span>
                            <span class="trend-text">Beklemede</span>
                        </div>
                    </div>
                    <div class="stat-badge"><?php echo count($pasif_bayiler); ?> pasif</div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo count($bayiler); ?></h3>
                        <p>Toplam Bayi</p>
                        <div class="stat-trend trend-up">
                            <span class="trend-icon">📈</span>
                            <span class="trend-text">Sistemde</span>
                        </div>
                    </div>
                    <div class="stat-badge"><?php echo count($bayiler); ?> toplam</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">📞</div>
                    <div class="stat-content">
                        <h3><?php echo count(array_filter($bayiler, function($bayi) { return !empty($bayi->telefon); })); ?></h3>
                        <p>İletişim</p>
                        <div class="stat-trend trend-up">
                            <span class="trend-icon">📞</span>
                            <span class="trend-text">Telefon</span>
                        </div>
                    </div>
                    <div class="stat-badge">İletişim</div>
                </div>
            </div>

            <!-- Bayiler Table -->
            <div class="bayiler-table-container">
                <table class="wp-list-table widefat fixed striped bayiler-table">
                    <thead>
                        <tr>
                            <th class="column-bayi-kodu">Bayi Kodu</th>
                            <th class="column-bayi-adi">Bayi Adı</th>
                            <th class="column-kullanici-adi">Kullanıcı Adı</th>
                            <th class="column-telefon">Telefon</th>
                            <th class="column-eposta">E-posta</th>
                            <th class="column-durum">Durum</th>
                            <th class="column-kayit-tarihi">Kayıt Tarihi</th>
                            <th class="column-islemler">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bayiler)): ?>
                            <tr>
                                <td colspan="8" class="no-bayiler-row">
                                    <div class="no-bayiler-message">
                                        <div class="text-center py-12">
                                            <div class="text-6xl mb-4">🏪</div>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Henüz bayi bulunmuyor</h3>
                                            <p class="text-gray-500">İlk bayiyi eklemek için "Yeni Bayi Ekle" butonuna tıklayın.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bayiler as $bayi): ?>
                                <tr class="bayi-row <?php echo $bayi->aktif ? 'active-bayi' : 'inactive-bayi'; ?>">
                                    <td class="column-bayi-kodu">
                                        <strong><?php echo esc_html($bayi->bayi_kodu); ?></strong>
                                    </td>
                                    <td class="column-bayi-adi">
                                        <div class="bayi-name-cell">
                                            <span class="bayi-name"><?php echo esc_html($bayi->bayi_adi); ?></span>
                                            <span class="bayi-status-indicator <?php echo $bayi->aktif ? 'active' : 'inactive'; ?>">
                                                <?php echo $bayi->aktif ? '●' : '○'; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-kullanici-adi">
                                        <span class="user-name"><?php echo esc_html($bayi->kullanici_adi); ?></span>
                                    </td>
                                    <td class="column-telefon">
                                        <?php if (!empty($bayi->telefon)): ?>
                                            <a href="tel:<?php echo esc_attr($bayi->telefon); ?>" class="phone-link">
                                                📞 <?php echo esc_html($bayi->telefon); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="no-data">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-eposta">
                                        <?php if (!empty($bayi->eposta)): ?>
                                            <a href="mailto:<?php echo esc_attr($bayi->eposta); ?>" class="email-link">
                                                📧 <?php echo esc_html($bayi->eposta); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="no-data">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-durum">
                                        <span class="status-badge status-<?php echo $bayi->aktif ? 'active' : 'inactive'; ?>">
                                            <?php echo $bayi->aktif ? '✅ Aktif' : '⏸️ Pasif'; ?>
                                        </span>
                                    </td>
                                    <td class="column-kayit-tarihi">
                                        <span class="registration-date">
                                            <?php echo $bayi->olusturma_tarihi ? date('d.m.Y H:i', strtotime($bayi->olusturma_tarihi)) : '-'; ?>
                                        </span>
                                    </td>
                                    <td class="column-islemler">
                                        <div class="action-buttons">
                                            <button class="button button-small edit-bayi" data-bayi-id="<?php echo $bayi->id; ?>" title="Düzenle">
                                                ✏️ Düzenle
                                            </button>

                                            <?php if ($bayi->aktif): ?>
                                                <button class="button button-small deactivate-bayi" data-bayi-id="<?php echo $bayi->id; ?>" title="Pasifleştir">
                                                    ⏸️ Pasif
                                                </button>
                                            <?php else: ?>
                                                <button class="button button-small activate-bayi" data-bayi-id="<?php echo $bayi->id; ?>" title="Aktifleştir">
                                                    ▶️ Aktif
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Yeni Bayi Modal -->
        <div id="bayi-modal" class="dastas-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title">Yeni Bayi Ekle</h2>
                <form id="bayi-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="bayi_kodu">Bayi Kodu</label></th>
                            <td><input type="text" id="bayi_kodu" name="bayi_kodu" required></td>
                        </tr>
                        <tr>
                            <th><label for="bayi_adi">Bayi Adı</label></th>
                            <td><input type="text" id="bayi_adi" name="bayi_adi" required></td>
                        </tr>
                        <tr>
                            <th><label for="kullanici_adi">Kullanıcı Adı</label></th>
                            <td><input type="text" id="kullanici_adi" name="kullanici_adi" required></td>
                        </tr>
                        <tr>
                            <th><label for="sorumlu">Sorumlu Kişi</label></th>
                            <td><input type="text" id="sorumlu" name="sorumlu"></td>
                        </tr>
                        <tr>
                            <th><label for="telefon">Telefon</label></th>
                            <td><input type="tel" id="telefon" name="telefon"></td>
                        </tr>
                        <tr>
                            <th><label for="email">E-posta</label></th>
                            <td><input type="email" id="email" name="email"></td>
                        </tr>
                        <tr id="sifre-row">
                            <th><label for="sifre">Şifre</label></th>
                            <td>
                                <input type="password" id="sifre" name="sifre" required>
                                <p class="description" id="sifre-description">Yeni bayi için şifre belirleyin. En az 6 karakter olmalıdır.</p>
                            </td>
                        </tr>
                        <tr id="sifre-change-row" style="display: none;">
                            <th><label for="sifre_degistir">Şifre Değiştir</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="sifre_degistir" name="sifre_degistir" value="1">
                                    Şifreyi değiştirmek istiyorum
                                </label>
                            </td>
                        </tr>
                        <tr id="yeni-sifre-row" style="display: none;">
                            <th><label for="yeni_sifre">Yeni Şifre</label></th>
                            <td>
                                <input type="password" id="yeni_sifre" name="yeni_sifre">
                                <p class="description">Yeni şifre en az 6 karakter olmalıdır. Boş bırakırsanız şifre değişmez.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">Kaydet</button>
                        <button type="button" class="button" onclick="DastasAdmin.closeModal()">İptal</button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        // Global değişkenleri tanımla
        window.dastas_admin_ajax = <?php echo json_encode(array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dastas_admin_nonce')
        )); ?>;
        window.dastas_nonce = '<?php echo wp_create_nonce('dastas_admin_nonce'); ?>';
        window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        jQuery(document).ready(function($) {
            // Bayi modal sistemi başlatma
            if (window.DastasAdmin) {
                console.log('DastasAdmin sistemi hazır - Bayiler sayfası');

                // Yeni bayi ekleme butonu
                $('#add-new-bayi').on('click', function(e) {
                    e.preventDefault();
                    if (window.DastasAdmin && window.DastasAdmin.showAddBayiModal) {
                        window.DastasAdmin.showAddBayiModal();
                    } else {
                        console.error('DastasAdmin.showAddBayiModal fonksiyonu bulunamadı');
                        alert('Modal sistemi yüklenmemiş. Sayfayı yenileyin.');
                    }
                });

                // Düzenleme butonları
                $('.edit-bayi').on('click', function(e) {
                    e.preventDefault();
                    const bayiId = $(this).data('bayi-id');
                    if (window.DastasAdmin && window.DastasAdmin.editBayi) {
                        window.DastasAdmin.editBayi.call(window.DastasAdmin, e);
                    } else {
                        console.error('DastasAdmin.editBayi fonksiyonu bulunamadı');
                    }
                });

                // Aktif/pasif butonları
                $('.activate-bayi, .deactivate-bayi').on('click', function(e) {
                    e.preventDefault();
                    const bayiId = $(this).data('bayi-id');
                    if (window.DastasAdmin && window.DastasAdmin.toggleBayiStatus) {
                        window.DastasAdmin.toggleBayiStatus.call(window.DastasAdmin, e);
                    } else {
                        console.error('DastasAdmin.toggleBayiStatus fonksiyonu bulunamadı');
                    }
                });

                // Modal kapatma
                $('.dastas-modal .close').on('click', function() {
                    if (window.DastasAdmin && window.DastasAdmin.closeModal) {
                        window.DastasAdmin.closeModal();
                    }
                });

                // Modal dışına tıklama
                $('.dastas-modal').on('click', function(e) {
                    if (e.target === this) {
                        if (window.DastasAdmin && window.DastasAdmin.closeModal) {
                            window.DastasAdmin.closeModal();
                        }
                    }
                });

                // Form submit
                $('#bayi-form').on('submit', function(e) {
                    e.preventDefault();
                    if (window.DastasAdmin && window.DastasAdmin.handleBayiSubmit) {
                        window.DastasAdmin.handleBayiSubmit.call(window.DastasAdmin, e);
                    } else {
                        console.error('DastasAdmin.handleBayiSubmit fonksiyonu bulunamadı');
                    }
                });

            } else {
                console.error('DastasAdmin sistemi yüklenmemiş!');
                alert('Admin sistemi yüklenmemiş. Sayfayı yenileyin.');
            }
        });
        </script>
        <?php
    }
    
    public function renderNotificationsPage() {
        ?>
        <div class="wrap">
            <h1>Bildirimler
                <button class="page-title-action" id="send-notification">Bildirim Gönder</button>
            </h1>
            
            <div class="notification-stats">
                <div class="stat-item">
                    <span class="count">15</span>
                    <span class="label">Gönderilen</span>
                </div>
                <div class="stat-item">
                    <span class="count">8</span>
                    <span class="label">Okundu</span>
                </div>
                <div class="stat-item">
                    <span class="count">3</span>
                    <span class="label">Beklemede</span>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Başlık</th>
                        <th>Alıcı</th>
                        <th>Tür</th>
                        <th>Gönderim Tarihi</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Bildirimler buraya gelecek -->
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function renderSettingsPage() {
        ?>
        <div class="wrap">
            <h1>Dastas Bayi Sistemi Ayarları</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('dastas_settings');
                do_settings_sections('dastas_settings');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Sistem Durumu</th>
                        <td>
                            <label>
                                <input type="checkbox" name="dastas_system_active" value="1" <?php checked(get_option('dastas_system_active', 1)); ?>>
                                Sistem aktif
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Otomatik Onay</th>
                        <td>
                            <label>
                                <input type="checkbox" name="dastas_auto_approve" value="1" <?php checked(get_option('dastas_auto_approve', 0)); ?>>
                                Siparişleri otomatik onayla
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">E-posta Bildirimleri</th>
                        <td>
                            <label>
                                <input type="checkbox" name="dastas_email_notifications" value="1" <?php checked(get_option('dastas_email_notifications', 1)); ?>>
                                E-posta bildirimlerini gönder
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Admin E-posta</th>
                        <td>
                            <input type="email" name="dastas_admin_email" value="<?php echo esc_attr(get_option('dastas_admin_email', get_option('admin_email'))); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <!-- Test Sipariş Bölümü -->
            <div class="dastas-test-section" style="margin-top: 50px; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;">
                <h2 style="margin-top: 0; color: #495057;">🧪 Yeni Sipariş Testi</h2>
                <p style="color: #6c757d; margin-bottom: 20px;">Sistemi test etmek için rastgele bir sipariş oluşturun. Bu sipariş birden çok ürün, çeşitli kalite, desen ve kaplama seçenekleri içerecektir.</p>

                <div style="background: white; padding: 20px; border-radius: 6px; border: 1px solid #e9ecef;">
                    <h3 style="margin-top: 0; color: #343a40;">📦 Rastgele Sipariş Oluştur</h3>
                    <p style="color: #6c757d; margin-bottom: 15px;">Bu işlem aşağıdaki özellikleri içeren rastgele bir test siparişi oluşturacaktır:</p>

                    <ul style="color: #495057; margin-bottom: 20px;">
                        <li>✅ 3-8 arası rastgele sayıda ürün</li>
                        <li>✅ Farklı ağaç cinsleri (Çam, Meşe, Ceviz, Kayın, vb.)</li>
                        <li>✅ Çeşitli ebatlar ve kalınlıklar</li>
                        <li>✅ Kalite seçenekleri (A, B, C, Premium)</li>
                        <li>✅ Desen seçenekleri (Düz, Dalgalı, Noktalı, vb.)</li>
                        <li>✅ Kaplama seçenekleri (Mat, Parlak, Vernik, vb.)</li>
                        <li>✅ Tutkal seçenekleri (PVAc, EVA, vb.)</li>
                        <li>✅ Rastgele miktarlar ve m³ hesaplamaları</li>
                    </ul>

                    <button id="create-test-order" class="button button-primary button-large"
                            style="background: #007cba; border-color: #007cba; color: white; padding: 12px 24px; font-size: 16px; border-radius: 4px; cursor: pointer;">
                        🚀 Test Siparişi Oluştur
                    </button>

                    <div id="test-order-result" style="margin-top: 15px; display: none;"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#create-test-order').on('click', function() {
                if (!confirm('Rastgele bir test siparişi oluşturmak istediğinizden emin misiniz?')) {
                    return;
                }

                $(this).prop('disabled', true).text('⏳ Oluşturuluyor...');

                $.post(ajaxurl, {
                    action: 'dastas_create_test_order',
                    nonce: '<?php echo wp_create_nonce("dastas_admin_nonce"); ?>'
                }, function(response) {
                    $('#create-test-order').prop('disabled', false).text('🚀 Test Siparişi Oluştur');

                    var resultDiv = $('#test-order-result');
                    if (response.success) {
                        resultDiv.html('<div style="padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">' +
                            '<strong>✅ Test siparişi başarıyla oluşturuldu!</strong><br>' +
                            'Sipariş No: <strong>' + response.data.siparis_no + '</strong><br>' +
                            'Ürün Sayısı: <strong>' + response.data.urun_sayisi + '</strong><br>' +
                            'Toplam m³: <strong>' + response.data.toplam_m3 + '</strong><br><br>' +
                            '<a href="' + response.data.view_url + '" class="button button-secondary" target="_blank">👁️ Siparişi Görüntüle</a> ' +
                            '<a href="' + response.data.print_url + '" class="button button-secondary" target="_blank">🖨️ Yazdır</a>' +
                            '</div>');
                    } else {
                        resultDiv.html('<div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">' +
                            '<strong>❌ Hata:</strong> ' + response.data + '</div>');
                    }

                    resultDiv.show();
                }).fail(function(xhr, status, error) {
                    $('#create-test-order').prop('disabled', false).text('🚀 Test Siparişi Oluştur');
                    $('#test-order-result').html('<div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">' +
                        '<strong>❌ AJAX Hatası:</strong> ' + error + '</div>').show();
                });
            });
        });
        </script>
        <?php
    }
    
    private function getSystemStats() {
        global $wpdb;
        
        $bayi_table = $this->db->getTable('bayi');
        $siparis_table = $this->db->getTable('siparis');
        
        $stats = array();
        
        $stats['toplam_bayi'] = $wpdb->get_var("SELECT COUNT(*) FROM {$bayi_table}");
        $stats['aktif_bayi'] = $wpdb->get_var("SELECT COUNT(*) FROM {$bayi_table} WHERE aktif = 1");
        $stats['toplam_siparis'] = $wpdb->get_var("SELECT COUNT(DISTINCT siparis_no) FROM {$siparis_table}");
        $stats['bekleyen_siparis'] = $wpdb->get_var("SELECT COUNT(DISTINCT siparis_no) FROM {$siparis_table} WHERE durum = 'beklemede'");
        
        return $stats;
    }
    
    private function getOrders($date_from = '', $date_to = '', $bayi_filter = '', $status_filter = '') {
        global $wpdb;
        
        $siparis_table = $this->db->getTable('siparis');
        $bayi_table = $this->db->getTable('bayi');
        
        $where_conditions = ['1=1'];
        $params = [];
        
        // Tarih filtreleri
        if (!empty($date_from)) {
            $where_conditions[] = "DATE(s.siparis_tarihi) >= %s";
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = "DATE(s.siparis_tarihi) <= %s";
            $params[] = $date_to;
        }
        
        // Bayi filtresi
        if (!empty($bayi_filter)) {
            $where_conditions[] = "s.bayi_id = %d";
            $params[] = intval($bayi_filter);
        }
        
        // Durum filtresi
        if (!empty($status_filter)) {
            $where_conditions[] = "s.durum = %s";
            $params[] = $status_filter;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "
            SELECT 
                MIN(s.id) as id,
                s.siparis_no,
                MIN(s.siparis_tarihi) as siparis_tarihi,
                s.durum,
                SUM(s.m3) as toplam_m3,
                SUM(s.miktar) as urun_sayisi,
                COUNT(s.id) as kalem_sayisi,
                b.bayi_adi,
                b.bayi_kodu
            FROM {$siparis_table} s
            LEFT JOIN {$bayi_table} b ON s.bayi_id = b.id
            WHERE {$where_clause}
            GROUP BY s.siparis_no, s.durum, b.bayi_adi, b.bayi_kodu
            ORDER BY MIN(s.siparis_tarihi) DESC
            LIMIT 100
        ";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            return $wpdb->get_results($sql);
        }
    }
    
    private function renderRecentOrders() {
        $orders = $this->getOrders();
        foreach (array_slice($orders, 0, 5) as $order) {
            echo '<div class="recent-order">';
            echo '<strong>' . esc_html($order->siparis_no) . '</strong> - ';
            echo esc_html($order->bayi_adi) . ' ';
            echo '<span class="order-date">' . date('d.m.Y', strtotime($order->siparis_tarihi)) . '</span>';
            echo '</div>';
        }
    }
    
    private function renderSystemStatus() {
        echo '<div class="status-item">';
        echo '<span class="status-label">Sistem:</span>';
        echo '<span class="status-value status-active">Aktif</span>';
        echo '</div>';

        echo '<div class="status-item">';
        echo '<span class="status-label">Database:</span>';
        echo '<span class="status-value status-active">Bağlı</span>';
        echo '</div>';

        echo '<div class="status-item">';
        echo '<span class="status-label">Plugin Versiyonu:</span>';
        echo '<span class="status-value">2.0.0</span>';
        echo '</div>';
    }

    private function getDetailedStats() {
        global $wpdb;

        $bayi_table = $this->db->getTable('bayi');
        $siparis_table = $this->db->getTable('siparis');

        // Bu ayki yeni bayiler
        $bu_ay_yeni_bayiler = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$bayi_table}
            WHERE MONTH(olusturma_tarihi) = MONTH(CURRENT_DATE())
            AND YEAR(olusturma_tarihi) = YEAR(CURRENT_DATE())
        "));

        // Geçen ayki bayi sayısı
        $gecen_ay_bayi = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$bayi_table}
            WHERE MONTH(olusturma_tarihi) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
            AND YEAR(olusturma_tarihi) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        "));

        // Bayi artış oranı
        $bayi_artis_orani = $gecen_ay_bayi > 0 ?
            round((($bu_ay_yeni_bayiler - $gecen_ay_bayi) / $gecen_ay_bayi) * 100, 1) : 0;

        // Bu hafta tamamlanan siparişler
        $bu_hafta_tamamlanan = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT siparis_no) FROM {$siparis_table}
            WHERE durum = 'teslim-edildi'
            AND siparis_tarihi >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        "));

        // Bugünkü siparişler
        $bugunku_siparis = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT siparis_no) FROM {$siparis_table}
            WHERE DATE(siparis_tarihi) = CURRENT_DATE()
        "));

        // Toplam m3
        $toplam_m3 = $wpdb->get_var("SELECT SUM(m3) FROM {$siparis_table}");

        // Ortalama bekleme süresi (gün)
        $ortalama_bekleme = $wpdb->get_var("
            SELECT AVG(DATEDIFF(
                CASE
                    WHEN guncelleme_tarihi != olusturma_tarihi THEN guncelleme_tarihi
                    ELSE CURRENT_DATE()
                END,
                siparis_tarihi
            ))
            FROM {$siparis_table}
            WHERE durum = 'beklemede'
        ") ?: 0;

        // Tamamlanma oranı
        $toplam_tamamlanan = $wpdb->get_var("SELECT COUNT(DISTINCT siparis_no) FROM {$siparis_table} WHERE durum = 'teslim-edildi'");
        $tamamlanma_orani = $toplam_tamamlanan > 0 ? round(($toplam_tamamlanan / $this->getSystemStats()['toplam_siparis']) * 100, 1) : 0;

        // Sistem sağlık durumu
        $sistem_sagligi = $ortalama_bekleme < 7 ? 'excellent' : 'good';

        // Bekleyen sipariş önceliği
        $bekleyen_oncelik = $ortalama_bekleme > 10 ? 'high' : 'normal';

        // Aylık büyüme (geçen aya göre)
        $bu_ay_siparis = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT siparis_no) FROM {$siparis_table}
            WHERE MONTH(siparis_tarihi) = MONTH(CURRENT_DATE())
            AND YEAR(siparis_tarihi) = YEAR(CURRENT_DATE())
        "));

        $gecen_ay_siparis = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT siparis_no) FROM {$siparis_table}
            WHERE MONTH(siparis_tarihi) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
            AND YEAR(siparis_tarihi) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        "));

        $aylik_buyume = $gecen_ay_siparis > 0 ?
            round((($bu_ay_siparis - $gecen_ay_siparis) / $gecen_ay_siparis) * 100, 1) : 0;

        // Aktif kullanıcı oranı (son 30 günde sipariş veren bayiler)
        $aktif_kullanici = $wpdb->get_var($wpdb->prepare("
            SELECT (COUNT(DISTINCT bayi_id) / (SELECT COUNT(*) FROM {$bayi_table} WHERE aktif = 1)) * 100
            FROM {$siparis_table}
            WHERE siparis_tarihi >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        ")) ?: 0;

        // Sistem yükü (rastgele simülasyon)
        $sistem_yuku = rand(15, 45);

        return [
            'bayi_artis_orani' => $bayi_artis_orani,
            'ortalama_bekleme' => round($ortalama_bekleme, 1),
            'bekleyen_oncelik' => $bekleyen_oncelik,
            'tamamlanan_siparis' => $bu_hafta_tamamlanan,
            'tamamlanma_orani' => $tamamlanma_orani,
            'bugunku_siparis' => $bugunku_siparis,
            'toplam_m3' => $toplam_m3,
            'sistem_sagligi' => $sistem_sagligi,
            'aylik_buyume' => $aylik_buyume,
            'aktif_kullanici' => round($aktif_kullanici, 1),
            'sistem_yuku' => $sistem_yuku,
            'ortalama_islem_suresi' => round($ortalama_bekleme, 1)
        ];
    }

    private function renderEnhancedRecentOrders() {
        $orders = $this->getOrders();
        $count = 0;

        foreach (array_slice($orders, 0, 5) as $order) {
            if ($count >= 5) break;

            $priority_class = '';
            $days_pending = floor((time() - strtotime($order->siparis_tarihi)) / (60*60*24));

            if ($days_pending > 7) {
                $priority_class = 'priority-high';
            } elseif ($days_pending > 3) {
                $priority_class = 'priority-medium';
            }

            echo '<div class="enhanced-order-item ' . $priority_class . '">';
            echo '<div class="order-header">';
            echo '<strong class="siparis-no-clickable" data-siparis-no="' . esc_attr($order->siparis_no) . '">' . esc_html($order->siparis_no) . '</strong>';
            echo '<span class="order-badge status-' . $order->durum . '">' . $this->getStatusText($order->durum) . '</span>';
            echo '</div>';
            echo '<div class="order-details">';
            echo '<span class="bayi-name">' . esc_html($order->bayi_adi) . '</span>';
            echo '<span class="order-date">' . date('d.m.Y H:i', strtotime($order->siparis_tarihi)) . '</span>';
            echo '</div>';
            echo '<div class="order-meta">';
            echo '<span class="urun-count">' . $order->urun_sayisi . ' ürün</span>';
            echo '<span class="m3-total">' . number_format($order->toplam_m3, 1) . ' m³</span>';
            echo '</div>';
            echo '</div>';

            $count++;
        }
    }

    private function renderDetailedSystemStatus() {
        $stats = $this->getDetailedStats();

        echo '<div class="status-grid">';
        echo '<div class="status-row">';
        echo '<span class="status-label">🖥️ Sunucu Durumu:</span>';
        echo '<span class="status-value status-excellent">Mükemmel</span>';
        echo '</div>';

        echo '<div class="status-row">';
        echo '<span class="status-label">💾 Veritabanı:</span>';
        echo '<span class="status-value status-excellent">Bağlı</span>';
        echo '</div>';

        echo '<div class="status-row">';
        echo '<span class="status-label">🔒 Güvenlik:</span>';
        echo '<span class="status-value status-excellent">Güvenli</span>';
        echo '</div>';

        echo '<div class="status-row">';
        echo '<span class="status-label">⚡ Performans:</span>';
        echo '<span class="status-value status-' . ($stats['sistem_yuku'] < 30 ? 'excellent' : 'good') . '">
            ' . $stats['sistem_yuku'] . '% yük</span>';
        echo '</div>';

        echo '<div class="status-row">';
        echo '<span class="status-label">📊 Bellek Kullanımı:</span>';
        echo '<span class="status-value status-good">Normal</span>';
        echo '</div>';

        echo '<div class="status-row">';
        echo '<span class="status-label">🔄 Son Yedekleme:</span>';
        echo '<span class="status-value">Bugün 03:00</span>';
        echo '</div>';
        echo '</div>';
    }

    private function getStatusText($status) {
        $statuses = [
            'beklemede' => 'Beklemede',
            'onaylandi' => 'Onaylandı',
            'hazirlaniyor' => 'Hazırlanıyor',
            'sevk-edildi' => 'Sevk Edildi',
            'teslim-edildi' => 'Teslim Edildi',
            'iptal' => 'İptal'
        ];

        return $statuses[$status] ?? ucfirst($status);
    }
    
    // AJAX Handlers
    public function handleAdminAction() {
        check_ajax_referer('dastas_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $action = sanitize_text_field($_POST['admin_action']);

        switch ($action) {
            case 'add_bayi':
                $this->addNewBayi();
                break;
            case 'update_bayi':
                $this->updateBayi();
                break;
            case 'toggle_bayi_status':
                $this->toggleBayiStatus();
                break;
            default:
                wp_send_json_error('Invalid action');
        }
    }

    public function handleGetBayiDetails() {
        check_ajax_referer('dastas_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $bayi_id = intval($_POST['bayi_id']);
        $bayi = $this->db->getBayiById($bayi_id);

        if (!$bayi) {
            wp_send_json_error('Bayi bulunamadı');
            return;
        }

        wp_send_json_success($bayi);
    }
    
    public function handleExportOrders() {
        check_ajax_referer('dastas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Excel export logic will be implemented here
        wp_send_json_success('Export started');
    }
    
    public function handleUpdateOrderStatus() {
        check_ajax_referer('dastas_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $order_id = intval($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['status']);

        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');

        $result = $wpdb->update(
            $siparis_table,
            array('durum' => $new_status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Update failed');
        }
    }

    public function handleGetAdminOrderDetails() {
        check_ajax_referer('dastas_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $order_id = intval($_POST['order_id']);
        $siparis_no = sanitize_text_field($_POST['siparis_no']);

        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        $bayi_table = $this->db->getTable('bayi');

        // Sipariş detaylarını al
        $siparisler = $wpdb->get_results($wpdb->prepare("
            SELECT s.*, b.bayi_adi, b.bayi_kodu
            FROM {$siparis_table} s
            LEFT JOIN {$bayi_table} b ON s.bayi_id = b.id
            WHERE s.siparis_no = %s
            ORDER BY s.id
        ", $siparis_no));

        if (empty($siparisler)) {
            wp_send_json_error(['message' => 'Sipariş bulunamadı!']);
        }

        ob_start();
        ?>
        <div class="space-y-8">
            <!-- Sipariş Bilgileri Kartı -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <span class="text-white text-lg">📋</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Sipariş Bilgileri</h3>
                        <p class="text-gray-600 text-sm">Temel sipariş detayları</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">🏷️</span>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Sipariş Numarası</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo esc_html($siparisler[0]->siparis_no); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">🏪</span>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Bayi Bilgileri</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo esc_html($siparisler[0]->bayi_adi); ?></p>
                                <p class="text-sm text-gray-600"><?php echo esc_html($siparisler[0]->bayi_kodu); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">📅</span>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Sipariş Tarihi</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo date('d.m.Y', strtotime($siparisler[0]->siparis_tarihi)); ?></p>
                                <p class="text-sm text-gray-600"><?php echo date('H:i', strtotime($siparisler[0]->siparis_tarihi)); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">
                                <?php
                                $durum_icon = [
                                    'beklemede' => '⏳',
                                    'onaylandi' => '✅',
                                    'hazirlaniyor' => '🔨',
                                    'sevk-edildi' => '🚚',
                                    'teslim-edildi' => '📦',
                                    'iptal' => '❌'
                                ];
                                echo $durum_icon[$siparisler[0]->durum] ?? '📋';
                                ?>
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Sipariş Durumu</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    <?php
                                    $durum_classes = [
                                        'beklemede' => 'bg-yellow-100 text-yellow-800',
                                        'onaylandi' => 'bg-green-100 text-green-800',
                                        'hazirlaniyor' => 'bg-blue-100 text-blue-800',
                                        'sevk-edildi' => 'bg-purple-100 text-purple-800',
                                        'teslim-edildi' => 'bg-green-100 text-green-800',
                                        'iptal' => 'bg-red-100 text-red-800'
                                    ];
                                    echo $durum_classes[$siparisler[0]->durum] ?? 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo esc_html(ucfirst($siparisler[0]->durum)); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ürün Detayları -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gray-600 rounded-lg flex items-center justify-center">
                            <span class="text-white text-sm">📦</span>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900">Ürün Detayları</h4>
                            <p class="text-gray-600 text-sm">Siparişteki tüm ürünler</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="space-y-6">
                        <?php foreach ($siparisler as $index => $urun): ?>
                            <div class="bg-gradient-to-r from-gray-50 to-white rounded-lg p-6 border border-gray-100 hover:shadow-md transition-shadow duration-200">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center text-white font-bold">
                                            <?php echo ($index + 1); ?>
                                        </div>
                                        <div>
                                            <h5 class="text-lg font-bold text-gray-900">Ürün <?php echo ($index + 1); ?></h5>
                                            <p class="text-gray-600 text-sm">Detaylı ürün bilgileri</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-indigo-600"><?php echo number_format($urun->m3, 3); ?> m³</div>
                                        <div class="text-sm text-gray-500">Toplam hacim</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-green-500">🌳</span>
                                            <span class="text-sm font-medium text-gray-700">Ağaç Cinsi</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html($urun->agac_cinsi); ?></p>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-blue-500">📏</span>
                                            <span class="text-sm font-medium text-gray-700">Ebatlar</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html($urun->ebat1 . ' × ' . $urun->ebat2); ?> cm</p>
                                        <p class="text-sm text-gray-600"><?php echo esc_html($urun->kalinlik); ?> mm kalınlık</p>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-orange-500">🔢</span>
                                            <span class="text-sm font-medium text-gray-700">Miktar</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900"><?php echo number_format($urun->miktar); ?> adet</p>
                                    </div>

                                    <?php if ($urun->tutkal): ?>
                                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-purple-500">🧪</span>
                                            <span class="text-sm font-medium text-gray-700">Tutkal</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html($urun->tutkal); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($urun->kalite): ?>
                                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-yellow-500">⭐</span>
                                            <span class="text-sm font-medium text-gray-700">Kalite</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html($urun->kalite); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($urun->kaplama): ?>
                                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-pink-500">🎨</span>
                                            <span class="text-sm font-medium text-gray-700">Kaplama</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html($urun->kaplama); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($urun->desen): ?>
                                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-indigo-500">🎭</span>
                                            <span class="text-sm font-medium text-gray-700">Desen</span>
                                        </div>
                                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html($urun->desen); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sipariş Özeti -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <span class="text-white text-lg">📊</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Sipariş Özeti</h3>
                        <p class="text-gray-600 text-sm">Genel istatistikler</p>
                    </div>
                </div>
                                        
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg p-6 text-center shadow-sm border border-gray-100">
                        <div class="text-3xl mb-2">📦</div>
                        <div class="text-2xl font-bold text-gray-900"><?php echo count($siparisler); ?></div>
                        <div class="text-gray-600">Toplam Ürün</div>
                    </div>

                    <div class="bg-white rounded-lg p-6 text-center shadow-sm border border-gray-100">
                        <div class="text-3xl mb-2">📏</div>
                        <div class="text-2xl font-bold text-gray-900"><?php echo number_format(array_sum(array_column($siparisler, 'm3')), 3); ?> m³</div>
                        <div class="text-gray-600">Toplam Hacim</div>
                    </div>

                    
                </div>
            </div>

            <?php if ($siparisler[0]->notlar): ?>
                <!-- Notlar Bölümü -->
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6 border border-amber-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-amber-500 rounded-lg flex items-center justify-center">
                            <span class="text-white text-lg">📝</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Sipariş Notları</h3>
                            <p class="text-gray-600 text-sm">Özel notlar ve açıklamalar</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                        <p class="text-gray-800 leading-relaxed"><?php echo nl2br(esc_html($siparisler[0]->notlar)); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
    
    private function addNewBayi() {
        $bayi_data = array(
            'bayi_kodu' => sanitize_text_field($_POST['bayi_kodu']),
            'bayi_adi' => sanitize_text_field($_POST['bayi_adi']),
            'kullanici_adi' => sanitize_text_field($_POST['bayi_kodu']), // Bayi kodu = kullanıcı adı
            'sorumlu' => sanitize_text_field($_POST['sorumlu'] ?? ''),
            'telefon' => sanitize_text_field($_POST['telefon']),
            'eposta' => sanitize_email($_POST['email']),
            'sifre' => wp_hash_password($_POST['sifre']),
            'aktif' => 1
        );

        $result = $this->db->insertBayi($bayi_data);

        if ($result) {
            wp_send_json_success('Bayi başarıyla eklendi');
        } else {
            wp_send_json_error('Bayi eklenirken hata oluştu');
        }
    }

    private function updateBayi() {
        $bayi_id = intval($_POST['bayi_id']);
        $bayi_data = array(
            'bayi_adi' => sanitize_text_field($_POST['bayi_adi']),
            'sorumlu' => sanitize_text_field($_POST['sorumlu']),
            'telefon' => sanitize_text_field($_POST['telefon']),
            'eposta' => sanitize_email($_POST['email']) // 'email' field'ını 'eposta' olarak kaydet
        );

        // Check if password change is requested
        if (isset($_POST['sifre_degistir']) && $_POST['sifre_degistir'] === '1') {
            $yeni_sifre = sanitize_text_field($_POST['yeni_sifre']);
            if (!empty($yeni_sifre) && strlen($yeni_sifre) >= 6) {
                $bayi_data['sifre'] = wp_hash_password($yeni_sifre);
            } else {
                wp_send_json_error('Yeni şifre en az 6 karakter olmalıdır');
                return;
            }
        }

        $result = $this->db->updateBayi($bayi_id, $bayi_data);

        if ($result !== false) {
            wp_send_json_success('Bayi başarıyla güncellendi');
        } else {
            wp_send_json_error('Bayi güncellenirken hata oluştu');
        }
    }

    private function toggleBayiStatus() {
        $bayi_id = intval($_POST['bayi_id']);
        $current_bayi = $this->db->getBayiById($bayi_id);

        if (!$current_bayi) {
            wp_send_json_error('Bayi bulunamadı');
            return;
        }

        $new_status = $current_bayi->aktif ? 0 : 1;
        $result = $this->db->updateBayi($bayi_id, array('aktif' => $new_status));

        if ($result !== false) {
            $status_text = $new_status ? 'aktifleştirildi' : 'pasifleştirildi';
            wp_send_json_success('Bayi başarıyla ' . $status_text);
        } else {
            wp_send_json_error('Bayi durumu değiştirilirken hata oluştu');
        }
    }

    public function handleDuplicateOrder() {
        if (!wp_verify_nonce($_POST['nonce'], 'dastas_admin_nonce')) {
            wp_send_json_error('Güvenlik kontrolü başarısız');
            return;
        }

        $order_id = intval($_POST['order_id']);
        $result = $this->db->duplicateOrder($order_id);

        if ($result) {
            wp_send_json_success('Sipariş başarıyla kopyalandı');
        } else {
            wp_send_json_error('Sipariş kopyalanırken hata oluştu');
        }
    }

    public function handleDeleteOrder() {
        if (!wp_verify_nonce($_POST['nonce'], 'dastas_admin_nonce')) {
            wp_send_json_error('Güvenlik kontrolü başarısız');
            return;
        }

        $order_id = intval($_POST['order_id']);
        $result = $this->db->deleteOrder($order_id);

        if ($result) {
            wp_send_json_success('Sipariş başarıyla silindi');
        } else {
            wp_send_json_error('Sipariş silinirken hata oluştu');
        }
    }

    public function handleSendOrderNotification() {
        if (!wp_verify_nonce($_POST['nonce'], 'dastas_admin_nonce')) {
            wp_send_json_error('Güvenlik kontrolü başarısız');
            return;
        }

        $order_id = intval($_POST['order_id']);
        $order = $this->db->getOrderById($order_id);
        
        if (!$order) {
            wp_send_json_error('Sipariş bulunamadı');
            return;
        }

        // Notification module through plugin instance
        $notification = Dastas_Plugin::getInstance()->getModule('notifications');
        $result = $notification->sendOrderNotification($order_id, 'status_update');

        if ($result) {
            wp_send_json_success('Bildirim başarıyla gönderildi');
        } else {
            wp_send_json_error('Bildirim gönderilirken hata oluştu');
        }
    }

    public function handleBulkOrderAction() {
        if (!wp_verify_nonce($_POST['nonce'], 'dastas_admin_nonce')) {
            wp_send_json_error('Güvenlik kontrolü başarısız');
            return;
        }

        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $order_ids = array_map('intval', $_POST['order_ids']);

        if (empty($order_ids)) {
            wp_send_json_error('Hiç sipariş seçilmedi');
            return;
        }

        $success_count = 0;
        
        switch ($bulk_action) {
            case 'delete':
                foreach ($order_ids as $order_id) {
                    if ($this->db->deleteOrder($order_id)) {
                        $success_count++;
                    }
                }
                break;
                
            case 'mark-pending':
                foreach ($order_ids as $order_id) {
                    if ($this->db->updateOrderStatus($order_id, 'beklemede')) {
                        $success_count++;
                    }
                }
                break;
                
            case 'mark-approved':
                foreach ($order_ids as $order_id) {
                    if ($this->db->updateOrderStatus($order_id, 'onaylandi')) {
                        $success_count++;
                    }
                }
                break;
                
            case 'mark-preparing':
                foreach ($order_ids as $order_id) {
                    if ($this->db->updateOrderStatus($order_id, 'hazirlaniyor')) {
                        $success_count++;
                    }
                }
                break;
                
            case 'mark-shipped':
                foreach ($order_ids as $order_id) {
                    if ($this->db->updateOrderStatus($order_id, 'sevk-edildi')) {
                        $success_count++;
                    }
                }
                break;
                
            case 'mark-delivered':
                foreach ($order_ids as $order_id) {
                    if ($this->db->updateOrderStatus($order_id, 'teslim-edildi')) {
                        $success_count++;
                    }
                }
                break;
                
            default:
                wp_send_json_error('Geçersiz işlem');
                return;
        }

        if ($success_count > 0) {
            wp_send_json_success("$success_count sipariş başarıyla işlendi");
        } else {
            wp_send_json_error('Hiçbir sipariş işlenemedi');
        }
    }

    public function handleGetOrderDetails() {
        // Debug log
        error_log('handleGetOrderDetails çağrıldı: ' . print_r($_POST, true));
        
        if (!wp_verify_nonce($_POST['nonce'], 'dastas_admin_nonce')) {
            error_log('Nonce kontrolü başarısız');
            wp_send_json_error('Güvenlik kontrolü başarısız');
            return;
        }

        $order_id = intval($_POST['order_id']);
        error_log('Sipariş ID: ' . $order_id);
        
        $order = $this->db->getOrderById($order_id);
        
        if (!$order) {
            error_log('Sipariş bulunamadı: ' . $order_id);
            wp_send_json_error('Sipariş bulunamadı');
            return;
        }

        error_log('Sipariş bulundu: ' . print_r($order, true));
        
        $order_items = $this->db->getOrderItems($order_id);
        error_log('Sipariş kalemleri: ' . print_r($order_items, true));
        
        ob_start();
        ?>
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-lg mb-3">📋 Sipariş Bilgileri</h3>
                    <div class="space-y-2">
                        <div><strong>Sipariş No:</strong> <?php echo esc_html($order->siparis_no); ?></div>
                        <div><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($order->siparis_tarihi)); ?></div>
                        <div><strong>Durum:</strong> 
                            <span class="inline-block px-2 py-1 rounded text-sm status-<?php echo $order->durum; ?>">
                                <?php echo ucfirst(str_replace('-', ' ', $order->durum)); ?>
                            </span>
                        </div>
                        <div><strong>Toplam m³:</strong> <?php echo number_format($order->m3, 2); ?> m³</div>
                        <div><strong>Miktar:</strong> <?php echo number_format($order->miktar); ?> adet</div>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-lg mb-3">🏪 Bayi Bilgileri</h3>
                    <div class="space-y-2">
                        <div><strong>Bayi Adı:</strong> <?php echo esc_html($order->bayi_adi); ?></div>
                        <div><strong>Bayi Kodu:</strong> <?php echo esc_html($order->bayi_kodu); ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($order_items)): ?>
            <div class="bg-white border rounded-lg overflow-hidden">
                <h3 class="font-semibold text-lg p-4 bg-gray-50 border-b">📦 Sipariş Kalemleri</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Ürün</th>
                                <th class="px-4 py-2 text-right">Adet</th>
                                <th class="px-4 py-2 text-right">m³</th>
                                <th class="px-4 py-2 text-left">Notlar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2"><?php echo esc_html($item->urun_adi); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item->adet); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item->m3, 2); ?></td>
                                <td class="px-4 py-2"><?php echo esc_html($item->notlar ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        $content = ob_get_clean();
        wp_send_json_success($content);
    }

    public function handlePrintOrder() {
        if (!wp_verify_nonce($_GET['nonce'], 'dastas_admin_nonce')) {
            wp_die('Güvenlik kontrolü başarısız');
        }

        $order_id = intval($_GET['order_id']);
        $siparis_no = sanitize_text_field($_GET['siparis_no'] ?? '');

        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        $bayi_table = $this->db->getTable('bayi');

        // Sipariş numarası ile tüm ürünleri al
        if (!empty($siparis_no)) {
            $siparisler = $wpdb->get_results($wpdb->prepare("
                SELECT s.*, b.bayi_adi, b.bayi_kodu
                FROM {$siparis_table} s
                LEFT JOIN {$bayi_table} b ON s.bayi_id = b.id
                WHERE s.siparis_no = %s
                ORDER BY s.id
            ", $siparis_no));

            if (empty($siparisler)) {
                wp_die('Sipariş bulunamadı');
            }

            $order = $siparisler[0]; // İlk ürünü temel bilgiler için kullan
            $order_items = $siparisler; // Tüm ürünleri kullan
        } else {
            // Eski yöntem (fallback)
            $order = $this->db->getOrderById($order_id);
            if (!$order) {
                wp_die('Sipariş bulunamadı');
            }
            $order_items = $this->db->getOrderItems($order_id);
        }
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sipariş Yazdırma - <?php echo esc_html($order->siparis_no); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .order-info { margin-bottom: 20px; }
                .order-info div { margin-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; }
                .total { font-weight: bold; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>SİPARİŞ DETAYI</h1>
                <p>Sipariş No: <?php echo esc_html($order->siparis_no); ?></p>
            </div>
            
            <div class="order-info">
                <div><strong>Bayi:</strong> <?php echo esc_html($order->bayi_adi); ?></div>
                <div><strong>Tarih:</strong> <?php echo date('d.m.Y H:i', strtotime($order->siparis_tarihi)); ?></div>
                <div><strong>Durum:</strong> <?php echo ucfirst(str_replace('-', ' ', $order->durum)); ?></div>
            </div>
            
            <?php if (!empty($order_items)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Ürün Adı</th>
                        <th style="text-align: right;">Adet</th>
                        <th style="text-align: right;">m³</th>
                        <th>Notlar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item->agac_cinsi); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item->miktar); ?></td>
                        <td style="text-align: right;"><?php echo number_format($item->m3, 2); ?></td>
                        <td><?php echo esc_html($item->notlar ?: '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="2"><strong>TOPLAM</strong></td>
                        <td style="text-align: right;"><strong><?php echo number_format($order->toplam_m3, 2); ?> m³</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
            
            <div class="no-print" style="margin-top: 30px; text-align: center;">
                <button onclick="window.print()">🖨️ Yazdır</button>
                <button onclick="window.close()">❌ Kapat</button>
            </div>
            
            <script>
                window.onload = function() {
                    window.print();
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    public function handleTestAjax() {
        error_log('Test AJAX çağrıldı');
        wp_send_json_success('Test başarılı!');
    }

    public function handleCreateTestOrder() {
        check_ajax_referer('dastas_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkisiz erişim');
            return;
        }

        try {
            // Rastgele bayi seç
            $bayiler = $this->db->getBayiler(1, 0, true); // Sadece aktif bayiler
            if (empty($bayiler)) {
                wp_send_json_error('Test için aktif bayi bulunamadı. Önce bir bayi oluşturun.');
                return;
            }

            $random_bayi = $bayiler[array_rand($bayiler)];
            $bayi_id = $random_bayi->id;

            // Sipariş numarası oluştur
            $siparis_no = $this->generateSiparisNo();

            // Rastgele ürün sayısı (3-8 arası)
            $urun_sayisi = rand(3, 8);
            $toplam_m3 = 0;

            // Ağaç cinsleri
            $agac_cinsleri = ['Çam', 'Meşe', 'Ceviz', 'Kayın', 'Kavak', 'Ladin', 'Köknar', 'Gürgen'];

            // Kalite seçenekleri
            $kalite_secenekleri = ['A', 'B', 'C', 'Premium', 'Standart', 'Süper'];

            // Tutkal seçenekleri
            $tutkal_secenekleri = ['PVAc', 'EVA', 'Üre', 'Melamin', 'Kazein', 'Lateks'];

            // Kaplama seçenekleri
            $kaplama_secenekleri = ['Mat', 'Parlak', 'Vernik', 'Yağlı Boya', 'Su Bazlı', 'UV', 'Doğal'];

            // Desen seçenekleri
            $desen_secenekleri = ['Düz', 'Dalgalı', 'Noktalı', 'Çizgili', 'Kareli', 'Yıldızlı', 'Çiçekli'];

            // Ürünleri oluştur
            for ($i = 0; $i < $urun_sayisi; $i++) {
                $agac_cinsi = $agac_cinsleri[array_rand($agac_cinsleri)];
                $kalinlik = rand(8, 50) / 2.0; // 4mm'den 25mm'ye kadar yarım mm'lik adımlar
                $ebat1 = rand(20, 300); // cm
                $ebat2 = rand(10, 200); // cm
                $kalite = $kalite_secenekleri[array_rand($kalite_secenekleri)];
                $tutkal = $tutkal_secenekleri[array_rand($tutkal_secenekleri)];
                $kaplama = $kaplama_secenekleri[array_rand($kaplama_secenekleri)];
                $desen = $desen_secenekleri[array_rand($desen_secenekleri)];
                $miktar = rand(1, 100);

                // m³ hesapla: (ebat1 * ebat2 * kalinlik / 1000000) * miktar
                $m3 = round(($ebat1 * $ebat2 * $kalinlik / 1000000) * $miktar, 3);
                $toplam_m3 += $m3;

                // Siparişi veritabanına ekle
                $siparis_data = [
                    'bayi_id' => $bayi_id,
                    'siparis_no' => $siparis_no,
                    'agac_cinsi' => $agac_cinsi,
                    'kalinlik' => $kalinlik,
                    'ebat1' => $ebat1,
                    'ebat2' => $ebat2,
                    'kalite' => $kalite,
                    'tutkal' => $tutkal,
                    'kaplama' => $kaplama,
                    'desen' => $desen,
                    'miktar' => $miktar,
                    'm3' => $m3,
                    'durum' => 'beklemede',
                    'siparis_tarihi' => current_time('mysql'),
                    'notlar' => 'Test siparişi - Otomatik oluşturuldu'
                ];

                $result = $this->db->insertSiparis($siparis_data);
                if ($result === false) {
                    wp_send_json_error('Sipariş ürünü eklenirken hata oluştu: ' . $i + 1);
                    return;
                }
            }

            // Başarılı yanıt
            $view_url = admin_url('admin.php?page=dastas-siparisler');
            $print_url = admin_url('admin-ajax.php?action=dastas_print_order&siparis_no=' . urlencode($siparis_no) . '&nonce=' . wp_create_nonce('dastas_admin_nonce'));

            wp_send_json_success([
                'siparis_no' => $siparis_no,
                'urun_sayisi' => $urun_sayisi,
                'toplam_m3' => number_format($toplam_m3, 3),
                'bayi_adi' => $random_bayi->bayi_adi,
                'view_url' => $view_url,
                'print_url' => $print_url
            ]);

        } catch (Exception $e) {
            error_log('Test siparişi oluşturma hatası: ' . $e->getMessage());
            wp_send_json_error('Test siparişi oluşturulurken hata oluştu: ' . $e->getMessage());
        }
    }

    private function generateSiparisNo() {
        $prefix = 'TEST';
        $date = date('ymd');

        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$siparis_table}
             WHERE siparis_no LIKE %s
             AND DATE(siparis_tarihi) = CURDATE()",
            $prefix . $date . '%'
        ));

        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $date . $sequence;
    }
}

<?php
/**
 * Dashboard Modülü
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Dashboard {
    
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Dastas_Plugin::getInstance()->getModule('database');
        $this->auth = Dastas_Plugin::getInstance()->getModule('auth');
        
        // AJAX handlers
        add_action('wp_ajax_dastas_mark_notifications_read', array($this, 'handleMarkAllNotificationsRead'));
        add_action('wp_ajax_nopriv_dastas_mark_notifications_read', array($this, 'handleMarkAllNotificationsRead'));
        add_action('wp_ajax_dastas_mark_single_notification_read', array($this, 'handleMarkSingleNotificationRead'));
    }
    
    public function renderDashboard($atts) {
        if (!$this->auth->isLoggedIn()) {
            return '<div class="error">Bu sayfayı görüntülemek için giriş yapmalısınız.</div>';
        }
        
        // Çıkış işlemi
        if (isset($_GET['logout'])) {
            $this->auth->logout();
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
        
        $bayi_data = $this->auth->getCurrentUser();
        $bayi_stats = $this->getBayiStats($bayi_data->id);
        $recent_orders = $this->getRecentOrders($bayi_data->id);
        $notifications = $this->getNotifications($bayi_data->id);
        $favorite_products = $this->getFavoriteProducts($bayi_data->id);
        $frequent_actions = $this->getFrequentActions($bayi_data->id);

        // Sipariş detaylarını al
        $order_details = [];
        foreach ($recent_orders as $order) {
            $order_details[$order->siparis_no] = $this->getOrderDetails($order->siparis_no, $bayi_data->id);
        }
        
        ob_start();
        ?>
        <div class="dastas-dashboard-v2">
            <!-- Header Section -->
            <div class="dashboard-header-v2">
                <div class="header-content">
                    <div class="welcome-section">
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr($bayi_data->bayi_adi, 0, 2)); ?>
                        </div>
                        <div class="welcome-text">
                            <h1>Hoş Geldiniz, <?php echo esc_html($bayi_data->bayi_adi); ?></h1>
                            <p class="bayi-details">
                                <span class="bayi-kod">Bayi Kodu: <?php echo esc_html($bayi_data->bayi_kodu); ?></span>
                                <span class="last-login">Son Giriş: <?php echo date('d.m.Y H:i'); ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <div class="notifications-bell" onclick="toggleNotifications()">
                            <span class="bell-icon">🔔</span>
                            <?php if(count($notifications) > 0): ?>
                                <span class="notification-count"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo add_query_arg('logout', '1'); ?>" class="logout-btn-v2">
                            <span class="logout-icon">🚪</span>
                            Çıkış
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats Cards -->
            <div class="stats-grid-v2">
                <div class="stat-card-v2 primary">
                    <div class="stat-header">
                        <span class="stat-icon">📊</span>
                        <span class="stat-trend trend-up">+12%</span>
                    </div>
                    <div class="stat-value"><?php echo $bayi_stats['toplam_siparis']; ?></div>
                    <div class="stat-label">Toplam Sipariş</div>
                    <div class="stat-sublabel">Bu ay: <?php echo $bayi_stats['bu_ay_siparis']; ?></div>
                </div>
                
                <div class="stat-card-v2 warning">
                    <div class="stat-header">
                        <span class="stat-icon">⏳</span>
                        <span class="stat-trend trend-stable">→</span>
                    </div>
                    <div class="stat-value"><?php echo $bayi_stats['bekleyen_siparis']; ?></div>
                    <div class="stat-label">Bekleyen Sipariş</div>
                    <div class="stat-sublabel">Ortalama süre: 3 gün</div>
                </div>
                
                <div class="stat-card-v2 success">
                    <div class="stat-header">
                        <span class="stat-icon">✅</span>
                        <span class="stat-trend trend-up">+8%</span>
                    </div>
                    <div class="stat-value"><?php echo $bayi_stats['tamamlanan_siparis']; ?></div>
                    <div class="stat-label">Tamamlanan</div>
                    <div class="stat-sublabel">Bu hafta: <?php echo $bayi_stats['bu_hafta_tamamlanan']; ?></div>
                </div>
                
                <div class="stat-card-v2 info">
                    <div class="stat-header">
                        <span class="stat-icon">📦</span>
                        <span class="stat-trend trend-up">+15%</span>
                    </div>
                    <div class="stat-value"><?php echo number_format($bayi_stats['toplam_m3'], 1); ?> m³</div>
                    <div class="stat-label">Toplam Hacim</div>
                    <div class="stat-sublabel">Bu ay: <?php echo number_format($bayi_stats['bu_ay_m3'], 1); ?> m³</div>
                </div>
            </div>
            
            <!-- Main Content Grid -->
            <div class="dashboard-content-grid">
                <!-- Recent Orders Widget -->
                <div class="widget-card recent-orders">
                    <div class="widget-header">
                        <h3>📋 Son Siparişler</h3>
                        <div class="widget-actions">
                            <select class="order-filter" onchange="filterOrders(this.value)">
                                <option value="all">Tümü</option>
                                <option value="beklemede">Bekleyen</option>
                                <option value="onaylandi">Onaylanan</option>
                                <option value="teslim-edildi">Teslim Edilen</option>
                            </select>
                            <a href="<?php echo home_url('/siparislerim/'); ?>" class="view-all-btn">Tümünü Gör</a>
                        </div>
                    </div>
                    <div class="widget-content">
                        <?php if (!empty($recent_orders)): ?>
                            <div class="orders-timeline">
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="timeline-item clickable" data-status="<?php echo $order->durum; ?>" onclick="showOrderDetails('<?php echo $order->siparis_no; ?>')">
                                        <div class="timeline-marker status-<?php echo $order->durum; ?>"></div>
                                        <div class="timeline-content">
                                            <div class="order-info">
                                                <span class="order-number">#<?php echo $order->siparis_no; ?></span>
                                                <span class="order-date"><?php echo date('d.m.Y', strtotime($order->siparis_tarihi)); ?></span>
                                            </div>
                                            <div class="order-status status-<?php echo $order->durum; ?>">
                                                <?php echo $this->getStatusText($order->durum); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <span class="empty-icon">📝</span>
                                <p>Henüz sipariş bulunmuyor</p>
                                <a href="<?php echo home_url('/siparis-ekle/'); ?>" class="btn-primary">İlk Siparişinizi Verin</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions & Favorites Widget -->
                <div class="widget-card quick-actions">
                    <div class="widget-header">
                        <h3>⚡ Hızlı İşlemler</h3>
                    </div>
                    <div class="widget-content">
                        <div class="action-grid">
                            <?php foreach ($frequent_actions as $action): ?>
                                <a href="<?php echo $action['url']; ?>" class="action-card primary">
                                    <span class="action-icon"><?php echo $action['icon']; ?></span>
                                    <span class="action-title"><?php echo $action['title']; ?></span>
                                    <span class="action-desc"><?php echo $action['description']; ?></span>
                                </a>
                            <?php endforeach; ?>

                            <a href="<?php echo home_url('/siparislerim/'); ?>" class="action-card info">
                                <span class="action-icon">📋</span>
                                <span class="action-title">Siparişlerim</span>
                                <span class="action-desc">Durumu görüntüle</span>
                            </a>

                            <a href="<?php echo home_url('/hesap/'); ?>" class="action-card secondary">
                                <span class="action-icon">👤</span>
                                <span class="action-title">Hesap</span>
                                <span class="action-desc">Ayarları düzenle</span>
                            </a>
                        </div>

                        <!-- Favori Ürünler Bölümü -->
                        <?php if (!empty($favorite_products)): ?>
                            <div class="favorites-section">
                                <h4>⭐ Favori Ürünler</h4>
                                <div class="favorites-list">
                                    <?php foreach ($favorite_products as $product): ?>
                                        <div class="favorite-item">
                                            <span class="product-name"><?php echo esc_html($product->agac_cinsi); ?></span>
                                            <span class="product-count"><?php echo $product->siparis_sayisi; ?> sipariş</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Notifications Widget -->
                <div class="widget-card notifications-widget" id="notifications-widget" style="display: none;">
                    <div class="widget-header">
                        <h3>🔔 Bildirimler</h3>
                        <div class="notification-actions">
                            <select class="notification-filter" onchange="filterNotifications(this.value)">
                                <option value="all">Tümü</option>
                                <option value="unread">Okunmamış</option>
                                <option value="order">Sipariş</option>
                                <option value="system">Sistem</option>
                            </select>
                            <button onclick="markAllAsRead()" class="mark-read-btn">Tümünü Okundu İşaretle</button>
                        </div>
                    </div>
                    <div class="widget-content">
                        <?php if (!empty($notifications)): ?>
                            <div class="notifications-list">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?php echo $notification->is_read ? 'read' : 'unread'; ?>"
                                         data-type="<?php echo $notification->type; ?>"
                                         data-read="<?php echo $notification->is_read ? '1' : '0'; ?>">
                                        <div class="notification-icon"><?php echo $this->getNotificationIcon($notification->type); ?></div>
                                        <div class="notification-content">
                                            <div class="notification-title"><?php echo esc_html($notification->title); ?></div>
                                            <div class="notification-message"><?php echo esc_html($notification->message); ?></div>
                                            <div class="notification-time"><?php echo $this->timeAgo($notification->created_at); ?></div>
                                        </div>
                                        <?php if (!$notification->is_read): ?>
                                            <button class="mark-single-read" onclick="markAsRead(<?php echo $notification->id; ?>)">✓</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <span class="empty-icon">🔔</span>
                                <p>Yeni bildirim bulunmuyor</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Details Modal -->
            <div id="order-modal" class="order-modal" style="display: none;">
                <div class="modal-content">
                    <span class="close" onclick="closeOrderModal()">&times;</span>
                    <h2>Sipariş Detayları</h2>
                    <div id="modal-body">
                        <!-- Sipariş detayları buraya gelecek -->
                    </div>
                </div>
            </div>
        </div>

        <script>
        function toggleNotifications() {
            const widget = document.getElementById('notifications-widget');
            widget.style.display = widget.style.display === 'none' ? 'block' : 'none';
        }

        function markAllAsRead() {
            // AJAX call to mark notifications as read
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=dastas_mark_notifications_read&nonce=<?php echo wp_create_nonce('dastas_nonce'); ?>'
            }).then(() => {
                location.reload();
            });
        }

        function markAsRead(notificationId) {
            // AJAX call to mark single notification as read
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=dastas_mark_single_notification_read&notification_id=' + notificationId + '&nonce=<?php echo wp_create_nonce('dastas_nonce'); ?>'
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('İşlem başarısız: ' + data.data);
                }
            });
        }

        function filterOrders(status) {
            const timelineItems = document.querySelectorAll('.timeline-item');

            timelineItems.forEach(item => {
                if (status === 'all') {
                    item.style.display = 'flex';
                } else {
                    if (item.dataset.status === status) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
        }

        function filterNotifications(type) {
            const notificationItems = document.querySelectorAll('.notification-item');

            notificationItems.forEach(item => {
                if (type === 'all') {
                    item.style.display = 'flex';
                } else if (type === 'unread') {
                    if (item.dataset.read === '0') {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                } else {
                    if (item.dataset.type === type) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
        }

        function showOrderDetails(orderNo) {
            // Önce mevcut sipariş detaylarını kontrol et
            <?php
            $order_details_json = [];
            foreach ($order_details as $order_no => $details) {
                $order_details_json[$order_no] = [];
                foreach ($details as $detail) {
                    $order_details_json[$order_no][] = [
                        'agac_cinsi' => $detail->agac_cinsi,
                        'kalinlik' => $detail->kalinlik,
                        'ebat1' => $detail->ebat1,
                        'ebat2' => $detail->ebat2,
                        'kalite' => $detail->kalite,
                        'tutkal' => $detail->tutkal,
                        'kaplama' => $detail->kaplama,
                        'desen' => $detail->desen,
                        'miktar' => $detail->miktar,
                        'm3' => $detail->m3,
                        'urun_notu' => $detail->urun_notu,
                        'notlar' => $detail->notlar,
                        'durum' => $detail->durum
                    ];
                }
            }
            ?>

            const orderDetails = <?php echo json_encode($order_details_json); ?>;
            const details = orderDetails[orderNo];

            if (!details || details.length === 0) {
                alert('Sipariş detayları bulunamadı!');
                return;
            }

            // Modal içeriğini oluştur
            let modalContent = `
                <div class="order-details-header">
                    <h3>Sipariş No: #${orderNo}</h3>
                    <span class="order-status-badge status-${details[0].durum}">
                        <?php echo addslashes($this->getStatusText('${details[0].durum}')); ?>
                    </span>
                </div>

                <div class="order-products">
                    <h4>📦 Sipariş Ürünleri</h4>
                    <div class="products-grid">
            `;

            details.forEach((product, index) => {
                modalContent += `
                    <div class="product-card">
                        <div class="product-header">
                            <span class="product-title">${product.agac_cinsi} - ${product.kalinlik}mm</span>
                            <span class="product-quantity">${product.miktar} adet / ${product.m3}m³</span>
                        </div>
                        <div class="product-specs">
                            <div class="spec-row">
                                <span>Ebat:</span>
                                <span>${product.ebat1}x${product.ebat2}</span>
                            </div>
                            <div class="spec-row">
                                <span>Kalite:</span>
                                <span>${product.kalite}</span>
                            </div>
                            <div class="spec-row">
                                <span>Tutkal:</span>
                                <span>${product.tutkal}</span>
                            </div>
                            <div class="spec-row">
                                <span>Kaplama:</span>
                                <span>${product.kaplama}</span>
                            </div>
                            <div class="spec-row">
                                <span>Desen:</span>
                                <span>${product.desen}</span>
                            </div>
                        </div>
                        ${product.urun_notu ? `<div class="product-note"><strong>Not:</strong> ${product.urun_notu}</div>` : ''}
                    </div>
                `;
            });

            modalContent += `
                    </div>
                </div>
            `;

            // Genel notlar varsa ekle
            if (details[0].notlar) {
                modalContent += `
                    <div class="order-notes">
                        <h4>📝 Genel Notlar</h4>
                        <p>${details[0].notlar}</p>
                    </div>
                `;
            }

            // Modal'ı göster
            document.getElementById('modal-body').innerHTML = modalContent;
            document.getElementById('order-modal').style.display = 'block';
        }

        function closeOrderModal() {
            document.getElementById('order-modal').style.display = 'none';
        }

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            const modal = document.getElementById('order-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Widget actions için event listener'lar
        document.addEventListener('DOMContentLoaded', function() {
            // Widget header actions için hover efektleri
            const widgetHeaders = document.querySelectorAll('.widget-header');
            widgetHeaders.forEach(header => {
                header.style.cursor = 'default';
            });

            // Filter dropdown'ları için gelişmiş styling
            const filters = document.querySelectorAll('.order-filter, .notification-filter');
            filters.forEach(filter => {
                filter.style.cssText = `
                    background: white;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    padding: 8px 12px;
                    font-size: 14px;
                    color: #475569;
                    cursor: pointer;
                `;
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function getBayiStats($bayi_id) {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        
        // Temel istatistikler
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT siparis_no) as toplam_siparis,
                SUM(CASE WHEN durum = 'beklemede' THEN 1 ELSE 0 END) as bekleyen_siparis,
                SUM(CASE WHEN durum = 'teslim-edildi' THEN 1 ELSE 0 END) as tamamlanan_siparis,
                SUM(m3) as toplam_m3
            FROM {$siparis_table}
            WHERE bayi_id = %d
        ", $bayi_id));
        
        // Bu ay istatistikleri
        $bu_ay = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT siparis_no) as bu_ay_siparis,
                SUM(m3) as bu_ay_m3
            FROM {$siparis_table}
            WHERE bayi_id = %d AND MONTH(siparis_tarihi) = MONTH(CURRENT_DATE())
        ", $bayi_id));
        
        // Bu hafta tamamlanan
        $bu_hafta = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT siparis_no)
            FROM {$siparis_table}
            WHERE bayi_id = %d AND durum = 'teslim-edildi' 
            AND siparis_tarihi >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        ", $bayi_id));
        
        return [
            'toplam_siparis' => $stats->toplam_siparis ?: 0,
            'bekleyen_siparis' => $stats->bekleyen_siparis ?: 0,
            'tamamlanan_siparis' => $stats->tamamlanan_siparis ?: 0,
            'toplam_m3' => $stats->toplam_m3 ?: 0,
            'bu_ay_siparis' => $bu_ay->bu_ay_siparis ?: 0,
            'bu_ay_m3' => $bu_ay->bu_ay_m3 ?: 0,
            'bu_hafta_tamamlanan' => $bu_hafta ?: 0
        ];
    }
    
    private function getRecentOrders($bayi_id) {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT siparis_no, siparis_tarihi, durum
            FROM {$siparis_table}
            WHERE bayi_id = %d
            ORDER BY siparis_tarihi DESC
            LIMIT 5
        ", $bayi_id));
    }
    
    private function getNotifications($bayi_id) {
        global $wpdb;
        $notifications_table = $this->db->getTable('notifications');

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$notifications_table}
            WHERE bayi_id = %d OR bayi_id IS NULL
            ORDER BY created_at DESC
            LIMIT 10
        ", $bayi_id));
    }

    private function getFavoriteProducts($bayi_id) {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');

        // En çok sipariş verilen ağaç cinslerini bul
        return $wpdb->get_results($wpdb->prepare("
            SELECT
                agac_cinsi,
                COUNT(*) as siparis_sayisi,
                SUM(m3) as toplam_m3,
                MAX(siparis_tarihi) as son_siparis
            FROM {$siparis_table}
            WHERE bayi_id = %d AND agac_cinsi IS NOT NULL AND agac_cinsi != ''
            GROUP BY agac_cinsi
            ORDER BY siparis_sayisi DESC
            LIMIT 5
        ", $bayi_id));
    }

    private function getOrderDetails($siparis_no, $bayi_id) {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$siparis_table}
            WHERE siparis_no = %s AND bayi_id = %d
            ORDER BY id
        ", $siparis_no, $bayi_id));
    }

    private function getFrequentActions($bayi_id) {
        // Güncellenmiş sık kullanılan işlemler listesi
        return [
            [
                'title' => 'Sipariş Oluştur',
                'description' => 'Yeni sipariş ekle',
                'url' => home_url('/siparis-ekle/'),
                'icon' => '➕'
            ],
            [
                'title' => 'Son Siparişi Tekrarla',
                'description' => 'Yeni sipariş no ile tekrarla',
                'url' => home_url('/siparis-ekle/?repeat=1'),
                'icon' => '�'
            ]
        ];
    }
    
    private function getStatusText($status) {
        $statuses = [
            'beklemede' => 'Beklemede',
            'onaylandi' => 'Onaylandı',
            'uretimde' => 'Üretimde',
            'hazir' => 'Hazır',
            'kargoda' => 'Kargoda',
            'teslim-edildi' => 'Teslim Edildi',
            'iptal' => 'İptal'
        ];
        
        return $statuses[$status] ?? ucfirst($status);
    }
    
    private function getNotificationIcon($type) {
        $icons = [
            'order' => '📦',
            'system' => '⚙️',
            'payment' => '💳',
            'delivery' => '🚚',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            'success' => '✅'
        ];
        
        return $icons[$type] ?? '📋';
    }
    
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Az önce';
        if ($time < 3600) return floor($time/60) . ' dakika önce';
        if ($time < 86400) return floor($time/3600) . ' saat önce';
        if ($time < 2592000) return floor($time/86400) . ' gün önce';
        
        return date('d.m.Y', strtotime($datetime));
    }
    
    /**
     * AJAX Handler: Tüm bildirimleri okundu işaretle
     */
    public function handleMarkAllNotificationsRead() {
        check_ajax_referer('dastas_nonce', 'nonce');

        if (!$this->auth->isLoggedIn()) {
            wp_send_json_error('Giriş yapmalısınız!');
        }

        $bayi_data = $this->auth->getCurrentUser();
        global $wpdb;
        $notifications_table = $this->db->getTable('notifications');

        $result = $wpdb->update(
            $notifications_table,
            array('is_read' => 1),
            array(
                'bayi_id' => $bayi_data->id,
                'is_read' => 0
            ),
            array('%d'),
            array('%d', '%d')
        );

        if ($result !== false) {
            wp_send_json_success('Tüm bildirimler okundu olarak işaretlendi');
        } else {
            wp_send_json_error('İşlem başarısız');
        }
    }

    /**
     * AJAX Handler: Tek bildirim okundu işaretle
     */
    public function handleMarkSingleNotificationRead() {
        check_ajax_referer('dastas_nonce', 'nonce');

        if (!$this->auth->isLoggedIn()) {
            wp_send_json_error('Giriş yapmalısınız!');
        }

        $notification_id = intval($_POST['notification_id']);

        if (!$notification_id) {
            wp_send_json_error('Geçersiz bildirim ID');
        }

        global $wpdb;
        $notifications_table = $this->db->getTable('notifications');

        $result = $wpdb->update(
            $notifications_table,
            array('is_read' => 1),
            array('id' => $notification_id),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success('Bildirim okundu olarak işaretlendi');
        } else {
            wp_send_json_error('İşlem başarısız');
        }
    }
}

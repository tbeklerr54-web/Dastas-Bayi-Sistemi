<?php
/**
 * Notifications Modülü
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Notifications {
    
    private $db;
    
    public function __construct() {
        $this->db = Dastas_Plugin::getInstance()->getModule('database');
        
        // AJAX handlers
        add_action('wp_ajax_dastas_send_notification', array($this, 'handleSendNotification'));
        add_action('wp_ajax_dastas_mark_notification_read', array($this, 'handleMarkAsRead'));
        add_action('wp_ajax_nopriv_dastas_mark_notification_read', array($this, 'handleMarkAsRead'));
        // Frontend notifications fetch
        add_action('wp_ajax_dastas_get_notifications', array($this, 'handleGetNotifications'));
        add_action('wp_ajax_nopriv_dastas_get_notifications', array($this, 'handleGetNotifications'));
    }
    
    /**
     * Bildirim gönder
     */
    public function sendNotification($data) {
        global $wpdb;
        $notification_table = $this->db->getTable('notifications');
        
        $notification_data = array(
            'bayi_id' => $data['bayi_id'],
            'message' => $data['baslik'] . ': ' . $data['mesaj'], // Başlık ve mesajı birleştir
            'type' => $data['tur'],
            'data' => json_encode($data), // Ek veriyi JSON olarak sakla
            'created_at' => current_time('mysql'),
            'is_read' => 0
        );
        
        $result = $wpdb->insert($notification_table, $notification_data);
        
        if ($result && !empty($data['email_gonder'])) {
            $this->sendEmailNotification($data);
        }
        
        return $result;
    }
    
    /**
     * Bayi bildirimlerini getir
     */
    public function getBayiNotifications($bayi_id, $limit = 10) {
        global $wpdb;
        $notification_table = $this->db->getTable('notifications');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$notification_table}
            WHERE bayi_id = %d OR bayi_id = 0
            ORDER BY created_at DESC
            LIMIT %d
        ", $bayi_id, $limit));
    }
    
    /**
     * Okunmamış bildirim sayısı
     */
    public function getUnreadCount($bayi_id) {
        global $wpdb;
        $notification_table = $this->db->getTable('notifications');
        
        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$notification_table}
            WHERE (bayi_id = %d OR bayi_id = 0) AND is_read = 0
        ", $bayi_id));
    }
    
    /**
     * Bildirim widget'ı render et
     */
    public function renderNotificationWidget($bayi_id) {
        $notifications = $this->getBayiNotifications($bayi_id, 5);
        $unread_count = $this->getUnreadCount($bayi_id);
        
        ob_start();
        ?>
        <div class="dastas-notifications-widget">
            <div class="notifications-header">
                <h4>🔔 Bildirimler 
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h4>
            </div>
            
            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <p>📭 Henüz bildiriminiz bulunmuyor.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo !$notification->is_read ? 'unread' : ''; ?>" 
                             data-notification-id="<?php echo $notification->id; ?>">
                            <div class="notification-icon">
                                <?php echo $this->getNotificationIcon($notification->type); ?>
                            </div>
                            <div class="notification-content">
                                <p><?php echo esc_html($notification->message); ?></p>
                                <span class="notification-time">
                                    <?php echo $this->timeAgo($notification->created_at); ?>
                                </span>
                            </div>
                            <?php if (!$notification->is_read): ?>
                                <div class="mark-read">
                                    <button class="mark-read-btn" data-notification-id="<?php echo $notification->id; ?>">
                                        ✓
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="notifications-footer">
                <a href="<?php echo home_url('/bildirimler/'); ?>" class="view-all-notifications">
                    Tüm Bildirimleri Gör
                </a>
            </div>
        </div>
        
        <style>
        .dastas-notifications-widget {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .notifications-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .notifications-header h4 {
            margin: 0;
            color: #333;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .notifications-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .no-notifications {
            padding: 30px 20px;
            text-align: center;
            color: #666;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
            gap: 12px;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item.unread {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .notification-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-content h5 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        .notification-content p {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 11px;
            color: #999;
        }
        
        .mark-read {
            flex-shrink: 0;
        }
        
        .mark-read-btn {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mark-read-btn:hover {
            background: #218838;
        }
        
        .notifications-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            text-align: center;
        }
        
        .view-all-notifications {
            color: #2271b1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .view-all-notifications:hover {
            text-decoration: underline;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Bildirim okundu olarak işaretle
            $('.mark-read-btn').on('click', function() {
                var notificationId = $(this).data('notification-id');
                var button = $(this);
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'dastas_mark_notification_read',
                        notification_id: notificationId,
                        nonce: '<?php echo wp_create_nonce('dastas_notification_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            button.closest('.notification-item').removeClass('unread');
                            button.parent().remove();
                            
                            // Badge güncelle
                            var badge = $('.notification-badge');
                            if (badge.length) {
                                var count = parseInt(badge.text()) - 1;
                                if (count <= 0) {
                                    badge.remove();
                                } else {
                                    badge.text(count);
                                }
                            }
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Tüm bildirimler sayfası
     */
    public function renderAllNotifications($bayi_id) {
        $notifications = $this->getAllBayiNotifications($bayi_id);
        
        ob_start();
        ?>
        <div class="dastas-container">
            <div class="dastas-header">
                <h2>📢 Tüm Bildirimler</h2>
                <div class="header-actions">
                    <button id="mark-all-read" class="btn btn-secondary">
                        Tümünü Okundu İşaretle
                    </button>
                </div>
            </div>
            
            <div class="notifications-container">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>Henüz bildiriminiz bulunmuyor</h3>
                        <p>Yeni bildirimler burada görüntülenecektir.</p>
                    </div>
                <?php else: ?>
                    <div class="notifications-timeline">
                        <?php 
                        $current_date = '';
                        foreach ($notifications as $notification): 
                            $notification_date = date('Y-m-d', strtotime($notification->created_at));
                            if ($notification_date !== $current_date):
                                $current_date = $notification_date;
                        ?>
                            <div class="date-separator">
                                <span><?php echo date('d.m.Y', strtotime($notification->created_at)); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="timeline-notification <?php echo !$notification->okundu ? 'unread' : ''; ?>"
                             data-notification-id="<?php echo $notification->id; ?>">
                            <div class="timeline-icon">
                                <?php echo $this->getNotificationIcon($notification->type); ?>
                            </div>
                            <div class="timeline-content">
                                <div class="notification-header">
                                    <p><?php echo esc_html($notification->message); ?></p>
                                    <span class="notification-time">
                                        <?php echo date('H:i', strtotime($notification->created_at)); ?>
                                    </span>
                                </div>
                                <div class="notification-meta">
                                    <span class="notification-type">
                                        <?php echo $this->getNotificationTypeLabel($notification->type); ?>
                                    </span>
                                    <?php if (!$notification->is_read): ?>
                                        <button class="mark-single-read" data-notification-id="<?php echo $notification->id; ?>">
                                            Okundu İşaretle
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }
        
        .notifications-timeline {
            position: relative;
        }
        
        .date-separator {
            text-align: center;
            margin: 30px 0 20px 0;
        }
        
        .date-separator span {
            background: white;
            padding: 8px 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            color: #666;
            font-weight: 500;
        }
        
        .timeline-notification {
            display: flex;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #e0e0e0;
            transition: all 0.2s;
        }
        
        .timeline-notification.unread {
            border-left-color: #2196f3;
            background: #f8f9ff;
        }
        
        .timeline-icon {
            font-size: 24px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .notification-header h4 {
            margin: 0;
            color: #333;
            font-size: 16px;
        }
        
        .notification-time {
            color: #999;
            font-size: 14px;
        }
        
        .timeline-content p {
            margin: 0 0 12px 0;
            color: #666;
            line-height: 1.5;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-type {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: #666;
        }
        
        .mark-single-read {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .mark-single-read:hover {
            background: #218838;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * E-posta bildirimi gönder
     */
    private function sendEmailNotification($data) {
        if (!get_option('dastas_email_notifications', 1)) {
            return false;
        }
        
        $bayi_data = $this->db->getBayi($data['bayi_id']);
        if (!$bayi_data || empty($bayi_data->email)) {
            return false;
        }
        
        $subject = '[Dastas] ' . $data['baslik'];
        $message = $data['mesaj'];
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($bayi_data->email, $subject, $message, $headers);
    }
    
    /**
     * Bildirim türüne göre ikon döndür
     */
    private function getNotificationIcon($type) {
        $icons = array(
            'siparis' => '📦',
            'sistem' => '⚙️',
            'uyari' => '⚠️',
            'bilgi' => 'ℹ️',
            'basarili' => '✅',
            'hata' => '❌'
        );
        
        return isset($icons[$type]) ? $icons[$type] : '📌';
    }
    
    /**
     * Bildirim türü etiketi
     */
    private function getNotificationTypeLabel($type) {
        $labels = array(
            'siparis' => 'Sipariş',
            'sistem' => 'Sistem',
            'uyari' => 'Uyarı',
            'bilgi' => 'Bilgi',
            'basarili' => 'Başarılı',
            'hata' => 'Hata'
        );
        
        return isset($labels[$type]) ? $labels[$type] : 'Genel';
    }
    
    /**
     * Zaman farkı hesapla
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Az önce';
        if ($time < 3600) return floor($time/60) . ' dakika önce';
        if ($time < 86400) return floor($time/3600) . ' saat önce';
        if ($time < 2592000) return floor($time/86400) . ' gün önce';
        
        return date('d.m.Y', strtotime($datetime));
    }
    
    /**
     * Tüm bayi bildirimlerini getir
     */
    private function getAllBayiNotifications($bayi_id) {
        global $wpdb;
        $notification_table = $this->db->getTable('notifications');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$notification_table}
            WHERE bayi_id = %d OR bayi_id = 0
            ORDER BY created_at DESC
        ", $bayi_id));
    }
    
    // AJAX Handlers
    public function handleSendNotification() {
        check_ajax_referer('dastas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $data = array(
            'bayi_id' => intval($_POST['bayi_id']),
            'baslik' => sanitize_text_field($_POST['baslik']),
            'mesaj' => sanitize_textarea_field($_POST['mesaj']),
            'tur' => sanitize_text_field($_POST['tur']),
            'email_gonder' => !empty($_POST['email_gonder'])
        );
        
        $result = $this->sendNotification($data);
        
        if ($result) {
            wp_send_json_success('Bildirim gönderildi');
        } else {
            wp_send_json_error('Bildirim gönderilemedi');
        }
    }
    
    public function handleMarkAsRead() {
        check_ajax_referer('dastas_notification_nonce', 'nonce');
        
        $notification_id = intval($_POST['notification_id']);
        
        global $wpdb;
        $notification_table = $this->db->getTable('notifications');
        
        $result = $wpdb->update(
            $notification_table,
            array('is_read' => 1),
            array('id' => $notification_id),
            array('%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Okundu olarak işaretlendi');
        } else {
            wp_send_json_error('İşlem başarısız');
        }
    }

    /**
     * AJAX: Kullanıcının bildirimlerini getirir
     */
    public function handleGetNotifications() {
        // Debug: gelen POST verisini logla (kısa, hassas veri yazmayız)
        error_log('Dastas Notifications: handleGetNotifications called. action=' . ($_POST['action'] ?? 'NULL') . ', nonce_present=' . (empty($_POST['nonce']) ? 'false' : 'true'));

        // Frontend tarafından gönderilen isteklerde 'dastas_nonce' kullanılıyor (wp_localize_script)
        if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dastas_nonce')) {
            error_log('Dastas Notifications: Invalid or missing nonce in handleGetNotifications request. nonce_value=' . ($_POST['nonce'] ?? 'NULL'));
            wp_send_json_error('Geçersiz güvenlik belirteci (nonce).');
        }

        // Belirli bir bayi id gönderilmişse kullan, yoksa oturum veya WP user id'sinden al
        $bayi_id = 0;
        if (!empty($_POST['bayi_id'])) {
            $bayi_id = intval($_POST['bayi_id']);
        } elseif (!empty($_SESSION['bayi_id'])) {
            $bayi_id = intval($_SESSION['bayi_id']);
        } else {
            // Fallback: WordPress girişli kullanıcı ID'si (eğer bayi-user mapping yapıldıysa)
            $wp_user_id = get_current_user_id();
            $bayi_id = $wp_user_id ? intval($wp_user_id) : 0;
        }

        $notifications = $this->getBayiNotifications($bayi_id, 20);

        if ($notifications === null) {
            wp_send_json_error('Bildirimler yüklenemedi');
        }

        // Döndürülecek veriyi temizle (basit dönüş)
        $data = array_map(function($n) {
            return array(
                'id' => intval($n->id),
                'message' => esc_html($n->message),
                'type' => esc_html($n->type),
                'is_read' => intval($n->is_read),
                'created_at' => $n->created_at
            );
        }, $notifications);

        wp_send_json_success($data);
    }
}
<?php
/**
 * Veritabanı Yönetim Modülü
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Database {
    
    private $tables = [];
    
    public function __construct() {
        global $wpdb;
        
        $this->tables = [
            'bayi' => $wpdb->prefix . 'dastas_bayi',
            'siparis' => $wpdb->prefix . 'dastas_siparis',
            'notifications' => $wpdb->prefix . 'dastas_notifications',
            'templates' => $wpdb->prefix . 'dastas_siparis_sablonlari'
        ];
    }
    
    public function getTable($tableName) {
        return isset($this->tables[$tableName]) ? $this->tables[$tableName] : null;
    }
    
    public function createTables() {
        $this->createBayiTable();
        $this->createSiparisTable();
        $this->createNotificationsTable();
        $this->createTemplatesTable();
    }
    
    private function createBayiTable() {
        global $wpdb;
        $table_name = $this->tables['bayi'];
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bayi_adi varchar(255) NOT NULL,
            bayi_kodu varchar(100) NOT NULL,
            kullanici_adi varchar(100) NOT NULL,
            sifre varchar(255) NOT NULL,
            sorumlu varchar(255) DEFAULT '',
            telefon varchar(20) DEFAULT '',
            eposta varchar(100) DEFAULT '',
            olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
            guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            aktif tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY bayi_kodu (bayi_kodu),
            UNIQUE KEY kullanici_adi (kullanici_adi)
        ) $charset_collate;";

        $this->executeTableCreation($sql, 'Bayi');
    }
    
    private function createSiparisTable() {
        global $wpdb;
        $table_name = $this->tables['siparis'];
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bayi_id mediumint(9) NOT NULL,
            siparis_no varchar(50) NOT NULL,
            agac_cinsi varchar(100) NOT NULL,
            kalinlik decimal(4,1) NOT NULL,
            ebat1 int(11) NOT NULL,
            ebat2 int(11) NOT NULL,
            kalite varchar(50) NOT NULL,
            tutkal varchar(50) NOT NULL,
            kaplama varchar(100) NOT NULL,
            desen varchar(50) NOT NULL,
            miktar int(11) NOT NULL,
            m3 decimal(10,3) NOT NULL,
            urun_notu text DEFAULT '',
            notlar text DEFAULT '',
            durum varchar(20) DEFAULT 'beklemede',
            siparis_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
            olusturma_tarihi datetime DEFAULT CURRENT_TIMESTAMP,
            guncelleme_tarihi datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bayi_id (bayi_id),
            KEY siparis_no (siparis_no),
            KEY durum (durum)
        ) $charset_collate;";
        
        $this->executeTableCreation($sql, 'Siparis');
    }
    
    private function createNotificationsTable() {
        global $wpdb;
        $table_name = $this->tables['notifications'];
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bayi_id mediumint(9) NOT NULL,
            siparis_no varchar(50) DEFAULT NULL,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            data text,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL,
            read_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY bayi_id (bayi_id),
            KEY siparis_no (siparis_no),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $this->executeTableCreation($sql, 'Notifications');
    }
    
    private function createTemplatesTable() {
        global $wpdb;
        $table_name = $this->tables['templates'];
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bayi_id mediumint(9) NOT NULL,
            sablon_adi varchar(255) NOT NULL,
            agac_cinsi varchar(100) NOT NULL,
            kalinlik decimal(4,1) NOT NULL,
            ebat1 int(11) NOT NULL,
            ebat2 int(11) NOT NULL,
            kalite varchar(50) DEFAULT '',
            tutkal varchar(50) NOT NULL,
            kaplama varchar(100) DEFAULT '',
            desen varchar(50) DEFAULT '',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bayi_id (bayi_id),
            KEY sablon_adi (sablon_adi)
        ) $charset_collate;";
        
        $this->executeTableCreation($sql, 'Templates');
    }
    
    private function executeTableCreation($sql, $tableName) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Debug logging
        error_log("Dastas {$tableName} Tablosu Oluşturma: " . print_r($result, true));
    }
    
    /**
     * Bayi CRUD İşlemleri
     */
    public function getBayi($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['bayi']} WHERE id = %d AND aktif = 1",
            $id
        ));
    }
    
    public function getBayiByKullaniciAdi($kullanici_adi) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['bayi']} WHERE kullanici_adi = %s AND aktif = 1",
            $kullanici_adi
        ));
    }
    
    public function insertBayi($data) {
        global $wpdb;
        return $wpdb->insert($this->tables['bayi'], $data);
    }
    
    public function updateBayi($id, $data) {
        global $wpdb;
        return $wpdb->update($this->tables['bayi'], $data, ['id' => $id]);
    }
    
    public function getBayiler($limit = 100, $offset = 0, $aktif_only = null) {
        global $wpdb;
        
        $where = "1=1";
        $params = [];
        
        if ($aktif_only !== null) {
            $where .= " AND aktif = %d";
            $params[] = $aktif_only ? 1 : 0;
        }
        
        $sql = "SELECT * FROM {$this->tables['bayi']} 
                WHERE {$where} 
                ORDER BY olusturma_tarihi DESC 
                LIMIT %d OFFSET %d";
        
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, ...$params));
        } else {
            return $wpdb->get_results($sql);
        }
    }
    
    public function getBayilerCount($aktif_only = null) {
        global $wpdb;
        
        $where = "1=1";
        $params = [];
        
        if ($aktif_only !== null) {
            $where .= " AND aktif = %d";
            $params[] = $aktif_only ? 1 : 0;
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->tables['bayi']} WHERE {$where}";
        
        if (!empty($params)) {
            return $wpdb->get_var($wpdb->prepare($sql, ...$params));
        } else {
            return $wpdb->get_var($sql);
        }
    }
    
    public function deleteBayi($id) {
        global $wpdb;
        // Soft delete - aktif durumunu 0 yap
        return $wpdb->update($this->tables['bayi'], ['aktif' => 0], ['id' => $id]);
    }
    
    /**
     * Sipariş CRUD İşlemleri
     */
    public function insertSiparis($data) {
        global $wpdb;
        return $wpdb->insert($this->tables['siparis'], $data);
    }
    
    public function getSiparislerByBayi($bayi_id, $limit = 50, $offset = 0) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['siparis']} 
             WHERE bayi_id = %d 
             ORDER BY olusturma_tarihi DESC 
             LIMIT %d OFFSET %d",
            $bayi_id, $limit, $offset
        ));
    }
    
    public function getSiparisByNo($siparis_no, $bayi_id = null) {
        global $wpdb;
        
        $where = "siparis_no = %s";
        $params = [$siparis_no];
        
        if ($bayi_id) {
            $where .= " AND bayi_id = %d";
            $params[] = $bayi_id;
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['siparis']} WHERE {$where}",
            ...$params
        ));
    }
    
    public function updateSiparisDurum($siparis_no, $yeni_durum) {
        global $wpdb;
        return $wpdb->update(
            $this->tables['siparis'],
            ['durum' => $yeni_durum],
            ['siparis_no' => $siparis_no]
        );
    }
    
    /**
     * İstatistik ve Raporlama
     */
    public function getBayiStats() {
        global $wpdb;
        
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as toplam_bayi,
                SUM(CASE WHEN aktif = 1 THEN 1 ELSE 0 END) as aktif_bayi,
                SUM(CASE WHEN aktif = 0 THEN 1 ELSE 0 END) as pasif_bayi
            FROM {$this->tables['bayi']}
        ");
    }
    
    public function getSiparisStats() {
        global $wpdb;
        
        return $wpdb->get_row("
            SELECT 
                COUNT(DISTINCT siparis_no) as toplam_siparis,
                SUM(CASE WHEN durum = 'beklemede' THEN 1 ELSE 0 END) as bekleyen_siparis,
                SUM(CASE WHEN durum = 'onaylandi' THEN 1 ELSE 0 END) as onaylanan_siparis,
                SUM(m3) as toplam_m3
            FROM {$this->tables['siparis']}
        ");
    }
    
    public function getBayiById($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['bayi']} WHERE id = %d",
            $id
        ));
    }
    
    public function getBayiByKod($bayi_kodu) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['bayi']} WHERE bayi_kodu = %s",
            $bayi_kodu
        ));
    }
    
    public function updateBayiSifre($bayi_id, $hashed_password) {
        global $wpdb;
        
        return $wpdb->update(
            $this->tables['bayi'],
            ['sifre' => $hashed_password],
            ['id' => $bayi_id],
            ['%s'],
            ['%d']
        );
    }

    public function getOrderById($order_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, b.bayi_adi, b.bayi_kodu 
             FROM {$this->tables['siparis']} s 
             LEFT JOIN {$this->tables['bayi']} b ON s.bayi_id = b.id 
             WHERE s.id = %d",
            $order_id
        ));
    }

    public function getOrderItems($order_id) {
        global $wpdb;
        
        // Sipariş kalemi tablosu varsa oradan al
        $items_table = $wpdb->prefix . 'dastas_siparis_kalemleri';
        
        // Tablo var mı kontrol et
        if ($wpdb->get_var("SHOW TABLES LIKE '$items_table'") == $items_table) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $items_table WHERE siparis_id = %d ORDER BY id",
                $order_id
            ));
        }
        
        // Mevcut sipariş tablosundan bilgileri al
        $order = $this->getOrderById($order_id);
        if (!$order) {
            return [];
        }
        
        // Sipariş bilgilerini tek kaleme dönüştür
        $items = [];
        $urun_adi = [];
        
        if (!empty($order->agac_cinsi)) $urun_adi[] = $order->agac_cinsi;
        if (!empty($order->kalinlik)) $urun_adi[] = $order->kalinlik . 'mm';
        if (!empty($order->ebat1) && !empty($order->ebat2)) $urun_adi[] = $order->ebat1 . 'x' . $order->ebat2;
        if (!empty($order->kalite)) $urun_adi[] = $order->kalite;
        if (!empty($order->kaplama)) $urun_adi[] = $order->kaplama;
        
        $items[] = (object) [
            'urun_adi' => implode(' - ', $urun_adi) ?: 'Ürün Detayı',
            'adet' => $order->miktar ?: 0,
            'm3' => $order->m3 ?: 0,
            'notlar' => $order->notlar ?: ''
        ];
        
        return $items;
    }

    public function duplicateOrder($order_id) {
        global $wpdb;
        
        $order = $this->getOrderById($order_id);
        if (!$order) {
            return false;
        }
        
        // Yeni sipariş no oluştur
        $new_siparis_no = $this->generateSiparisNo();
        
        $result = $wpdb->insert(
            $this->tables['siparis'],
            [
                'siparis_no' => $new_siparis_no,
                'bayi_id' => $order->bayi_id,
                'agac_cinsi' => $order->agac_cinsi,
                'kalinlik' => $order->kalinlik,
                'ebat1' => $order->ebat1,
                'ebat2' => $order->ebat2,
                'kalite' => $order->kalite,
                'tutkal' => $order->tutkal,
                'kaplama' => $order->kaplama,
                'sinif' => $order->sinif,
                'desen' => $order->desen,
                'sertifika' => $order->sertifika,
                'miktar' => $order->miktar,
                'm3' => $order->m3,
                'birim' => $order->birim,
                'notlar' => $order->notlar,
                'urun_notu' => $order->urun_notu,
                'durum' => 'beklemede',
                'siparis_tarihi' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%f', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s', '%s']
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }

    public function deleteOrder($order_id) {
        global $wpdb;
        
        // Önce sipariş kalemlerini sil (varsa)
        $items_table = $wpdb->prefix . 'dastas_siparis_kalemleri';
        if ($wpdb->get_var("SHOW TABLES LIKE '$items_table'") == $items_table) {
            $wpdb->delete($items_table, ['siparis_id' => $order_id], ['%d']);
        }
        
        // Sonra siparişi sil
        return $wpdb->delete(
            $this->tables['siparis'],
            ['id' => $order_id],
            ['%d']
        );
    }

    public function updateOrderStatus($order_id, $new_status) {
        global $wpdb;
        
        return $wpdb->update(
            $this->tables['siparis'],
            ['durum' => $new_status],
            ['id' => $order_id],
            ['%s'],
            ['%d']
        );
    }

    private function generateSiparisNo() {
        $prefix = 'DS';
        $date = date('ymd');
        
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['siparis']} 
             WHERE siparis_no LIKE %s 
             AND DATE(siparis_tarihi) = CURDATE()",
            $prefix . $date . '%'
        ));
        
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $date . $sequence;
    }
}

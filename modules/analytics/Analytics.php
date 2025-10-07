<?php
/**
 * Analiz Modülü
 * Bayi performansı, sipariş istatistikleri ve raporlama
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Analytics {
    
    private $db;
    
    public function __construct() {
        error_log('Dastas Analytics modülü yüklendi');
        
        // WordPress tamamen yüklenene kadar bekle
        add_action('wp_loaded', array($this, 'init'), 10);
    }
    
    public function init() {
        error_log('Analytics init çalışıyor');
        
        // Sadece admin panelinde çalış
        if (!is_admin()) {
            return;
        }
        
        $this->db = Dastas_Plugin::getInstance()->getModule('database');
        
        // Admin menüye analiz sayfası ekle
        add_action('admin_menu', array($this, 'addAnalyticsMenu'), 25);
        
        // AJAX handlers
        add_action('wp_ajax_dastas_get_analytics_data', array($this, 'handleGetAnalyticsData'));
        add_action('wp_ajax_dastas_export_analytics', array($this, 'handleExportAnalytics'));
    }
    
    public function addAnalyticsMenu() {
        error_log('Analytics menü ekleniyor');
        add_submenu_page(
            'dastas-bayi',
            'Analiz & Raporlar',
            'Analiz',
            'manage_options',
            'dastas-analytics',
            array($this, 'renderAnalyticsPage')
        );
    }
    
    public function renderAnalyticsPage() {
        // Temel istatistikleri al
        $bayiStats = $this->getBayiStats();
        $siparisStats = $this->getSiparisStats();
        $monthlyStats = $this->getMonthlyStats();
        $topBayiler = $this->getTopBayiler();
        $urunAnaliz = $this->getUrunAnalizi();
        
        ?>
        <div class="wrap">
            <h1>📊 Analiz & Raporlar</h1>
            <p>Modüler analiz sistemi - Gerçek zamanlı veriler</p>
            
            <!-- Özet Kartlar -->
            <div class="dastas-stats-grid">
                <div class="stats-card stats-card-primary">
                    <div class="stats-icon">👥</div>
                    <h3>Toplam Bayiler</h3>
                    <div class="stats-number"><?php echo $bayiStats->toplam_bayi; ?></div>
                    <div class="stats-detail">
                        <span class="active">✅ Aktif: <?php echo $bayiStats->aktif_bayi; ?></span>
                        <span class="inactive">❌ Pasif: <?php echo $bayiStats->pasif_bayi; ?></span>
                    </div>
                </div>
                
                <div class="stats-card stats-card-success">
                    <div class="stats-icon">📦</div>
                    <h3>Toplam Siparişler</h3>
                    <div class="stats-number"><?php echo $siparisStats->toplam_siparis; ?></div>
                    <div class="stats-detail">
                        <span class="pending">⏳ Bekleyen: <?php echo $siparisStats->bekleyen_siparis; ?></span>
                        <span class="approved">✅ Onaylanan: <?php echo $siparisStats->onaylanan_siparis; ?></span>
                    </div>
                </div>
                
                <div class="stats-card stats-card-info">
                    <div class="stats-icon">📏</div>
                    <h3>Toplam Hacim</h3>
                    <div class="stats-number"><?php echo number_format($siparisStats->toplam_m3, 2); ?> <small>m³</small></div>
                    <div class="stats-detail">Son 30 gün sipariş hacmi</div>
                </div>
                
                <div class="stats-card stats-card-warning">
                    <div class="stats-icon">📈</div>
                    <h3>Ortalama Sipariş</h3>
                    <div class="stats-number">
                        <?php echo $siparisStats->toplam_siparis > 0 ? number_format($siparisStats->toplam_m3 / $siparisStats->toplam_siparis, 2) : 0; ?> <small>m³</small>
                    </div>
                    <div class="stats-detail">Sipariş başına ortalama hacim</div>
                </div>
            </div>
            
            <!-- Grafik Alanları -->
            <div class="dastas-analytics-row">
                <div class="analytics-col-8">
                    <div class="analytics-widget">
                        <div class="widget-header">
                            <h3>📈 Aylık Sipariş Trendi</h3>
                            <div class="widget-actions">
                                <button class="btn-refresh" onclick="refreshCharts()">🔄</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlyChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="analytics-col-4">
                    <div class="analytics-widget">
                        <div class="widget-header">
                            <h3>🥧 Sipariş Durumları</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart" width="200" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detaylı Tablolar -->
            <div class="dastas-analytics-row">
                <div class="analytics-col-6">
                    <div class="analytics-widget">
                        <div class="widget-header">
                            <h3>🏆 En Aktif Bayiler (Son 30 Gün)</h3>
                            <div class="widget-badge"><?php echo count($topBayiler); ?> bayi</div>
                        </div>
                        <div class="table-responsive">
                            <table class="wp-list-table widefat fixed striped modern-table">
                                <thead>
                                    <tr>
                                        <th>Bayi</th>
                                        <th>Sipariş</th>
                                        <th>Hacim (m³)</th>
                                        <th>Ortalama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topBayiler as $index => $bayi): ?>
                                    <tr>
                                        <td>
                                            <div class="bayi-info">
                                                <div class="rank-badge rank-<?php echo min($index + 1, 3); ?>">#<?php echo $index + 1; ?></div>
                                                <div>
                                                    <strong><?php echo esc_html($bayi->bayi_adi); ?></strong><br>
                                                    <small class="bayi-code"><?php echo esc_html($bayi->bayi_kodu); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="metric-badge"><?php echo $bayi->siparis_sayisi; ?></span></td>
                                        <td><span class="volume-text"><?php echo number_format($bayi->toplam_m3, 2); ?></span></td>
                                        <td><span class="avg-text"><?php echo number_format($bayi->ortalama_m3, 2); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="analytics-col-6">
                    <div class="analytics-widget">
                        <div class="widget-header">
                            <h3>🌳 Ürün Analizi</h3>
                            <div class="widget-badge"><?php echo count($urunAnaliz); ?> çeşit</div>
                        </div>
                        <div class="table-responsive">
                            <table class="wp-list-table widefat fixed striped modern-table">
                                <thead>
                                    <tr>
                                        <th>Ağaç Cinsi</th>
                                        <th>Sipariş</th>
                                        <th>Hacim</th>
                                        <th>Oran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($urunAnaliz as $urun): ?>
                                    <tr>
                                        <td><span class="product-name"><?php echo esc_html($urun->agac_cinsi); ?></span></td>
                                        <td><span class="metric-badge"><?php echo $urun->siparis_sayisi; ?></span></td>
                                        <td><span class="volume-text"><?php echo number_format($urun->toplam_m3, 2); ?> m³</span></td>
                                        <td>
                                            <div class="percentage-bar">
                                                <div class="percentage-fill" style="width: <?php echo $urun->oran; ?>%"></div>
                                                <span class="percentage-text"><?php echo number_format($urun->oran, 1); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtreler ve Aksiyonlar -->
            <div class="analytics-actions">
                <div class="analytics-filters">
                    <div class="filter-group">
                        <label>📅 Zaman Aralığı:</label>
                        <select id="time-filter" class="modern-select">
                            <option value="7">Son 7 Gün</option>
                            <option value="30" selected>Son 30 Gün</option>
                            <option value="90">Son 3 Ay</option>
                            <option value="365">Son 1 Yıl</option>
                            <option value="all">Tüm Zamanlar</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>🏪 Bayi Filtresi:</label>
                        <select id="bayi-filter" class="modern-select">
                            <option value="">Tüm Bayiler</option>
                            <?php 
                            $allBayiler = $this->db->getBayiler(1000, 0, 1);
                            foreach ($allBayiler as $bayi): 
                            ?>
                            <option value="<?php echo $bayi->id; ?>"><?php echo esc_html($bayi->bayi_adi); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button id="filter-analytics" class="button button-primary modern-btn">
                        🔍 Filtrele
                    </button>
                </div>
                
                <div class="analytics-export">
                    <button id="export-excel" class="button modern-btn export-btn">
                        📊 Excel İndir
                    </button>
                    <button id="export-pdf" class="button modern-btn export-btn">
                        📄 PDF İndir
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Modern CSS Styling -->
        <style>
        .dastas-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin: 24px 0;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #fff 0%, #f8fafe 100%);
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stats-card-primary { border-left: 5px solid #0073aa; }
        .stats-card-success { border-left: 5px solid #46b450; }
        .stats-card-info { border-left: 5px solid #00a0d2; }
        .stats-card-warning { border-left: 5px solid #ffb900; }
        
        .stats-icon {
            font-size: 32px;
            margin-bottom: 12px;
            opacity: 0.8;
        }
        
        .stats-card h3 {
            margin: 0 0 12px 0;
            color: #666;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stats-number {
            font-size: 36px;
            font-weight: 800;
            color: #2c3e50;
            margin: 12px 0;
            line-height: 1.1;
        }
        
        .stats-number small {
            font-size: 18px;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .stats-detail {
            font-size: 13px;
            color: #666;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .stats-detail .active { color: #27ae60; font-weight: 600; }
        .stats-detail .inactive { color: #e74c3c; font-weight: 600; }
        .stats-detail .pending { color: #f39c12; font-weight: 600; }
        .stats-detail .approved { color: #27ae60; font-weight: 600; }
        
        .dastas-analytics-row {
            display: flex;
            gap: 24px;
            margin: 24px 0;
            flex-wrap: wrap;
        }
        
        .analytics-col-4 { flex: 1; min-width: 320px; }
        .analytics-col-6 { flex: 1.5; min-width: 400px; }
        .analytics-col-8 { flex: 2; min-width: 500px; }
        
        .analytics-widget {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .widget-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f8fafe 0%, #fff 100%);
        }
        
        .widget-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 16px;
            font-weight: 700;
        }
        
        .widget-badge {
            background: #0073aa;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-refresh {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .btn-refresh:hover {
            background: rgba(0,0,0,0.1);
        }
        
        .chart-container {
            padding: 24px;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .modern-table {
            border: none !important;
            box-shadow: none !important;
        }
        
        .modern-table th {
            background: #f8fafe !important;
            border: none !important;
            color: #2c3e50 !important;
            font-weight: 700 !important;
            padding: 16px !important;
            font-size: 13px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .modern-table td {
            padding: 16px !important;
            border-bottom: 1px solid #f0f0f1 !important;
            vertical-align: middle !important;
        }
        
        .modern-table tr:hover {
            background: #f8fafe !important;
        }
        
        .bayi-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .rank-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            color: white;
        }
        
        .rank-1 { background: #ffd700; color: #333; }
        .rank-2 { background: #c0c0c0; color: #333; }
        .rank-3 { background: #cd7f32; }
        .rank-badge:not(.rank-1):not(.rank-2):not(.rank-3) { 
            background: #95a5a6; 
        }
        
        .bayi-code {
            color: #95a5a6;
            font-size: 11px;
        }
        
        .metric-badge {
            background: #e8f4fd;
            color: #0073aa;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .volume-text, .avg-text {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .product-name {
            font-weight: 600;
            color: #27ae60;
        }
        
        .percentage-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        
        .percentage-fill {
            height: 8px;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            border-radius: 4px;
            min-width: 4px;
            transition: width 0.3s ease;
        }
        
        .percentage-text {
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
            min-width: 40px;
        }
        
        .analytics-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 32px 0;
            padding: 24px;
            background: linear-gradient(135deg, #fff 0%, #f8fafe 100%);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .analytics-filters {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .modern-select {
            padding: 10px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            min-width: 150px;
            transition: all 0.2s ease;
        }
        
        .modern-select:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
        }
        
        .modern-btn {
            padding: 12px 24px !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        
        .modern-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%) !important;
            color: white !important;
            margin-left: 8px;
        }
        
        .analytics-export {
            display: flex;
            gap: 8px;
        }
        
        /* Responsive design */
        @media (max-width: 1200px) {
            .dastas-analytics-row {
                flex-direction: column;
            }
            
            .analytics-col-4,
            .analytics-col-6,
            .analytics-col-8 {
                flex: 1;
                min-width: auto;
            }
        }
        
        @media (max-width: 768px) {
            .dastas-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .analytics-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .analytics-filters {
                justify-content: center;
                flex-direction: column;
            }
            
            .analytics-export {
                justify-content: center;
            }
            
            .stats-number {
                font-size: 28px;
            }
            
            .bayi-info {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
        }
        
        /* Animation for loading */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .loading {
            animation: pulse 1.5s infinite;
        }
        </style>
        
        <!-- Chart.js JavaScript -->
        <script>
        jQuery(document).ready(function($) {
            // Chart.js ile grafikleri çiz
            loadCharts();
            
            // Filtre butonuna tıklama
            $('#filter-analytics').on('click', function() {
                $(this).addClass('loading').text('🔄 Yükleniyor...');
                
                var timeFilter = $('#time-filter').val();
                var bayiFilter = $('#bayi-filter').val();
                
                // AJAX ile verileri güncelle
                updateAnalytics(timeFilter, bayiFilter);
            });
            
            // Export işlemleri
            $('#export-excel, #export-pdf').on('click', function() {
                var format = $(this).attr('id').replace('export-', '');
                var text = $(this).text();
                $(this).text('📦 Hazırlanıyor...');
                
                setTimeout(() => {
                    exportAnalytics(format);
                    $(this).text(text);
                }, 1000);
            });
        });
        
        function loadCharts() {
            var monthlyData = <?php echo json_encode($monthlyStats); ?>;
            var statusData = {
                bekleyen: <?php echo $siparisStats->bekleyen_siparis; ?>,
                onaylanan: <?php echo $siparisStats->onaylanan_siparis; ?>,
                reddedilen: 0
            };
            
            // Aylık trend grafiği (Line Chart)
            var ctx1 = document.getElementById('monthlyChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: monthlyData.map(d => d.ay),
                    datasets: [{
                        label: 'Sipariş Sayısı',
                        data: monthlyData.map(d => d.siparis_sayisi),
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#0073aa',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Durum grafiği (Doughnut Chart)
            var ctx2 = document.getElementById('statusChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Bekleyen', 'Onaylanan', 'Reddedilen'],
                    datasets: [{
                        data: [statusData.bekleyen, statusData.onaylanan, statusData.reddedilen],
                        backgroundColor: ['#f39c12', '#27ae60', '#e74c3c'],
                        borderWidth: 0,
                        cutout: '60%'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });
        }
        
        function refreshCharts() {
            location.reload();
        }
        
        function updateAnalytics(timeFilter, bayiFilter) {
            // Sayfa yenileme simülasyonu
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
        
        function exportAnalytics(format) {
            // Export simülasyonu
            console.log('Exporting as:', format);
            alert('📊 ' + format.toUpperCase() + ' export özelliği yakında gelecek!');
        }
        </script>
        <?php
    }
    
    // Bayi istatistiklerini getir
    private function getBayiStats() {
        return $this->db->getBayiStats();
    }
    
    // Sipariş istatistiklerini getir
    private function getSiparisStats() {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        
        return $wpdb->get_row("
            SELECT 
                COUNT(DISTINCT siparis_no) as toplam_siparis,
                SUM(CASE WHEN durum = 'beklemede' THEN 1 ELSE 0 END) as bekleyen_siparis,
                SUM(CASE WHEN durum = 'onaylandi' THEN 1 ELSE 0 END) as onaylanan_siparis,
                SUM(CASE WHEN durum = 'reddedildi' THEN 1 ELSE 0 END) as reddedilen_siparis,
                SUM(m3) as toplam_m3
            FROM {$siparis_table}
            WHERE siparis_tarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    }
    
    // Aylık istatistikleri getir
    private function getMonthlyStats() {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        
        return $wpdb->get_results("
            SELECT 
                DATE_FORMAT(siparis_tarihi, '%Y-%m') as ay,
                COUNT(DISTINCT siparis_no) as siparis_sayisi,
                SUM(m3) as toplam_m3
            FROM {$siparis_table}
            WHERE siparis_tarihi >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(siparis_tarihi, '%Y-%m')
            ORDER BY ay DESC
            LIMIT 12
        ");
    }
    
    // En aktif bayileri getir
    private function getTopBayiler($limit = 10) {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        $bayi_table = $this->db->getTable('bayi');
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                b.bayi_adi,
                b.bayi_kodu,
                COUNT(DISTINCT s.siparis_no) as siparis_sayisi,
                SUM(s.m3) as toplam_m3,
                AVG(s.m3) as ortalama_m3
            FROM {$bayi_table} b
            LEFT JOIN {$siparis_table} s ON b.id = s.bayi_id
            WHERE s.siparis_tarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY b.id
            ORDER BY siparis_sayisi DESC, toplam_m3 DESC
            LIMIT %d
        ", $limit));
    }
    
    // Ürün analizi
    private function getUrunAnalizi() {
        global $wpdb;
        $siparis_table = $this->db->getTable('siparis');
        
        $results = $wpdb->get_results("
            SELECT 
                agac_cinsi,
                COUNT(DISTINCT siparis_no) as siparis_sayisi,
                SUM(m3) as toplam_m3
            FROM {$siparis_table}
            WHERE siparis_tarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY agac_cinsi
            ORDER BY toplam_m3 DESC
        ");
        
        // Yüzde hesapla
        $totalM3 = array_sum(array_column($results, 'toplam_m3'));
        foreach ($results as &$result) {
            $result->oran = $totalM3 > 0 ? ($result->toplam_m3 / $totalM3) * 100 : 0;
        }
        
        return $results;
    }
    
    // AJAX: Analiz verilerini getir
    public function handleGetAnalyticsData() {
        check_ajax_referer('dastas_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $timeFilter = sanitize_text_field($_POST['time_filter']);
        $bayiFilter = intval($_POST['bayi_filter']);
        
        // Filtrelenmiş verileri al
        $data = [
            'bayi_stats' => $this->getBayiStats(),
            'siparis_stats' => $this->getSiparisStats(),
            'monthly_stats' => $this->getMonthlyStats(),
            'top_bayiler' => $this->getTopBayiler(),
            'urun_analiz' => $this->getUrunAnalizi()
        ];
        
        wp_send_json_success($data);
    }
    
    // AJAX: Export işlemi
    public function handleExportAnalytics() {
        check_ajax_referer('dastas_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $format = sanitize_text_field($_GET['format']);
        $timeFilter = sanitize_text_field($_GET['time_filter']);
        $bayiFilter = intval($_GET['bayi_filter']);
        
        if ($format === 'excel') {
            $this->exportToExcel($timeFilter, $bayiFilter);
        } elseif ($format === 'pdf') {
            $this->exportToPDF($timeFilter, $bayiFilter);
        }
    }
    
    private function exportToExcel($timeFilter, $bayiFilter) {
        // Basit CSV export
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="dastas-analiz-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, ['Bayi Adi', 'Siparis Sayisi', 'Toplam M3', 'Ortalama M3']);
        
        // Data
        $topBayiler = $this->getTopBayiler(100);
        foreach ($topBayiler as $bayi) {
            fputcsv($output, [
                $bayi->bayi_adi,
                $bayi->siparis_sayisi,
                $bayi->toplam_m3,
                $bayi->ortalama_m3
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    private function exportToPDF($timeFilter, $bayiFilter) {
        // Basit HTML to PDF (daha gelişmiş PDF kütüphanesi gerekebilir)
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="dastas-analiz-' . date('Y-m-d') . '.pdf"');
        
        echo "PDF export özelliği geliştirilmektedir...";
        exit;
    }
}
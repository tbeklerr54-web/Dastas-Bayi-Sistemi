<?php
/**
 * Sipariş Yönetim Modülü
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dastas_Orders {
    
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Dastas_Plugin::getInstance()->getModule('database');
        $this->auth = Dastas_Plugin::getInstance()->getModule('auth');
        $this->initHooks();
    }
    
    private function initHooks() {
        add_action('wp_ajax_dastas_yeni_siparis', [$this, 'handleNewOrder']);
        add_action('wp_ajax_nopriv_dastas_yeni_siparis', [$this, 'handleNewOrder']);
        
        add_action('wp_ajax_dastas_get_siparis_detay', [$this, 'handleGetOrderDetail']);
        add_action('wp_ajax_nopriv_dastas_get_siparis_detay', [$this, 'handleGetOrderDetail']);
        
        add_action('wp_ajax_dastas_delete_siparis', [$this, 'handleDeleteOrder']);
        add_action('wp_ajax_nopriv_dastas_delete_siparis', [$this, 'handleDeleteOrder']);
    }
    
    public function renderOrderForm($atts) {
        if (!$this->auth->isLoggedIn()) {
            return '<div class="error">Bu sayfayı görüntülemek için giriş yapmalısınız.</div>';
        }
        
        ob_start();
        ?>
        <div id="dastas-order-wizard" class="dastas-wizard-container">
            <!-- Progress Header -->
            <div class="wizard-header">
                <h2>🛒 Sipariş Ver</h2>
                <p>Adım adım ürün bilgilerini girin ve siparişinizi oluşturun</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Ağaç Cinsi</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Kalınlık</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Ebat</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">Tutkal</div>
                </div>
                <div class="step" data-step="5">
                    <div class="step-number">5</div>
                    <div class="step-label">Özellikler</div>
                </div>
                <div class="step" data-step="6">
                    <div class="step-number">6</div>
                    <div class="step-label">Adet</div>
                </div>
                <div class="step" data-step="7">
                    <div class="step-number">7</div>
                    <div class="step-label">Sipariş</div>
                </div>
            </div>
            
            <!-- Form Container -->
            <div class="wizard-form">
                <form id="order-form">
                    <!-- Step 1: Ağaç Cinsi -->
                    <div class="step-content active" data-step="1">
                        <div class="step-header">
                            <h3>🌳 Ağaç Cinsi Seçin</h3>
                            <p>Ürününüz için uygun ağaç cinsini seçiniz</p>
                        </div>
                        <div class="form-content">
                            <div class="form-group">
                                <label for="agac_cinsi">Ağaç Cinsi <span class="required">*</span></label>
                                <select id="agac_cinsi" name="agac_cinsi" required>
                                    <option value="">-- Ağaç cinsini seçiniz --</option>
                                    <option value="Kayın">Kayın (Beech)</option>
                                    <option value="Huş">Huş (Birch)</option>
                                    <option value="Huş Kombi">Huş Kombi (Birch Combi)</option>
                                    <option value="Kızılağaç">Kızılağaç (Alder)</option>
                                    <option value="Egzotik Kombi">Egzotik Kombi (Exotic Combi)</option>
                                    <option value="Albazya">Albazya (Albasia)</option>
                                    <option value="Esnek">Esnek (Bending)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Kalınlık -->
                    <div class="step-content" data-step="2">
                        <div class="step-header">
                            <h3>📏 Kalınlık Seçin</h3>
                            <p>Ürün kalınlığını belirleyiniz</p>
                        </div>
                        <div class="form-content">
                            <div class="form-group">
                                <label for="kalinlik">Kalınlık <span class="required">*</span></label>
                                <select id="kalinlik" name="kalinlik" required>
                                    <option value="">-- Kalınlık seçiniz --</option>
                                    <option value="3">3 mm</option>
                                    <option value="4">4 mm</option>
                                    <option value="5">5 mm</option>
                                    <option value="6">6 mm</option>
                                    <option value="8">8 mm</option>
                                    <option value="9">9 mm</option>
                                    <option value="12">12 mm</option>
                                    <option value="15">15 mm</option>
                                    <option value="18">18 mm</option>
                                    <option value="20">20 mm</option>
                                    <option value="22">22 mm</option>
                                    <option value="25">25 mm</option>
                                    <option value="30">30 mm</option>
                                    <option value="35">35 mm</option>
                                    <option value="40">40 mm</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Ebat -->
                    <div class="step-content" data-step="3">
                        <div class="step-header">
                            <h3>📐 Ebat Seçin</h3>
                            <p>Ürün ebatlarını belirleyiniz</p>
                        </div>
                        <div class="form-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="ebat1">1. Ebat (cm) <span class="required">*</span></label>
                                    <select id="ebat1" name="ebat1" required>
                                        <option value="">-- 1. Ebat seçiniz --</option>
                                        <option value="122">122 cm</option>
                                        <option value="125">125 cm</option>
                                        <option value="150">150 cm</option>
                                        <option value="152.5">152.5 cm</option>
                                        <option value="170">170 cm</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="ebat2">2. Ebat (cm) <span class="required">*</span></label>
                                    <select id="ebat2" name="ebat2" required>
                                        <option value="">-- 2. Ebat seçiniz --</option>
                                        <option value="152.5">152.5 cm</option>
                                        <option value="220">220 cm</option>
                                        <option value="244">244 cm</option>
                                        <option value="250">250 cm</option>
                                        <option value="300">300 cm</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4: Tutkal -->
                    <div class="step-content" data-step="4">
                        <div class="step-header">
                            <h3>🔧 Tutkal Türü</h3>
                            <p>Tutkal türünü seçiniz</p>
                        </div>
                        <div class="form-content">
                            <div class="form-group">
                                <label for="tutkal">Tutkal <span class="required">*</span></label>
                                <select id="tutkal" name="tutkal" required>
                                    <option value="">-- Tutkal seçiniz --</option>
                                    <option value="Muf">Muf</option>
                                    <option value="Beton">Beton</option>
                                    <option value="Marin">Marin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 5: Özellikler -->
                    <div class="step-content" data-step="5">
                        <div class="step-header">
                            <h3>⭐ Ek Özellikler</h3>
                            <p>Kalite, kaplama ve desen seçenekleri (isteğe bağlı)</p>
                        </div>
                        <div class="form-content">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="kalite">Kalite</label>
                                    <select id="kalite" name="kalite">
                                        <option value="">-- Kalite seçiniz --</option>
                                        <option value="BB/BB">BB/BB</option>
                                        <option value="BB/CP">BB/CP</option>
                                        <option value="CP/CP">CP/CP</option>
                                        <option value="CP/C">CP/C</option>
                                        <option value="C/C">C/C</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="kaplama">Kaplama</label>
                                    <select id="kaplama" name="kaplama">
                                        <option value="">-- Kaplama seçiniz --</option>
                                        <option value="Filmli">Filmli</option>
                                        <option value="Petek Desen">Petek Desen</option>
                                        <option value="Tırtık Desen">Tırtık Desen</option>
                                        <option value="Arpa Desen">Arpa Desen</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="desen">Desen</label>
                                    <select id="desen" name="desen">
                                        <option value="">-- Desen seçiniz --</option>
                                        <option value="Suyuna">Suyuna</option>
                                        <option value="Sokrasına">Sokrasına</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 6: Adet -->
                    <div class="step-content" data-step="6">
                        <div class="step-header">
                            <h3>🔢 Adet Belirleyin</h3>
                            <p>Ürün adedini girin ve hesaplanan hacmi görün</p>
                        </div>
                        <div class="form-content">
                            <div class="form-group">
                                <label for="miktar">Miktar (Adet) <span class="required">*</span></label>
                                <input type="number" id="miktar" name="miktar" min="1" required placeholder="Adet giriniz">
                            </div>
                            
                            <!-- Product Summary -->
                            <div class="product-preview">
                                <h4>📋 Ürün Özeti</h4>
                                <div class="preview-grid">
                                    <div class="preview-item">
                                        <span class="label">Ağaç:</span>
                                        <span class="value" id="preview-agac">-</span>
                                    </div>
                                    <div class="preview-item">
                                        <span class="label">Kalınlık:</span>
                                        <span class="value" id="preview-kalinlik">-</span>
                                    </div>
                                    <div class="preview-item">
                                        <span class="label">Ebat:</span>
                                        <span class="value" id="preview-ebat">-</span>
                                    </div>
                                    <div class="preview-item">
                                        <span class="label">Tutkal:</span>
                                        <span class="value" id="preview-tutkal">-</span>
                                    </div>
                                    <div class="preview-item">
                                        <span class="label">Adet:</span>
                                        <span class="value" id="preview-miktar">-</span>
                                    </div>
                                    <div class="preview-item total">
                                        <span class="label">Toplam m³:</span>
                                        <span class="value" id="preview-volume">0.000 m³</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="step-actions">
                                <button type="button" class="btn btn-success btn-add">
                                    ➕ Ürünü Listeye Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 7: Sipariş -->
                    <div class="step-content" data-step="7">
                        <div class="step-header">
                            <h3>📋 Sipariş Listesi</h3>
                            <p>Eklenen ürünleri kontrol edin ve siparişinizi gönderin</p>
                        </div>
                        <div class="form-content">
                            <!-- Product List -->
                            <div id="product-list" class="product-list">
                                <div class="no-products">
                                    <p>🛒 Henüz ürün eklenmedi.</p>
                                    <p>Ürün eklemek için yukarıdaki adımları tamamlayın.</p>
                                </div>
                            </div>
                            
                            <!-- Order Summary -->
                            <div id="order-summary" class="order-summary" style="display: none;">
                                <h4>📊 Sipariş Toplamı</h4>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span class="label">Toplam Ürün:</span>
                                        <span class="value" id="total-products">0</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="label">Toplam Hacim:</span>
                                        <span class="value" id="total-volume">0.000 m³</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Notes -->
                            <div class="form-group" id="order-notes-section" style="display: none;">
                                <label for="order_notes">📝 Sipariş Notları</label>
                                <textarea id="order_notes" name="order_notes" rows="3" placeholder="Sipariş ile ilgili ek notlarınız..."></textarea>
                            </div>
                            
                            <!-- Order Actions -->
                            <div class="order-actions" id="order-actions" style="display: none;">
                                <button type="button" class="btn btn-outline btn-clear-all">
                                    🗑️ Tümünü Temizle
                                </button>
                                <button type="button" class="btn btn-success btn-submit">
                                    🚀 Siparişi Gönder
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Navigation -->
                <div class="wizard-navigation">
                    <button type="button" class="btn btn-secondary btn-prev" style="display: none;">
                        ← Geri
                    </button>
                    <button type="button" class="btn btn-primary btn-next">
                        İleri →
                    </button>
                </div>
            </div>
            
            <!-- Success Modal -->
            <div id="success-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>✅ Başarılı!</h3>
                    </div>
                    <div class="modal-body">
                        <p id="success-message"></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function renderOrderList($atts) {
        // Session kontrolü
        if (!isset($_SESSION)) {
            session_start();
        }
        
        // Auth kontrolü 
        if (!$this->auth->isLoggedIn()) {
            return '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
                ❌ Bu sayfayı görüntülemek için giriş yapmalısınız. 
                <br><small>Session durumu: ' . (isset($_SESSION['dastas_bayi_id']) ? 'Mevcut (ID: '.$_SESSION['dastas_bayi_id'].')' : 'Yok') . '</small>
            </div>';
        }
        
        $bayi_id = $_SESSION['dastas_bayi_id'];
        $siparisler = $this->db->getSiparislerByBayi($bayi_id);
        
        // Debug bilgileri
        error_log("DASTAS DEBUG - Bayi ID: " . $bayi_id);
        error_log("DASTAS DEBUG - Sipariş sayısı: " . count($siparisler));
        if (!empty($siparisler)) {
            error_log("DASTAS DEBUG - İlk sipariş: " . print_r($siparisler[0], true));
        }
        
        // Siparişleri grupla
        $grouped_orders = [];
        foreach ($siparisler as $siparis) {
            if (!isset($grouped_orders[$siparis->siparis_no])) {
                $grouped_orders[$siparis->siparis_no] = [
                    'siparis_no' => $siparis->siparis_no,
                    'siparis_tarihi' => $siparis->siparis_tarihi,
                    'durum' => $siparis->durum,
                    'urun_sayisi' => 0,
                    'toplam_m3' => 0,
                    'urunler' => []
                ];
            }
            
            $grouped_orders[$siparis->siparis_no]['urun_sayisi']++;
            $grouped_orders[$siparis->siparis_no]['toplam_m3'] += $siparis->m3;
            $grouped_orders[$siparis->siparis_no]['urunler'][] = $siparis;
        }
        
        ob_start();
        ?>
        <div class="dastas-orders-wrapper">
            <style>
            .dastas-orders-wrapper {
                max-width: 100%;
                margin: 0;
                padding: 0;
                font-family: inherit;
            }
            .dastas-orders-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px 25px;
                margin-bottom: 30px;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            }
            .dastas-orders-header h2 {
                margin: 0 0 10px 0;
                font-size: 28px;
                font-weight: 700;
            }
            .dastas-orders-header p {
                margin: 0 0 20px 0;
                opacity: 0.9;
                font-size: 16px;
            }
            .dastas-btn-new {
                display: inline-block;
                background: rgba(255,255,255,0.2);
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: 600;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255,255,255,0.3);
            }
            .dastas-btn-new:hover {
                background: rgba(255,255,255,0.3);
                transform: translateY(-2px);
                color: white;
                text-decoration: none;
            }
            .dastas-empty-state {
                text-align: center;
                padding: 80px 30px;
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border-radius: 20px;
                margin: 40px 0;
            }
            .dastas-empty-icon {
                font-size: 4rem;
                margin-bottom: 20px;
                opacity: 0.7;
            }
            .dastas-empty-state h3 {
                color: #2c3e50;
                margin-bottom: 15px;
                font-weight: 600;
                font-size: 24px;
            }
            .dastas-empty-state p {
                color: #6c757d;
                margin-bottom: 25px;
                font-size: 16px;
            }
            .dastas-orders-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 25px;
                margin-top: 20px;
            }
            .dastas-order-card {
                background: white;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                transition: all 0.3s ease;
                border: 1px solid rgba(0,0,0,0.05);
            }
            .dastas-order-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            }
            .dastas-card-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                position: relative;
            }
            .dastas-card-header::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1);
            }
            .dastas-order-number {
                font-size: 18px;
                font-weight: 700;
                margin-bottom: 8px;
            }
            .dastas-order-date {
                font-size: 14px;
                opacity: 0.9;
            }
            .dastas-card-body {
                padding: 20px;
            }
            .dastas-stats-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-bottom: 20px;
            }
            .dastas-stat-item {
                text-align: center;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 10px;
            }
            .dastas-stat-value {
                display: block;
                font-size: 20px;
                font-weight: 700;
                color: #2c3e50;
                margin-bottom: 5px;
            }
            .dastas-stat-label {
                font-size: 12px;
                color: #6c757d;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .dastas-status-badge {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .dastas-status-beklemede {
                background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
                color: #d63031;
            }
            .dastas-status-onaylandi {
                background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
                color: white;
            }
            .dastas-status-hazirlaniyor {
                background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
                color: white;
            }
            .dastas-status-tamamlandi {
                background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
                color: white;
            }
            .dastas-status-iptal {
                background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
                color: white;
            }
            .dastas-card-footer {
                padding: 20px;
                background: #f8f9fa;
                border-top: 1px solid #e9ecef;
                display: flex;
                gap: 10px;
                justify-content: center;
            }
            .dastas-btn {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                font-size: 14px;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            .dastas-btn-detail {
                background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
                color: white;
            }
            .dastas-btn-detail:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(116, 185, 255, 0.4);
                color: white;
            }
            .dastas-btn-delete {
                background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
                color: white;
            }
            .dastas-btn-delete:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(253, 121, 168, 0.4);
                color: white;
            }
            
            /* Modal Styles */
            .loading-spinner {
                text-align: center;
                padding: 50px;
            }
            
            .spinner {
                width: 50px;
                height: 50px;
                border: 4px solid rgba(102, 126, 234, 0.2);
                border-top: 4px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .error-message {
                text-align: center;
                padding: 50px 30px;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 10px;
                margin: 20px 0;
            }
            
            .error-icon {
                font-size: 48px;
                display: block;
                margin-bottom: 15px;
            }
            
            .urun-detay {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .urun-detay h6 {
                margin-top: 0;
                color: #495057;
                border-bottom: 2px solid #dee2e6;
                padding-bottom: 10px;
            }
            
            .detail-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 10px;
            }
            
            .detail-grid div {
                padding: 8px 0;
                border-bottom: 1px solid #dee2e6;
            }
            
            .detail-grid strong {
                color: #495057;
            }
            
            .siparis-toplam {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                margin-top: 20px;
            }
            
            .siparis-toplam h6 {
                margin-top: 0;
                margin-bottom: 15px;
            }
            
            .durum-badge {
                display: inline-block;
                padding: 5px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }
            
            .durum-beklemede {
                background: #ffc107;
                color: #212529;
            }
            
            .durum-onaylandi {
                background: #28a745;
                color: white;
            }
            
            .durum-hazirlaniyor {
                background: #17a2b8;
                color: white;
            }
            
            .durum-tamamlandi {
                background: #28a745;
                color: white;
            }
            
            .durum-iptal {
                background: #dc3545;
                color: white;
            }
            @media (max-width: 768px) {
                .dastas-orders-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                .dastas-card-footer {
                    flex-direction: column;
                }
                .dastas-btn {
                    width: 100%;
                    justify-content: center;
                }
            }
            </style>
            
            <!-- Header -->
            <div class="dastas-orders-header">
                <h2>📋 Siparişlerim</h2>
                <p>Vermiş olduğunuz siparişleri görüntüleyin ve yönetin</p>
                <a href="<?php echo home_url('/siparis-ekle/'); ?>" class="dastas-btn-new">
                    ➕ Yeni Sipariş Ver
                </a>
            </div>
            
            <?php if (empty($grouped_orders)): ?>
                <div class="dastas-empty-state">
                    <div class="dastas-empty-icon">📦</div>
                    <h3>Henüz sipariş bulunmuyor</h3>
                    <p>İlk siparişinizi vererek başlayın!</p>
                    <a href="<?php echo home_url('/siparis-ekle/'); ?>" class="dastas-btn-new">
                        🚀 İlk Siparişi Ver
                    </a>
                </div>
            <?php else: ?>
                <!-- Orders Grid -->
                <div class="dastas-orders-grid">
                    <?php foreach ($grouped_orders as $siparis): ?>
                        <div class="dastas-order-card">
                            <!-- Card Header -->
                            <div class="dastas-card-header">
                                <div class="dastas-order-number">
                                    Sipariş #<?php echo esc_html($siparis['siparis_no']); ?>
                                </div>
                                <div class="dastas-order-date">
                                    <?php echo date('d.m.Y H:i', strtotime($siparis['siparis_tarihi'])); ?>
                                </div>
                            </div>
                            
                            <!-- Card Body -->
                            <div class="dastas-card-body">
                                <div class="dastas-stats-grid">
                                    <div class="dastas-stat-item">
                                        <span class="dastas-stat-value"><?php echo $siparis['urun_sayisi']; ?></span>
                                        <span class="dastas-stat-label">📦 Ürün</span>
                                    </div>
                                    <div class="dastas-stat-item">
                                        <span class="dastas-stat-value"><?php echo number_format($siparis['toplam_m3'], 3); ?></span>
                                        <span class="dastas-stat-label">📐 m³</span>
                                    </div>
                                </div>
                                
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <?php 
                                    $status_config = [
                                        'beklemede' => '⏳',
                                        'onaylandi' => '✅',
                                        'hazirlaniyor' => '🔄',
                                        'tamamlandi' => '🎉',
                                        'iptal' => '❌'
                                    ];
                                    $icon = $status_config[$siparis['durum']] ?? '📋';
                                    ?>
                                    <span class="dastas-status-badge dastas-status-<?php echo esc_attr($siparis['durum']); ?>">
                                        <?php echo $icon; ?> <?php echo esc_html(ucfirst($siparis['durum'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Card Footer -->
                            <div class="dastas-card-footer">
                                <button class="dastas-btn dastas-btn-detail btn-detail" data-siparis-no="<?php echo esc_attr($siparis['siparis_no']); ?>">
                                    👁️ Detay
                                </button>
                                
                                <?php if ($siparis['durum'] === 'beklemede'): ?>
                                    <button class="dastas-btn dastas-btn-delete btn-delete" data-siparis-no="<?php echo esc_attr($siparis['siparis_no']); ?>">
                                        🗑️ Sil
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        </div>
        
        <!-- Modern Modal -->
        <div id="order-detail-modal" style="display: none;">
            <style>
            #order-detail-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                z-index: 10000;
                backdrop-filter: blur(5px);
            }
            .dastas-modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                border-radius: 20px;
                max-width: 700px;
                width: 90%;
                max-height: 85vh;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                animation: modalSlideIn 0.3s ease-out;
            }
            @keyframes modalSlideIn {
                from {
                    opacity: 0;
                    transform: translate(-50%, -60%) scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
            }
            .dastas-modal-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .dastas-modal-title {
                margin: 0;
                font-size: 20px;
                font-weight: 700;
            }
            .dastas-modal-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                padding: 8px;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.3s ease;
            }
            .dastas-modal-close:hover {
                background: rgba(255,255,255,0.2);
            }
            .dastas-modal-body {
                padding: 30px;
                max-height: 60vh;
                overflow-y: auto;
            }
            .dastas-loading {
                text-align: center;
                padding: 50px;
            }
            .dastas-spinner {
                width: 50px;
                height: 50px;
                border: 4px solid rgba(102, 126, 234, 0.2);
                border-top: 4px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .dastas-loading p {
                color: #666;
                font-size: 16px;
            }
            </style>
            
            <div class="dastas-modal-content">
                <div class="dastas-modal-header">
                    <h3 class="dastas-modal-title">📋 Sipariş Detayları</h3>
                    <button class="dastas-modal-close modal-close" type="button">
                        ✕
                    </button>
                </div>
                <div id="modal-body-content" class="dastas-modal-body">
                    <div class="dastas-loading">
                        <div class="dastas-spinner"></div>
                        <p>Sipariş detayları yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Ensure dastas_ajax is defined for order list
        if (typeof dastas_ajax === 'undefined') {
            window.dastas_ajax = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('dastas_nonce'); ?>',
                site_url: '<?php echo home_url(); ?>'
            };
        }
        
        jQuery(document).ready(function($) {
            console.log('Order list JavaScript loaded'); // Debug
            console.log('dastas_ajax:', dastas_ajax); // Debug
            
            // Modern event handlers
            $(document).on('click', '.btn-detail', function() {
                console.log('Detail button clicked'); // Debug
                const siparis_no = $(this).data('siparis-no');
                const modal = $('#order-detail-modal');
                
                console.log('Siparis No:', siparis_no); // Debug
                
                // Show modal with loading
                modal.show();
                $('#modal-body-content').html(`
                    <div class="dastas-loading">
                        <div class="dastas-spinner"></div>
                        <p>Sipariş detayları yükleniyor...</p>
                    </div>
                `);
                
                // Fetch order details
                $.post(dastas_ajax.ajax_url, {
                    action: 'dastas_get_siparis_detay',
                    nonce: dastas_ajax.nonce,
                    siparis_no: siparis_no
                })
                .done(function(response) {
                    if (response.success) {
                        $('#modal-body-content').html(response.data.html);
                    } else {
                        $('#modal-body-content').html(`
                            <div class="error-message">
                                <span class="error-icon">❌</span>
                                <p>Detay yüklenirken hata oluştu: ${response.data.message}</p>
                            </div>
                        `);
                    }
                })
                .fail(function() {
                    $('#modal-body-content').html(`
                        <div class="error-message">
                            <span class="error-icon">❌</span>
                            <p>Bağlantı hatası oluştu. Lütfen tekrar deneyin.</p>
                        </div>
                    `);
                });
            });
            
            // Close modal handlers
            $(document).on('click', '.modal-close', function() {
                console.log('Modal close button clicked'); // Debug
                $('#order-detail-modal').hide();
            });

            // Close modal when clicking outside
            $(document).on('click', '#order-detail-modal', function(e) {
                console.log('Modal backdrop clicked'); // Debug
                if (e.target === this) {
                    $('#order-detail-modal').hide();
                }
            });

            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // ESC key
                    console.log('ESC key pressed'); // Debug
                    $('#order-detail-modal').hide();
                }
            });
            
            // Delete order with modern confirmation
            $(document).on('click', '.btn-delete', function() {
                const siparis_no = $(this).data('siparis-no');
                const $btn = $(this);
                
                // Custom confirmation modal could be added here
                if (!confirm('🗑️ Bu siparişi silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz.')) {
                    return;
                }
                
                // Disable button and show loading
                $btn.prop('disabled', true).html('⏳ Siliniyor...');
                
                $.post(dastas_ajax.ajax_url, {
                    action: 'dastas_delete_siparis',
                    nonce: dastas_ajax.nonce,
                    siparis_no: siparis_no
                })
                .done(function(response) {
                    if (response.success) {
                        // Smooth remove animation
                        $btn.closest('.order-card').fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if no orders left
                            if ($('.order-card').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert('❌ Sipariş silinirken hata oluştu: ' + response.data.message);
                        $btn.prop('disabled', false).html('🗑️ Sil');
                    }
                })
                .fail(function() {
                    alert('❌ Bağlantı hatası oluştu. Lütfen tekrar deneyin.');
                    $btn.prop('disabled', false).html('🗑️ Sil');
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function handleNewOrder() {
        if (!$this->auth->isLoggedIn()) {
            wp_send_json_error(['message' => 'Giriş yapmalısınız!']);
        }
        
        check_ajax_referer('dastas_nonce', 'nonce');
        
        $bayi_id = $_SESSION['dastas_bayi_id'];
        $products_json = stripslashes($_POST['products']); // WordPress magic quotes fix
        $products = json_decode($products_json, true);
        
        if ($products === null) {
            wp_send_json_error(['message' => 'JSON decode hatası: ' . json_last_error_msg()]);
        }
        
        if (empty($products)) {
            wp_send_json_error(['message' => 'Ürün listesi boş!']);
        }
        
        // Sipariş numarası oluştur
        $siparis_no = 'SP' . date('YmdHis') . rand(100, 999);
        
        $success_count = 0;
        foreach ($products as $product) {
            $order_data = [
                'bayi_id' => $bayi_id,
                'siparis_no' => $siparis_no,
                'agac_cinsi' => sanitize_text_field($product['agac_cinsi']),
                'kalinlik' => floatval($product['kalinlik']),
                'ebat1' => floatval($product['ebat1']),
                'ebat2' => floatval($product['ebat2']),
                'tutkal' => sanitize_text_field($product['tutkal']),
                'kalite' => sanitize_text_field($product['kalite'] ?? ''),
                'kaplama' => sanitize_text_field($product['kaplama'] ?? ''),
                'desen' => sanitize_text_field($product['desen'] ?? ''),
                'miktar' => intval($product['miktar']),
                'm3' => floatval($product['m3']),
                'notlar' => sanitize_textarea_field($_POST['order_notes'] ?? ''),
                'durum' => 'beklemede',
                'olusturma_tarihi' => current_time('mysql')
            ];
            
            if ($this->db->insertSiparis($order_data)) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            wp_send_json_success([
                'message' => 'Siparişiniz başarıyla oluşturuldu! ' . $success_count . ' ürün eklendi. Sipariş No: ' . $siparis_no,
                'siparis_no' => $siparis_no
            ]);
        } else {
            wp_send_json_error(['message' => 'Hiçbir ürün eklenemedi!']);
        }
    }
    
    public function handleGetOrderDetail() {
        if (!$this->auth->isLoggedIn()) {
            wp_send_json_error(['message' => 'Giriş yapmalısınız!']);
        }
        
        check_ajax_referer('dastas_nonce', 'nonce');
        
        $bayi_id = $_SESSION['dastas_bayi_id'];
        $siparis_no = sanitize_text_field($_POST['siparis_no']);
        
        $urunler = $this->db->getSiparisByNo($siparis_no, $bayi_id);
        
        if (empty($urunler)) {
            wp_send_json_error(['message' => 'Sipariş bulunamadı!']);
        }
        
        ob_start();
        ?>
        <div class="siparis-detay-content">
            <h5>Sipariş Detayları</h5>
            
            <?php foreach ($urunler as $index => $urun): ?>
                <div class="urun-detay">
                    <h6>Ürün <?php echo ($index + 1); ?></h6>
                    <div class="detail-grid">
                        <div><strong>Ağaç Cinsi:</strong> <?php echo esc_html($urun->agac_cinsi); ?></div>
                        <div><strong>Kalınlık:</strong> <?php echo esc_html($urun->kalinlik); ?> mm</div>
                        <div><strong>Ebat:</strong> <?php echo esc_html($urun->ebat1); ?> x <?php echo esc_html($urun->ebat2); ?> cm</div>
                        <div><strong>Miktar:</strong> <?php echo esc_html($urun->miktar); ?> adet</div>
                        <div><strong>m³:</strong> <?php echo esc_html(number_format($urun->m3, 3)); ?> m³</div>
                        <div><strong>Tutkal:</strong> <?php echo esc_html($urun->tutkal); ?></div>
                        <?php if ($urun->kalite): ?>
                            <div><strong>Kalite:</strong> <?php echo esc_html($urun->kalite); ?></div>
                        <?php endif; ?>
                        <?php if ($urun->kaplama): ?>
                            <div><strong>Kaplama:</strong> <?php echo esc_html($urun->kaplama); ?></div>
                        <?php endif; ?>
                        <?php if ($urun->desen): ?>
                            <div><strong>Desen:</strong> <?php echo esc_html($urun->desen); ?></div>
                        <?php endif; ?>
                        <!-- Ürün notu kaldırıldı: artık gösterilmiyor -->
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="siparis-toplam">
                <h6>Sipariş Toplamı</h6>
                <p><strong>Toplam Ürün:</strong> <?php echo count($urunler); ?> ürün</p>
                <p><strong>Toplam m³:</strong> <?php echo number_format(array_sum(array_column($urunler, 'm3')), 3); ?> m³</p>
                <p><strong>Durum:</strong> 
                    <span class="durum-badge durum-<?php echo esc_attr($urunler[0]->durum); ?>">
                        <?php echo esc_html(ucfirst($urunler[0]->durum)); ?>
                    </span>
                </p>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    public function handleDeleteOrder() {
        if (!$this->auth->isLoggedIn()) {
            wp_send_json_error(['message' => 'Giriş yapmalısınız!']);
        }
        
        check_ajax_referer('dastas_nonce', 'nonce');
        
        $bayi_id = $_SESSION['dastas_bayi_id'];
        $siparis_no = sanitize_text_field($_POST['siparis_no']);
        
        global $wpdb;
        
        // Siparişin sahibi olup olmadığını ve durumunu kontrol et
        $siparis = $wpdb->get_row($wpdb->prepare("
            SELECT durum FROM {$this->db->getTable('siparis')} 
            WHERE bayi_id = %d AND siparis_no = %s 
            LIMIT 1
        ", $bayi_id, $siparis_no));
        
        if (!$siparis) {
            wp_send_json_error(['message' => 'Sipariş bulunamadı!']);
        }
        
        if ($siparis->durum !== 'beklemede') {
            wp_send_json_error(['message' => 'Sadece beklemede olan siparişler silinebilir!']);
        }
        
        // Siparişi sil
        $result = $wpdb->delete(
            $this->db->getTable('siparis'),
            ['bayi_id' => $bayi_id, 'siparis_no' => $siparis_no],
            ['%d', '%s']
        );
        
        if ($result) {
            wp_send_json_success(['message' => 'Sipariş başarıyla silindi!']);
        } else {
            wp_send_json_error(['message' => 'Sipariş silinirken hata oluştu!']);
        }
    }
}

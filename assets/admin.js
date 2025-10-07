/**
 * Dastas Bayi Sistemi - Admin JavaScript
 */

(function($) {
    'use strict';

    // Admin System
    window.DastasAdmin = {
        ajaxUrl: (typeof dastas_admin_ajax !== 'undefined') ? dastas_admin_ajax.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php'),
        nonce: (typeof dastas_admin_ajax !== 'undefined') ? dastas_admin_ajax.nonce : (window.dastas_nonce || ''),
        
        // Initialize admin system
        init: function() {
            this.bindEvents();
            this.initDataTables();
            this.initCharts();
            this.loadSystemStats();
            console.log('Dastas Admin System initialized');
        },
        
        // Bind all admin event handlers
        bindEvents: function() {
            // Order management
            $(document).on('change', '.order-status-select', this.handleStatusChange.bind(this));
            $(document).on('click', '.view-order-btn', this.viewAdminOrderDetails.bind(this));
            $(document).on('click', '.delete-order-btn', this.deleteOrder.bind(this));

            // Bulk actions
            $(document).on('click', '#doaction', this.handleBulkAction.bind(this));
            $(document).on('click', '#cb-select-all-1', this.toggleSelectAll.bind(this));

            // Export functionality
            $(document).on('click', '#export-orders', this.exportOrders.bind(this));

            // Bayi management
            $(document).on('click', '#add-new-bayi', this.showAddBayiModal.bind(this));
            $(document).on('submit', '#bayi-form', this.handleBayiSubmit.bind(this));
            $(document).on('click', '.edit-bayi', this.editBayi.bind(this));
            $(document).on('click', '.activate-bayi, .deactivate-bayi', this.toggleBayiStatus.bind(this));
            $(document).on('change', '#sifre_degistir', this.togglePasswordChange.bind(this));

            // Notifications
            $(document).on('click', '#send-notification', this.showNotificationModal.bind(this));
            $(document).on('submit', '#notification-form', this.sendNotification.bind(this));

            // Modal controls
            $(document).on('click', '.close', this.closeModal.bind(this));
            $(document).on('click', '.dastas-modal', function(e) {
                if (e.target === this) {
                    DastasAdmin.closeModal();
                }
            });

            // Admin modal controls
            $(document).on('click', '.admin-modal-close', this.closeAdminModal.bind(this));
            $(document).on('click', '.dastas-admin-modal', function(e) {
                if (e.target === this) {
                    DastasAdmin.closeAdminModal();
                }
            });
        },
        
        // Handle order status change
        handleStatusChange: function(e) {
            const select = $(e.target);
            const orderId = select.data('order-id');
            const newStatus = select.val();
            const originalStatus = select.data('original-status') || select.find('option:selected').siblings().first().val();
            
            // Show loading
            select.prop('disabled', true);
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_update_order_status',
                    order_id: orderId,
                    status: newStatus,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Sipariş durumu güncellendi', 'success');
                        select.data('original-status', newStatus);
                        
                        // Update row styling based on status
                        const row = select.closest('tr');
                        row.removeClass('status-beklemede status-onaylandi status-hazirlaniyor status-sevk-edildi status-teslim-edildi status-iptal');
                        row.addClass('status-' + newStatus);
                        
                    } else {
                        this.showNotification('Durum güncellenemedi: ' + (response.data || 'Bilinmeyen hata'), 'error');
                        select.val(originalStatus);
                    }
                },
                error: () => {
                    this.showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                    select.val(originalStatus);
                },
                complete: () => {
                    select.prop('disabled', false);
                }
            });
        },
        
        // View order details (Admin panel için)
        viewOrderDetails: function(orderId, siparisNo) {
            console.log('Admin panel sipariş detayı isteniyor:', orderId, siparisNo);

            // Admin panelinde PHP'deki modal fonksiyonunu kullan
            // Bu fonksiyon Admin.php'de tanımlı
            if (window.showOrderDetailModal) {
                // Loading mesajı göster
                window.showOrderDetailModal('<div style="text-align: center; padding: 40px;"><div>Yükleniyor...</div></div>');

                // Fetch order details via AJAX
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dastas_get_admin_order_details',
                        order_id: orderId,
                        siparis_no: siparisNo,
                        nonce: this.nonce
                    },
                    success: (response) => {
                        console.log('AJAX success:', response);
                        if (response.success && response.data && response.data.html) {
                            // Modal içeriğini güncelle
                            document.getElementById('modal-content').innerHTML = response.data.html;
                        } else {
                            console.error('AJAX error response:', response);
                            document.getElementById('modal-content').innerHTML = `
                                <div class="error-message" style="text-align: center; padding: 40px;">
                                    <span class="error-icon">❌</span>
                                    <p>Detay yüklenirken hata oluştu: ${response.data ? response.data.message : 'Bilinmeyen hata'}</p>
                                    <small>Order ID: ${orderId}, Sipariş No: ${siparisNo}</small>
                                </div>
                            `;
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('AJAX error:', xhr, status, error);
                        if (document.getElementById('modal-content')) {
                            document.getElementById('modal-content').innerHTML = `
                                <div class="error-message" style="text-align: center; padding: 40px;">
                                    <span class="error-icon">❌</span>
                                    <p>Bağlantı hatası oluştu. Lütfen tekrar deneyin.</p>
                                    <small>Hata: ${error}</small>
                                </div>
                            `;
                        }
                    }
                });
            } else {
                console.error('showOrderDetailModal fonksiyonu bulunamadı');
                alert('Modal sistemi yüklenmemiş. Sayfayı yenileyin.');
            }
        },

        // Admin panel order detail view - düzeltilmiş
        viewAdminOrderDetails: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(e.target);
            const orderId = $btn.data('order-id');
            const siparisNo = $btn.data('siparis-no');

            console.log('Admin viewing order:', orderId, siparisNo);

            // Show loading state on button
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('⏳ Yükleniyor...');

            // Admin panelinde PHP'deki modal fonksiyonunu kullan
            if (window.showOrderDetailModal) {
                // Loading mesajı göster
                window.showOrderDetailModal('<div style="text-align: center; padding: 40px;"><div>Yükleniyor...</div></div>');

                // Fetch order details via AJAX
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'dastas_get_admin_order_details',
                        order_id: orderId,
                        siparis_no: siparisNo,
                        nonce: this.nonce
                    },
                    success: (response) => {
                        console.log('AJAX success:', response);
                        if (response.success && response.data && response.data.html) {
                            // Modal içeriğini güncelle
                            document.getElementById('modal-content').innerHTML = response.data.html;
                        } else {
                            console.error('AJAX error response:', response);
                            document.getElementById('modal-content').innerHTML = `
                                <div class="error-message" style="text-align: center; padding: 40px;">
                                    <span class="error-icon">❌</span>
                                    <p>Detay yüklenirken hata oluştu: ${response.data ? response.data.message : 'Bilinmeyen hata'}</p>
                                    <small>Order ID: ${orderId}, Sipariş No: ${siparisNo}</small>
                                </div>
                            `;
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('AJAX error:', xhr, status, error);
                        if (document.getElementById('modal-content')) {
                            document.getElementById('modal-content').innerHTML = `
                                <div class="error-message" style="text-align: center; padding: 40px;">
                                    <span class="error-icon">❌</span>
                                    <p>Bağlantı hatası oluştu. Lütfen tekrar deneyin.</p>
                                    <small>Hata: ${error}</small>
                                </div>
                            `;
                        }
                    },
                    complete: () => {
                        // Reset button state
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            } else {
                console.error('showOrderDetailModal fonksiyonu bulunamadı');
                alert('Modal sistemi yüklenmemiş. Sayfayı yenileyin.');
                $btn.prop('disabled', false).html(originalText);
            }
        },

        // Show admin order modal
        showAdminOrderModal: function() {
            const modalHtml = `
                <div id="admin-order-modal" class="dastas-admin-modal">
                    <div class="dastas-admin-modal-content">
                        <div class="dastas-admin-modal-header">
                            <h3 class="dastas-admin-modal-title">📋 Sipariş Detayları</h3>
                            <button class="dastas-admin-modal-close admin-modal-close" type="button">
                                ✕
                            </button>
                        </div>
                        <div id="admin-order-modal-body" class="dastas-admin-modal-body">
                            <div class="dastas-loading">
                                <div class="dastas-spinner"></div>
                                <p>Sipariş detayları yükleniyor...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
        },
        
        // Delete order
        deleteOrder: function(e) {
            const orderId = $(e.target).data('order-id');
            const row = $(e.target).closest('tr');
            
            if (!confirm('Bu siparişi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                return;
            }
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_delete_order',
                    order_id: orderId,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Sipariş silindi', 'success');
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        this.updateStats();
                    } else {
                        this.showNotification('Sipariş silinemedi: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: () => {
                    this.showNotification('Bir hata oluştu', 'error');
                }
            });
        },
        
        // Handle bulk actions
        handleBulkAction: function(e) {
            e.preventDefault();
            
            const action = $('#bulk-action-selector-top').val();
            const selectedOrders = $('input[name="order[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (action === '-1') {
                this.showNotification('Lütfen bir işlem seçin', 'warning');
                return;
            }
            
            if (selectedOrders.length === 0) {
                this.showNotification('Lütfen en az bir sipariş seçin', 'warning');
                return;
            }
            
            if (!confirm(`Seçili ${selectedOrders.length} siparişe "${action}" işlemini uygulamak istediğinizden emin misiniz?`)) {
                return;
            }
            
            this.showLoadingOverlay();
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_bulk_order_action',
                    bulk_action: action,
                    order_ids: selectedOrders,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(`${selectedOrders.length} sipariş başarıyla güncellendi`, 'success');
                        location.reload();
                    } else {
                        this.showNotification('Toplu işlem başarısız: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: () => {
                    this.showNotification('Bir hata oluştu', 'error');
                },
                complete: () => {
                    this.hideLoadingOverlay();
                }
            });
        },
        
        // Toggle select all checkboxes
        toggleSelectAll: function(e) {
            const isChecked = $(e.target).prop('checked');
            $('input[name="order[]"]').prop('checked', isChecked);
        },
        
        // Export orders to Excel
        exportOrders: function() {
            this.showLoadingOverlay();
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_export_orders',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Create download link
                        const link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        this.showNotification('Excel dosyası oluşturuldu ve indiriliyor', 'success');
                    } else {
                        this.showNotification('Export başarısız: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: () => {
                    this.showNotification('Export sırasında hata oluştu', 'error');
                },
                complete: () => {
                    this.hideLoadingOverlay();
                }
            });
        },
        
        // Show add bayi modal
        showAddBayiModal: function() {
            console.log('showAddBayiModal çağrıldı');
            
            const modal = $('#bayi-modal');
            const form = $('#bayi-form');
            
            if (modal.length === 0) {
                console.error('Bayi modal bulunamadı');
                this.showNotification('Modal bulunamadı', 'error');
                return;
            }
            
            if (form.length === 0) {
                console.error('Bayi form bulunamadı');
                this.showNotification('Form bulunamadı', 'error');
                return;
            }
            
            // Form'u reset et (güvenli)
            try {
                form[0].reset();
            } catch (e) {
                console.warn('Form reset hatası:', e);
                // Manuel olarak temizle
                form.find('input').val('');
            }
            
            modal.show();
            form.removeData('bayi-id');
            $('#modal-title').text('Yeni Bayi Ekle');
            $('#sifre-row').show();
            $('#sifre').prop('required', true);
            $('#sifre-change-row').hide();
            $('#yeni-sifre-row').hide();
            
            console.log('Modal başarıyla gösterildi');
        },
        
        // Handle bayi form submission
        handleBayiSubmit: function(e) {
            e.preventDefault();

            const form = $(e.target);
            const bayiId = form.data('bayi-id');
            const isEdit = !!bayiId;

            const formData = {
                action: 'dastas_admin_action',
                admin_action: isEdit ? 'update_bayi' : 'add_bayi',
                bayi_id: bayiId,
                bayi_kodu: form.find('#bayi_kodu').val(),
                bayi_adi: form.find('#bayi_adi').val(),
                sorumlu: form.find('#sorumlu').val(),
                telefon: form.find('#telefon').val(),
                email: form.find('#email').val(),
                nonce: this.nonce
            };

            if (!isEdit) {
                formData.sifre = form.find('#sifre').val();
            } else {
                // Edit mode - check if password change is requested
                const changePassword = form.find('#sifre_degistir').is(':checked');
                if (changePassword) {
                    formData.yeni_sifre = form.find('#yeni_sifre').val();
                    formData.sifre_degistir = '1';
                }
            }

            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.text();

            this.setLoadingState(submitBtn, 'Kaydediliyor...');

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(isEdit ? 'Bayi güncellendi' : 'Bayi eklendi', 'success');
                        this.closeModal();
                        location.reload();
                    } else {
                        this.showNotification('İşlem başarısız: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: () => {
                    this.showNotification('Bir hata oluştu', 'error');
                },
                complete: () => {
                    this.resetLoadingState(submitBtn, originalText);
                }
            });
        },
        
        // Edit bayi
        editBayi: function(e) {
            e.preventDefault();
            console.log('editBayi fonksiyonu çağrıldı', e);
            
            const bayiId = $(e.target).data('bayi-id');
            console.log('Bayi ID:', bayiId);

            if (!bayiId) {
                console.error('Bayi ID bulunamadı');
                this.showNotification('Bayi ID bulunamadı', 'error');
                return;
            }

            console.log('AJAX isteği gönderiliyor...');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_get_bayi_details',
                    bayi_id: bayiId,
                    nonce: this.nonce
                },
                success: (response) => {
                    console.log('AJAX başarılı:', response);
                    if (response.success) {
                        const bayi = response.data;
                        console.log('Bayi verileri:', bayi);

                        // Populate form
                        $('#bayi_kodu').val(bayi.bayi_kodu || '');
                        $('#bayi_adi').val(bayi.bayi_adi || '');
                        $('#sorumlu').val(bayi.sorumlu || '');
                        $('#telefon').val(bayi.telefon || '');
                        $('#email').val(bayi.email || '');

                        // Hide password fields for edit mode initially
                        $('#sifre-row').hide();
                        $('#sifre').prop('required', false);
                        $('#sifre-change-row').show();
                        $('#yeni-sifre-row').hide();
                        $('#yeni_sifre').prop('required', false);

                        // Reset password change checkbox
                        $('#sifre_degistir').prop('checked', false);

                        // Update modal title
                        $('#modal-title').text('Bayi Düzenle');

                        // Store bayi ID for update
                        $('#bayi-form').data('bayi-id', bayiId);

                        // Show modal
                        $('#bayi-modal').show();
                        console.log('Modal gösterildi');
                    } else {
                        console.error('AJAX yanıt hatası:', response);
                        this.showNotification('Bayi bilgileri yüklenemedi: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX hatası:', xhr, status, error);
                    this.showNotification('Bayi bilgileri yüklenirken hata oluştu: ' + error, 'error');
                }
            });
        },
        
        // Toggle bayi status
        toggleBayiStatus: function(e) {
            const bayiId = $(e.target).data('bayi-id');
            const isActivate = $(e.target).hasClass('activate-bayi');
            const newStatus = isActivate ? 1 : 0;

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_admin_action',
                    admin_action: 'toggle_bayi_status',
                    bayi_id: bayiId,
                    status: newStatus,
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(`Bayi ${isActivate ? 'aktifleştirildi' : 'pasifleştirildi'}`, 'success');
                        location.reload();
                    } else {
                        this.showNotification('Durum değiştirilemedi', 'error');
                    }
                }
            });
        },

        // Toggle password change fields
        togglePasswordChange: function(e) {
            const isChecked = $(e.target).is(':checked');
            if (isChecked) {
                $('#yeni-sifre-row').show();
                $('#yeni_sifre').prop('required', true);
            } else {
                $('#yeni-sifre-row').hide();
                $('#yeni_sifre').prop('required', false).val('');
            }
        },
        
        // Show notification modal
        showNotificationModal: function() {
            const modalHtml = `
                <div class="dastas-modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Bildirim Gönder</h2>
                        <form id="notification-form">
                            <table class="form-table">
                                <tr>
                                    <th><label for="notification_bayi">Alıcı</label></th>
                                    <td>
                                        <select id="notification_bayi" name="bayi_id" required>
                                            <option value="0">Tüm Bayiler</option>
                                            <!-- Bayiler buraya AJAX ile yüklenecek -->
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="notification_title">Başlık</label></th>
                                    <td><input type="text" id="notification_title" name="baslik" required></td>
                                </tr>
                                <tr>
                                    <th><label for="notification_message">Mesaj</label></th>
                                    <td><textarea id="notification_message" name="mesaj" rows="4" required></textarea></td>
                                </tr>
                                <tr>
                                    <th><label for="notification_type">Tür</label></th>
                                    <td>
                                        <select id="notification_type" name="tur">
                                            <option value="bilgi">Bilgi</option>
                                            <option value="uyari">Uyarı</option>
                                            <option value="siparis">Sipariş</option>
                                            <option value="sistem">Sistem</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="email_gonder" value="1">
                                            E-posta olarak da gönder
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            <p class="submit">
                                <button type="submit" class="button button-primary">Gönder</button>
                                <button type="button" class="button close-modal">İptal</button>
                            </p>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            this.loadBayilerForNotification();
        },
        
        // Load bayiler for notification dropdown
        loadBayilerForNotification: function() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_get_bayiler_list',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const select = $('#notification_bayi');
                        response.data.forEach(bayi => {
                            select.append(`<option value="${bayi.id}">${bayi.bayi_adi} (${bayi.bayi_kodu})</option>`);
                        });
                    }
                }
            });
        },
        
        // Send notification
        sendNotification: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.text();
            
            this.setLoadingState(submitBtn, 'Gönderiliyor...');
            
            const formData = {
                action: 'dastas_send_notification',
                bayi_id: form.find('#notification_bayi').val(),
                baslik: form.find('#notification_title').val(),
                mesaj: form.find('#notification_message').val(),
                tur: form.find('#notification_type').val(),
                email_gonder: form.find('input[name="email_gonder"]').is(':checked'),
                nonce: this.nonce
            };
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: formData,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Bildirim gönderildi', 'success');
                        this.closeModal();
                    } else {
                        this.showNotification('Bildirim gönderilemedi: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                },
                error: () => {
                    this.showNotification('Bir hata oluştu', 'error');
                },
                complete: () => {
                    this.resetLoadingState(submitBtn, originalText);
                }
            });
        },
        
        // Initialize DataTables
        initDataTables: function() {
            if ($.fn.DataTable) {
                $('.wp-list-table').DataTable({
                    pageLength: 25,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
                    },
                    columnDefs: [
                        { orderable: false, targets: [0, -1] } // Disable ordering on checkbox and actions columns
                    ]
                });
            }
        },
        
        // Initialize charts
        initCharts: function() {
            // This would initialize Chart.js or similar for dashboard charts
            this.initOrdersChart();
            this.initStatusChart();
        },
        
        // Initialize orders chart
        initOrdersChart: function() {
            const canvas = document.getElementById('orders-chart');
            if (!canvas || !window.Chart) return;
            
            // Sample data - would be loaded from server
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
                    datasets: [{
                        label: 'Siparişler',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },
        
        // Initialize status chart
        initStatusChart: function() {
            const canvas = document.getElementById('status-chart');
            if (!canvas || !window.Chart) return;
            
            const ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Beklemede', 'Onaylandı', 'Hazırlanıyor', 'Teslim Edildi'],
                    datasets: [{
                        data: [12, 8, 15, 25],
                        backgroundColor: ['#ffc107', '#17a2b8', '#6f42c1', '#28a745']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        },
        
        // Load system statistics
        loadSystemStats: function() {
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dastas_get_system_stats',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStatsDisplay(response.data);
                    }
                }
            });
        },
        
        // Update stats display
        updateStatsDisplay: function(stats) {
            $('.stat-box:nth-child(1) h3').text(stats.toplam_bayi || 0);
            $('.stat-box:nth-child(2) h3').text(stats.aktif_bayi || 0);
            $('.stat-box:nth-child(3) h3').text(stats.toplam_siparis || 0);
            $('.stat-box:nth-child(4) h3').text(stats.bekleyen_siparis || 0);
        },
        
        // Update stats after actions
        updateStats: function() {
            this.loadSystemStats();
        },
        
        // Show order details modal
        showOrderDetailsModal: function(orderData) {
            const modalHtml = this.generateOrderDetailsModal(orderData);
            $('body').append(modalHtml);
        },

        // Generate order details modal HTML
        generateOrderDetailsModal: function(order) {
            const statusText = {
                'beklemede': 'Beklemede',
                'onaylandi': 'Onaylandı',
                'hazirlaniyor': 'Hazırlanıyor',
                'sevk-edildi': 'Sevk Edildi',
                'teslim-edildi': 'Teslim Edildi',
                'iptal': 'İptal'
            };

            return `
                <div class="dastas-modal">
                    <div class="modal-content">
                        <span class="close" onclick="window.DastasAdmin.closeModal()">&times;</span>
                        <h2>Sipariş Detayları - ${order.siparis_no}</h2>
                        <div class="order-details-content">
                            <table class="form-table">
                                <tr>
                                    <th>Sipariş No:</th>
                                    <td><strong>${order.siparis_no}</strong></td>
                                </tr>
                                <tr>
                                    <th>Bayi:</th>
                                    <td>${order.bayi_adi}</td>
                                </tr>
                                <tr>
                                    <th>Tarih:</th>
                                    <td>${order.tarih}</td>
                                </tr>
                                <tr>
                                    <th>Ürün Sayısı:</th>
                                    <td>${order.urun_sayisi}</td>
                                </tr>
                                <tr>
                                    <th>Toplam m³:</th>
                                    <td>${order.toplam_m3}</td>
                                </tr>
                                <tr>
                                    <th>Durum:</th>
                                    <td><span class="order-status-${order.durum}">${statusText[order.durum] || order.durum}</span></td>
                                </tr>
                            </table>
                            <div class="modal-footer">
                                <button type="button" class="button button-secondary" onclick="window.DastasAdmin.closeModal()">Kapat</button>
                                <button type="button" class="button button-primary" onclick="window.location.href='${this.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=dastas-siparisler')}';">Siparişler Sayfasına Git</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },
        
        // Utility functions
        showLoadingOverlay: function() {
            if ($('.loading-overlay').length === 0) {
                $('body').append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
            }
        },
        
        hideLoadingOverlay: function() {
            $('.loading-overlay').remove();
        },
        
        showNotification: function(message, type = 'info') {
            // Create WordPress admin notice
            const notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss"></button>
                </div>
            `);
            
            $('.wrap h1').after(notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                notice.fadeOut();
            }, 5000);
            
            // Manual dismiss
            notice.find('.notice-dismiss').on('click', function() {
                notice.fadeOut();
            });
        },
        
        closeModal: function() {
            $('.dastas-modal').remove();
        },

        closeAdminModal: function() {
            $('.dastas-admin-modal').remove();
        },

        setLoadingState: function(button, text) {
            button.prop('disabled', true).text(text);
        },

        resetLoadingState: function(button, originalText) {
            button.prop('disabled', false).text(originalText);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.DastasAdmin.init();
    });

})(jQuery);

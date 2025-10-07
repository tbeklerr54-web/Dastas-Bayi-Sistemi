// Dastas Bayi Sistemi - Basit Admin Panel JavaScript

jQuery(document).ready(function($) {

    // Sipariş numarası tıklama
    $(document).on('click', '.order-number', function() {
        const siparisNo = $(this).data('siparis-no');
        if (siparisNo) {
            alert('Sipariş No: ' + siparisNo + '\nBu özellik yakında eklenecek!');
        }
    });

    // Hızlı işlem butonları
    $(document).on('click', '.quick-action-btn', function() {
        const action = $(this).data('action');
        handleQuickAction(action);
    });

});

// Hızlı işlemler
function handleQuickAction(action) {
    switch(action) {
        case 'bulk_approve':
            if (confirm('Bekleyen tüm siparişleri onaylamak istediğinizden emin misiniz?')) {
                alert('Toplu onay işlemi başlatıldı!');
            }
            break;
        case 'export_data':
            alert('Veri export işlemi başlatıldı!');
            break;
        case 'send_notification':
            alert('Bildirim gönderildi!');
            break;
        case 'system_maintenance':
            alert('Bakım modu aktifleştirildi!');
            break;
        default:
            alert('Bu işlem henüz tanımlanmamış!');
    }
}

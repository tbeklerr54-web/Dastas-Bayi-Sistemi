// Dastaş Bayi Admin JavaScript

jQuery(document).ready(function($) {
    
    // Form validasyonu
    $('.dastas-admin-form form').on('submit', function(e) {
        let isValid = true;
        let errors = [];
        
        // Gerekli alanları kontrol et
        $(this).find('input[required]').each(function() {
            if ($(this).val().trim() === '') {
                isValid = false;
                $(this).addClass('error');
                errors.push($(this).prev('label').text() + ' alanı zorunludur.');
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Bayi kodu kontrolü
        const bayiKodu = $('#bayi_kodu').val();
        if (bayiKodu && !/^[A-Z0-9]+$/.test(bayiKodu)) {
            isValid = false;
            $('#bayi_kodu').addClass('error');
            errors.push('Bayi kodu sadece büyük harf ve rakamlardan oluşmalıdır.');
        }
        
        // Kullanıcı adı kontrolü
        const kullaniciAdi = $('#kullanici_adi').val();
        if (kullaniciAdi && kullaniciAdi.length < 3) {
            isValid = false;
            $('#kullanici_adi').addClass('error');
            errors.push('Kullanıcı adı en az 3 karakter olmalıdır.');
        }
        
        // Şifre kontrolü
        const sifre = $('#sifre').val();
        if (sifre && sifre.length < 6) {
            isValid = false;
            $('#sifre').addClass('error');
            errors.push('Şifre en az 6 karakter olmalıdır.');
        }
        
        // Hata varsa formu gönderme
        if (!isValid) {
            e.preventDefault();
            showAdminMessage(errors.join('<br>'), 'error');
        }
    });
    
    // Input focus olayları
    $('input').on('focus', function() {
        $(this).removeClass('error');
    });
    
    // Bayi kodu otomatik büyük harf
    $('#bayi_kodu').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
    
    // Kullanıcı adı otomatik küçük harf
    $('#kullanici_adi').on('input', function() {
        $(this).val($(this).val().toLowerCase());
    });
    
    // Silme onayı
    $('.delete-bayi').on('click', function(e) {
        if (!confirm('Bu bayiyi silmek istediğinizden emin misiniz?')) {
            e.preventDefault();
        }
    });
    
    // Şifre değiştirme sayfası için kontroller
    if ($('#yeni_sifre').length) {
        $('#sifre_tekrar').on('input', function() {
            const yeniSifre = $('#yeni_sifre').val();
            const sifreTekrar = $(this).val();
            
            if (sifreTekrar && yeniSifre !== sifreTekrar) {
                $(this).addClass('error');
                $(this).next('.description').html('<span style="color: red;">Şifreler eşleşmiyor!</span>');
            } else {
                $(this).removeClass('error');
                $(this).next('.description').html('Yeni şifreyi tekrar giriniz.');
            }
        });
        
        // Şifre gücü göstergesi
        $('#yeni_sifre').on('input', function() {
            const sifre = $(this).val();
            let guc = 0;
            let mesaj = '';
            
            if (sifre.length >= 6) guc++;
            if (sifre.match(/[a-z]/)) guc++;
            if (sifre.match(/[A-Z]/)) guc++;
            if (sifre.match(/[0-9]/)) guc++;
            if (sifre.match(/[^a-zA-Z0-9]/)) guc++;
            
            switch(guc) {
                case 0:
                case 1:
                    mesaj = '<span style="color: red;">Çok zayıf</span>';
                    break;
                case 2:
                    mesaj = '<span style="color: orange;">Zayıf</span>';
                    break;
                case 3:
                    mesaj = '<span style="color: #f0ad4e;">Orta</span>';
                    break;
                case 4:
                    mesaj = '<span style="color: #5cb85c;">Güçlü</span>';
                    break;
                case 5:
                    mesaj = '<span style="color: green;">Çok güçlü</span>';
                    break;
            }
            
            $(this).next('.description').html('En az 6 karakter olmalıdır. Şifre gücü: ' + mesaj);
        });
    }
    
});

// Admin mesaj gösterme fonksiyonu
function showAdminMessage(message, type = 'success') {
    const alertClass = type === 'error' ? 'notice-error' : 'notice-success';
    const messageHtml = `
        <div class="notice ${alertClass} is-dismissible">
            <p>${message}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Bu bildirimi kapat.</span>
            </button>
        </div>
    `;
    
    $('.wrap h1').after(messageHtml);
    
    // Mesajı otomatik kapat
    setTimeout(function() {
        $('.notice').fadeOut();
    }, 5000);
}

// Tablo sıralama
jQuery(document).ready(function($) {
    if ($('.wp-list-table').length) {
        // Basit tablo sıralama
        $('.wp-list-table th').css('cursor', 'pointer').on('click', function() {
            const table = $(this).parents('table').eq(0);
            const rows = table.find('tbody tr').toArray().sort(compareRows($(this).index()));
            
            if ($(this).hasClass('asc')) {
                rows.reverse();
                $(this).removeClass('asc').addClass('desc');
            } else {
                $(this).removeClass('desc').addClass('asc');
            }
            
            for (let i = 0; i < rows.length; i++) {
                table.children('tbody').append(rows[i]);
            }
        });
    }
});

function compareRows(index) {
    return function(a, b) {
        const valA = getCellValue(a, index);
        const valB = getCellValue(b, index);
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.localeCompare(valB);
    };
}

function getCellValue(row, index) {
    return $(row).children('td').eq(index).text();
}
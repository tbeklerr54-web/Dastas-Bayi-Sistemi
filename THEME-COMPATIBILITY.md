# Dastas Bayi v2 - Tema Uyumluluğu Rehberi

## Yapılan İyileştirmeler

### 1. PHP 8+ Uyumluluğu
- Deprecated uyarıları global olarak gizleme
- ACF Ohio tema dynamic property uyarılarını özel filtreleme
- Production ortamında error suppression

### 2. Tema Uyumluluğu CSS
- Ohio ACF tema için özel stiller
- Bootstrap çakışması önleme
- jQuery UI dialog uyumluluğu
- Responsive tasarım iyileştirmeleri

### 3. Accessibility İyileştirmeleri
- Screen reader desteği
- High contrast mode desteği
- Keyboard navigation iyileştirmeleri
- Focus indicator'ları

### 4. Cross-browser Uyumluluğu
- Flexbox fallback'leri
- CSS Grid desteği
- IE11+ uyumluluk (eğer gerekirse)

## Dosya Yapısı

```
dastas-bayi-v2/
├── dastas-bayi-v2.php (Global deprecated warning handling)
├── core/
│   └── Plugin.php (Theme compatibility methods)
├── assets/
│   ├── theme-compatibility.css (NEW - Tema uyumluluğu)
│   ├── order-wizard-new.css (Modern wizard styles)
│   ├── order-wizard-new.js (Modern wizard logic)
│   └── ... (diğer assets)
└── modules/
    ├── orders/Orders.php (Modern wizard & list)
    ├── notifications/Notifications.php (Fixed field mapping)
    └── ... (diğer modüller)
```

## Tema Uyumluluğu Özellikleri

### Error Handling
- PHP 8+ deprecated warnings suppression
- ACF/Ohio specific error filtering
- Development vs production mode handling

### CSS Compatibility
- Theme-specific overrides
- CSS reset for plugin components
- Z-index management
- Print styles

### JavaScript Compatibility
- No global variable pollution
- Event delegation
- jQuery conflict resolution

## Test Edilmesi Gerekenler

### Ohio ACF Tema
- [ ] Dynamic property warnings gözükmüyor
- [ ] Order wizard düzgün çalışıyor
- [ ] Order list modern tasarım çalışıyor
- [ ] Admin area'da error yok

### Diğer Temalar
- [ ] Twenty Twenty serisi
- [ ] Astra, OceanWP gibi popüler temalar
- [ ] Custom temalar

### Browser Testing
- [ ] Chrome/Safari/Firefox
- [ ] Mobile devices
- [ ] Tablet screens

## Kullanım

Plugin aktif olduktan sonra otomatik olarak:
1. Deprecated warnings gizlenir (production'da)
2. Tema uyumluluğu CSS'i yüklenir
3. ACF Ohio tema için özel handling devreye girer

## Hata Ayıklama

Eğer hala deprecated warnings görünüyorsa:

1. `wp-config.php`'de `WP_DEBUG = false` olduğundan emin olun
2. Plugin'i deaktif/aktif edin
3. Browser cache'i temizleyin
4. WordPress object cache'i temizleyin

## Versiyon

- Plugin Version: 2.1.0
- Theme Compatibility: v1.0
- PHP Support: 7.4+ (8.0+ optimized)
- WordPress: 5.0+ (6.0+ recommended)

## Changelog

### v2.1.0 - Theme Compatibility Update
- ✅ PHP 8+ deprecated warning suppression
- ✅ ACF Ohio theme compatibility
- ✅ Global error handling
- ✅ Theme-specific CSS overrides
- ✅ Accessibility improvements
- ✅ Modern browser support
- ✅ Responsive design fixes
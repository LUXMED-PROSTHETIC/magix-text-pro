jQuery(document).ready(function($) {
    console.log('Magix Text Pro Admin JS başlatılıyor');

    // magixTextPro değişkeninin varlığını kontrol et
    if (typeof magixTextPro === 'undefined') {
        console.error('magixTextPro değişkeni tanımlı değil. WordPress Ajax ayarları düzgün çalışmayabilir.');
        // Devam et, sadece AJAX çalışmayacak
    }

    // Renk seçicileri başlat
    if ($.fn.wpColorPicker) {
        $('.color-picker').wpColorPicker();
    } else {
        console.error('WordPress Renk Seçici mevcut değil');
    }

    // Sayfa yüklendiğinde başlangıç durumu
    function initializePageState() {
        var activeTab = $('#tab-list-link').hasClass('nav-tab-active') ? 'list' : 'new';
        
        if (activeTab === 'list') {
            $('#tab-new').hide();
            $('#tab-list').show();
        } else {
            $('#tab-list').hide();
            $('#tab-new').show();
        }
    }

    // Sayfa yüklendiğinde ayarları yap
    initializePageState();

    // Sekme geçişleri
    $('.nav-tab-wrapper').on('click', '.nav-tab', function(e) {
        e.preventDefault();
        
        var targetTabId = $(this).attr('href');
        
        // Eğer düzenleme modundaysak ve kullanıcı liste sekmesine geçiyorsa onay iste
        if (targetTabId === '#tab-list' && $('#magix-text-id').val() !== '0' && $('#cancel-edit').is(':visible')) {
            if (!confirm('Düzenlemeleri kaydetmeden çıkmak istediğinize emin misiniz?')) {
                return;
            }
        }
        
        // Sekmeleri gizle/göster
        $('.tab-content').hide();
        $(targetTabId).show();
        
        // Sekme stillerini güncelle
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Eğer liste sekmesine geçildiyse formu sıfırla
        if (targetTabId === '#tab-list') {
            resetForm();
            $('#cancel-edit').hide();
        }
        
        // Eğer yeni ekleme sekmesine geçildiyse
        if (targetTabId === '#tab-new' && $('#magix-text-id').val() === '0') {
            resetForm();
            $('#magix-text-form-title').text('Yeni Magix Text Oluştur');
            $('#cancel-edit').show();
        }
    });

    // Düzenle butonuna tıklama
    $(document).on('click', '.edit-magix-text', function(e) {
        e.preventDefault();
        
        // Çift tıklama sorununu engelle
        if ($(this).data('processing')) {
            return;
        }
        
        $(this).data('processing', true);
        var id = $(this).data('id');
        var self = $(this);
        
        // Sekmeyi değiştir
        $('.nav-tab').removeClass('nav-tab-active');
        $('#tab-new-link').addClass('nav-tab-active');
        $('.tab-content').hide();
        $('#tab-new').show();
        
        // Form başlığını değiştir
        $('#magix-text-form-title').text('Magix Text Düzenle');
        $('#cancel-edit').show();
        
        // ID'yi ayarla
        $('#magix-text-id').val(id);
        
        // Küçük bir yükleniyor göster
        var loadingInfo = $('<div class="loading-info" style="color:#0073aa;">Veriler yükleniyor...</div>');
        $('#magix-text-form').prepend(loadingInfo);
        
        // Veri getirme AJAX isteği
        $.ajax({
            url: magixTextPro.ajaxurl,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'magix_text_pro_get',
                id: id,
                nonce: magixTextPro.nonce
            },
            success: function(response) {
                if (response && response.success && response.data) {
                    fillForm(response.data);
                } else {
                    showNotice('error', 'Kayıt bilgisi alınamadı');
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'Sunucu hatası: ' + error);
            },
            complete: function() {
                $('.loading-info').remove();
                self.data('processing', false);
            }
        });
    });
    
    // İptal butonuna tıklama
    $('#cancel-edit').on('click', function(e) {
        e.preventDefault();
        
        // Liste sekmesine geç
        $('.nav-tab').removeClass('nav-tab-active');
        $('#tab-list-link').addClass('nav-tab-active');
        $('.tab-content').hide();
        $('#tab-list').show();
        
        // Formu sıfırla
        resetForm();
    });
    
    // Sil butonuna tıklama
    $(document).on('click', '.delete-magix-text', function(e) {
        e.preventDefault();
        
        if (!confirm('Bu Magix Text\'i silmek istediğinizden emin misiniz?')) {
            return;
        }
        
        var id = $(this).data('id');
        
        // Silme AJAX isteği
        $.ajax({
            url: magixTextPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'magix_text_pro_delete',
                id: id,
                nonce: magixTextPro.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotice('error', 'Silme işlemi başarısız oldu');
                }
            },
            error: function() {
                showNotice('error', 'Sunucu hatası');
            }
        });
    });
    
    // Dönen metin alanı ekle
    $('#add-rotating-text').on('click', function() {
        var count = $('#rotating-texts-container .rotating-text-input').length;
        
        if (count >= 5) {
            alert('En fazla 5 dönen metin ekleyebilirsiniz.');
            return;
        }
        
        var html = '<div class="rotating-text-input">' +
                   '<input type="text" name="rotating_texts[]" required>' +
                   '<button type="button" class="remove-rotating-text" title="Sil">×</button>' +
                   '</div>';
                   
        $('#rotating-texts-container').append(html);
    });
    
    // Dönen metin alanı sil
    $(document).on('click', '.remove-rotating-text', function() {
        if ($('#rotating-texts-container .rotating-text-input').length > 1) {
            $(this).parent().remove();
        } else {
            alert('En az bir dönen metin olmalıdır.');
        }
    });
    
    // Form gönderimi
    $('#magix-text-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        
        // Boş input kontrolü
        var hasEmptyFields = false;
        form.find('input[required]').each(function() {
            if ($(this).val().trim() === '') {
                hasEmptyFields = true;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (hasEmptyFields) {
            showNotice('error', 'Lütfen tüm gerekli alanları doldurun.');
            return;
        }
        
        // Form verilerini oluştur
        var formData = new FormData(form[0]);
        formData.append('action', 'magix_text_pro_save');
        formData.append('nonce', magixTextPro.nonce);
        
        // Checkbox değerlerini ayarla
        var is_bold_fixed = $('#magix-text-fixed-bold').is(':checked') ? 1 : 0;
        var is_bold_rotating = $('#magix-text-rotating-bold').is(':checked') ? 1 : 0;
        var is_bold_suffix = $('#magix-text-suffix-bold').is(':checked') ? 1 : 0;
        
        formData.set('is_bold_fixed', is_bold_fixed);
        formData.set('is_bold_rotating', is_bold_rotating);
        formData.set('is_bold_suffix', is_bold_suffix);
        
        // Boş suffix'i doğru gönder
        if ($('#magix-text-suffix').val() === '') {
            formData.set('suffix', '');
        }
        
        // ID'yi kontrol et ve logla
        var recordId = $('#magix-text-id').val();
        console.log('Kaydedilecek kayıt ID:', recordId, 'Yeni kayıt mı:', recordId === '0');

        // AJAX ile kaydet
        $.ajax({
            url: magixTextPro.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                console.log('AJAX gönderiliyor... Veriler:', {
                    id: recordId,
                    name: $('#magix-text-name').val(),
                    fixed_text: $('#magix-text-fixed').val(),
                    alignment: $('#magix-text-alignment').val()
                });
                
                form.find('button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
                $('<div class="spinner is-active" style="float:none;margin-left:10px;display:inline-block;"></div>').insertAfter(form.find('button[type="submit"]'));
            },
            success: function(response) {
                console.log('AJAX yanıtı:', response);
                
                if (response.success) {
                    showNotice('success', response.data.message);
                    
                    // Kaydedilen ID'yi güncelle (yeni kayıt ise)
                    if (recordId === '0' && response.data.id) {
                        $('#magix-text-id').val(response.data.id);
                        console.log('Yeni ID atandı:', response.data.id);
                    }
                    
                    // Sayfayı yenile
                    setTimeout(function() {
                        // Sadece hızlı bir form yanıtından sonra yönlendirme gereklidir
                        console.log('Sayfa yenileniyor...');
                        window.location.href = window.location.pathname + '?page=magix-text-pro';
                    }, 2000);
                } else {
                    var errorMsg = 'Bir hata oluştu.';
                    if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                        console.error('AJAX hata yanıtı:', response.data);
                    }
                    showNotice('error', errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX request failed:', status, error, xhr.responseText);
                showNotice('error', 'Sunucu hatası: ' + error);
            },
            complete: function() {
                form.find('button[type="submit"]').prop('disabled', false).text('Kaydet');
                form.find('.spinner').remove();
            }
        });
    });
    
    /**
     * Formu doldur
     */
    function fillForm(data) {
        // Temel bilgiler
        $('#magix-text-id').val(data.id || 0);
        $('#magix-text-name').val(data.name || '');
        $('#magix-text-fixed').val(data.fixed_text || '');
        $('#magix-text-suffix').val(data.suffix || '');
        $('#magix-text-font-size').val(data.font_size || 30);
        
        // Font seçimi
        $('#magix-text-font-family').val(data.font_family || 'inherit');
        $('#magix-text-alignment').val(data.text_alignment || 'left');
        
        // Animasyon ayarları
        $('#magix-text-animation-duration').val(parseFloat(data.animation_duration) || 6);
        $('#magix-text-animation-delay').val(parseFloat(data.animation_delay) || 0.2);
        
        // Renk seçicileri güncelle
        setTimeout(function() {
            $('#magix-text-fixed-color').wpColorPicker('color', data.fixed_color || '#000000');
            $('#magix-text-rotating-color').wpColorPicker('color', data.rotating_color || '#000000');
            $('#magix-text-suffix-color').wpColorPicker('color', data.suffix_color || '#000000');
        }, 100);
        
        // Kalın seçenekleri
        $('#magix-text-fixed-bold').prop('checked', data.is_bold_fixed == '1');
        $('#magix-text-rotating-bold').prop('checked', data.is_bold_rotating == '1');
        $('#magix-text-suffix-bold').prop('checked', data.is_bold_suffix == '1');
        
        // Dönen metinleri ayarla
        var container = $('#rotating-texts-container');
        container.empty();
        
        if (data.rotating_texts && data.rotating_texts.length > 0) {
            // Her bir dönen metni ekle
            for (var i = 0; i < data.rotating_texts.length; i++) {
                var text = data.rotating_texts[i] || '';
                // HTML olarak güvenli metin
                text = text.replace(/&/g, '&amp;')
                          .replace(/</g, '&lt;')
                          .replace(/>/g, '&gt;')
                          .replace(/"/g, '&quot;')
                          .replace(/'/g, '&#039;');
                
                var html = '<div class="rotating-text-input">' +
                         '<input type="text" name="rotating_texts[]" value="' + text + '" required>' +
                         '<button type="button" class="remove-rotating-text" title="Sil">×</button>' +
                         '</div>';
                
                container.append(html);
            }
        } else {
            // En az bir dönen metin ekle
            container.html('<div class="rotating-text-input">' +
                         '<input type="text" name="rotating_texts[]" required>' +
                         '<button type="button" class="remove-rotating-text" title="Sil">×</button>' +
                         '</div>');
        }
    }
    
    /**
     * Bildirim mesajı
     */
    function showNotice(type, message) {
        // Eski bildirimleri kaldır
        $('.magix-text-notice').remove();
        
        // Yeni bildirimi ekle
        var notice = $('<div class="magix-text-notice ' + type + '">' + message + '</div>');
        $('#magix-text-form-title').after(notice);
        
        // Bildirimi kaydır
        $('html, body').animate({
            scrollTop: notice.offset().top - 50
        }, 300);
        
        // Bildirimi 3 saniye sonra kaldır
        setTimeout(function() {
            notice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Formu sıfırla
     */
    function resetForm() {
        // ID ve form başlığı
        $('#magix-text-id').val(0);
        $('#magix-text-form-title').text('Yeni Magix Text Oluştur');
        
        // Form elemanlarını temizle
        $('#magix-text-name').val('');
        $('#magix-text-fixed').val('');
        $('#magix-text-suffix').val('');
        
        // Font ayarlarını sıfırla
        $('#magix-text-font-family').val('inherit');
        $('#magix-text-alignment').val('left');
        
        // Sayısal değerleri varsayılanlara döndür
        $('#magix-text-font-size').val(30);
        $('#magix-text-animation-duration').val(6);
        $('#magix-text-animation-delay').val(0.2);
        
        // Renk seçicileri sıfırla
        $('#magix-text-fixed-color').wpColorPicker('color', '#000000');
        $('#magix-text-rotating-color').wpColorPicker('color', '#000000');
        $('#magix-text-suffix-color').wpColorPicker('color', '#000000');
        
        // Checkbox'ları sıfırla
        $('#magix-text-fixed-bold').prop('checked', false);
        $('#magix-text-rotating-bold').prop('checked', false);
        $('#magix-text-suffix-bold').prop('checked', false);
        
        // Dönen metinleri sıfırla
        $('#rotating-texts-container').html('<div class="rotating-text-input">' +
                                           '<input type="text" name="rotating_texts[]" required>' +
                                           '<button type="button" class="remove-rotating-text" title="Sil">×</button>' +
                                           '</div>');
    }
});

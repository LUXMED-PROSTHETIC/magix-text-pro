(function($) {
    'use strict';

    $(document).ready(function() {
        // Debug modu - gerekirse açılabilir
        var DEBUG = false;
        
        // Önbellek sorununu önlemek için Magix Text sürümü
        var MAGIX_VERSION = new Date().getTime();
        
        function debug(title, content) {
            if (DEBUG) {
                console.log('MAGIX-DEBUG [' + title + ']:', content);
            }
        }
        
        // Metinlerin sabit genişlik hesaplaması
        function calculateTextWidths() {
            $('.magix-text-container').each(function(index) {
                var $container = $(this);
                var $content = $container.find('.magix-text-content');
                var $fixedElement = $container.find('.magix-text-fixed');
                var $rotatingElement = $container.find('.magix-text-rotating');
                var $suffixElement = $container.find('.magix-text-suffix');
                
                // Dönen metinleri kontrol et
                if ($rotatingElement.length && $rotatingElement.attr('data-texts')) {
                    try {
                        var textsJSON = $rotatingElement.attr('data-texts');
                        var texts = JSON.parse(textsJSON);
                        
                        if (texts && texts.length > 0) {
                            // En geniş dönen metni bul
                            var $tempSpan = $('<span style="visibility:hidden;position:absolute;white-space:nowrap;font-family:' + 
                                $rotatingElement.css('font-family') + ';font-size:' + 
                                $rotatingElement.css('font-size') + ';font-weight:' + 
                                $rotatingElement.css('font-weight') + ';"></span>').appendTo('body');
                            
                            var maxWidth = 0;
                            for (var i = 0; i < texts.length; i++) {
                                $tempSpan.text(texts[i]);
                                var width = $tempSpan.width();
                                if (width > maxWidth) {
                                    maxWidth = width;
                                }
                            }
                            
                            // Minimum genişlik garantisi ve ekstra boşluk
                            maxWidth = Math.max(maxWidth, 10) + 10;
                            
                            // Dönen metin alanı için sabit genişlik ayarla
                            $rotatingElement.css({
                                'min-width': maxWidth + 'px',
                                'width': maxWidth + 'px'
                            });
                            
                            // Tüm container içeriğinin genişliğini hesapla
                            var fixedWidth = $fixedElement.outerWidth();
                            var suffixWidth = $suffixElement.length ? $suffixElement.outerWidth() : 0;
                            var totalWidth = fixedWidth + maxWidth + suffixWidth;
                            
                            // Content'in genişliğini sabit tut
                            $content.css({
                                'width': totalWidth + 'px',
                                'min-width': totalWidth + 'px'
                            });
                            
                            $tempSpan.remove();
                            debug('Container #' + index, 'Toplam şablon genişliği: ' + totalWidth + 'px, Dönen metin genişliği: ' + maxWidth + 'px');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                    }
                }
            });
            
            // Tüm içeriğin dikey hizalamasını ayarla
            fixVerticalAlignment();
        }
        
        // Dikey hizalama sorunlarını düzelt
        function fixVerticalAlignment() {
            $('.magix-text-fixed, .magix-text-rotating, .magix-text-suffix').css('vertical-align', 'middle');
        }
        
        // Şablon hizalama mantığını uygula
        function applyTemplateAlignment() {
            debug('Template Alignment', 'Şablon hizalama uygulanıyor');
            
            $('.magix-text-wrapper').each(function(index) {
                var $wrapper = $(this);
                var $content = $wrapper.find('.magix-text-content');
                var alignment = 'left'; // Varsayılan
                
                // Hizalama sınıfını kontrol et
                if ($wrapper.hasClass('center')) {
                    alignment = 'center';
                } else if ($wrapper.hasClass('right')) {
                    alignment = 'right';
                    
                    // Sağa hizalamada metinler arasında boşluk olmaması için
                    $content.css('justify-content', 'flex-end');
                } else {
                    // Sola hizalamada varsayılan flex davranışı
                    $content.css('justify-content', 'flex-start');
                }
                
                // CSS text-align özelliğini uygula
                $wrapper.css('text-align', alignment);
                
                debug('Wrapper #' + index, 'Hizalama: ' + alignment);
            });
        }
        
        // Her bir Magix Text öğesini başlat
        function initMagixTexts() {
            debug('Init', 'Magix Text öğeleri başlatılıyor');
            
            $('.magix-text-container').each(function() {
                var $container = $(this);
                
                // Zaten başlatıldıysa tekrar başlatma
                if ($container.data('initialized')) {
                    return;
                }
                
                var $rotatingElement = $container.find('.magix-text-rotating');
                
                // Başlatıldı olarak işaretle
                $container.data('initialized', true);
                
                // Animasyon ayarlarını al
                var duration = parseFloat($container.data('duration')) || 6;
                var delay = parseFloat($container.data('delay')) || 0.2;
                
                // Dönen metinleri al
                if ($rotatingElement.length) {
                    var textsAttr = $rotatingElement.attr('data-texts');
                    
                    if (textsAttr) {
                        try {
                            var texts = JSON.parse(textsAttr);
                            
                            if (texts && texts.length > 0) {
                                // İlk metni statik olarak ayarla
                                $rotatingElement.text(texts[0]);
                                
                                // Birden fazla metin varsa animasyon başlat
                                if (texts.length > 1) {
                                    // Rotasyon indeksi
                                    $container.data('current-index', 0);
                                    $container.data('is-animating', false);
                                    
                                    // Rotasyon döngüsü
                                    setInterval(function() {
                                        if (!$container.data('is-animating')) {
                                            rotateText($container, texts, delay);
                                        }
                                    }, duration * 1000);
                                }
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                        }
                    }
                }
            });
            
            // Tüm metinlerin genişliğini ve hizalamasını ayarla
            calculateTextWidths();
            applyTemplateAlignment();
        }
        
        // Metin döndürme fonksiyonu - Aynalama animasyonu
        function rotateText($container, texts, delay) {
            $container.data('is-animating', true);
            
            var $rotatingElement = $container.find('.magix-text-rotating');
            var currentIndex = $container.data('current-index') || 0;
            var nextIndex = (currentIndex + 1) % texts.length;
            var fadeTime = delay * 1000;
            
            // Sağa doğru dönerek kaybolma
            $rotatingElement.css({
                'transform': 'rotateY(90deg)',
                'opacity': '0'
            });
            
            // Metni değiştir
            setTimeout(function() {
                $rotatingElement.text(texts[nextIndex]);
                
                // Soldan dönerek görünme
                $rotatingElement.css({
                    'transform': 'rotateY(-90deg)',
                    'opacity': '0'
                });
                
                // Hafif gecikmeyle normal pozisyona dön
                setTimeout(function() {
                    $rotatingElement.css({
                        'transform': 'rotateY(0deg)',
                        'opacity': '1'
                    });
                    
                    // Animasyon tamamlandı
                    setTimeout(function() {
                        $container.data('is-animating', false);
                        $container.data('current-index', nextIndex);
                    }, fadeTime / 2);
                }, 50);
            }, fadeTime);
        }
        
        // Önbellek temizleme - Sayfa yüklendiğinde bir kez çalışır
        function clearCache() {
            // CSS ve JS dosyalarına sürüm parametresi ekleyerek önbelleği bypass et
            var links = document.getElementsByTagName('link');
            var scripts = document.getElementsByTagName('script');
            
            // CSS dosyaları
            for (var i = 0; i < links.length; i++) {
                var link = links[i];
                if (link.rel === 'stylesheet' && link.href.indexOf('magix-text') > -1) {
                    if (link.href.indexOf('?ver=') > -1) {
                        link.href = link.href.split('?ver=')[0] + '?ver=' + MAGIX_VERSION;
                    } else {
                        link.href = link.href + '?ver=' + MAGIX_VERSION;
                    }
                }
            }
            
            // JS dosyaları
            for (var j = 0; j < scripts.length; j++) {
                var script = scripts[j];
                if (script.src && script.src.indexOf('magix-text') > -1) {
                    if (script.src.indexOf('?ver=') > -1) {
                        script.src = script.src.split('?ver=')[0] + '?ver=' + MAGIX_VERSION;
                    } else {
                        script.src = script.src + '?ver=' + MAGIX_VERSION;
                    }
                }
            }
            
            debug('Cache', 'Önbellek temizlendi, sürüm: ' + MAGIX_VERSION);
        }
        
        // Başlangıç işlemleri
        clearCache(); // Önbelleği temizle
        initMagixTexts(); // Magix Text'leri başlat
        
        // Sayfa tam yüklendiğinde ve pencere boyutu değiştiğinde tekrar ayarla
        $(window).on('load resize', function() {
            setTimeout(function() {
                calculateTextWidths();
                applyTemplateAlignment();
                fixVerticalAlignment();
            }, 300);
        });
        
        // AJAX yüklemelerinde tekrar kontrol et
        $(document).on('ajaxComplete', function() {
            setTimeout(initMagixTexts, 100);
        });
    });
})(jQuery); 
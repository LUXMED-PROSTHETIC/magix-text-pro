<?php
/**
 * Ana eklenti sınıfı
 */
class Magix_Text_Pro {

    /**
     * Eklentiyi başlatır
     */
    public function run() {
        // Admin tarafı hook'ları
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX işleyicileri
        add_action('wp_ajax_magix_text_pro_save', array($this, 'ajax_save_magix_text'));
        add_action('wp_ajax_magix_text_pro_delete', array($this, 'ajax_delete_magix_text'));
        add_action('wp_ajax_magix_text_pro_get', array($this, 'ajax_get_magix_text'));
        
        // Ön yüz hook'ları
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // Shortcode'u kaydet
        add_shortcode('magix_text', array($this, 'magix_text_shortcode'));
    }

    /**
     * Admin menüsünü ekle
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Magix Text Pro', 'magix-text-pro'),
            __('Magix Text Pro', 'magix-text-pro'),
            'manage_options',
            'magix-text-pro',
            array($this, 'display_admin_page'),
            'dashicons-text',
            30
        );
    }

    /**
     * Admin stillerini kaydet
     */
    public function enqueue_admin_styles($hook) {
        if ('toplevel_page_magix-text-pro' !== $hook) {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('magix-text-pro-admin', MAGIX_TEXT_PRO_URL . 'admin/css/admin.css', array(), MAGIX_TEXT_PRO_VERSION);
    }

    /**
     * Admin scriptlerini kaydet
     */
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_magix-text-pro' !== $hook) {
            return;
        }
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('magix-text-pro-admin', MAGIX_TEXT_PRO_URL . 'admin/js/admin.js', array('jquery', 'wp-color-picker'), MAGIX_TEXT_PRO_VERSION, true);
        
        wp_localize_script('magix-text-pro-admin', 'magixTextPro', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('magix_text_pro_nonce'),
        ));
    }

    /**
     * Ön yüz stillerini kaydet
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style('magix-text-pro-public', MAGIX_TEXT_PRO_URL . 'public/css/public.css', array(), MAGIX_TEXT_PRO_VERSION);
    }

    /**
     * Ön yüz scriptlerini kaydet
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_script('magix-text-pro-public', MAGIX_TEXT_PRO_URL . 'public/js/public.js', array('jquery'), MAGIX_TEXT_PRO_VERSION, true);
    }

    /**
     * Admin sayfasını görüntüle
     */
    public function display_admin_page() {
        require_once MAGIX_TEXT_PRO_PATH . 'admin/admin-page.php';
    }

    /**
     * Magix Text kaydet (AJAX)
     */
    public function ajax_save_magix_text() {
        check_ajax_referer('magix_text_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetki hatası');
        }
        
        // POST verilerini debug için loglama
        // error_log('POST verileri: ' . print_r($_POST, true));
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $fixed_text = isset($_POST['fixed_text']) ? sanitize_text_field($_POST['fixed_text']) : '';
        
        // Dönen metinleri JSON olarak kaydediyoruz
        $rotating_texts = array();
        if (isset($_POST['rotating_texts']) && is_array($_POST['rotating_texts'])) {
            foreach ($_POST['rotating_texts'] as $text) {
                if (!empty($text)) {
                    $rotating_texts[] = sanitize_text_field($text);
                }
            }
        }
        
        // Son eki ayarla
        $suffix = '';
        if (isset($_POST['suffix'])) {
            $suffix = sanitize_text_field($_POST['suffix']);
        }
        
        // Tüm gerekli ayarları alıp doğrula
        $font_size = isset($_POST['font_size']) ? intval($_POST['font_size']) : 30;
        $font_family = isset($_POST['font_family']) ? sanitize_text_field($_POST['font_family']) : '';
        $text_alignment = isset($_POST['text_alignment']) ? sanitize_text_field($_POST['text_alignment']) : 'left';
        $fixed_color = isset($_POST['fixed_color']) ? sanitize_hex_color($_POST['fixed_color']) : '#000000';
        $rotating_color = isset($_POST['rotating_color']) ? sanitize_hex_color($_POST['rotating_color']) : '#000000';
        $suffix_color = isset($_POST['suffix_color']) ? sanitize_hex_color($_POST['suffix_color']) : '#000000';
        $is_bold_fixed = isset($_POST['is_bold_fixed']) ? intval($_POST['is_bold_fixed']) : 0;
        $is_bold_rotating = isset($_POST['is_bold_rotating']) ? intval($_POST['is_bold_rotating']) : 0;
        $is_bold_suffix = isset($_POST['is_bold_suffix']) ? intval($_POST['is_bold_suffix']) : 0;
        $animation_duration = isset($_POST['animation_duration']) ? floatval($_POST['animation_duration']) : 6;
        $animation_delay = isset($_POST['animation_delay']) ? floatval($_POST['animation_delay']) : 0.2;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'magix_text_pro';
        
        // Kaydetmeden önce veriyi loglama
        $data = array(
            'name' => $name,
            'fixed_text' => $fixed_text,
            'rotating_texts' => json_encode($rotating_texts),
            'suffix' => $suffix,
            'font_size' => $font_size,
            'font_family' => $font_family,
            'text_alignment' => $text_alignment,
            'fixed_color' => $fixed_color,
            'rotating_color' => $rotating_color,
            'suffix_color' => $suffix_color,
            'is_bold_fixed' => $is_bold_fixed,
            'is_bold_rotating' => $is_bold_rotating,
            'is_bold_suffix' => $is_bold_suffix,
            'animation_duration' => $animation_duration,
            'animation_delay' => $animation_delay
        );
        
        // error_log('Kaydedilecek veri: ' . print_r($data, true));
        
        $format = array(
            '%s', // name
            '%s', // fixed_text
            '%s', // rotating_texts
            '%s', // suffix
            '%d', // font_size
            '%s', // font_family
            '%s', // text_alignment
            '%s', // fixed_color
            '%s', // rotating_color
            '%s', // suffix_color
            '%d', // is_bold_fixed
            '%d', // is_bold_rotating
            '%d', // is_bold_suffix
            '%f', // animation_duration
            '%f'  // animation_delay
        );
        
        if ($id > 0) {
            // Güncelleme
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $id),
                $format,
                array('%d')
            );
            
            error_log('Magix Text Pro: Update işlemi: ID=' . $id . ', Sonuç=' . ($result !== false ? 'başarılı' : 'başarısız') . ', SQL Hatası: ' . $wpdb->last_error);
        } else {
            // Yeni kayıt
            $result = $wpdb->insert(
                $table_name,
                $data,
                $format
            );
            
            error_log('Magix Text Pro: Insert işlemi: Sonuç=' . ($result !== false ? 'başarılı, son ID: ' . $wpdb->insert_id : 'başarısız') . ', SQL Hatası: ' . $wpdb->last_error);
            
            if ($result !== false) {
                $id = $wpdb->insert_id;
            }
        }
        
        if ($result === false) {
            // Hata durumunda hata mesajını gönder
            error_log('Magix Text Pro: Kayıt hatası! SQL hatası: ' . $wpdb->last_error);
            wp_send_json_error(array(
                'message' => __('Kaydederken bir hata oluştu: ', 'magix-text-pro') . $wpdb->last_error,
                'sql_error' => $wpdb->last_error
            ));
        } else {
            error_log('Magix Text Pro: Kayıt başarılı! ID: ' . $id . ', Veri: ' . print_r($data, true));
            wp_send_json_success(array(
                'id' => $id,
                'message' => __('Magix Text başarıyla kaydedildi!', 'magix-text-pro')
            ));
        }
    }

    /**
     * Magix Text sil (AJAX)
     */
    public function ajax_delete_magix_text() {
        check_ajax_referer('magix_text_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetki hatası');
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error('Geçersiz ID');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'magix_text_pro';
        
        $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => __('Magix Text başarıyla silindi!', 'magix-text-pro')
        ));
    }

    /**
     * Magix Text getir (AJAX)
     */
    public function ajax_get_magix_text() {
        check_ajax_referer('magix_text_pro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            error_log('Magix Text Pro: Yetki hatası - get işlemi');
            wp_send_json_error(array('message' => 'Yetki hatası'));
            return;
        }
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        error_log('Magix Text Pro: Get isteği - ID: ' . $id);
        
        if ($id <= 0) {
            error_log('Magix Text Pro: Geçersiz ID: ' . $id);
            wp_send_json_error(array('message' => 'Geçersiz ID'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'magix_text_pro';
        
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
        error_log('Magix Text Pro: Çalıştırılan SQL: ' . $sql);
        
        $magix_text = $wpdb->get_row($sql, ARRAY_A);
        
        if (null === $magix_text) {
            error_log('Magix Text Pro: Kayıt bulunamadı - ID: ' . $id);
            wp_send_json_error(array('message' => 'Kayıt bulunamadı'));
            return;
        }
        
        error_log('Magix Text Pro: Bulunan kayıt: ' . print_r($magix_text, true));
        
        // Dönen metinleri JSON'dan diziye çevir
        if (isset($magix_text['rotating_texts']) && !empty($magix_text['rotating_texts'])) {
            $rotating_texts = json_decode($magix_text['rotating_texts'], true);
            if (is_array($rotating_texts)) {
                $magix_text['rotating_texts'] = $rotating_texts;
            } else {
                error_log('Magix Text Pro: Dönen metinler JSON çözme hatası: ' . json_last_error_msg());
                $magix_text['rotating_texts'] = array('');
            }
        } else {
            $magix_text['rotating_texts'] = array('');
        }
        
        // Verileri doğrula
        foreach (['name', 'fixed_text', 'fixed_color', 'rotating_color', 'suffix_color', 
                'font_family', 'text_alignment', 'font_size', 'animation_duration', 
                'animation_delay', 'is_bold_fixed', 'is_bold_rotating', 'is_bold_suffix'] as $key) {
            if (!isset($magix_text[$key])) {
                $magix_text[$key] = '';
                error_log('Magix Text Pro: Eksik veri alanı: ' . $key);
            }
        }
        
        // Başarılı yanıt gönder
        error_log('Magix Text Pro: Veri gönderiliyor: ' . json_encode($magix_text));
        wp_send_json_success($magix_text);
    }

    /**
     * Magix Text shortcode işleyicisi - Fixed-Width Container Yaklaşımı
     */
    public function magix_text_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
            ),
            $atts,
            'magix_text'
        );
        
        $id = intval($atts['id']);
        
        if ($id <= 0) {
            return '<p>' . __('Geçersiz Magix Text ID', 'magix-text-pro') . '</p>';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'magix_text_pro';
        
        $magix_text = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (null === $magix_text) {
            return '<p>' . __('Magix Text bulunamadı', 'magix-text-pro') . '</p>';
        }
        
        $rotating_texts = json_decode($magix_text['rotating_texts'], true);
        
        if (empty($rotating_texts)) {
            return '<p>' . __('Dönen metin bulunamadı', 'magix-text-pro') . '</p>';
        }
        
        // Varsayılan değerler ve ayarlar
        $animation_duration = isset($magix_text['animation_duration']) ? floatval($magix_text['animation_duration']) : 6;
        $animation_delay = isset($magix_text['animation_delay']) ? floatval($magix_text['animation_delay']) : 0.2;
        $font_size = intval($magix_text['font_size']);
        $font_family = !empty($magix_text['font_family']) ? $magix_text['font_family'] : 'inherit';
        $text_alignment = isset($magix_text['text_alignment']) ? $magix_text['text_alignment'] : 'left';
        $unique_id = 'magix-text-' . $id . '-' . uniqid();
        
        // Renk ve kalınlık ayarları
        $fixed_color = isset($magix_text['fixed_color']) ? $magix_text['fixed_color'] : '#000000';
        $rotating_color = isset($magix_text['rotating_color']) ? $magix_text['rotating_color'] : '#000000'; 
        $suffix_color = isset($magix_text['suffix_color']) ? $magix_text['suffix_color'] : '#000000';
        $fixed_bold = !empty($magix_text['is_bold_fixed']) ? 'font-weight: bold;' : '';
        $rotating_bold = !empty($magix_text['is_bold_rotating']) ? 'font-weight: bold;' : '';
        $suffix_bold = !empty($magix_text['is_bold_suffix']) ? 'font-weight: bold;' : '';
        
        // Ortak metin stili
        $common_style = 'font-size: ' . esc_attr($font_size) . 'px; font-family: ' . esc_attr($font_family) . ';';
        
        // WRAPPER: Şablon dış container - hizalama buradan yapılır
        $output = '<div class="magix-text-wrapper ' . esc_attr($text_alignment) . '" style="text-align: ' . esc_attr($text_alignment) . ';">';
        
        // CONTAINER: Ana container
        $output .= '<div class="magix-text-container" id="' . esc_attr($unique_id) . '" data-id="' . esc_attr($id) . '" data-duration="' . esc_attr($animation_duration) . '" data-delay="' . esc_attr($animation_delay) . '">';
        
        // CONTENT: Sabit genişliğe sahip içerik container'ı
        $output .= '<div class="magix-text-content">';
        
        // Sabit metin - Her zaman solda
        $output .= '<span class="magix-text-fixed" style="color: ' . esc_attr($fixed_color) . '; ' . $common_style . ' ' . $fixed_bold . '">' . esc_html($magix_text['fixed_text']) . '</span>';
        
        // Dönen metin - Sabit genişlikte (JavaScript ile ayarlanır)
        $output .= '<span class="magix-text-rotating" style="color: ' . esc_attr($rotating_color) . '; ' . $common_style . ' ' . $rotating_bold . '"';
        $output .= ' data-texts="' . esc_attr(json_encode($rotating_texts)) . '">' . esc_html($rotating_texts[0]) . '</span>';
        
        // Son ek (varsa)
        if (!empty($magix_text['suffix'])) {
            $output .= '<span class="magix-text-suffix" style="color: ' . esc_attr($suffix_color) . '; ' . $common_style . ' ' . $suffix_bold . '">' . esc_html($magix_text['suffix']) . '</span>';
        }
        
        $output .= '</div>'; // content kapat
        $output .= '</div>'; // container kapat
        $output .= '</div>'; // wrapper kapat
        
        return $output;
    }
} 
<?php
/**
 * Plugin Name: Magix Text Pro
 * Plugin URI: https://luxmedprotez.com
 * Description: Metin animasyonu oluşturarak sabit metinleri dönen değişken metinlerle birleştirin.
 * Version: 1.0.7
 * Author: LUXMED PROTEZ
 * Author URI: https://luxmedprotez.com
 * Text Domain: magix-text-pro
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Plugin yolu ve URL'sini tanımla
define('MAGIX_TEXT_PRO_PATH', plugin_dir_path(__FILE__));
define('MAGIX_TEXT_PRO_URL', plugin_dir_url(__FILE__));
define('MAGIX_TEXT_PRO_VERSION', '1.0.7');

// Gerekli dosyaları dahil et
require_once MAGIX_TEXT_PRO_PATH . 'includes/class-magix-text-pro.php';

// Önbellek temizleme - Eklenti yüklendiğinde çalışır
add_action('plugins_loaded', 'magix_text_pro_clear_cache', 5); // Önceliği 5 yaptım, diğer işlemlerden önce çalışsın
function magix_text_pro_clear_cache() {
    // Önbellekleri temizle
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Eklenti sürümünü stil ve script dosyalarına ekle
    add_filter('style_loader_src', 'magix_text_pro_add_version_to_style', 10, 2);
    add_filter('script_loader_src', 'magix_text_pro_add_version_to_script', 10, 2);
    
    // Transient önbellekleri temizle
    delete_transient('magix_text_pro_styles_cache');
    delete_transient('magix_text_pro_scripts_cache');
}

// Stil dosyalarına sürüm ekle
function magix_text_pro_add_version_to_style($src, $handle) {
    if (strpos($handle, 'magix-text') !== false) {
        if (strpos($src, '?ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        $src = add_query_arg('ver', MAGIX_TEXT_PRO_VERSION . '.' . time(), $src);
    }
    return $src;
}

// Script dosyalarına sürüm ekle
function magix_text_pro_add_version_to_script($src, $handle) {
    if (strpos($handle, 'magix-text') !== false) {
        if (strpos($src, '?ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        $src = add_query_arg('ver', MAGIX_TEXT_PRO_VERSION . '.' . time(), $src);
    }
    return $src;
}

// Veritabanını zorla sıfırlama seçeneği
if (isset($_GET['reset_magix_db']) && $_GET['reset_magix_db'] === 'yes' && current_user_can('manage_options')) {
    add_action('admin_init', 'magix_text_pro_force_reset_db');
}

function magix_text_pro_force_reset_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'magix_text_pro';
    
    // Tabloyu sil
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Versiyonu sıfırla
    delete_option('magix_text_pro_db_version');
    
    // Tabloyu yeniden oluştur
    magix_text_pro_activate();
    
    error_log('Magix Text Pro: Veritabanı tablosu zorla sıfırlandı ve yeniden oluşturuldu.');
    
    // Başarı mesajını göster
    add_action('admin_notices', 'magix_text_pro_reset_success_notice');
}

function magix_text_pro_reset_success_notice() {
    echo '<div class="notice notice-success is-dismissible"><p>Magix Text Pro veritabanı tablosu başarıyla sıfırlandı ve yeniden oluşturuldu!</p></div>';
}

// Eklentiyi başlat
function magix_text_pro_init() {
    $plugin = new Magix_Text_Pro();
    $plugin->run();
}
magix_text_pro_init();

// Aktivasyon hook'u
register_activation_hook(__FILE__, 'magix_text_pro_activate');
function magix_text_pro_activate() {
    // Veritabanı tablosunu oluştur
    global $wpdb;
    $table_name = $wpdb->prefix . 'magix_text_pro';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        fixed_text text NOT NULL,
        rotating_texts longtext NOT NULL,
        suffix text,
        font_size int DEFAULT 30 NOT NULL,
        font_family varchar(255) DEFAULT 'inherit',
        text_alignment varchar(10) DEFAULT 'left' NOT NULL,
        fixed_color varchar(20) DEFAULT '#000000' NOT NULL,
        rotating_color varchar(20) DEFAULT '#000000' NOT NULL,
        suffix_color varchar(20) DEFAULT '#000000' NOT NULL,
        is_bold_fixed tinyint(1) DEFAULT 0 NOT NULL,
        is_bold_rotating tinyint(1) DEFAULT 0 NOT NULL,
        is_bold_suffix tinyint(1) DEFAULT 0 NOT NULL,
        animation_duration float DEFAULT 6 NOT NULL,
        animation_delay float DEFAULT 0.2 NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Debug için log
    error_log('Magix Text Pro: Veritabanı tablosu oluşturuldu veya güncellendi.');
}

// Kaldırma hook'u
register_uninstall_hook(__FILE__, 'magix_text_pro_uninstall');
function magix_text_pro_uninstall() {
    // Veritabanı tablosunu sil
    global $wpdb;
    $table_name = $wpdb->prefix . 'magix_text_pro';
    
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Eklenti ayarlarını temizle
    delete_option('magix_text_pro_db_version');
    
    error_log('Magix Text Pro: Kaldırıldı, veritabanı tablosu silindi.');
}

// Deaktivasyon hook'u
register_deactivation_hook(__FILE__, 'magix_text_pro_deactivate');
function magix_text_pro_deactivate() {
    // Deaktivasyon işlemleri - şu an için bir şey yapmıyoruz
    error_log('Magix Text Pro: Deaktive edildi.');
}

// Eklenti güncellendiğinde veritabanını güncelle
add_action('plugins_loaded', 'magix_text_pro_update_db_check');
function magix_text_pro_update_db_check() {
    $current_version = get_option('magix_text_pro_db_version', '1.0.0');
    
    if (version_compare($current_version, '1.0.1', '<')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'magix_text_pro';
        
        // Animasyon sütunları olup olmadığını kontrol et
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'animation_duration'");
        if (empty($columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN animation_duration float DEFAULT 6 NOT NULL");
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN animation_delay float DEFAULT 0.2 NOT NULL");
            // error_log('Magix Text Pro veritabanı tablosu güncellendi: 1.0.1');
        }
        
        update_option('magix_text_pro_db_version', '1.0.1');
    }
    
    // Font family sütununu eklemek için versiyon kontrolü
    if (version_compare($current_version, '1.0.2', '<')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'magix_text_pro';
        
        // Font family sütunu olup olmadığını kontrol et
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'font_family'");
        if (empty($columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN font_family varchar(255) DEFAULT 'inherit'");
            // error_log('Magix Text Pro veritabanı tablosu güncellendi: 1.0.2 - Font family sütunu eklendi');
        }
        
        update_option('magix_text_pro_db_version', '1.0.2');
    }
    
    // Hizalama sütunu için versiyon kontrolü
    if (version_compare($current_version, '1.0.5', '<')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'magix_text_pro';
        
        // Text alignment sütunu olup olmadığını kontrol et
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'text_alignment'");
        if (empty($columns)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN text_alignment varchar(10) DEFAULT 'left' NOT NULL");
            // error_log('Magix Text Pro veritabanı tablosu güncellendi: 1.0.5 - Text alignment sütunu eklendi');
        }
        
        update_option('magix_text_pro_db_version', '1.0.5');
    }
} 
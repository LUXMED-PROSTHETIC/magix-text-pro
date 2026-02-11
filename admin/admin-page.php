<?php
if (!defined('ABSPATH')) {
    exit;
}

// URL parametrelerini alıyoruz
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Düzenleme modu için veri çek
$editing_data = null;
if ($action === 'edit' && $edit_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'magix_text_pro';
    $editing_data = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id),
        ARRAY_A
    );
    
    // Dönen metinleri diziye çevir
    if ($editing_data && isset($editing_data['rotating_texts'])) {
        $rotating_texts = json_decode($editing_data['rotating_texts'], true);
        if (is_array($rotating_texts)) {
            $editing_data['rotating_texts_array'] = $rotating_texts;
        } else {
            $editing_data['rotating_texts_array'] = array('');
        }
    }
}

// Hangi sekmenin aktif olacağını belirle
$active_tab = 'list';
if ($action === 'edit' && $editing_data) {
    $active_tab = 'new';
}
?>

<div class="wrap magix-text-pro-admin">
    <h1><?php _e('Magix Text Pro', 'magix-text-pro'); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="#tab-list" class="nav-tab <?php echo $active_tab === 'list' ? 'nav-tab-active' : ''; ?>" id="tab-list-link"><?php _e('Tüm Magix Textler', 'magix-text-pro'); ?></a>
        <a href="#tab-new" class="nav-tab <?php echo $active_tab === 'new' ? 'nav-tab-active' : ''; ?>" id="tab-new-link"><?php _e('Yeni Ekle', 'magix-text-pro'); ?></a>
    </h2>
    
    <div id="tab-list" class="tab-content" <?php echo $active_tab === 'list' ? '' : 'style="display: none;"'; ?>>
        <h3><?php _e('Tüm Magix Textler', 'magix-text-pro'); ?></h3>
        <table class="wp-list-table widefat fixed striped magix-text-table">
            <thead>
                <tr>
                    <th><?php _e('ID', 'magix-text-pro'); ?></th>
                    <th><?php _e('İsim', 'magix-text-pro'); ?></th>
                    <th><?php _e('Sabit Yazı', 'magix-text-pro'); ?></th>
                    <th><?php _e('Değişken Yazılar', 'magix-text-pro'); ?></th>
                    <th><?php _e('Shortcode', 'magix-text-pro'); ?></th>
                    <th><?php _e('İşlemler', 'magix-text-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'magix_text_pro';
                $magix_texts = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A);
                
                if (empty($magix_texts)) {
                    echo '<tr><td colspan="6">' . __('Henüz bir Magix Text oluşturulmadı.', 'magix-text-pro') . '</td></tr>';
                } else {
                    foreach ($magix_texts as $magix_text) {
                        $rotating_texts = json_decode($magix_text['rotating_texts'], true);
                        $rotating_text_display = implode(', ', array_slice($rotating_texts, 0, 3));
                        
                        if (count($rotating_texts) > 3) {
                            $rotating_text_display .= '...';
                        }
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($magix_text['id']) . '</td>';
                        echo '<td>' . esc_html($magix_text['name']) . '</td>';
                        echo '<td>' . esc_html($magix_text['fixed_text']) . '</td>';
                        echo '<td>' . esc_html($rotating_text_display) . '</td>';
                        echo '<td><code>[magix_text id="' . esc_attr($magix_text['id']) . '"]</code></td>';
                        echo '<td>';
                        echo '<a href="#" class="button edit-magix-text" data-id="' . esc_attr($magix_text['id']) . '">' . __('Düzenle', 'magix-text-pro') . '</a> ';
                        echo '<a href="#" class="button delete-magix-text" data-id="' . esc_attr($magix_text['id']) . '">' . __('Sil', 'magix-text-pro') . '</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div id="tab-new" class="tab-content" <?php echo $active_tab === 'new' ? '' : 'style="display: none;"'; ?>>
        <h3 id="magix-text-form-title">
            <?php echo ($action === 'edit' && $editing_data) ? __('Magix Text Düzenle', 'magix-text-pro') : __('Yeni Magix Text Oluştur', 'magix-text-pro'); ?>
        </h3>
        
        <form id="magix-text-form" class="magix-text-form">
            <input type="hidden" id="magix-text-id" name="id" value="<?php echo ($action === 'edit' && $editing_data) ? esc_attr($editing_data['id']) : '0'; ?>">
            
            <div class="magix-text-form-container">
                <div class="col-2">
                    <div class="form-group">
                        <label for="magix-text-name"><?php _e('Magix Text Adı', 'magix-text-pro'); ?></label>
                        <input type="text" id="magix-text-name" name="name" required value="<?php echo ($editing_data) ? esc_attr($editing_data['name']) : ''; ?>">
                        <p class="description"><?php _e('Admin panelde gösterilecek adı girin', 'magix-text-pro'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-fixed"><?php _e('Sabit Yazı', 'magix-text-pro'); ?></label>
                        <input type="text" id="magix-text-fixed" name="fixed_text" required value="<?php echo ($editing_data) ? esc_attr($editing_data['fixed_text']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-fixed-color"><?php _e('Sabit Yazı Rengi', 'magix-text-pro'); ?></label>
                        <input type="text" id="magix-text-fixed-color" name="fixed_color" value="<?php echo ($editing_data) ? esc_attr($editing_data['fixed_color']) : '#000000'; ?>" class="color-picker">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="magix-text-fixed-bold" name="is_bold_fixed" <?php echo ($editing_data && $editing_data['is_bold_fixed'] == '1') ? 'checked' : ''; ?>>
                            <?php _e('Kalın Yazı', 'magix-text-pro'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="col-2">
                    <div class="form-group">
                        <label><?php _e('Değişken Yazılar (en fazla 5)', 'magix-text-pro'); ?></label>
                        <div id="rotating-texts-container">
                            <?php 
                            if ($editing_data && isset($editing_data['rotating_texts_array']) && is_array($editing_data['rotating_texts_array'])) {
                                foreach ($editing_data['rotating_texts_array'] as $text) {
                                    echo '<div class="rotating-text-input">';
                                    echo '<input type="text" name="rotating_texts[]" required value="' . esc_attr($text) . '">';
                                    echo '<button type="button" class="remove-rotating-text" title="Sil">×</button>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="rotating-text-input">';
                                echo '<input type="text" name="rotating_texts[]" required>';
                                echo '<button type="button" class="remove-rotating-text" title="Sil">×</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" id="add-rotating-text" class="button"><?php _e('Değişken Yazı Ekle', 'magix-text-pro'); ?></button>
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-rotating-color"><?php _e('Değişken Yazı Rengi', 'magix-text-pro'); ?></label>
                        <input type="text" id="magix-text-rotating-color" name="rotating_color" value="<?php echo ($editing_data) ? esc_attr($editing_data['rotating_color']) : '#000000'; ?>" class="color-picker">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="magix-text-rotating-bold" name="is_bold_rotating" <?php echo ($editing_data && $editing_data['is_bold_rotating'] == '1') ? 'checked' : ''; ?>>
                            <?php _e('Kalın Yazı', 'magix-text-pro'); ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-2">
                    <div class="form-group">
                        <label for="magix-text-suffix"><?php _e('Son Ek (opsiyonel)', 'magix-text-pro'); ?></label>
                        <input type="text" id="magix-text-suffix" name="suffix" value="<?php echo ($editing_data) ? esc_attr($editing_data['suffix']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-suffix-color"><?php _e('Son Ek Rengi', 'magix-text-pro'); ?></label>
                        <input type="text" id="magix-text-suffix-color" name="suffix_color" value="<?php echo ($editing_data) ? esc_attr($editing_data['suffix_color']) : '#000000'; ?>" class="color-picker">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="magix-text-suffix-bold" name="is_bold_suffix" <?php echo ($editing_data && $editing_data['is_bold_suffix'] == '1') ? 'checked' : ''; ?>>
                            <?php _e('Kalın Yazı', 'magix-text-pro'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="col-2">
                    <div class="form-group">
                        <label for="magix-text-font-family"><?php _e('Yazı Fontu', 'magix-text-pro'); ?></label>
                        <select id="magix-text-font-family" name="font_family" class="regular-text">
                            <option value="inherit" <?php echo ($editing_data && $editing_data['font_family'] == 'inherit') ? 'selected' : ''; ?>><?php _e('Tema Fontu (Varsayılan)', 'magix-text-pro'); ?></option>
                            <option value="Arial, sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == 'Arial, sans-serif') ? 'selected' : ''; ?>>Arial</option>
                            <option value="Verdana, Geneva, sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == 'Verdana, Geneva, sans-serif') ? 'selected' : ''; ?>>Verdana</option>
                            <option value="Helvetica, sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == 'Helvetica, sans-serif') ? 'selected' : ''; ?>>Helvetica</option>
                            <option value="Tahoma, Geneva, sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == 'Tahoma, Geneva, sans-serif') ? 'selected' : ''; ?>>Tahoma</option>
                            <option value="'Trebuchet MS', Helvetica, sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == "'Trebuchet MS', Helvetica, sans-serif") ? 'selected' : ''; ?>>Trebuchet MS</option>
                            <option value="'Times New Roman', Times, serif" <?php echo ($editing_data && $editing_data['font_family'] == "'Times New Roman', Times, serif") ? 'selected' : ''; ?>>Times New Roman</option>
                            <option value="Georgia, serif" <?php echo ($editing_data && $editing_data['font_family'] == 'Georgia, serif') ? 'selected' : ''; ?>>Georgia</option>
                            <option value="'Courier New', Courier, monospace" <?php echo ($editing_data && $editing_data['font_family'] == "'Courier New', Courier, monospace") ? 'selected' : ''; ?>>Courier New</option>
                            <option value="'Palatino Linotype', 'Book Antiqua', Palatino, serif" <?php echo ($editing_data && $editing_data['font_family'] == "'Palatino Linotype', 'Book Antiqua', Palatino, serif") ? 'selected' : ''; ?>>Palatino</option>
                            <option value="'Segoe UI', Tahoma, Geneva, sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == "'Segoe UI', Tahoma, Geneva, sans-serif") ? 'selected' : ''; ?>>Segoe UI</option>
                            <option value="'Open Sans', sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == "'Open Sans', sans-serif") ? 'selected' : ''; ?>>Open Sans</option>
                            <option value="'Roboto', sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == "'Roboto', sans-serif") ? 'selected' : ''; ?>>Roboto</option>
                            <option value="'Montserrat', sans-serif" <?php echo ($editing_data && $editing_data['font_family'] == "'Montserrat', sans-serif") ? 'selected' : ''; ?>>Montserrat</option>
                        </select>
                        <p class="description"><?php _e('Tüm yazılar için font seçimi yapabilirsiniz.', 'magix-text-pro'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-alignment"><?php _e('Hizalama', 'magix-text-pro'); ?></label>
                        <select id="magix-text-alignment" name="text_alignment" class="regular-text">
                            <option value="left" <?php echo ($editing_data && $editing_data['text_alignment'] == 'left') ? 'selected' : ''; ?>><?php _e('Sola Yasla', 'magix-text-pro'); ?></option>
                            <option value="center" <?php echo ($editing_data && $editing_data['text_alignment'] == 'center') ? 'selected' : ''; ?>><?php _e('Ortala', 'magix-text-pro'); ?></option>
                            <option value="right" <?php echo ($editing_data && $editing_data['text_alignment'] == 'right') ? 'selected' : ''; ?>><?php _e('Sağa Yasla', 'magix-text-pro'); ?></option>
                        </select>
                        <p class="description"><?php _e('Metin grubunun sayfadaki hizalamasını seçin', 'magix-text-pro'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-font-size"><?php _e('Yazı Boyutu (px)', 'magix-text-pro'); ?></label>
                        <input type="number" id="magix-text-font-size" name="font_size" value="<?php echo ($editing_data) ? esc_attr($editing_data['font_size']) : '30'; ?>" min="10" max="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-animation-duration"><?php _e('Animasyon Süresi (saniye)', 'magix-text-pro'); ?></label>
                        <input type="number" id="magix-text-animation-duration" name="animation_duration" value="<?php echo ($editing_data) ? esc_attr($editing_data['animation_duration']) : '6'; ?>" min="1" max="20" step="0.5">
                        <p class="description"><?php _e('Bir kelimenin gösterilme ve değişme süresi', 'magix-text-pro'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="magix-text-animation-delay"><?php _e('Geçiş Hızı (saniye)', 'magix-text-pro'); ?></label>
                        <input type="number" id="magix-text-animation-delay" name="animation_delay" value="<?php echo ($editing_data) ? esc_attr($editing_data['animation_delay']) : '0.2'; ?>" min="0.1" max="2" step="0.1">
                        <p class="description"><?php _e('Kelimeler arası geçiş animasyonunun hızı', 'magix-text-pro'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary"><?php _e('Kaydet', 'magix-text-pro'); ?></button>
                <button type="button" id="cancel-edit" class="button button-secondary" style="<?php echo ($action === 'edit' || $active_tab === 'new') ? 'display: inline-block;' : 'display: none;'; ?> margin-left: 10px;"><?php _e('İptal', 'magix-text-pro'); ?></button>
            </div>
        </form>
    </div>
</div>
<script>
    // Sayfa yüklendiğinde
    jQuery(document).ready(function($) {
        console.log('Admin sayfası yükleniyor - v1.0.6');
        
        // Admin sayfası için AJAX ayarları
        window.magixTextPro = {
            ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('magix_text_pro_nonce'); ?>'
        };
    });
</script> 
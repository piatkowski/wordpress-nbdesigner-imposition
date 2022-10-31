<?php

namespace NBDImposer;

class PresetPostType extends Singleton
{
    const POST_TYPE = "nbd_impose_preset";
    const FIELD_PREFIX = "nbdi_";
    
    public $fields = array('width', 'height', 'rows', 'cols', 'spacing', 'scale', 'rotation_f', 'rotation_b', 'mode_f', 'mode_b');
    
    public function init()
    {
        add_action('init', array($this, 'register_post_type'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'manage_posts_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);
        add_action('save_post_' . self::POST_TYPE, array($this, 'save_post'));
        add_filter('bulk_actions-edit-' . self::POST_TYPE, array($this, 'bulk_actions'));
        add_filter('post_row_actions', array($this, 'duplicate_post_link'), 10, 2);
        add_action('admin_action_nbd_duplicate_impose_preset', array($this, 'duplicate_post'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 11);
    }
    
    public function register_post_type()
    {
        if (post_type_exists(self::POST_TYPE)) {
            return;
        }
        
        register_post_type(self::POST_TYPE,
            array(
                'labels' => array(
                    'name' => 'Impozycja',
                    'singular_name' => 'Ustawienia',
                    'menu_name' => 'Impozycja',
                    'all_items' => 'Impozycja',
                    'view_item' => 'Pokaż',
                    'add_new_item' => 'Dodaj nowy',
                    'add_new' => 'Dodaj nowy',
                    'edit_item' => 'Edytuj',
                    'update_item' => 'Zapisz',
                    'search_items' => 'Szukaj',
                    'not_found' => 'Brak zdefiniowanych ustawień',
                    'not_found_in_trash' => 'Nie znaleziono w koszu'
                ),
                'description' => 'Ustawienia dla impozycji',
                'public' => false,
                'hierarchical' => false,
                'show_ui' => true,
                'has_archive' => false,
                'map_meta_cap' => true,
                'capability_type' => 'post',
                'capabilities' => array(),
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'query_var' => true,
                'show_in_nav_menus' => false,
                'show_in_menu' => 'nbdesigner',
                'delete_with_user' => false,
                'supports' => array('title'),
                'register_meta_box_cb' => array($this, 'meta_boxes')
            )
        );
    }
    
    public function manage_posts_columns($columns)
    {
        $columns['nbdi_document_size'] = 'Rozmiar dokumentu';
        $columns['nbdi_grid_options'] = 'Rozmiar siatki';
        $columns['nbdi_scale'] = 'Skalowanie';
        unset($columns['date']);
        return $columns;
    }
    
    public function manage_posts_custom_column($column, $post_id)
    {
        switch ($column) {
            case 'nbdi_document_size':
                $width = get_post_meta($post_id, 'nbdi_width', true);
                $height = get_post_meta($post_id, 'nbdi_height', true);
                echo esc_html($width . ' [mm] x ' . $height . ' [mm]');
                break;
            case 'nbdi_grid_options':
                $rows = get_post_meta($post_id, 'nbdi_rows', true);
                $cols = get_post_meta($post_id, 'nbdi_cols', true);
                $spacing = get_post_meta($post_id, 'nbdi_spacing', true);
                echo esc_html($rows . ' x ' . $cols . ' / odstęp: ' . $spacing . ' mm');
                break;
            case 'nbdi_scale':
                $scale = get_post_meta($post_id, 'nbdi_scale', true);
                echo esc_html(($scale * 100)) . '%';
                break;
        }
    }
    
    public function meta_boxes()
    {
        add_meta_box(
            'nbd_impose_presets',
            'Impozycja - ustawienia',
            function ($post, $data) {
                include 'view/preset_metabox.php';
            },
            self::POST_TYPE,
            'advanced',
            'default'
        );
    }
    
    public function save_post($post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        
        global $post;
        
        if (isset($_POST['_wck_nonce']) && wp_verify_nonce($_POST['_wck_nonce'], self::POST_TYPE)) {
            
            $post_id = $post->ID;
            
            foreach ($this->fields as $field) {
                $key = self::FIELD_PREFIX . $field;
                if (isset($_POST[$key]) && !empty($_POST[$key])) {
                    update_post_meta($post_id, $key, (float)$_POST[$key]);
                }
            }
        }
    }
    
    public function bulk_actions($actions)
    {
        unset($actions['edit']);
        return $actions;
    }
    
    public function duplicate_post()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Dostęp zabrioniony!');
        }
        
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $nonce = isset($_GET['nbd_duplicate_impose_preset']) ? $_GET['nbd_duplicate_impose_preset'] : '';
        
        if ($post_id === 0 || !wp_verify_nonce($nonce, 'nbd_duplicate_impose_preset')) {
            $link = ' <a href="' . admin_url() . 'edit.php?post_type=' . self::POST_TYPE . '">Wróć do listy ustawień.</a>';
            wp_die('Nie można zduplikować ustawień. ' . $link);
        }
        
        $post = get_post($post_id);
        $user = wp_get_current_user();
        
        if (isset($post) && $post !== null) {
            
            $new_id = wp_insert_post(array(
                'post_author' => $user->ID,
                'post_name' => $post->post_name,
                'post_status' => 'draft',
                'post_title' => $post->post_title,
                'post_type' => $post->post_type
            ));
            
            foreach ($this->fields as $field) {
                $key = self::FIELD_PREFIX . $field;
                $meta_value = get_post_meta($post_id, $key, true);
                if (!empty($meta_value)) {
                    update_post_meta($new_id, $key, $meta_value);
                }
            }
        }
        wp_redirect(admin_url() . 'edit.php?post_type=' . self::POST_TYPE);
        exit;
    }
    
    public function duplicate_post_link($actions, $post)
    {
        if (current_user_can('manage_woocommerce') && $post->post_type === self::POST_TYPE) {
            $link = '<a href="';
            $link .= wp_nonce_url('admin.php?action=nbd_duplicate_impose_preset&post=' . $post->ID, 'nbd_duplicate_impose_preset', 'nbd_duplicate_impose_preset');
            $link .= '" rel="permalink">';
            $link .= 'Duplikuj';
            $link .= '</a>';
            $actions['duplicate'] = $link;
        }
        return $actions;
    }
    
    public function enqueue_scripts($hook)
    {
        global $post;
        $screen = get_current_screen();
        if ((($hook === 'post.php' || $hook === 'post-new.php') && $post->post_type === self::POST_TYPE)) {
            wp_enqueue_script(
                'nbd-impose-script',
                Plugin::url() . '/assets/js/admin.js',
                array('jquery')
            );
            wp_register_style('nbd-impose-styles', Plugin::url() . '/assets/css/admin.css');
            wp_enqueue_style('nbd-impose-styles');
        }
        if ($screen->base == 'nbdesigner_page_nbdesigner_manager_product') {
            wp_register_style('nbd-impose-styles', Plugin::url() . '/assets/css/admin.css');
            wp_enqueue_style('nbd-impose-styles');
        }
    }
    
}
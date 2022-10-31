<?php

namespace NBDImposer;

class NBDIntegrator extends Singleton
{
    public function init()
    {
        add_action('nbd_impose_manager_product', array($this, 'manager_product'));
        add_action('nbd_impose_manager_product_before', array($this, 'manager_product_before'));
        add_action('nbd_impose_manager_product_after', array($this, 'manager_product_after'));
        add_action('nbd_impose_detail_order_tab', array($this, 'detail_order_tab'));
        add_action('nbd_impose_detail_order_tab_content', array($this, 'detail_order_tab_content'));
        add_action('wp_ajax_imp_refresh_pdf_list', array($this, 'imp_refresh_pdf_list'));
    }
    
    public function imp_refresh_pdf_list()
    {
        foreach ($this->get_customer_pdfs() as $_pdf) {
            if (is_dir($_pdf)) continue;
            echo '<option value="' . $_pdf . '">' . basename($_pdf) . '</option>';
        }
        wp_die();
    }
    
    public function get_customer_pdfs()
    {
        $path = NBDESIGNER_CUSTOMER_DIR . '/' . $_GET['nbd_item_key'];
        return file_exists($path . '/pdfs') ? \Nbdesigner_IO::get_list_files($path . '/pdfs', 2) : array();
    }
    
    public function manager_product_before()
    {
        if (!empty($_POST) && !empty($_POST['nbdi_preset']) && is_array($_POST['nbdi_preset'])) {
            $this->save();
        }
        echo '<form method="post">';
        wp_nonce_field('nbdi_preset_product_save', 'nbdi_preset_product_nonce');
        echo '<div class="nbdi_submit"><button type="submit" class="button">Zapisz opcje impozycji</button></div>';
    }
    
    private function save()
    {
        if (
            isset($_POST['nbdi_preset_product_nonce'])
            && wp_verify_nonce($_POST['nbdi_preset_product_nonce'], 'nbdi_preset_product_save')
        ) {
            foreach ($_POST['nbdi_preset'] as $product_id => $preset_id) {
                $product_id = absint($product_id);
                $preset_id = absint($preset_id);
                if ($product_id > 0) {
                    update_post_meta($product_id, '_nbdi_presets', serialize(
                        array(
                            0 => $preset_id
                        )
                    ));
                }
            }
        }
    }
    
    public function manager_product_after()
    {
        echo '<div class="nbdi_submit"><button type="submit" class="button">Zapisz opcje impozycji</button></div>';
        echo '</form>';
    }
    
    public function detail_order_tab()
    {
        echo '<li><a href="#impose">Impozycja</a></li>';
    }
    
    public function detail_order_tab_content($product_id)
    {
        echo '<div id="impose">';
        echo '<div><form method="post" action="#impose"><table class="form-table"><tbody>';
        
        wp_nonce_field('nbdi_impose_generate', 'nbdi_impose_generate');
        
        echo '<tr valign="top">';
        echo '<th scope="row" class="titledesc"><label>Ustawienia impozycji</label></th>';
        echo '<td class="forminp forminp-text">';
        $this->manager_product(array(
            'id' => $product_id
        ));
        echo '</td></tr>';
        
        echo '<tr valign="top"><th scope="row" class="titledesc"><label>Wybierz plik PDF</label></th>';
        echo '<td class="forminp forminp-text"><select name="input_file">';
        foreach ($this->get_customer_pdfs() as $_pdf) {
            if (is_dir($_pdf)) continue;
            echo '<option value="' . $_pdf . '">' . basename($_pdf) . '</option>';
        }
        echo '</select>';
        //echo '<button type="button" id="imp_refresh_pdf_list">Odśwież</button>';
        ?>
        <script>
            jQuery(document).ready(function ($) {
                console.log("Ready!");
                $(document).on('click', 'a[href="#impose"]', function () {
                    console.log("Refreshing...");
                    $("select[name=input_file]").html('<option>wczytuję...</option>');
                    //$(this).prop('disabled', true);
                    $.get("/wp-admin/admin-ajax.php", {
                        "action": "imp_refresh_pdf_list",
                        "nbd_item_key": "<?php echo esc_html($_GET['nbd_item_key']); ?>"
                    }, function (data) {
                        $("select[name=input_file]").html(data);
                        console.log("Refresh done");
                        //$("#imp_refresh_pdf_list").prop('disabled', false);
                    });
                });
            });
        </script>
        </td>

        <tr>
            <th scope="row" class="titledesc"><label>Tryb generowania</label></th>
            <td class="forminp forminp-text">
                <select name="generator_mode">
                    <option value="0">Automatycznie</option>
                    <option value="1">1 strona</option>
                    <option value="2">2 strony</option>
                    <option value="3">Personalizacja</option>
                    <option value="4">Wielostronny</option>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row" class="titledesc"><label>Obróć dodatkowo pierwszą stronę</label></th>
            <td class="forminp forminp-text">
                <select name="first_page_rotation">
                    <option value="0">bez obrotu</option>
                    <option value="-90">90&deg; w lewo</option>
                    <option value="90">90&deg; w prawo</option>
                </select>
            </td>
        </tr>

        <?php

        echo '</tbody>';
        echo '<tfoot><tr><td>';
        echo '<button type="submit" class="button">Generuj</button>';
        echo '</td></tr></tfoot>';
        echo '</table></form></div>';
        echo '</div>';
        
        
        if (
            isset($_POST['nbdi_impose_generate'])
            && isset($_POST['input_file'])
            && isset($_POST['nbdi_preset'][$product_id])
            && wp_verify_nonce($_POST['nbdi_impose_generate'], 'nbdi_impose_generate')
            && in_array($_POST['input_file'], $this->get_customer_pdfs())
            && file_exists($_POST['input_file'])
        ) {
            $preset_id = absint($_POST['nbdi_preset'][$product_id]);
            
            $presets = PresetPostType::getInstance();
            $values = array();
            
            foreach ($presets->fields as $field) {
                $values[$field] = (float)get_post_meta($preset_id, $presets::FIELD_PREFIX . $field, true);
            }

            $options = array();

            if(isset($_POST['generator_mode'])) {
                $options['mode'] = absint($_POST['generator_mode']);
            }
            if(isset($_POST['first_page_rotation'])) {
                $options['first_page_rotation'] = (int)($_POST['first_page_rotation']);
            }

            $imposer = new PDFImposer($values['width'], $values['height'], $_POST['input_file'], $values['rows'], $values['cols'], $values['spacing'], $options);
            $imposer->impose(
                $values['scale'],
                array($values['mode_f'], $values['mode_b']),
                array($values['rotation_f'], $values['rotation_b'])
            );
            ob_clean();
            $basename = substr($_POST['input_file'], strrpos($_POST['input_file'], '/') + 1);
            $imposer->output('IMP_' . $basename, 'D');
            exit;
        } elseif (!empty($_POST)) {
            echo "<p>Wystąpił błąd podczas generowania impozycji!</p>";
        }
        
    }
    
    public function manager_product($data)
    {
        $product_id = $data['id'];
        
        $product_preset = unserialize(get_post_meta($product_id, '_nbdi_presets', true));
        $presets = Preset::getInstance()->getAll();
        
        echo '<select name="nbdi_preset[' . $product_id . ']" class="nbdi_select" placeholder="Opcje impozycji...">';
        echo '<option value="0" ' . selected(!isset($product_preset[0]) || $product_preset[0] == 0, true, false) . '> - </option>';
        foreach ($presets as $preset) {
            echo '<option value="' . $preset->ID . '" ' . selected($product_preset[0], $preset->ID, false) . '>' . $preset->post_title . ' (#' . $preset->ID . ')</option>';
        }
        echo '</select>';
    }
}
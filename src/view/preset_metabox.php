<?php
if (!defined('ABSPATH')) {
    exit;
}

use NBDImposer\Plugin;
use NBDImposer\PresetPostType;

wp_nonce_field(PresetPostType::POST_TYPE, '_wck_nonce');

global $post;

$presets = PresetPostType::getInstance();
$values = array();

foreach ($presets->fields as $field) {
    $values[$field] = (float)get_post_meta($post->ID, $presets::FIELD_PREFIX . $field, true);
}

?>

<p class="post-attributes-label-wrapper">
    <label class="post-attributes-label">Szerokość dokumentu wynikowego [mm]:</label>
</p>
<input type="number" name="nbdi_width" step="any" min="0" value="<?php echo isset($values['width']) ? $values['width'] : ''; ?>"
       required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Wysokość dokumentu wynikowego
        [mm]:</label></p>
<input type="number" name="nbdi_height" step="any" min="0" value="<?php echo isset($values['height']) ? $values['height'] : ''; ?>"
       required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Ilość wierszy:</label></p>
<input type="number" name="nbdi_rows" step="1" min="1" max="99"
       value="<?php echo isset($values['rows']) ? $values['rows'] : ''; ?>"
       required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Ilość kolumn:</label></p>
<input type="number" name="nbdi_cols" step="1" min="1" max="99"
       value="<?php echo isset($values['cols']) ? $values['cols'] : ''; ?>"
       required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Odległość między wierszami/kolumnami
        [mm]:</label></p>
<input type="number" name="nbdi_spacing" step="any" min="0"
       value="<?php echo isset($values['spacing']) ? $values['spacing'] : ''; ?>"
       required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Skala, wartość od 0 do 1:</label></p>
<input type="number" name="nbdi_scale" step="any" min="0" max="1"
       value="<?php echo isset($values['scale']) ? $values['scale'] : ''; ?>"
       required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Obrót stron (PRZÓD) (0, 90, 180,
        270):</label></p>
<input type="number" name="nbdi_rotation_f" min="0" step="90" max="270"
       value="<?php echo isset($values['rotation_f']) ? $values['rotation_f'] : ''; ?>" required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Obrót stron (TYŁ) (0, 90, 180,
        270):</label></p>
<input type="number" name="nbdi_rotation_b" min="0" step="90" max="270"
       value="<?php echo isset($values['rotation_b']) ? $values['rotation_b'] : ''; ?>" required>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Kierunek generowania (PRZÓD):</label></p>
<?php foreach (range(1, 8) as $mode): ?>
    <?php
    $active_mode = isset($values['mode_f']) ? $values['mode_f'] : 1;
    ?>
    <label class="inline-block">
        <input type="radio" name="nbdi_mode_f"
               value="<?php echo $mode; ?>" <?php echo $mode == $active_mode ? 'checked="checked"' : '' ?>>
        <img src="<?php echo Plugin::url(); ?>/assets/img/<?php echo $mode; ?>.png" class="imgf" alt="">
    </label>
<?php endforeach; ?>
<p class="post-attributes-label-wrapper"><label class="post-attributes-label">Kierunek generowania (TYŁ):</label></p>
<?php foreach (range(1, 8) as $mode): ?>
    <?php
    $active_mode = isset($values['mode_b']) ? $values['mode_b'] : 1;
    ?>
    <label class="inline-block">
        <input type="radio" name="nbdi_mode_b"
               value="<?php echo $mode; ?>" <?php echo $mode == $active_mode ? 'checked="checked"' : '' ?>>
        <img src="<?php echo Plugin::url(); ?>/assets/img/<?php echo $mode; ?>.png" class="imgb" alt="">
    </label>
<?php endforeach; ?>

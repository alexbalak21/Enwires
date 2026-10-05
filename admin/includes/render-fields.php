<?php
// HTML rendering for each field type in the schema. All functions echo
// directly (keeps edit.php's main loop simple to read).

function bracket_name($prefix, $path) {
    $segments = explode('.', $path);
    return $prefix . implode('', array_map(fn($s) => "[$s]", $segments));
}

function render_field_label($label, $forId = null) {
    $for = $forId ? ' for="' . htmlspecialchars($forId) . '"' : '';
    echo '<label class="field-label"' . $for . '>' . htmlspecialchars($label) . '</label>';
}

function render_text_field($field, $value) {
    $name = bracket_name('field', $field['path']);
    $id = 'f_' . str_replace('.', '_', $field['path']);
    echo '<div class="field">';
    render_field_label($field['label'], $id);
    echo '<input type="text" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars((string)$value) . '">';
    echo '</div>';
}

function render_textarea_field($field, $value) {
    $name = bracket_name('field', $field['path']);
    $id = 'f_' . str_replace('.', '_', $field['path']);
    echo '<div class="field">';
    render_field_label($field['label'], $id);
    echo '<textarea id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" rows="3">' . htmlspecialchars((string)$value) . '</textarea>';
    echo '</div>';
}

function render_repeater_field($field, $rows) {
    $path = $field['path'];
    $itemLabel = $field['item_label'] ?? 'Item';
    echo '<div class="field repeater">';
    render_field_label($field['label']);
    echo '<div class="repeater-rows">';
    foreach ($rows as $index => $row) {
        $rowName = bracket_name('field', $path) . "[$index]";
        echo '<div class="repeater-row">';
        echo '<div class="repeater-row-head"><span>' . htmlspecialchars($itemLabel) . ' ' . ($index + 1) . '</span>';
        echo '<span class="repeater-row-actions">';
        echo '<button type="submit" name="action" value="move_up:' . htmlspecialchars($path) . ':' . $index . '" class="btn-icon" title="Move up">&uarr;</button>';
        echo '<button type="submit" name="action" value="move_down:' . htmlspecialchars($path) . ':' . $index . '" class="btn-icon" title="Move down">&darr;</button>';
        echo '<button type="submit" name="action" value="remove:' . htmlspecialchars($path) . ':' . $index . '" class="btn-icon btn-danger" title="Remove" onclick="return confirm(\'Remove this item?\')">&times;</button>';
        echo '</span></div>';
        foreach ($field['item_fields'] as $sub) {
            $subName = $rowName . '[' . $sub['name'] . ']';
            $subValue = $row[$sub['name']] ?? '';
            echo '<div class="field">';
            render_field_label($sub['label']);
            if ($sub['type'] === 'textarea') {
                echo '<textarea name="' . htmlspecialchars($subName) . '" rows="2">' . htmlspecialchars((string)$subValue) . '</textarea>';
            } else {
                echo '<input type="text" name="' . htmlspecialchars($subName) . '" value="' . htmlspecialchars((string)$subValue) . '">';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="submit" name="action" value="add:' . htmlspecialchars($path) . '" class="btn-secondary">+ Add ' . htmlspecialchars(strtolower($itemLabel)) . '</button>';
    echo '</div>';
}

function render_gallery_field($field, $rows, $assetsBaseUrl) {
    $path = $field['path'];
    echo '<div class="field repeater gallery-field">';
    render_field_label($field['label']);
    echo '<div class="repeater-rows gallery-rows">';
    foreach ($rows as $index => $row) {
        $src = $row['src'] ?? '';
        $alt = $row['alt'] ?? '';
        $rowName = bracket_name('field', $path) . "[$index]";
        echo '<div class="repeater-row gallery-row">';
        if ($src !== '') {
            echo '<img class="gallery-thumb" src="' . htmlspecialchars($assetsBaseUrl . 'assets/img/' . $src) . '" alt="">';
            echo '<p class="gallery-filename">' . htmlspecialchars($src) . '</p>';
        } else {
            echo '<div class="gallery-thumb gallery-thumb-empty">No image yet</div>';
        }
        echo '<input type="hidden" name="' . htmlspecialchars($rowName) . '[src]" value="' . htmlspecialchars($src) . '">';
        echo '<div class="field">';
        render_field_label($src !== '' ? 'Replace image' : 'Upload image');
        echo '<input type="file" name="upload[' . htmlspecialchars($path) . '][' . $index . ']" accept="image/jpeg,image/png,image/webp">';
        echo '</div>';
        echo '<div class="field">';
        render_field_label('Alt text (describe the photo)');
        echo '<input type="text" name="' . htmlspecialchars($rowName) . '[alt]" value="' . htmlspecialchars($alt) . '">';
        echo '</div>';
        echo '<div class="repeater-row-actions">';
        echo '<button type="submit" name="action" value="move_up:' . htmlspecialchars($path) . ':' . $index . '" class="btn-icon" title="Move up">&uarr;</button>';
        echo '<button type="submit" name="action" value="move_down:' . htmlspecialchars($path) . ':' . $index . '" class="btn-icon" title="Move down">&darr;</button>';
        echo '<button type="submit" name="action" value="remove:' . htmlspecialchars($path) . ':' . $index . '" class="btn-icon btn-danger" title="Remove" onclick="return confirm(\'Remove this image?\')">&times;</button>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="submit" name="action" value="add:' . htmlspecialchars($path) . '" class="btn-secondary">+ Add image</button>';
    echo '</div>';
}

function render_image_field($field, $value, $assetsBaseUrl) {
    $path = $field['path'];
    $src = $value['src'] ?? '';
    $alt = $value['alt'] ?? '';
    $caption = $value['caption'] ?? null;
    echo '<div class="field image-field">';
    render_field_label($field['label']);
    echo '<div class="repeater-row gallery-row">';
    if ($src !== '') {
        echo '<img class="gallery-thumb" src="' . htmlspecialchars($assetsBaseUrl . 'assets/img/' . $src) . '" alt="">';
        echo '<p class="gallery-filename">' . htmlspecialchars($src) . '</p>';
    } else {
        echo '<div class="gallery-thumb gallery-thumb-empty">No image yet</div>';
    }
    echo '<input type="hidden" name="' . bracket_name('field', $path) . '[src]" value="' . htmlspecialchars($src) . '">';
    echo '<div class="field">';
    render_field_label($src !== '' ? 'Replace image' : 'Upload image');
    echo '<input type="file" name="upload_single[' . htmlspecialchars($path) . ']" accept="image/jpeg,image/png,image/webp">';
    echo '</div>';
    echo '<div class="field">';
    render_field_label('Alt text (describe the photo)');
    echo '<input type="text" name="' . bracket_name('field', $path) . '[alt]" value="' . htmlspecialchars($alt) . '">';
    echo '</div>';
    if ($caption !== null) {
        echo '<div class="field">';
        render_field_label('Caption (shown under the photo)');
        echo '<input type="text" name="' . bracket_name('field', $path) . '[caption]" value="' . htmlspecialchars($caption) . '">';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
}

function render_field($field, $fullData, $assetsBaseUrl) {
    $value = cs_get($fullData, $field['path']);
    switch ($field['type']) {
        case 'text':
            render_text_field($field, $value ?? '');
            break;
        case 'textarea':
            render_textarea_field($field, $value ?? '');
            break;
        case 'repeater':
            render_repeater_field($field, is_array($value) ? $value : []);
            break;
        case 'gallery':
            render_gallery_field($field, is_array($value) ? $value : [], $assetsBaseUrl);
            break;
        case 'image':
            render_image_field($field, is_array($value) ? $value : [], $assetsBaseUrl);
            break;
    }
}

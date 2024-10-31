<?php

namespace Devnet\PPH\Admin;

/**
 * Settings API wrapper class.
 * 
 * @updated    28.08.2024.
 */

class Settings_API
{

    /**
     * settings sections array
     *
     * @var array
     */
    protected $settings_sections = [];

    /**
     * Settings fields array
     *
     * @var array
     */
    protected $settings_fields = [];

    private $plugin_slug;

    public function __construct($plugin_slug = '')
    {
        $this->plugin_slug = $plugin_slug;
    }

    /**
     * Set settings sections
     *
     * @param array   $sections setting sections array
     */
    public function set_sections($sections)
    {
        $this->settings_sections = $sections;

        return $this;
    }

    /**
     * Add a single section
     *
     * @param array   $section
     */
    public function add_section($section)
    {
        $this->settings_sections[] = $section;

        return $this;
    }

    /**
     * Set settings fields
     *
     * @param array   $fields settings fields array
     */
    public function set_fields($fields)
    {
        $this->settings_fields = $fields;

        return $this;
    }

    public function add_field($section, $field)
    {
        $defaults = [
            'name'  => '',
            'label' => '',
            'desc'  => '',
            'type'  => 'text'
        ];

        $arg = wp_parse_args($field, $defaults);
        $this->settings_fields[$section][] = $arg;

        return $this;
    }

    /**
     * Initialize and registers the settings sections and fields to WordPress
     *
     * Usually this should be called at `admin_init` hook.
     *
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     */
    public function admin_init()
    {
        //register settings sections
        foreach ($this->settings_sections as $section) {
            if (false == get_option($section['id'])) {
                add_option($section['id']);
            }

            if (isset($section['desc']) && !empty($section['desc'])) {
                $section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
                $callback        = function () use ($section) {
                    $desc = str_replace('"', '\"', $section['desc']);
                    echo esc_html($desc);
                };
            } elseif (isset($section['callback'])) {
                $callback = $section['callback'];
            } else {
                $callback = null;
            }

            add_settings_section($section['id'], $section['title'], $callback, $section['id']);
        }

        //register settings fields
        foreach ($this->settings_fields as $section => $field) {
            foreach ($field as $option) {
                $name     = $option['name'];
                $type     = isset($option['type']) ? $option['type'] : 'text';
                $label    = isset($option['label']) ? $option['label'] : '';
                $callback = isset($option['callback']) ? $option['callback'] : [$this, 'callback_' . $type];

                $args = [
                    'id'                => $name,
                    'class'             => isset($option['class']) ? $option['class'] : $name,
                    'label_for'         => "{$section}[{$name}]",
                    'desc'              => isset($option['desc']) ? $option['desc'] : '',
                    'name'              => $label,
                    'section'           => $section,
                    'size'              => isset($option['size']) ? $option['size'] : null,
                    'options'           => isset($option['options']) ? $option['options'] : '',
                    'optgroup'          => isset($option['optgroup']) ? $option['optgroup'] : [],
                    'std'               => isset($option['default']) ? $option['default'] : '',
                    'sanitize_callback' => isset($option['sanitize_callback']) ? $option['sanitize_callback'] : '',
                    'type'              => $type,
                    'placeholder'       => isset($option['placeholder']) ? $option['placeholder'] : '',
                    'min'               => isset($option['min']) ? $option['min'] : '',
                    'max'               => isset($option['max']) ? $option['max'] : '',
                    'step'              => isset($option['step']) ? $option['step'] : '',
                    'multiple'          => isset($option['multiple']) ? $option['multiple'] : '',
                    'unit'              => isset($option['unit']) ? $option['unit'] : '',
                    'rows'              => isset($option['rows']) ? $option['rows'] : '',
                    'cols'              => isset($option['cols']) ? $option['cols'] : '',
                    'disabled'          => isset($option['disabled']) ? $option['disabled'] : '',
                    'pro_plan'          => isset($option['pro_plan']) ? $option['pro_plan'] : '',
                    'fields'             => isset($option['fields']) ? $option['fields'] : [],
                    'repeatable'        => isset($option['repeatable']) ? $option['repeatable'] : false,
                ];

                if ($args['pro_plan']) {
                    $args['class'] .= ' ' . $args['pro_plan'];
                }

                add_settings_field("{$section}[{$name}]", $label, $callback, $section, $section, $args);
            }
        }

        // creates our settings in the options table
        foreach ($this->settings_sections as $section) {
            register_setting($section['id'], $section['id'], [$this, 'sanitize_options']);
        }
    }

    public function kses_args($additional_args = [])
    {
        $args = [
            'br'    => [],
            'input' => [
                'type'     => [],
                'readonly' => [],
                'value'    => []
            ],
            'a'     => [
                'href'   => [],
                'target' => []
            ]
        ];

        return $args + $additional_args;
    }

    /**
     * Get field description for display
     *
     * @param array   $args settings field args
     */
    public function get_field_description($args)
    {
        if (!empty($args['desc'])) {
            $desc = sprintf('<p class="description">%s</p>', wp_kses($args['desc'], $this->kses_args()));
        } else {
            $desc = '';
        }

        return $desc;
    }

    /**
     * Displays subtitle or descriptio settings group
     *
     * @param array   $args settings field args
     */
    public function callback_info($args)
    {
        printf('<p class="info-description">%1$s</p>', wp_kses($args['desc'], $this->kses_args()));
    }

    /**
     * Displays a hidden field for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_hidden($args)
    {
        $auto_value = $args['value'] ?? null;
        $_value = $this->get_option($args['id'], $args['section'], $args['std']);

        $value = $auto_value !== null || $auto_value === 0 ? $auto_value : $_value;

        $type  = isset($args['type']) ? $args['type'] : 'hidden';

        printf(
            '<input type="%1$s" class="%2$s-hidden" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" />',
            esc_attr($type),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($value)
        );
    }

    /**
     * Displays a text field for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_text($args)
    {
        $value       = $this->get_option($args['id'], $args['section'], $args['std']);
        $size        = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $type        = isset($args['type']) ? $args['type'] : 'text';
        $placeholder = empty($args['placeholder']) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $maxlength   = isset($args['max']) ? $args['max'] : '';
        $disabled    = isset($args['disabled']) && $args['disabled'] == true ? 'disabled' : '';

        printf(
            '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s" %6$s maxlength="%7$s" %8$s/>',
            esc_attr($type),
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($value),
            esc_attr($placeholder),
            esc_attr($maxlength),
            esc_attr($disabled)
        );

        echo $this->get_field_description($args);
    }

    /**
     * Displays a url field for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_url($args)
    {
        $this->callback_text($args);
    }

    /**
     * Displays a number field for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_number($args)
    {
        $value        = $this->get_option($args['id'], $args['section'], $args['std']);
        $size         = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $type         = isset($args['type']) ? $args['type'] : 'number';
        $placeholder  = empty($args['placeholder']) ? '' : $args['placeholder'];
        $min          = empty($args['min']) ? '' : $args['min'];
        $max          = empty($args['max']) ? '' : $args['max'];
        $step         = empty($args['step']) ? '' : $args['step'];
        $has_unit_box = !empty($args['unit']) ? 'has-unit-box' : '';


        printf(
            '<input type="%1$s" class="%2$s-number %10$s" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s" placeholder="%6$s" min="%7$s" max="%8$s" step="%9$s" />',

            esc_attr($type),
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($value),
            esc_attr($placeholder),
            esc_attr($min),
            esc_attr($max),
            esc_attr($step),
            esc_attr($has_unit_box)
        );

        if ($has_unit_box) {
            echo '<div class="input-unit-box">' . esc_html($args['unit']) . '</div>';
        }

        echo $this->get_field_description($args);
    }

    /**
     * Displays a checkbox for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_checkbox($args)
    {
        $disabled = isset($args['disabled']) && $args['disabled'] == true ? 'disabled' : '';
        $value    = $this->get_option($args['id'], $args['section'], $args['std']);

        echo '<fieldset>';
        printf('<label for="dvnt-%1$s[%2$s]" class="switch">', esc_attr($args['section']), esc_attr($args['id']));
        printf('<input type="hidden" name="%1$s[%2$s]" value="0" />', esc_attr($args['section']), esc_attr($args['id']));
        printf(
            '<input type="checkbox" class="checkbox" id="dvnt-%1$s[%2$s]" name="%1$s[%2$s]" value="1" %3$s  %4$s />',
            esc_attr($args['section']),
            esc_attr($args['id']),
            checked(esc_attr($value), '1', false),
            esc_attr($disabled)
        );
        echo '<span class="devnet-switch devnet-switch--round"></span>';
        echo '</label>';
        printf('<span class="description">%1$s</span>', wp_kses($args['desc'], $this->kses_args()));
        echo '</fieldset>';
    }

    /**
     * Displays a multicheckbox for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_multicheck($args)
    {

        $value = $this->get_option($args['id'], $args['section'], $args['std']);

        echo '<fieldset>';
        printf(
            '<input type="hidden" name="%1$s[%2$s]" value="" />',
            esc_attr($args['section']),
            esc_attr($args['id'])
        );
        foreach ($args['options'] as $key => $label) {
            $checked = isset($value[$key]) ? $value[$key] : '0';

            printf(
                '<label for="dvnt-%1$s[%2$s][%3$s]" class="switch">',
                esc_attr($args['section']),
                esc_attr($args['id']),
                esc_attr($key)
            );
            printf(
                '<input type="checkbox" class="checkbox" id="dvnt-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />',
                esc_attr($args['section']),
                esc_attr($args['id']),
                esc_attr($key),
                checked(esc_attr($checked), esc_attr($key), false)
            );
            echo '<span class="slider round"></span>';
            echo '</label>';
            printf('<span class="desc">%1$s</span><br>', esc_html($label));
        }

        echo $this->get_field_description($args);
        echo '</fieldset>';
    }

    /**
     * Displays a radio button for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_radio($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);

        echo '<fieldset>';

        foreach ($args['options'] as $key => $label) {
            printf(
                '<label for="dvnt-%1$s[%2$s][%3$s]">',
                esc_attr($args['section']),
                esc_attr($args['id']),
                esc_attr($key)
            );
            printf(
                '<input type="radio" class="radio" id="dvnt-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />',
                esc_attr($args['section']),
                esc_attr($args['id']),
                esc_attr($key),
                checked(esc_attr($value), esc_attr($key), false)
            );
            printf('%1$s</label><br>', esc_html($label));
        }

        echo $this->get_field_description($args);
        echo '</fieldset>';
    }

    /**
     * Displays a radio images as buttons for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_radio_image($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);

        echo '<fieldset class="radio-images">';

        foreach ($args['options'] as $key => $label) {
            printf(
                '<label for="dvnt-%1$s[%2$s][%3$s]">',
                esc_attr($args['section']),
                esc_attr($args['id']),
                esc_attr($key)
            );
            printf(
                '<input type="radio" class="radio" id="dvnt-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />',
                esc_attr($args['section']),
                esc_attr($args['id']),
                esc_attr($key),
                checked(esc_attr($value), esc_attr($key), false)
            );
            printf('<span class="img">%1$s</span></label><br>', $label);
        }

        echo $this->get_field_description($args);
        echo '</fieldset>';
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_select($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $optgroup = !empty($args['optgroup']) ? $args['optgroup'] : null;

        printf(
            '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">',
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id'])
        );

        foreach ($args['options'] as $key => $label) {

            $is_disabled = strpos($key, '_disabled') === 0 ? 'disabled' : '';

            printf(
                '<option value="%s" %s %s>%s</option>',
                esc_attr($key),
                esc_attr($is_disabled),
                selected(esc_attr($value), esc_attr($key), false),
                esc_attr($label)
            );
        }

        if ($optgroup) {
            foreach ($optgroup as $group) {

                $disabled = $group['disabled'] ?? '';
                $is_disabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN) ? 'disabled' : '';

                echo '<optgroup label="' . esc_attr($group['label']) . '" ' . esc_attr($is_disabled) . '>';

                foreach ($group['options'] as $key => $label) {

                    $is_disabled = strpos($key, '_disabled') === 0 ? 'disabled' : '';

                    printf(
                        '<option value="%s" %s %s>%s</option>',
                        esc_attr($key),
                        esc_attr($is_disabled),
                        selected(esc_attr($value), esc_attr($key), false),
                        esc_attr($label)
                    );
                }
                echo '</optgroup>';
            }
        }

        printf('</select>');
        echo $this->get_field_description($args);
    }


    /**
     * Displays a select2 selectbox for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_select2($args)
    {
        $value               = $this->get_option($args['id'], $args['section'], $args['std']);
        $size                = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $multiple            = isset($args['multiple']) && $args['multiple'] ? 'multiple' : '';
        $disabled            = isset($args['disabled']) && $args['disabled'] == true ? 'disabled' : '';
        $group_index         = $args['group_index'] ?? null;
        $multiple_collection = $multiple ? '[]' : '';

        $options = [];

        if ($value) {
            $options = is_array($value) ? $value : [$value];
        }

        if ($group_index !== null) {
            $options = $args['std'];
        }

        printf(
            '<select class="%1$s" name="%2$s[%3$s]%4$s" id="%2$s[%3$s]" %5$s %6$s>',
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($multiple_collection),
            esc_attr($multiple),
            esc_attr($disabled)
        );

        foreach ((array) $options as $key => $label) {

            $key   = $label;
            $parts = explode('___', $label);
            $label = $parts[1] ?? $label;

            // all options are always selected.
            printf('<option value="%s" selected>%s</option>', esc_attr($key), esc_html($label));
        }

        printf('</select>');
        echo $this->get_field_description($args);
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_textarea($args)
    {
        $value       = $this->get_option($args['id'], $args['section'], $args['std']);
        $size        = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $rows        = isset($args['rows']) && !empty($args['rows']) ? $args['rows'] : 5;
        $cols        = isset($args['cols']) && !empty($args['cols']) ? $args['cols'] : 55;
        $placeholder = empty($args['placeholder']) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $disabled    = isset($args['disabled']) && $args['disabled'] == true ? 'disabled' : '';

        printf(
            '<textarea rows="%1$s" cols="%2$s" class="%3$s-text" id="%4$s[%5$s]" name="%4$s[%5$s]"%6$s %7$s>%8$s</textarea>',
            esc_attr($rows),
            esc_attr($cols),
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($placeholder),
            esc_attr($disabled),
            esc_textarea($value)
        );

        echo $this->get_field_description($args);
    }

    /**
     * Displays the html for a settings field
     *
     * @param array   $args settings field args
     * @return string
     */
    public function callback_html($args)
    {
        echo $this->get_field_description($args);
    }

    /**
     * Displays a rich text textarea for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_wysiwyg($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : '500px';

        echo '<div style="max-width: ' . esc_attr($size) . ';">';

        $editor_settings = [
            'teeny'         => false,
            'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
            'textarea_rows' => 10
        ];

        if (isset($args['options']) && is_array($args['options'])) {
            $editor_settings = array_merge($editor_settings, $args['options']);
        }

        wp_editor($value, $args['section'] . '-' . $args['id'], $editor_settings);

        echo '</div>';

        echo $this->get_field_description($args);
    }

    /**
     * Displays a file upload field for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_file($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';
        $id    = $args['section'] . '[' . $args['id'] . ']';
        $label = isset($args['options']['button_label']) ? $args['options']['button_label'] : esc_html__('Choose File', '');

        printf(
            '<input type="text" class="%1$s-text dvnt-f-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>',
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($value)
        );
        echo '<input type="button" class="button dvnt-f-browse" value="' . $label . '" />';

        // to check if is an image.  
        if ($value && $maybe_image = getimagesize($value)) {
            $mime_type = $maybe_image['mime'];
            if (strpos($mime_type, 'image/') === 0) {
                echo '<img src="' . esc_attr($value) . '" class="dvnt-f-preview" width="75" height="75"/>';
            }
        }

        echo $this->get_field_description($args);
    }

    /**
     * Displays a password field for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_password($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

        printf(
            '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>',
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($value)
        );

        echo $this->get_field_description($args);
    }

    /**
     * Displays a color picker field for a settings field
     *
     * @param array   $args settings field args
     */
    public function callback_color($args)
    {
        $value = $this->get_option($args['id'], $args['section'], $args['std']);
        $size  = isset($args['size']) && !is_null($args['size']) ? $args['size'] : 'regular';

        printf(
            '<input type="text" class="%1$s-text wp-color-picker-field color-picker" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" data-alpha-enabled="true" />',
            esc_attr($size),
            esc_attr($args['section']),
            esc_attr($args['id']),
            esc_attr($value),
            esc_attr($args['std'])
        );

        echo $this->get_field_description($args);
    }

    /**
     * Displays a repeatable for a settings field.
     *
     * @param array   $args settings field args
     */
    public function callback_group($args)
    {
        $fields = isset($args['fields']) ? $args['fields'] : [];

        $group_data = $this->get_option($args['id'], $args['section'], $args['std']) ?? [];

        $repeatable = isset($args['repeatable']) && $args['repeatable'];

        if (!$repeatable) {
            $group_data = [$group_data];
        }

        if (!is_array($fields) || empty($fields)) return;

        echo '<div class="dvnt-groups" data-repeatable="' . esc_attr($repeatable) . '">';


        foreach ((array)$group_data as $group_index => $group_values) {

            echo '<div class="dvnt-group">';

            foreach ($fields as $index => $field) {

                $callback = 'callback_' . $field['type'];


                $repeatable_index = $repeatable ? '[' . $group_index . ']' : '';

                /**
                 * to simplify life, we are building id upon callback functions structure.
                 * name attributes are build like: name="section[id]"
                 * that's why we have closing "]" first and no closing "]" at the end.
                 * $external id will be inserted on the place of $id in the callback function inside "[]" 
                 * 
                 */
                $extended_id = $args['id'] . ']' . $repeatable_index . '[' . $field['name'];


                $std = isset($field['std']) ? $field['std'] : '';

                $field_value = isset($group_values[$field['name']]) ? $group_values[$field['name']] : $std;

                $description = isset($field['desc']) ? $field['desc'] : '';

                $callback_args = [
                    'id'      => $extended_id,
                    'section' => $args['section'],
                    'std'     => $field_value,
                    'desc'    => $description,
                    'group_index' => $group_index
                ] + $field;


                echo '<div class="dvnt-group-field field-type-' . esc_attr($field['type']) . '">';
                echo '<label>' . esc_html($field['label']) . '</label>';
                call_user_func([$this, $callback], $callback_args);
                echo '</div>';
            }

            if ($repeatable) {

                echo '<a href="#" class="dvnt-remove-group">' . esc_html__('Remove', '') . '</a>';
            }

            echo '</div>';
        }

        if ($repeatable) {

            echo '<button class="dvnt-repeat-group" title="Add new">+</button>';
        }

        echo '</div>';

        echo $this->get_field_description($args);
    }

    /**
     * Displays a select box for creating the pages select box
     *
     * @param array   $args settings field args
     */
    public function callback_pages($args)
    {
        $dropdown_args = [
            'selected' => esc_attr($this->get_option($args['id'], $args['section'], $args['std'])),
            'name'     => esc_attr($args['section'] . '[' . $args['id'] . ']'),
            'id'       => esc_attr($args['section'] . '[' . $args['id'] . ']'),
            'echo'     => 0
        ];

        echo wp_dropdown_pages($dropdown_args);
    }

    /**
     * Sanitize callback for Settings API
     *
     * @return mixed
     */
    public function sanitize_options($options)
    {
        if (!$options) {
            return $options;
        }

        foreach ($options as $option_slug => $option_value) {
            $sanitize_callback = $this->get_sanitize_callback($option_slug);

            // If callback is set, call it
            if ($sanitize_callback) {

                $options[$option_slug] = call_user_func($sanitize_callback, $option_value);
                continue;
            }
        }

        return $options;
    }

    /**
     * Get sanitization callback for given option slug
     *
     * @param string $slug option slug
     *
     * @return mixed string or bool false
     */
    public function get_sanitize_callback($slug = '')
    {
        if (empty($slug)) {
            return false;
        }

        // Iterate over registered fields and see if we can find proper callback
        foreach ($this->settings_fields as $section => $options) {
            foreach ($options as $option) {
                if ($option['name'] != $slug) {
                    continue;
                }

                // Return the callback name
                return isset($option['sanitize_callback']) && is_callable($option['sanitize_callback']) ? $option['sanitize_callback'] : false;
            }
        }

        return false;
    }

    /**
     * Get the value of a settings field
     *
     * @param string  $option  settings field name
     * @param string  $section the section name this field belongs to
     * @param string  $default default text if it's not found
     * @return string
     */
    public function get_option($option, $section, $default = '')
    {

        $options = get_option($section);

        if (isset($options[$option])) {
            return $options[$option];
        }

        return $default;
    }

    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
    public function show_navigation()
    {

        $count = count($this->settings_sections);

        // don't show the navigation if only one section exists
        if ($count === 1) {
            return;
        }

        echo '<h2 class="nav-tab-wrapper">';

        foreach ($this->settings_sections as $tab) {
            printf('<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', esc_attr($tab['id']), esc_html($tab['title']));
        }

        echo '</h2>';
    }

    /**
     * Show the section settings forms
     *
     * This function displays every sections in a different form
     */
    public function show_forms()
    {
?>
        <div class="metabox-holder">
            <?php foreach ($this->settings_sections as $form) { ?>
                <div id="<?php echo esc_attr($form['id']); ?>" class="group" style="display: none;">
                    <form method="post" action="options.php">
                        <?php

                        do_action($this->plugin_slug . '_form_top', $form);
                        do_action($this->plugin_slug . '_form_top_' . $form['id'], $form);

                        settings_fields($form['id']);
                        do_settings_sections($form['id']);

                        do_action($this->plugin_slug . '_form_bottom_' . $form['id'], $form);
                        do_action($this->plugin_slug . '_form_bottom', $form);

                        if (isset($this->settings_fields[$form['id']])) :
                        ?>
                            <div class="submit-row" style="padding-left: 10px">
                                <?php submit_button(); ?>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php } ?>
        </div>
<?php

    }
}

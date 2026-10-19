<?php
// Block registry — the single source of truth for the block builder.
//
// Each block type lists its fields. A field's type decides what is SHARED
// by all languages and what is TRANSLATED per language:
//
//   text / textarea  translated   (string)
//   select / link    shared       (one of the allowed options)
//   checkbox         shared       (true / false)
//   image            src shared;  alt (and optional caption) translated
//   gallery          list of images. Order + src shared; alt translated
//   repeater         list of rows.   Order shared; the sub-fields translated
//
// To add a new block type: add an entry here, then create
// includes/blocks/<type>.php that renders it. The admin builder picks it up
// automatically.

/**
 * Every page of the site. 'slug' is the clean URL ('' = home); null means
 * the page has no URL of its own (the 404 page is only shown for unknown URLs).
 * 'file' is the PHP template that renders it.
 */
function enw_site_pages() {
    return [
        'home'     => ['file' => 'index.php',   'slug' => '',        'label' => 'Home'],
        'product'  => ['file' => 'product.php', 'slug' => 'product', 'label' => 'Product'],
        'contact'  => ['file' => 'contact.php', 'slug' => 'contact', 'label' => 'Contact'],
        'notfound' => ['file' => '404.php',     'slug' => null,      'label' => '404 page'],
    ];
}

/** Pages built with blocks (key => label shown in the admin). */
function enw_block_pages() {
    return [
        'home'     => 'Home',
        'product'  => 'Product (SiBoost)',
        'contact'  => 'Contact',
        'notfound' => '404 page (not found)',
    ];
}

/** Section background choices (value => label). Maps to .band-* CSS classes. */
function enw_bg_options() {
    return [
        'bg'       => 'Grey',
        'charcoal' => 'Charcoal (dark)',
        'paper'    => 'Paper (light)',
    ];
}

/** Internal pages a button can link to (file => label). */
function enw_link_options() {
    return [
        'index.php'   => 'Home',
        'product.php' => 'Product',
        'contact.php' => 'Contact',
    ];
}

function enw_block_types() {
    static $types = null;
    if ($types !== null) {
        return $types;
    }

    $bg = fn($default) => [
        'name' => 'bg', 'label' => 'Background', 'type' => 'select',
        'options' => enw_bg_options(), 'default' => $default,
    ];

    $types = [

        'hero' => [
            'label' => 'Hero (big headline)',
            'description' => 'Large opening section with two buttons.',
            'summary' => 'headline',
            'fields' => [
                ['name' => 'eyebrow',  'label' => 'Eyebrow (small line above headline)', 'type' => 'text'],
                ['name' => 'headline', 'label' => 'Headline', 'type' => 'text'],
                ['name' => 'text',     'label' => 'Text', 'type' => 'textarea'],
                ['name' => 'cta_primary',      'label' => 'Primary button label', 'type' => 'text'],
                ['name' => 'cta_primary_link', 'label' => 'Primary button links to', 'type' => 'link', 'default' => 'product.php'],
                ['name' => 'cta_secondary',      'label' => 'Secondary button label', 'type' => 'text'],
                ['name' => 'cta_secondary_link', 'label' => 'Secondary button links to', 'type' => 'link', 'default' => 'contact.php'],
                // Background. "default" keeps what the stylesheet draws (the microscope photo under a dark gradient).
                ['name' => 'bg_style', 'label' => 'Background', 'type' => 'select', 'default' => 'default',
                 'options' => ['default' => 'Default photo (from the stylesheet)', 'image' => 'My own photo', 'color' => 'A solid color']],
                ['name' => 'bg_image', 'label' => 'Background photo', 'type' => 'image', 'alt' => false,
                 'show_if' => ['bg_style' => ['image']]],
                ['name' => 'bg_color', 'label' => 'Background color', 'type' => 'color', 'default' => '#2b2b2c',
                 'show_if' => ['bg_style' => ['color']]],
                ['name' => 'bg_overlay', 'label' => 'Darkening over the photo', 'type' => 'select', 'default' => 'normal',
                 'options' => ['none' => 'None', 'soft' => 'Soft', 'normal' => 'Normal (as today)', 'strong' => 'Strong'],
                 'show_if' => ['bg_style' => ['default', 'image']]],
                ['name' => 'bg_tone', 'label' => 'Text color', 'type' => 'select', 'default' => 'light',
                 'options' => ['light' => 'Light text (for dark backgrounds)', 'dark' => 'Dark text (for light backgrounds)']],
            ],
        ],

        'page-intro' => [
            'label' => 'Page intro (title + text)',
            'description' => 'Compact page title for inner pages.',
            'summary' => 'heading',
            'fields' => [
                ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
                ['name' => 'heading', 'label' => 'Title', 'type' => 'text'],
                ['name' => 'text',    'label' => 'Text', 'type' => 'textarea'],
                ['name' => 'wide_title', 'label' => 'Let the title use the full width', 'type' => 'checkbox', 'default' => false],
                ['name' => 'tight',      'label' => 'Less space below', 'type' => 'checkbox', 'default' => false],
            ],
        ],

        'divider' => [
            'label' => 'Ribbon divider',
            'description' => 'The decorative line between sections.',
            'summary' => null,
            'fields' => [],
        ],

        'text-stat' => [
            'label' => 'Text + big number',
            'description' => 'Two paragraphs next to a highlighted statistic.',
            'summary' => 'heading',
            'fields' => [
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'p1', 'label' => 'Paragraph 1', 'type' => 'textarea'],
                ['name' => 'p2', 'label' => 'Paragraph 2', 'type' => 'textarea'],
                ['name' => 'stat_number', 'label' => 'Big number (e.g. 1.1&ndash;4&times;)', 'type' => 'text'],
                ['name' => 'stat_label',  'label' => 'Big number caption', 'type' => 'text'],
                [
                    'name' => 'layout', 'label' => 'Layout', 'type' => 'select', 'default' => 'text-left',
                    'options' => ['text-left' => 'Text left, number right', 'stat-left' => 'Number left, text right'],
                ],
                $bg('bg'),
            ],
        ],

        'card-list' => [
            'label' => 'Cards (title + text)',
            'description' => 'A heading with a list of short titled cards.',
            'summary' => 'heading',
            'fields' => [
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                [
                    'name' => 'items', 'label' => 'Cards', 'type' => 'repeater', 'item_label' => 'Card',
                    'item_fields' => [
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'text',  'label' => 'Text',  'type' => 'textarea'],
                    ],
                ],
                $bg('charcoal'),
            ],
        ],

        'image-strip' => [
            'label' => 'Image strip',
            'description' => 'Heading, short text and a row of photos.',
            'summary' => 'heading',
            'fields' => [
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'text',    'label' => 'Text', 'type' => 'textarea'],
                ['name' => 'images',  'label' => 'Photos', 'type' => 'gallery'],
                $bg('bg'),
                ['name' => 'tight_bottom', 'label' => 'Remove the space below the photos', 'type' => 'checkbox', 'default' => false],
            ],
        ],

        'figure' => [
            'label' => 'Photo with caption',
            'description' => 'Heading, text and one large captioned photo.',
            'summary' => 'heading',
            'fields' => [
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'text',    'label' => 'Text', 'type' => 'textarea'],
                ['name' => 'image',   'label' => 'Photo', 'type' => 'image', 'caption' => true],
                $bg('bg'),
            ],
        ],

        'steps' => [
            'label' => 'Steps + photos',
            'description' => 'A numbered list of steps beside supporting photos.',
            'summary' => 'heading',
            'fields' => [
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                [
                    'name' => 'steps', 'label' => 'Steps', 'type' => 'repeater', 'item_label' => 'Step',
                    'item_fields' => [
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'text',  'label' => 'Text',  'type' => 'textarea'],
                    ],
                ],
                ['name' => 'images', 'label' => 'Supporting photos', 'type' => 'gallery'],
                $bg('charcoal'),
            ],
        ],

        'contact-form' => [
            'label' => 'Contact form + details',
            'description' => 'The contact form (name, email, message) with the location and email beside it.',
            'summary' => 'submit',
            'fields' => [
                ['name' => 'name_label',    'label' => 'Label: name', 'type' => 'text'],
                ['name' => 'phone_label',   'label' => 'Label: phone', 'type' => 'text'],
                ['name' => 'email_label',   'label' => 'Label: email', 'type' => 'text'],
                ['name' => 'subject_label', 'label' => 'Label: subject', 'type' => 'text'],
                ['name' => 'message_label', 'label' => 'Label: message', 'type' => 'text'],
                ['name' => 'required_note', 'label' => 'Note about required fields', 'type' => 'text'],
                ['name' => 'submit',        'label' => 'Send button', 'type' => 'text'],
                ['name' => 'error_name',    'label' => 'Error: name missing', 'type' => 'text'],
                ['name' => 'error_email',   'label' => 'Error: email not valid', 'type' => 'text'],
                ['name' => 'error_message', 'label' => 'Error: message missing', 'type' => 'text'],
                ['name' => 'error_send',    'label' => 'Error: sending failed ({email} = your address)', 'type' => 'textarea'],
                ['name' => 'success',       'label' => 'Success message', 'type' => 'textarea'],
                ['name' => 'show_info', 'label' => 'Show the location and email beside the form', 'type' => 'checkbox', 'default' => true],
                ['name' => 'info_location_label', 'label' => 'Label: location', 'type' => 'text', 'show_if' => ['show_info' => [true]]],
                ['name' => 'info_email_label',    'label' => 'Label: email (details)', 'type' => 'text', 'show_if' => ['show_info' => [true]]],
                $bg('paper'),
            ],
        ],

        'cta' => [
            'label' => 'Call to action',
            'description' => 'Closing heading, text and a button.',
            'summary' => 'heading',
            'fields' => [
                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'text',    'label' => 'Text', 'type' => 'textarea'],
                ['name' => 'button',      'label' => 'Button label', 'type' => 'text'],
                ['name' => 'button_link', 'label' => 'Button links to', 'type' => 'link', 'default' => 'contact.php'],
                $bg('charcoal'),
            ],
        ],

    ];

    return $types;
}

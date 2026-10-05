<?php
// Single source of truth for what's editable in the admin panel: every
// field, grouped by page and section, with its content.json path and type.
//
// Field types:
//   text      - single-line string
//   textarea  - multi-line string
//   repeater  - array of objects, each with the same sub-fields
//               (e.g. "What we offer" items, "How it works" steps)
//   gallery   - array of {src, alt} image objects, reorderable
//   image     - a single {src, alt, caption} image object
//
// Adding a new field here is enough to make it editable — the generic
// admin/edit.php form renders itself from this schema. Adding a field here
// does NOT automatically make a template use it — that still needs a
// matching t() / t_list() call in the relevant .php page template.

return [

    'home' => [
        'label' => 'Home',
        'sections' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['path' => 'home.meta.title',       'label' => 'Page title',       'type' => 'text'],
                    ['path' => 'home.meta.description',  'label' => 'Meta description', 'type' => 'textarea'],
                    ['path' => 'home.meta.keywords',     'label' => 'Meta keywords',    'type' => 'text'],
                ],
            ],
            [
                'label' => 'Hero',
                'fields' => [
                    ['path' => 'home.hero.eyebrow',       'label' => 'Eyebrow (small line above headline)', 'type' => 'text'],
                    ['path' => 'home.hero.headline',      'label' => 'Headline',                            'type' => 'text'],
                    ['path' => 'home.hero.text',          'label' => 'Text',                                'type' => 'textarea'],
                    ['path' => 'home.hero.cta_primary',   'label' => 'Primary button label',                'type' => 'text'],
                    ['path' => 'home.hero.cta_secondary', 'label' => 'Secondary button label',               'type' => 'text'],
                ],
            ],
            [
                'label' => 'Who we are',
                'fields' => [
                    ['path' => 'home.who.heading',     'label' => 'Heading',       'type' => 'text'],
                    ['path' => 'home.who.p1',          'label' => 'Paragraph 1',   'type' => 'textarea'],
                    ['path' => 'home.who.p2',          'label' => 'Paragraph 2',   'type' => 'textarea'],
                    ['path' => 'home.who.stat_number', 'label' => 'Stat number (e.g. 1.1–4×)', 'type' => 'text'],
                    ['path' => 'home.who.stat_label',  'label' => 'Stat label',    'type' => 'text'],
                ],
            ],
            [
                'label' => 'What we offer',
                'fields' => [
                    ['path' => 'home.offer.heading', 'label' => 'Heading', 'type' => 'text'],
                    [
                        'path' => 'home.offer.items', 'label' => 'Items', 'type' => 'repeater',
                        'item_label' => 'Item',
                        'item_fields' => [
                            ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                            ['name' => 'text',  'label' => 'Text',  'type' => 'textarea'],
                        ],
                    ],
                ],
            ],
            [
                'label' => 'Built at pilot scale',
                'fields' => [
                    ['path' => 'home.pilot.heading', 'label' => 'Heading', 'type' => 'text'],
                    ['path' => 'home.pilot.text',    'label' => 'Text',    'type' => 'textarea'],
                    ['path' => 'home.pilot.images',  'label' => 'Gallery', 'type' => 'gallery'],
                ],
            ],
            [
                'label' => 'From powder to particle',
                'fields' => [
                    ['path' => 'home.filmstrip.heading', 'label' => 'Heading', 'type' => 'text'],
                    ['path' => 'home.filmstrip.text',    'label' => 'Text',    'type' => 'textarea'],
                    ['path' => 'home.filmstrip.images',  'label' => 'Gallery', 'type' => 'gallery'],
                ],
            ],
        ],
    ],

    'product' => [
        'label' => 'Product (SiBoost)',
        'sections' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['path' => 'product.meta.title',       'label' => 'Page title',       'type' => 'text'],
                    ['path' => 'product.meta.description',  'label' => 'Meta description', 'type' => 'textarea'],
                    ['path' => 'product.meta.keywords',     'label' => 'Meta keywords',    'type' => 'text'],
                ],
            ],
            [
                'label' => 'Hero',
                'fields' => [
                    ['path' => 'product.hero.eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
                    ['path' => 'product.hero.text',    'label' => 'Text',    'type' => 'textarea'],
                ],
            ],
            [
                'label' => 'Stat + "What is SiBoost?"',
                'fields' => [
                    ['path' => 'product.stat.number',  'label' => 'Stat number (e.g. 1.1–4×)', 'type' => 'text'],
                    ['path' => 'product.stat.label',   'label' => 'Stat label',                'type' => 'text'],
                    ['path' => 'product.what.heading', 'label' => 'Heading',                    'type' => 'text'],
                    ['path' => 'product.what.p1',      'label' => 'Paragraph 1',                'type' => 'textarea'],
                    ['path' => 'product.what.p2',      'label' => 'Paragraph 2',                'type' => 'textarea'],
                ],
            ],
            [
                'label' => 'Before and after',
                'fields' => [
                    ['path' => 'product.compare.heading', 'label' => 'Heading', 'type' => 'text'],
                    ['path' => 'product.compare.text',    'label' => 'Text',    'type' => 'textarea'],
                    ['path' => 'product.compare.image',   'label' => 'Comparison photo', 'type' => 'image'],
                ],
            ],
            [
                'label' => 'How it works',
                'fields' => [
                    ['path' => 'product.how.heading', 'label' => 'Heading', 'type' => 'text'],
                    [
                        'path' => 'product.how.steps', 'label' => 'Steps', 'type' => 'repeater',
                        'item_label' => 'Step',
                        'item_fields' => [
                            ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                            ['name' => 'text',  'label' => 'Text',  'type' => 'textarea'],
                        ],
                    ],
                    ['path' => 'product.how.images', 'label' => 'Supporting images', 'type' => 'gallery'],
                ],
            ],
            [
                'label' => 'From material to cell',
                'fields' => [
                    ['path' => 'product.cells.heading', 'label' => 'Heading', 'type' => 'text'],
                    ['path' => 'product.cells.text',    'label' => 'Text',    'type' => 'textarea'],
                    ['path' => 'product.cells.images',  'label' => 'Gallery', 'type' => 'gallery'],
                ],
            ],
            [
                'label' => 'Closing call to action',
                'fields' => [
                    ['path' => 'product.cta.heading', 'label' => 'Heading',       'type' => 'text'],
                    ['path' => 'product.cta.text',    'label' => 'Text',          'type' => 'textarea'],
                    ['path' => 'product.cta.button',  'label' => 'Button label',  'type' => 'text'],
                ],
            ],
        ],
    ],

    'contact' => [
        'label' => 'Contact',
        'sections' => [
            [
                'label' => 'SEO',
                'fields' => [
                    ['path' => 'contact.meta.title',       'label' => 'Page title',       'type' => 'text'],
                    ['path' => 'contact.meta.description',  'label' => 'Meta description', 'type' => 'textarea'],
                    ['path' => 'contact.meta.keywords',     'label' => 'Meta keywords',    'type' => 'text'],
                ],
            ],
            [
                'label' => 'Heading',
                'fields' => [
                    ['path' => 'contact.hero.eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
                    ['path' => 'contact.hero.title',   'label' => 'Title',   'type' => 'text'],
                    ['path' => 'contact.hero.text',    'label' => 'Text',    'type' => 'textarea'],
                ],
            ],
            [
                'label' => 'Form labels & messages',
                'fields' => [
                    ['path' => 'contact.form.name_label',    'label' => 'Name field label',    'type' => 'text'],
                    ['path' => 'contact.form.phone_label',   'label' => 'Phone field label',   'type' => 'text'],
                    ['path' => 'contact.form.email_label',   'label' => 'Email field label',   'type' => 'text'],
                    ['path' => 'contact.form.subject_label', 'label' => 'Subject field label', 'type' => 'text'],
                    ['path' => 'contact.form.message_label', 'label' => 'Message field label', 'type' => 'text'],
                    ['path' => 'contact.form.required_note', 'label' => '"Required" note',     'type' => 'text'],
                    ['path' => 'contact.form.submit',        'label' => 'Submit button label', 'type' => 'text'],
                    ['path' => 'contact.form.error_name',    'label' => 'Error: missing name',  'type' => 'text'],
                    ['path' => 'contact.form.error_email',   'label' => 'Error: invalid email', 'type' => 'text'],
                    ['path' => 'contact.form.error_message', 'label' => 'Error: missing message', 'type' => 'text'],
                    ['path' => 'contact.form.success',       'label' => 'Success message',      'type' => 'textarea'],
                ],
            ],
            [
                'label' => 'Contact info',
                'fields' => [
                    ['path' => 'contact.info.location_label', 'label' => '"Location" label', 'type' => 'text'],
                    ['path' => 'contact.info.email_label',    'label' => '"Email" label',     'type' => 'text'],
                ],
            ],
        ],
    ],

];

<?php

declare(strict_types=1);

/**
 * Curated unit-of-measure list for the B2B halal marketplace.
 *
 * Each entry: ['value' => string, 'label' => string, 'group' => string]
 * Groups are used to render <optgroup> in the select.
 */
return [

    // ── Weight ──────────────────────────────────────────────────────────────
    ['value' => 'kg',    'label' => 'kg — kilogram',      'group' => 'Weight'],
    ['value' => 'g',     'label' => 'g — gram',           'group' => 'Weight'],
    ['value' => 'mt',    'label' => 'mt — metric ton',    'group' => 'Weight'],
    ['value' => 'lb',    'label' => 'lb — pound',         'group' => 'Weight'],

    // ── Volume ───────────────────────────────────────────────────────────────
    ['value' => 'L',     'label' => 'L — liter',          'group' => 'Volume'],
    ['value' => 'mL',    'label' => 'mL — milliliter',    'group' => 'Volume'],

    // ── Packaging ────────────────────────────────────────────────────────────
    ['value' => 'bag',     'label' => 'bag',              'group' => 'Packaging'],
    ['value' => 'bottle',  'label' => 'bottle',           'group' => 'Packaging'],
    ['value' => 'box',     'label' => 'box',              'group' => 'Packaging'],
    ['value' => 'bundle',  'label' => 'bundle',           'group' => 'Packaging'],
    ['value' => 'can',     'label' => 'can',              'group' => 'Packaging'],
    ['value' => 'carton',  'label' => 'carton',           'group' => 'Packaging'],
    ['value' => 'crate',   'label' => 'crate',            'group' => 'Packaging'],
    ['value' => 'jar',     'label' => 'jar',              'group' => 'Packaging'],
    ['value' => 'pack',    'label' => 'pack',             'group' => 'Packaging'],
    ['value' => 'pail',    'label' => 'pail',             'group' => 'Packaging'],
    ['value' => 'pouch',   'label' => 'pouch',            'group' => 'Packaging'],
    ['value' => 'sack',    'label' => 'sack',             'group' => 'Packaging'],
    ['value' => 'tray',    'label' => 'tray',             'group' => 'Packaging'],
    ['value' => 'tub',     'label' => 'tub',              'group' => 'Packaging'],

    // ── Count ────────────────────────────────────────────────────────────────
    ['value' => 'pcs',    'label' => 'pcs — pieces',      'group' => 'Count'],
    ['value' => 'dozen',  'label' => 'dozen (12)',         'group' => 'Count'],
    ['value' => 'gross',  'label' => 'gross (144)',        'group' => 'Count'],

    // ── Other ────────────────────────────────────────────────────────────────
    ['value' => 'other',  'label' => 'Other…',            'group' => 'Other'],

];

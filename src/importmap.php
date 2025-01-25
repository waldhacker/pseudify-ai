<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'admin-lte' => [
        'version' => '3.2.0',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'admin-lte/dist/css/adminlte.min.css' => [
        'version' => '3.2.0',
        'type' => 'css',
    ],
    'bootstrap' => [
        'version' => '5.3.3',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.3',
        'type' => 'css',
    ],
    '@fortawesome/fontawesome-free/css/all.css' => [
        'version' => '6.6.0',
        'type' => 'css',
    ],
    'toastr' => [
        'version' => '2.1.4',
    ],
    'toastr/build/toastr.min.css' => [
        'version' => '2.1.4',
        'type' => 'css',
    ],
    'bootstrap-select' => [
        'version' => '1.14.0-beta3',
    ],
    'bootstrap-select/dist/css/bootstrap-select.min.css' => [
        'version' => '1.14.0-beta3',
        'type' => 'css',
    ],
    '@xterm/xterm' => [
        'version' => '5.5.0',
    ],
    '@xterm/xterm/css/xterm.min.css' => [
        'version' => '5.5.0',
        'type' => 'css',
    ],
    'datatables.net' => [
        'version' => '2.2.1',
    ],
    'datatables.net-bs5' => [
        'version' => '2.2.1',
    ],
    'datatables.net-bs5/css/dataTables.bootstrap5.min.css' => [
        'version' => '2.2.1',
        'type' => 'css',
    ],
];

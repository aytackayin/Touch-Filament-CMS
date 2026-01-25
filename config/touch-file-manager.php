<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reserved Folder Names
    |--------------------------------------------------------------------------
    |
    | These names cannot be used as folder names in the root directory.
    |
    */
    'reserved_names' => [
        // Windows Reserved Names
        'con',
        'prn',
        'aux',
        'nul',
        'com1',
        'com2',
        'com3',
        'com4',
        'com5',
        'com6',
        'com7',
        'com8',
        'com9',
        'lpt1',
        'lpt2',
        'lpt3',
        'lpt4',
        'lpt5',
        'lpt6',
        'lpt7',
        'lpt8',
        'lpt9',

        // Linux/System Common Directories
        'bin',
        'etc',
        'usr',
        'var',
        'boot',
        'dev',
        'home',
        'lib',
        'lib64',
        'mnt',
        'opt',
        'proc',
        'run',
        'sbin',
        'srv',
        'sys',
        'tmp',

        // App Specific
        'thumb',
        'thumbs',
        'temp',
        'temps',
        'attachment',
        'attachments',
        'default',
        'defaults',
        'public',
        'publics',
        'local',
        's3',
        'root',
        'storage',
        'app',
        'config',
        'database',
        'lang',
        'node_modules',
        'resources',
        'routes',
        'tests',
        'vendor',
        'bootstrap',
        'filament',
        'livewire',
        'assets'
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Assets Path
    |--------------------------------------------------------------------------
    |
    | Define the base path for colorful file and folder icons.
    |
    */
    'icon_paths' => [
        'base' => '/assets/icons/colorful-icons/',
        'folder' => 'folder.svg',
        'file' => 'file.svg',
    ],
];

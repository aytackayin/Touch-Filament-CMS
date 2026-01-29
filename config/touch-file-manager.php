<?php

return [
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
    | Default Thumbnail Sizes
    |--------------------------------------------------------------------------
    |
    | If no sizes are defined in model config or site settings,
    | these default sizes will be used.
    |
    */
    'thumb_sizes' => [150, 250, 500],

    /*
    |--------------------------------------------------------------------------
    | Accepted File Types for Uploads
    |--------------------------------------------------------------------------
    |
    | List of MIME types allowed for file uploads.
    |
    */
    'accepted_file_types' => [
        'image/*',
        'video/*',
        'application/pdf',
        'application/msword', // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/vnd.ms-excel', // .xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-powerpoint', // .ppt
        'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'text/plain',
        'application/json',
        'text/csv',
        'text/xml',
        'application/xml',

        // --- Popular but Disabled by Default (Uncomment to enable) ---

        // Audio
        // 'audio/mpeg', // .mp3
        // 'audio/wav',  // .wav
        // 'audio/ogg',  // .ogg

        // Images (Specific)
        // 'image/svg+xml', // .svg (Be careful with XSS)
        // 'image/webp',    // .webp
        // 'image/gif',     // .gif (included in image/* but explicit)
    ],
];

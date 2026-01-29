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
        'file' => 'blog.svg',
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
    'thumb_sizes' => [500],

    /*
    |--------------------------------------------------------------------------
    | Default Media (Frontend Fallback)
    |--------------------------------------------------------------------------
    |
    | If a blog has no attachments, this media will be used for the frontend.
    | You can provide a full URL or a relative path from the public folder.
    |
    */
    'default_media' => [
        'url' => 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&q=80&w=2070',
        'path' => null, // e.g., 'assets/images/default-blog.jpg'
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted File Types for Attachments
    |--------------------------------------------------------------------------
    |
    | List of MIME types allowed for file uploads in Blog resource.
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

        // Archives
        // 'application/x-tar', // .tar
        // 'application/gzip',  // .gz

        // Adobe
        // 'image/vnd.adobe.photoshop', // .psd
        // 'application/postscript',    // .ai, .eps

        // E-Books
        // 'application/epub+zip', // .epub

        // Fonts
        // 'font/ttf',
        // 'font/otf',
        // 'font/woff',
        // 'font/woff2',
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted File Types for Category Attachments
    |--------------------------------------------------------------------------
    |
    | List of MIME types allowed for file uploads in Blog Category resource.
    |
    */
    'accepted_category_file_types' => [
        'image/*',
    ],
];

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
];

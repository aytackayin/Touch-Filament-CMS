<?php
return [
  'profiles' => [
    'default' => [
      'menubar' => 'file edit view insert format tools table tc help',
      'plugins' => 'paste advlist autoresize codesample directionality emoticons fullscreen image imagetools autolink link lists media table wordcount preview pagebreak charmap anchor codeeditor hr',
      'toolbar' => 'paste undo redo removeformat | formatselect fontsizeselect | bold italic forecolor backcolor | rtl ltr alignjustify alignright aligncenter alignleft outdent indent | numlist bullist | image media link anchor blockquote codesample charmap emoticons | table toc hr pagebreak | wordcount fullscreen preview codeeditor',
      'upload_directory' => null,
      'custom_configs' => [
        //'content_css' => "/mycontent.css",
        'relative_urls' => false,
        'document_base_url' => config('app.url'),
        'toolbar_mode' => 'wrap',
        'paste_as_text' => true,
        'entity_encoding' => "raw",
        'allow_html_in_named_anchor' => true,
        'link_default_target' => '_blank',
        'codesample_global_prismjs' => true,
        'image_advtab' => true,
        'image_uploadtab' => true,
        'images_reuse_filename' => true,
        'image_class_list' => [
          [
            'title' => 'None',
            'value' => '',
          ],
          [
            'title' => 'Full Page',
            'value' => 'h-auto max-w-full',
          ],
          [
            'title' => 'Rounded lg',
            'value' => 'rounded-lg',
          ],
          [
            'title' => 'Rounded full',
            'value' => 'rounded-full',
          ],
          [
            'title' => 'Shadow',
            'value' => 'shadow-xl dark:shadow-gray-800',
          ],
        ],
      ]
    ],
    'simple' => [
      'plugins' => 'autoresize directionality emoticons link wordcount',
      'toolbar' => 'removeformat | bold italic | rtl ltr | link emoticons',
      'upload_directory' => null,
    ]
  ],
  'file_manager' => 'admin/touch-file-managers?iframe=1'
];

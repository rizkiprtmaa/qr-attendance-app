<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values. It is possible to add all defines that can be set
    | in dompdf_config.inc.php. You can also override the entire config file.
    |
    */
    'show_warnings' => false,   // Show or hide warnings
    'orientation' => 'portrait',
    'defines' => [
        /**
         * The paper size. Default is "letter" (8.5in x 11in)
         *
         * @see CPDF_Adapter::PAPER_SIZES for valid sizes
         */
        'DOMPDF_PAPER_SIZE' => 'A4',

        /**
         * The default font family
         *
         * Used if no suitable fonts can be found. This must exist in the font folder.
         * @var string
         */
        'DOMPDF_DEFAULT_FONT' => 'sans-serif',

        /**
         * Image DPI setting
         *
         * This setting determines the default DPI setting for images and fonts.  The
         * DPI may be overridden for inline images by explictly setting the
         * image's width & height style attributes (i.e. if the image's native
         * width is 600 pixels and you specify the image's width as 72 points,
         * the image will have a DPI of 600 in the rendered PDF.  The DPI of
         * background images can not be overridden and is controlled entirely
         * via this parameter.
         *
         * @var int
         */
        'DOMPDF_DPI' => 96,

        /**
         * Enable inline PHP
         *
         * If this setting is set to true then DOMPDF will automatically evaluate
         * inline PHP contained within <script type="text/php"> ... </script> tags.
         *
         * Enabling this for documents you do not trust (e.g. arbitrary remote html
         * pages) is a security risk.  Set this option to false if you wish to process
         * untrusted documents.
         *
         * @var bool
         */
        'DOMPDF_ENABLE_PHP' => false,

        /**
         * Enable remote file access
         *
         * If this setting is set to true, DOMPDF will access remote sites for
         * images and CSS files as required.
         * Enabled by default for compatibility.
         *
         * @var bool
         */
        'DOMPDF_ENABLE_REMOTE' => true,

        /**
         * A ratio applied to the fonts height to be more like browsers' line height
         */
        'DOMPDF_FONT_HEIGHT_RATIO' => 1.1,

        /**
         * Use the HTML5 Lib parser
         *
         * @var bool
         */
        'DOMPDF_ENABLE_HTML5PARSER' => true,

        /**
         * Use the more advanced Cpdf/Gd image renderer that enables more features
         * like images, form fields, alpha channel, etc.
         *
         * @var bool
         */
        'DOMPDF_ENABLE_FONTSUBSETTING' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | Options for DomPDF
    |
    */
    'options' => [
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true,
        'isJavascriptEnabled' => true,
        'isFontSubsettingEnabled' => true,
        'default_font' => 'sans-serif',
        'enable_css_float' => false, // Disable CSS float untuk performa
        'enable_javascript' => false, // Disable JavaScript jika tidak dibutuhkan
        'enable_remote' => false, // Disable remote resources jika semua local
        'font_cache' => storage_path('fonts/'), // Cache font
        'temp_dir' => sys_get_temp_dir(), // Set temp directory
        'log_output_file' => null, // Disable logging
    ],
];

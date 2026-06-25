<?php

return [
    'github' => [
        'repo' => env('SECTION_LIBRARY_GITHUB_REPO'),
        'token' => env('SECTION_LIBRARY_GITHUB_TOKEN'),
        'catalog_path' => env('SECTION_LIBRARY_CATALOG_PATH', 'catalog.yaml'),
    ],

    'cache' => [
        'ttl' => (int) env('SECTION_LIBRARY_CACHE_TTL', 300),
    ],

    'permissions' => [
        'access' => 'access section library',
    ],

    'paths' => [
        'registry' => resource_path('section-library.yaml'),
        'installed_manifest' => resource_path('section-library-installed.yaml'),
    ],

    'managed_files' => [
        'page_builder' => [
            'path' => resource_path('blueprints/collections/pages/page.yaml'),
            'start' => '# section-library:start:builder',
            'end' => '# section-library:end:builder',
        ],
        'site_scss' => [
            'path' => resource_path('css/site.scss'),
            'start' => '/* section-library:start:scss */',
            'end' => '/* section-library:end:scss */',
        ],
        'site_js_imports' => [
            'path' => resource_path('js/site.js'),
            'start' => '// section-library:start:imports',
            'end' => '// section-library:end:imports',
        ],
        'site_js_initializers' => [
            'path' => resource_path('js/site.js'),
            'start' => '// section-library:start:initializers',
            'end' => '// section-library:end:initializers',
        ],
        'blog_bard_blocks' => [
            'path' => resource_path('blueprints/collections/blog/blog.yaml'),
            'start' => '# section-library:start:bard-blocks',
            'end' => '# section-library:end:bard-blocks',
        ],
        'case_studies_bard_blocks' => [
            'path' => resource_path('blueprints/collections/case_studies/case_studies.yaml'),
            'start' => '# section-library:start:bard-blocks',
            'end' => '# section-library:end:bard-blocks',
        ],
    ],
];

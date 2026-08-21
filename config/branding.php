<?php

/*
|--------------------------------------------------------------------------
| Branding
|--------------------------------------------------------------------------
|
| Single source of truth for every user-facing brand string and outbound
| link. Nothing here is a functional identifier: Docker labels, the injected
| COOLIFY_* environment variables, image names and on-disk paths are
| deliberately left untouched so existing deployments survive an upgrade.
|
*/

$docs = env('BRAND_DOCS_URL', 'https://openrail.io/docs');

return [
    'name' => env('BRAND_NAME', 'OpenRail'),

    'tagline' => env('BRAND_TAGLINE', 'An open-source & self-hostable Heroku / Netlify / Vercel alternative'),

    'logo' => [
        'default' => 'openrail-logo.svg',
        'monochrome' => 'openrail-logo-monochrome.svg',
        'red' => 'openrail-logo-red.svg',
        'dev' => 'openrail-logo-dev.svg',
    ],

    'urls' => [
        'docs' => $docs,
        'contact' => env('BRAND_CONTACT_URL', $docs.'/contact'),
        'community' => env('BRAND_COMMUNITY_URL', 'https://openrail.io/discord'),
        'cloud' => env('BRAND_CLOUD_URL', 'https://app.openrail.io'),

        /*
         | Public repository of deployable example projects, offered as a
         | starting point in the "new resource" flow. Points upstream by
         | default because the repository has to actually exist; override it
         | to offer your own examples instead.
         */
        'examples' => env('BRAND_EXAMPLES_URL', 'https://github.com/coollabsio/coolify-examples'),
    ],
];

<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Set the IP addresses or CIDR blocks of trusted proxies. Behind Laravel
    | Cloud load balancers, '*' trusts all calling reverse proxies so that
    | URL generation, signed links and HTTPS schemes are accurately detected.
    |
    */
    'proxies' => env('TRUSTED_PROXIES', '*'),

    'headers' => env('TRUSTED_HEADERS', null),
];

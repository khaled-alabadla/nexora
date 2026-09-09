<?php

declare(strict_types=1);

return [

    /*
    |----------------------------------------------------------------------
    | API prefix
    |----------------------------------------------------------------------
    |
    | Every HTTP API route — core and module — is served under this prefix.
    | Kept in config so module providers and tests reference a single source.
    | Must match the `apiPrefix` argument in bootstrap/app.php.
    |
    */

    'api_prefix' => 'api/v1',

    /*
    |----------------------------------------------------------------------
    | Monetary precision
    |----------------------------------------------------------------------
    |
    | Standard scale for DECIMAL columns and money math. Amounts use scale 2;
    | unit prices / rates that need finer granularity use scale 4. Documented
    | here now; enforced by migrations and casts in later phases.
    |
    */

    'money' => [
        'amount_scale' => 2,
        'rate_scale' => 4,
        'precision' => 18,
    ],

];

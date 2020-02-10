<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Experiments
    |--------------------------------------------------------------------------
    |
    | A list of experiment identifiers.
    |
    | Example: ['big-logo', 'small-buttons']
    |
    */
    'experiments' => [],
    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | An associative list of URLs to redirect each experiment to.
    | Each key must be from the experiments list, will default to env('APP_URL')
    |
    | Example: ['big-logo' => 'ww1.url.com', 'small-buttons' => 'url.com']
    |
    */
    'urls' => [],
    /*
    |--------------------------------------------------------------------------
    | Goals
    |--------------------------------------------------------------------------
    |
    | A list of goals.
    |
    | Example: ['pricing/order', 'signup']
    |
    */
    'goals' => [],
    /*
    |--------------------------------------------------------------------------
    | Ignore Crawlers
    |--------------------------------------------------------------------------
    |
    | Ignore pageviews for crawlers.
    |
    */
    'ignore_crawlers' => false,
];

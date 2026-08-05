<?php

declare(strict_types=1);

return [
    'artifact_directory' => env('RELEASE_ARTIFACT_DIRECTORY', 'release'),
    'require_clean_tree' => (bool) env('RELEASE_REQUIRE_CLEAN_TREE', true),
    'max_phpstan_findings' => (int) env('RELEASE_MAX_PHPSTAN_FINDINGS', 118),
    'required_extensions' => [
        'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'json',
        'mbstring', 'openssl', 'pdo', 'session', 'tokenizer', 'xmlwriter',
    ],
];

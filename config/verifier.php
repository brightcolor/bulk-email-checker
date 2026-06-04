<?php

return [
    'smtp_enabled' => env('VERIFIER_SMTP_ENABLED', false),
    'catch_all_enabled' => env('VERIFIER_CATCH_ALL_ENABLED', true),
    'smtp_timeout' => (int) env('VERIFIER_SMTP_TIMEOUT', 5),
    'dns_timeout' => (int) env('VERIFIER_DNS_TIMEOUT', 5),
    'max_upload_size_mb' => (int) env('VERIFIER_MAX_UPLOAD_SIZE_MB', 10),
    'max_emails_per_job' => (int) env('VERIFIER_MAX_EMAILS_PER_JOB', 50000),
    'smtp_from_email' => env('VERIFIER_SMTP_FROM_EMAIL', 'verify@example.com'),
    'smtp_from_domain' => env('VERIFIER_SMTP_FROM_DOMAIN', 'example.com'),
];

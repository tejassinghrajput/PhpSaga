<?php

return [
    // Whether to persist audit log to the database
    // Requires running: php artisan migrate
    'log_to_database' => false,

    // Laravel log channel for ALERT_HUMAN strategy
    // Must match a channel defined in config/logging.php
    'alert_channel' => 'stack',

    // How many times to retry a failed compensation (RETRY strategy)
    'retry_attempts' => 3,

    // Initial delay in milliseconds between retry attempts (doubles each attempt)
    'retry_delay_ms' => 100,
];

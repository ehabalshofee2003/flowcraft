<?php

use App\Services\ExecutionLogger;

if (!function_exists('execution_logger')) {
    function execution_logger(): ExecutionLogger
    {
        return app(ExecutionLogger::class);
    }
}
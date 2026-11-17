<?php

return [
    'idle_seconds' => (int) env('ACTIVITY_TRACKING_IDLE_SECONDS', 300),
    'retention_days' => (int) env('ACTIVITY_TRACKING_RETENTION_DAYS', 365),
];

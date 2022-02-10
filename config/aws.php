<?php

return [
    'region' => env('AWS_KMS_REGION', 'ap-south-1'),
    'keyId' => env('AWS_KMS_KEY_ID'),
    'context' => [],
];

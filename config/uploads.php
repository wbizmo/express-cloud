<?php

declare(strict_types=1);

return [
    'image_max_bytes' => (int) env('UPLOAD_IMAGE_MAX_BYTES', 5_242_880),
    'document_max_bytes' => (int) env('UPLOAD_DOCUMENT_MAX_BYTES', 10_485_760),
];

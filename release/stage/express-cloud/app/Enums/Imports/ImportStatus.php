<?php

declare(strict_types=1);

namespace App\Enums\Imports;

enum ImportStatus: string
{
    case Uploaded = 'uploaded';
    case Validated = 'validated';
    case FailedValidation = 'failed_validation';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function terminal(): bool
    {
        return in_array(
            $this,
            [self::Completed, self::Failed, self::FailedValidation],
            true,
        );
    }
}

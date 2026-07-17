<?php

declare(strict_types=1);

namespace App\Support\Security;

final class SensitiveData
{
    /**
     * @return array<int, string>
     */
    public static function forbiddenLogFields(): array
    {
        return [
            'password',
            'password_confirmation',
            'login_key',
            'login_key_encrypted',
            'login_key_blind_index',
            'data_encryption_key',
            'blind_index_key',
            'backup_encryption_key',
            'cron_path_secret',
            'recovery_code',
            'api_secret',
        ];
    }
}

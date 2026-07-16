<?php

declare(strict_types=1);

namespace App\Enums\Authentication;

enum SecurityEventType: string
{
    case LoginSucceeded = 'login_succeeded';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case SessionRevoked = 'session_revoked';
    case ProfilePictureChanged = 'profile_picture_changed';
    case ProfilePictureRemoved = 'profile_picture_removed';
    case AccessKeyRevealed = 'access_key_revealed';
    case AccessKeyRegenerated = 'access_key_regenerated';
    case AccountSuspended = 'account_suspended';
    case AccountReactivated = 'account_reactivated';
}

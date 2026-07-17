<?php

declare(strict_types=1);

namespace App\Actions\Authentication;

use App\Models\Account;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class StoreProfilePicture
{
    public function execute(Account $account, UploadedFile $picture): string
    {
        $disk = (string) config(
            'authentication.profile_picture.disk',
            'public',
        );

        $directory = (string) config(
            'authentication.profile_picture.directory',
            'profile-pictures',
        );

        $previousPath = $account->profile_picture_path;

        $path = $picture->storePublicly(
            $directory.'/'.$account->getKey(),
            $disk,
        );

        if (is_string($previousPath) && $previousPath !== '') {
            Storage::disk($disk)->delete($previousPath);
        }

        $account->forceFill([
            'profile_picture_path' => $path,
        ])->save();

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Authentication;

use App\Models\Account;
use App\Services\Security\UploadSecurity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class StoreProfilePicture
{
    public function __construct(private UploadSecurity $uploads) {}

    public function execute(Account $account, UploadedFile $picture): string
    {
        $validated = $this->uploads->image($picture);
        $disk = (string) config('authentication.profile_picture.disk', 'public');
        $directory = (string) config('authentication.profile_picture.directory', 'profile-pictures');
        $previousPath = $account->profile_picture_path;

        $path = $picture->storePubliclyAs(
            $directory.'/'.$account->getKey(),
            $this->uploads->randomFilename($validated['extension']),
            $disk,
        );

        if (is_string($previousPath) && $previousPath !== '') {
            Storage::disk($disk)->delete($previousPath);
        }

        $account->forceFill(['profile_picture_path' => $path])->save();

        return $path;
    }
}

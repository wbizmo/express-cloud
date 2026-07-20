<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Actions\Authentication\StoreProfilePicture;
use App\Enums\Authentication\SecurityEventType;
use App\Http\Requests\Profile\UpdateProfilePictureRequest;
use App\Models\Account;
use App\Services\Authentication\SecurityEventRecorder;
use App\Support\Security\EncryptedValue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class ProfileController
{
    public function show(Request $request, EncryptedValue $encryptedValue): View
    {
        /** @var Account $account */
        $account = $request->user();

        return view('profile.show', [
            'account' => $account,
            'loginKey' => $encryptedValue->decrypt(
                $account->login_key_encrypted,
            ),
        ]);
    }

    public function updatePicture(
        UpdateProfilePictureRequest $request,
        StoreProfilePicture $storeProfilePicture,
        SecurityEventRecorder $securityEvents,
    ): RedirectResponse {
        /** @var Account $account */
        $account = $request->user();

        $storeProfilePicture->execute(
            $account,
            $request->file('profile_picture'),
        );

        $securityEvents->record(
            SecurityEventType::ProfilePictureChanged,
            $request,
            actor: $account,
            subject: $account,
        );

        return back()->with('status', 'Profile picture updated.');
    }

    public function destroyPicture(
        Request $request,
        SecurityEventRecorder $securityEvents,
    ): RedirectResponse {
        /** @var Account $account */
        $account = $request->user();

        $path = $account->profile_picture_path;

        if (is_string($path) && $path !== '') {
            Storage::disk((string) config(
                'authentication.profile_picture.disk',
                'public',
            ))->delete($path);
        }

        $account->forceFill([
            'profile_picture_path' => null,
        ])->save();

        $securityEvents->record(
            SecurityEventType::ProfilePictureRemoved,
            $request,
            actor: $account,
            subject: $account,
        );

        return back()->with('status', 'Profile picture removed.');
    }
}

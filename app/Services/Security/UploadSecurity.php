<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class UploadSecurity
{
    /** @return array{mime: string, extension: string} */
    public function image(UploadedFile $file): array
    {
        return $this->validate($file, [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ], (int) config('uploads.image_max_bytes', 5_242_880));
    }

    /** @return array{mime: string, extension: string} */
    public function document(UploadedFile $file): array
    {
        return $this->validate($file, [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ], (int) config('uploads.document_max_bytes', 10_485_760));
    }

    public function safeOriginalName(UploadedFile $file): string
    {
        $name = basename($file->getClientOriginalName());
        $name = preg_replace('/[^A-Za-z0-9._ -]+/', '-', $name) ?: 'upload';

        return Str::limit(trim($name), 180, '');
    }

    public function randomFilename(string $extension): string
    {
        return Str::uuid()->toString().'.'.$extension;
    }

    /**
     * @param  array<string, string>  $allowed
     * @return array{mime: string, extension: string}
     */
    private function validate(
        UploadedFile $file,
        array $allowed,
        int $maxBytes,
    ): array {
        $size = $file->getSize();
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        if (! $file->isValid() || $size === false || $size <= 0 || $size > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is invalid or exceeds the allowed size.',
            ]);
        }

        if (! array_key_exists($mime, $allowed)) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file type is not allowed.',
            ]);
        }

        return ['mime' => $mime, 'extension' => $allowed[$mime]];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Services\Security\UploadSecurity;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UploadSecurityTest extends TestCase
{
    #[Test]
    public function it_accepts_a_real_image_mime_and_generates_an_opaque_name(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'avatar.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='),
        );
        $service = new UploadSecurity;
        $validated = $service->image($file);

        self::assertSame('image/png', $validated['mime']);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}\.png$/', $service->randomFilename($validated['extension']));
    }

    #[Test]
    public function it_rejects_an_unapproved_document_mime(): void
    {
        $file = UploadedFile::fake()->create('payload.php', 1, 'application/x-php');

        $this->expectException(ValidationException::class);
        (new UploadSecurity)->document($file);
    }
}

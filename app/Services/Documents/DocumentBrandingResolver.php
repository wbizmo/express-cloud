<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\DocumentBranding;
use Illuminate\Support\Facades\Storage;

final class DocumentBrandingResolver
{
    /**
     * @return array{
     *   business_name:string,
     *   logo_data_uri:?string,
     *   address:?string,
     *   phone:?string,
     *   email:?string,
     *   receipt_footer:?string,
     *   document_terms:?string
     * }
     */
    public function resolve(): array
    {
        /** @var DocumentBranding|null $branding */
        $branding = DocumentBranding::query()->latest('updated_at')->first();

        $logo = null;

        if (
            $branding?->logo_path !== null
            && Storage::disk('public')->exists($branding->logo_path)
        ) {
            $contents = Storage::disk('public')->get(
                $branding->logo_path,
            );
            $mime = Storage::disk('public')->mimeType(
                $branding->logo_path,
            ) ?: 'image/png';

            $logo = sprintf(
                'data:%s;base64,%s',
                $mime,
                base64_encode($contents),
            );
        }

        return [
            'business_name' => $branding === null
                ? (string) config('app.name')
                : $branding->business_name,
            'logo_data_uri' => $logo,
            'address' => $branding?->address,
            'phone' => $branding?->phone,
            'email' => $branding?->email,
            'receipt_footer' => $branding?->receipt_footer,
            'document_terms' => $branding?->document_terms,
        ];
    }
}

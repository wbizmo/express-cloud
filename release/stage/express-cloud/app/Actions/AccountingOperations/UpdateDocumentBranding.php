<?php

declare(strict_types=1);

namespace App\Actions\AccountingOperations;

use App\Http\Requests\AccountingOperations\UpdateDocumentBrandingRequest;
use App\Models\Account;
use App\Models\DocumentBranding;
use Illuminate\Support\Facades\Storage;

final class UpdateDocumentBranding
{
    public function execute(
        UpdateDocumentBrandingRequest $request,
        Account $actor,
    ): DocumentBranding {
        /** @var DocumentBranding $branding */
        $branding = DocumentBranding::query()->firstOrNew();

        $logoPath = $branding->logo_path;

        if ($request->boolean('remove_logo') && $logoPath !== null) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        if ($request->hasFile('logo')) {
            if ($logoPath !== null) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')?->store(
                'company-branding',
                'public',
            );
        }

        $branding->forceFill([
            'business_name' => $request->string(
                'business_name',
            )->trim()->toString(),
            'logo_path' => $logoPath,
            'address' => $request->filled('address')
                ? $request->string('address')->trim()->toString()
                : null,
            'phone' => $request->filled('phone')
                ? $request->string('phone')->trim()->toString()
                : null,
            'email' => $request->filled('email')
                ? $request->string('email')->trim()->toString()
                : null,
            'receipt_footer' => $request->filled('receipt_footer')
                ? $request->string(
                    'receipt_footer',
                )->trim()->toString()
                : null,
            'document_terms' => $request->filled('document_terms')
                ? $request->string(
                    'document_terms',
                )->trim()->toString()
                : null,
            'updated_by_account_id' => $actor->getKey(),
        ])->save();

        return $branding->refresh();
    }
}

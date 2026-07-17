<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AccountingOperations;

use App\Actions\AccountingOperations\UpdateDocumentBranding;
use App\Http\Requests\AccountingOperations\UpdateDocumentBrandingRequest;
use App\Models\Account;
use App\Models\DocumentBranding;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class DocumentBrandingController
{
    public function __construct(
        private UpdateDocumentBranding $update,
        private AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        return view('admin.accounting-operations.branding', [
            'branding' => DocumentBranding::query()
                ->latest('updated_at')
                ->first(),
        ]);
    }

    public function update(
        UpdateDocumentBrandingRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $branding = $this->update->execute($request, $actor);

        $this->audit->record(
            $request,
            'document-branding.updated',
            'document_branding',
            $branding,
            after: $branding->only([
                'business_name',
                'logo_path',
                'address',
                'phone',
                'email',
            ]),
        );

        return back()->with(
            'status',
            'Document branding updated.',
        );
    }
}

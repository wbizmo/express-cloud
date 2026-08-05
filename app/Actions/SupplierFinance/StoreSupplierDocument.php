<?php

declare(strict_types=1);

namespace App\Actions\SupplierFinance;

use App\Models\Account;
use App\Models\SupplierBill;
use App\Models\SupplierDocument;
use App\Services\Security\UploadSecurity;
use Illuminate\Http\UploadedFile;

final readonly class StoreSupplierDocument
{
    public function __construct(private UploadSecurity $uploads) {}

    public function execute(
        UploadedFile $file,
        string $supplierId,
        ?SupplierBill $bill,
        Account $actor,
        ?string $description,
    ): SupplierDocument {
        $validated = $this->uploads->document($file);
        $disk = (string) config('supplier-finance.attachments.disk', 'local');
        $directory = (string) config('supplier-finance.attachments.directory', 'supplier-finance/attachments');
        $path = $file->storeAs(
            $directory.'/'.$supplierId,
            $this->uploads->randomFilename($validated['extension']),
            $disk,
        );

        return SupplierDocument::query()->create([
            'supplier_id' => $supplierId,
            'supplier_bill_id' => $bill?->getKey(),
            'uploaded_by_account_id' => $actor->getKey(),
            'original_filename' => $this->uploads->safeOriginalName($file),
            'stored_path' => $path,
            'mime_type' => $validated['mime'],
            'size_bytes' => $file->getSize(),
            'description' => $description,
        ]);
    }
}

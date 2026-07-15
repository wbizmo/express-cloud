<?php

declare(strict_types=1);

namespace App\Actions\SupplierFinance;

use App\Models\Account;
use App\Models\SupplierBill;
use App\Models\SupplierDocument;
use Illuminate\Http\UploadedFile;

final class StoreSupplierDocument
{
    public function execute(
        UploadedFile $file,
        string $supplierId,
        ?SupplierBill $bill,
        Account $actor,
        ?string $description,
    ): SupplierDocument {
        $disk = (string) config(
            'supplier-finance.attachments.disk',
            'local',
        );

        $directory = (string) config(
            'supplier-finance.attachments.directory',
            'supplier-finance/attachments',
        );

        $path = $file->store(
            $directory.'/'.$supplierId,
            $disk,
        );

        return SupplierDocument::query()->create([
            'supplier_id' => $supplierId,
            'supplier_bill_id' => $bill?->getKey(),
            'uploaded_by_account_id' => $actor->getKey(),
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType()
                ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'description' => $description,
        ]);
    }
}

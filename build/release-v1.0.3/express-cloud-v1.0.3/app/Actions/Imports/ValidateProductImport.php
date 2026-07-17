<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\Imports\ImportStatus;
use App\Exports\ProductImportErrorExport;
use App\Models\Account;
use App\Models\ProductImport;
use App\Models\ProductImportRow;
use App\Services\Imports\ProductImportValidator;
use App\Services\Imports\ProductWorkbookReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class ValidateProductImport
{
    public function __construct(
        private ProductWorkbookReader $reader,
        private ProductImportValidator $validator,
        private ProductImportErrorExport $errorExport,
    ) {}

    public function execute(
        UploadedFile $file,
        Account $account,
    ): ProductImport {
        $disk = (string) config('imports.products.disk', 'local');
        $directory = (string) config(
            'imports.products.directory',
            'imports/products',
        );

        $storedPath = $file->store($directory, $disk);
        $absolutePath = Storage::disk($disk)->path($storedPath);

        try {
            $rows = $this->reader->read($absolutePath);
            $validated = $this->validator->validate($rows);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);
            throw $exception;
        }

        $import = DB::transaction(function () use (
            $account,
            $file,
            $storedPath,
            $validated,
        ): ProductImport {
            $validRows = count(array_filter(
                $validated,
                static fn (array $row): bool => $row['is_valid'],
            ));
            $invalidRows = count($validated) - $validRows;

            $import = ProductImport::query()->create([
                'account_id' => $account->getKey(),
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'status' => $invalidRows === 0
                    ? ImportStatus::Validated
                    : ImportStatus::FailedValidation,
                'total_rows' => count($validated),
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'summary' => [
                    'preview_rows' => min(
                        count($validated),
                        (int) config(
                            'imports.products.preview_rows',
                            50,
                        ),
                    ),
                ],
                'validated_at' => now(),
            ]);

            foreach (
                array_chunk(
                    $validated,
                    (int) config('imports.products.batch_size', 250),
                ) as $chunk
            ) {
                $payload = array_map(
                    static fn (array $row): array => [
                        'id' => (string) Str::ulid(),
                        'product_import_id' => $import->getKey(),
                        'row_number' => $row['row_number'],
                        'payload' => json_encode(
                            $row['payload'],
                            JSON_THROW_ON_ERROR,
                        ),
                        'errors' => $row['errors'] === []
                            ? null
                            : json_encode(
                                $row['errors'],
                                JSON_THROW_ON_ERROR,
                            ),
                        'is_valid' => $row['is_valid'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    $chunk,
                );

                ProductImportRow::query()->insert($payload);
            }

            return $import;
        });

        if ($import->invalid_rows > 0) {
            $errorDirectory = (string) config(
                'imports.products.error_directory',
                'imports/products/errors',
            );
            $errorPath = $errorDirectory.'/'.$import->getKey().'.xlsx';
            Storage::disk($disk)->makeDirectory($errorDirectory);

            $this->errorExport->create(
                $import,
                Storage::disk($disk)->path($errorPath),
            );

            $import->forceFill([
                'error_report_path' => $errorPath,
            ])->save();
        }

        return $import;
    }
}

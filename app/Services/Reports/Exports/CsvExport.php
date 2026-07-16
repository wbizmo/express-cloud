<?php

declare(strict_types=1);

namespace App\Services\Reports\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExport
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public function download(
        string $filename,
        array $headings,
        iterable $rows,
    ): StreamedResponse {
        return response()->streamDownload(
            static function () use ($headings, $rows): void {
                $handle = fopen('php://output', 'wb');

                if ($handle === false) {
                    throw new \RuntimeException(
                        'Unable to create export stream.',
                    );
                }

                fputcsv($handle, $headings, ', ', '"', '', '"', '', '');

                foreach ($rows as $row) {
                    fputcsv($handle, $row, ', ', '"', '', '"', '', '');
                }

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }
}

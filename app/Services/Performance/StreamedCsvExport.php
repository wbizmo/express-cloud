<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class StreamedCsvExport
{
    /**
     * @param Builder<*> $query
     * @param  list<string>  $headings
     * @param  callable(object): list<string|int|float|null>  $map
     */
    public function download(
        Builder $query,
        array $headings,
        callable $map,
        string $filename,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($query, $headings, $map): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                throw new \RuntimeException('Unable to open the export stream.');
            }
            fputcsv($stream, $headings);
            $query->orderBy('id')->lazyById(
                (int) config('performance.stream_chunk_size', 500),
            )->each(static function (object $row) use ($stream, $map): void {
                fputcsv($stream, $map($row));
            });
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

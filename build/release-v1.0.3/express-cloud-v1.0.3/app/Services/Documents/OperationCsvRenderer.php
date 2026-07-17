<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Support\Documents\OperationReportData;

final class OperationCsvRenderer
{
    public function render(OperationReportData $report): string
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new \RuntimeException(
                'Unable to create spreadsheet stream.',
            );
        }

        fputcsv($stream, [$report->title], ',', '"', '');
        fputcsv($stream, ['Reference', $report->reference], ',', '"', '');
        fputcsv($stream, ['Date', $report->date], ',', '"', '');
        fputcsv($stream, [], ',', '"', '');

        if ($report->rows !== []) {
            fputcsv(
                $stream,
                array_keys($report->rows[0]),
                ',',
                '"',
                '',
            );

            foreach ($report->rows as $row) {
                fputcsv(
                    $stream,
                    array_values($row),
                    ',',
                    '"',
                    '',
                );
            }
        }

        fputcsv($stream, [], ',', '"', '');

        foreach ($report->summary as $label => $value) {
            fputcsv($stream, [$label, $value], ',', '"', '');
        }

        if ($report->notes !== null) {
            fputcsv($stream, [], ',', '"', '');
            fputcsv($stream, ['Notes', $report->notes], ',', '"', '');
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        if (! is_string($contents)) {
            throw new \RuntimeException(
                'Unable to read spreadsheet stream.',
            );
        }

        return "\xEF\xBB\xBF".$contents;
    }
}
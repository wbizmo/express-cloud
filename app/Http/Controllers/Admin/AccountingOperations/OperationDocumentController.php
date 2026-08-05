<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AccountingOperations;

use App\Models\Account;
use App\Models\OperationDocumentLog;
use App\Services\Documents\OperationCsvRenderer;
use App\Services\Documents\OperationPdfRenderer;
use App\Services\Documents\OperationReportFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class OperationDocumentController
{
    public function __construct(
        private OperationReportFactory $factory,
        private OperationPdfRenderer $pdf,
        private OperationCsvRenderer $csv,
    ) {}

    public function pdf(
        Request $request,
        string $type,
        string $id,
    ): Response {
        /** @var Account $actor */
        $actor = $request->user();
        $report = $this->factory->make($type, $id, $actor);
        $contents = $this->pdf->render($report);

        $this->log($request, $type, $id, 'pdf', $contents);

        return response($contents)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'attachment; filename="'
                    .$this->filename($report->reference, 'pdf')
                    .'"',
            );
    }

    public function spreadsheet(
        Request $request,
        string $type,
        string $id,
    ): Response {
        /** @var Account $actor */
        $actor = $request->user();
        $report = $this->factory->make($type, $id, $actor);
        $contents = $this->csv->render($report);

        $this->log(
            $request,
            $type,
            $id,
            'spreadsheet',
            $contents,
        );

        return response($contents)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header(
                'Content-Disposition',
                'attachment; filename="'
                    .$this->filename($report->reference, 'csv')
                    .'"',
            );
    }

    private function log(
        Request $request,
        string $type,
        string $id,
        string $format,
        string $contents,
    ): void {
        /** @var Account $actor */
        $actor = $request->user();

        OperationDocumentLog::query()->create([
            'operation_type' => $type,
            'operation_id' => $id,
            'format' => $format,
            'document_hash' => hash('sha256', $contents),
            'generated_by_account_id' => $actor->getKey(),
            'generated_at' => now(),
        ]);
    }

    private function filename(
        string $reference,
        string $extension,
    ): string {
        $safe = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            $reference,
        );

        return ($safe ?: 'operation-report').'.'.$extension;
    }
}

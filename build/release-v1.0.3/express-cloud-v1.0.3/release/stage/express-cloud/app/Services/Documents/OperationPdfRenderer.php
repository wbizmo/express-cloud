<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Support\Documents\OperationReportData;
use Dompdf\Dompdf;
use Dompdf\Options;

final readonly class OperationPdfRenderer
{
    public function __construct(
        private DocumentBrandingResolver $branding,
    ) {}

    public function render(OperationReportData $report): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            view('documents.operations.report', [
                'report' => $report,
                'branding' => $this->branding->resolve(),
            ])->render(),
        );
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}

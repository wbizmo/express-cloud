<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Documents;

use App\Models\Sale;
use App\Services\Documents\PdfRenderer;
use App\Services\Documents\SaleDocumentData;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

final readonly class SaleDocumentController
{
    public function __construct(
        private SaleDocumentData $documents,
        private PdfRenderer $pdf,
    ) {}

    public function thermal(Sale $sale): View
    {
        return view(
            'documents.sale-thermal',
            $this->documents->make($sale),
        );
    }

    public function a4(Sale $sale): View
    {
        return view(
            'documents.sale-a4',
            $this->documents->make($sale),
        );
    }

    public function pdf(Sale $sale): Response
    {
        $content = $this->pdf->render(
            'documents.sale-a4',
            $this->documents->make($sale),
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf(
                'attachment; filename="%s.pdf"',
                $sale->sale_code,
            ),
        ]);
    }
}

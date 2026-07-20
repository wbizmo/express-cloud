<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Documents;

use App\Models\Account;
use App\Models\Sale;
use App\Services\Documents\PdfRenderer;
use App\Services\Documents\SaleDocumentData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SaleDocumentController
{
    public function __construct(
        private SaleDocumentData $documents,
        private PdfRenderer $pdf,
    ) {}

    public function thermal(Request $request, Sale $sale): View
    {
        /** @var Account $actor */
        $actor = $request->user();

        return view(
            'documents.sale-thermal',
            $this->documents->make($sale, $actor),
        );
    }

    public function a4(Request $request, Sale $sale): View
    {
        /** @var Account $actor */
        $actor = $request->user();

        return view(
            'documents.sale-a4',
            $this->documents->make($sale, $actor),
        );
    }

    public function pdf(Request $request, Sale $sale): Response
    {
        /** @var Account $actor */
        $actor = $request->user();

        $content = $this->pdf->render(
            'documents.sale-a4',
            $this->documents->make($sale, $actor),
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

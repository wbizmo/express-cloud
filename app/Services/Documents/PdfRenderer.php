<?php

declare(strict_types=1);

namespace App\Services\Documents;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfRenderer
{
    /** @param array<string, mixed> $data */
    public function render(string $view, array $data): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data)->render());
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}

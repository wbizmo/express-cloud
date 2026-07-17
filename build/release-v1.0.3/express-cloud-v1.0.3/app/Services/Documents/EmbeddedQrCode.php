<?php

declare(strict_types=1);

namespace App\Services\Documents;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

final class EmbeddedQrCode
{
    public function dataUri(string $payload): string
    {
        $result = new Builder(
            writer: new SvgWriter,
            data: $payload,
            size: 240,
            margin: 8,
        )->build();

        return $result->getDataUri();
    }
}

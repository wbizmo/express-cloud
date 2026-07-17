<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Documents;

use App\Models\Product;
use App\Services\Documents\EmbeddedQrCode;
use Illuminate\Contracts\View\View;

final readonly class ProductBarcodeController
{
    public function __construct(private EmbeddedQrCode $codes) {}

    public function __invoke(Product $product): View
    {
        $payload = $product->barcode ?: $product->sku;

        return view('documents.product-barcode-label', [
            'product' => $product,
            'codeDataUri' => $this->codes->dataUri($payload),
            'payload' => $payload,
        ]);
    }
}

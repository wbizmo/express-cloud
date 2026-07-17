<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class StoreProductImage
{
    public function execute(
        Product $product,
        UploadedFile $image,
    ): string {
        $disk = (string) config('catalog.images.disk', 'public');
        $directory = (string) config(
            'catalog.images.directory',
            'product-images',
        );

        $previousPath = $product->image_path;

        $path = $image->storePublicly(
            $directory.'/'.$product->getKey(),
            $disk,
        );

        if (is_string($previousPath) && $previousPath !== '') {
            Storage::disk($disk)->delete($previousPath);
        }

        $product->forceFill(['image_path' => $path])->save();

        return $path;
    }
}

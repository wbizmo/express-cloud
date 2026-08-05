<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Models\Product;
use App\Services\Security\UploadSecurity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class StoreProductImage
{
    public function __construct(private UploadSecurity $uploads) {}

    public function execute(Product $product, UploadedFile $image): string
    {
        $validated = $this->uploads->image($image);
        $disk = (string) config('catalog.images.disk', 'public');
        $directory = (string) config('catalog.images.directory', 'product-images');
        $previousPath = $product->image_path;

        $path = $image->storePubliclyAs(
            $directory.'/'.$product->getKey(),
            $this->uploads->randomFilename($validated['extension']),
            $disk,
        );

        if (is_string($previousPath) && $previousPath !== '') {
            Storage::disk($disk)->delete($previousPath);
        }

        $product->forceFill(['image_path' => $path])->save();

        return $path;
    }
}

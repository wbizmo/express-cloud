<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\BulkPriceAdjustmentRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Services\Organisation\BranchAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final readonly class ProductPriceAdjustmentController
{
    public function __construct(private BranchAccess $branchAccess) {}
    public function index(): View { return view('admin.catalog.price-adjustments', ['branches'=>Branch::query()->where('status','active')->orderBy('name')->get(['id','name'])]); }
    public function store(BulkPriceAdjustmentRequest $request): RedirectResponse
    {
        /** @var Account $actor */ $actor=$request->user();
        $branchIds=$request->boolean('all_branches')
            ? Branch::query()->where('status','active')->pluck('id')->all()
            : $request->array('branch_ids');
        foreach($branchIds as $branchId) $this->branchAccess->enforce($actor,(string)$branchId);
        $productIds=$request->boolean('all_products') ? Product::query()->where('status','active')->pluck('id')->all() : $request->array('product_ids');
        abort_if($branchIds===[] || $productIds===[],422,'Select at least one branch and product.');
        DB::transaction(function() use($request,$branchIds,$productIds):void{
            foreach(Product::query()->whereKey($productIds)->cursor() as $product){
                foreach($branchIds as $branchId){
                    $current=(int)(ProductBranchPrice::query()->where('product_id',$product->getKey())->where('branch_id',$branchId)->value('price_kobo') ?? $product->default_price_kobo);
                    $delta=$request->string('mode')->toString()==='percentage' ? (int)round($current*((float)$request->input('value')/100)) : (int)round((float)$request->input('value')*100);
                    $next=$request->string('direction')->toString()==='subtract' ? max(0,$current-$delta) : $current+$delta;
                    ProductBranchPrice::query()->updateOrCreate(['product_id'=>$product->getKey(),'branch_id'=>$branchId],['price_kobo'=>$next]);
                }
            }
        });
        return back()->with('status','Branch prices updated.');
    }
}

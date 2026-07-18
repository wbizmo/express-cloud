<?php

declare(strict_types=1);
namespace App\Services\Insights;
use App\Models\Account;
use App\Models\ProductBranchStock;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
final class LisaConversationEngine
{
    public function answer(Account $actor,string $question): array
    {
        $branchIds=$actor->is_allowed_all_branches?null:$actor->branches()->pluck('branches.id');
        $sales=Sale::query()->when($branchIds!==null,fn(Builder $q)=>$q->whereIn('branch_id',$branchIds))->whereDate('created_at',today());
        $stock=ProductBranchStock::query()->when($branchIds!==null,fn(Builder $q)=>$q->whereIn('branch_id',$branchIds));
        $ctx=['sales_count'=>(clone $sales)->count(),'sales_total_kobo'=>(int)(clone $sales)->sum('grand_total_kobo'),
            'low_stock'=>(clone $stock)->whereColumn('quantity_milliunits','<=','minimum_stock_milliunits')->count(),
            'open_purchases'=>PurchaseOrder::query()->when($branchIds!==null,fn(Builder $q)=>$q->whereIn('branch_id',$branchIds))->whereNotIn('status',['completed','cancelled'])->count()];
        $q=Str::lower(trim($question)); $name=trim(($actor->first_name??'').' '.($actor->last_name??''))?:'there';
        if (Str::contains($q,['politics','medical diagnosis','betting','relationship advice']))
            return ['reply'=>"Sorry {$name}, I can only respond about Express Cloud and business data you are authorised to access. Contact your manager or administrator.",'context'=>$ctx];
        $reply=match(true){
            Str::contains($q,['how to create a sale','how do i create a sale'])=>'Open Create Sale, select an active branch, optionally add or choose a customer, select products or scan a barcode, confirm quantities and payment, then complete the transaction.',
            Str::contains($q,['how to add a customer','how do i add a customer'])=>'On Create Sale or POS, click New Customer. Only name is required. Phone, WhatsApp, email, address and notes are optional. You may leave it blank for a walk-in customer.',
            Str::contains($q,['how to transfer stock','how do i transfer stock'])=>'Open Inventory, choose source and destination branches, select the product, verify source and destination stock, enter quantity and a reference note, then submit.',
            Str::contains($q,['today sales",\"today's sales\","revenue'])=>sprintf('%s, your authorised scope has %d sale(s) today worth ₦%s.',$name,$ctx['sales_count'],number_format($ctx['sales_total_kobo']/100,2)),
            Str::contains($q,['low stock','stockout'])=>sprintf('There are %d low-stock rows in your authorised branch scope.',$ctx['low_stock']),
            Str::contains($q,['purchase','supplier'])=>sprintf('There are %d open purchase orders in your authorised scope.',$ctx['open_purchases']),
            default=>"I understand, {$name}. Ask me about sales, stock, customers, purchasing, staff performance, reports or how to use Express Cloud.",
        };
        return ['reply'=>$reply,'context'=>$ctx];
    }
}

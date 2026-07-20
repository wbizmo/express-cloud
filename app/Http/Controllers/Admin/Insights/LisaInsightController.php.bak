<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Insights;

use App\Models\BusinessInsight;
use App\Services\Insights\LisaInsightEngine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class LisaInsightController
{
    public function __construct(private LisaInsightEngine $engine) {}

    public function index(Request $request): View
    {
        $from = $request->date('from')?->toDateString() ?? now()->subDays(30)->toDateString();
        $to = $request->date('to')?->toDateString() ?? today()->toDateString();

        return view('admin.insights.index', [
            'from' => $from,
            'to' => $to,
            'insights' => BusinessInsight::query()
                ->whereBetween('period_end', [$from, $to])
                ->whereNull('dismissed_at')
                ->latest('generated_at')
                ->paginate(config('pagination.default', 10))
                ->withQueryString(),
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $count = $this->engine->generate($validated['from'], $validated['to']);

        return back()->with('status', sprintf('Lisa refreshed %d business insights.', $count));
    }

    public function dismiss(BusinessInsight $insight): RedirectResponse
    {
        $insight->forceFill(['dismissed_at' => now()])->save();

        return back()->with('status', 'Insight dismissed.');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Insights;

use App\Models\Account;
use App\Models\LisaConversation;
use App\Models\LisaMessage;
use App\Services\Insights\LisaConversationEngine;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final readonly class LisaChatController
{
    public function __construct(private LisaConversationEngine $engine, private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $search = $request->string('search')->trim()->toString();

        /** @var view-string $view */
        $view = 'admin.insights.chat';

        return view($view, [
            'activeConversation' => null,
            'messagePage' => null,
            'conversations' => $this->conversations($actor, $search),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $branchId = $request->string('branch_id')->trim()->toString() ?: null;
        if ($branchId !== null && ! $actor->is_allowed_all_branches) {
            abort_unless($actor->branches()->whereKey($branchId)->exists(), 404);
        }
        $conversation = LisaConversation::query()->create([
            'account_id' => $actor->getKey(),
            'branch_id' => $branchId,
            'title' => 'New conversation',
            'last_message_at' => now(),
        ]);
        $this->audit->record($request, 'lisa.conversation.created', 'lisa_conversation', $conversation);

        return redirect()->route('admin.insights.chat.show', $conversation);
    }

    public function show(Request $request, LisaConversation $conversation): View
    {
        /** @var Account $actor */
        $actor = $request->user();
        $this->enforceOwner($actor, $conversation);
        $messages = $conversation->messages()->latest('created_at')->paginate((int) config('lisa.message_page_size', 50))->withQueryString();
        $messages->setCollection($messages->getCollection()->reverse()->values());

        /** @var view-string $view */
        $view = 'admin.insights.chat';

        return view($view, [
            'activeConversation' => $conversation,
            'messagePage' => $messages,
            'conversations' => $this->conversations($actor, ''),
        ]);
    }

    public function message(Request $request, LisaConversation $conversation): JsonResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $this->enforceOwner($actor, $conversation);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:'.(int) config('lisa.max_question_characters', 5000)],
        ]);
        $question = trim((string) $validated['message']);
        $start = hrtime(true);
        LisaMessage::query()->create([
            'conversation_id' => $conversation->getKey(),
            'account_id' => $actor->getKey(),
            'role' => 'user',
            'content' => $question,
        ]);
        $result = $this->engine->answer($actor, $conversation, $question);
        $milliseconds = (int) round((hrtime(true) - $start) / 1_000_000);
        LisaMessage::query()->create([
            'conversation_id' => $conversation->getKey(),
            'role' => 'assistant',
            'content' => $result['reply'],
            'context_snapshot' => [
                'snapshot_id' => $result['snapshot_id'],
                'evidence_hash' => $result['evidence_hash'],
            ],
            'response_time_ms' => $milliseconds,
        ]);
        $conversation->forceFill([
            'title' => $conversation->title === 'New conversation' ? Str::limit($question, 80) : $conversation->title,
            'last_message_at' => now(),
        ])->save();
        $this->audit->record($request, 'lisa.message.sent', 'lisa_conversation', $conversation, after: [
            'question_preview' => Str::limit($question, 160),
            'response_time_ms' => $milliseconds,
            'snapshot_id' => $result['snapshot_id'],
        ]);

        return response()->json([
            'reply' => $result['reply'],
            'response_time_ms' => $milliseconds,
            'snapshot_id' => $result['snapshot_id'],
            'evidence_hash' => $result['evidence_hash'],
        ]);
    }

    public function audit(Request $request): View
    {
        /** @var view-string $view */
        $view = 'admin.insights.audit';

        return view($view, [
            'conversations' => LisaConversation::query()->with(['account', 'branch'])->withCount('messages')->latest('last_message_at')->paginate(config('pagination.default', 10)),
        ]);
    }

    public function auditShow(LisaConversation $conversation): View
    {
        /** @var view-string $view */
        $view = 'admin.insights.audit-show';

        return view($view, [
            'conversation' => $conversation->load(['account', 'branch']),
            'messages' => $conversation->messages()->latest('created_at')->paginate((int) config('lisa.message_page_size', 50)),
        ]);
    }

    private function enforceOwner(Account $actor, LisaConversation $conversation): void
    {
        abort_unless((string) $conversation->account_id === (string) $actor->getKey(), 404);
    }

    /** @return LengthAwarePaginator<int, LisaConversation> */
    private function conversations(Account $actor, string $search): LengthAwarePaginator
    {
        return LisaConversation::query()->where('account_id', $actor->getKey())
            ->when($search !== '', static fn ($query) => $query->where(static fn ($nested) => $nested->where('title', 'like', '%'.$search.'%')->orWhereHas('messages', static fn ($messages) => $messages->where('content', 'like', '%'.$search.'%'))))
            ->latest('last_message_at')->paginate((int) config('lisa.conversation_page_size', 20))->withQueryString();
    }
}

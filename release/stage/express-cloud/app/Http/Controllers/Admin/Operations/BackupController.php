<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Models\Account;
use App\Models\BackupRun;
use App\Services\Backup\CreateApplicationBackup;
use App\Services\Backup\VerifyStoredBackup;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class BackupController
{
    public function __construct(
        private CreateApplicationBackup $create,
        private VerifyStoredBackup $verify,
        private AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.operations.backups', [
            'runs' => BackupRun::query()
                ->orderByDesc('started_at')
                ->paginate(40),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Account $actor */
        $actor = $request->user();

        $run = $this->create->execute(
            (string) $actor->getKey(),
        );

        $this->audit->record(
            $request,
            'backup.created',
            'backup_run',
            $run,
            after: [
                'path' => $run->path,
                'checksum_sha256' => $run->checksum_sha256,
                'size_bytes' => $run->size_bytes,
            ],
        );

        return back()->with(
            'status',
            "Backup {$run->id} completed.",
        );
    }

    public function verify(
        Request $request,
        BackupRun $backupRun,
    ): RedirectResponse {
        $valid = $this->verify->execute($backupRun);

        $this->audit->record(
            $request,
            'backup.verified',
            'backup_run',
            $backupRun,
            after: ['valid' => $valid],
        );

        return back()->with(
            $valid ? 'status' : 'error',
            $valid
                ? 'Backup checksum verified.'
                : 'Backup verification failed.',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Organisation;

use App\Models\Account;
use Illuminate\Support\Facades\Route;

final readonly class NavigationVisibility
{
    public function __construct(private AuthorizationService $authorization) {}

    /**
     * @param  array<int, array{label: string, items: array<int, array<string, mixed>>}>  $sections
     * @return array<int, array{label: string, items: array<int, array<string, mixed>>}>
     */
    public function sections(Account $account, array $sections): array
    {
        $visible = [];

        foreach ($sections as $section) {
            $items = array_values(array_filter(
                $section['items'] ?? [],
                fn (array $item): bool => $this->canSee($account, $item),
            ));

            if ($items !== []) {
                $visible[] = [
                    'label' => (string) ($section['label'] ?? ''),
                    'items' => $items,
                ];
            }
        }

        return $visible;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function items(Account $account, array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item): bool => $this->canSee($account, $item),
        ));
    }

    /** @param array<string, mixed> $item */
    public function canSee(Account $account, array $item): bool
    {
        $route = $item['route'] ?? null;

        if (! is_string($route) || $route === '' || ! Route::has($route)) {
            return false;
        }

        $permission = $item['permission'] ?? null;

        if (is_string($permission) && $permission !== '') {
            return $this->authorization->hasPermission($account, $permission);
        }

        $any = $item['permission_any'] ?? null;

        if (is_array($any) && $any !== []) {
            return $this->authorization->hasAnyPermission($account, $any);
        }

        $all = $item['permission_all'] ?? null;

        if (is_array($all) && $all !== []) {
            return $this->authorization->hasAllPermissions($account, $all);
        }

        return true;
    }
}

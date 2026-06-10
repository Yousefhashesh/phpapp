<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permissions\AccountPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'user.profile.page');
        $this->authorizePermission($request, 'user.profile.view');

        /** @var User $user */
        $user = $request->user();
        $user->load([
            'roles:id,name,label',
            'shipper',
            'client',
        ]);

        return response()->json($this->formatProfilePayload($request, $user));
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'user.profile.update');

        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        DB::transaction(function () use ($user, $data): void {
            if (! empty($data)) {
                $user->update($data);
            }
        });

        $user->refresh();
        $user->load([
            'roles:id,name,label',
            'shipper',
            'client',
        ]);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => $this->formatProfilePayload($request, $user),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    /**
     * @param array<int, string> $columns
     */
    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = AccountPermissionMap::PROFILE_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission for profile column [{$column}]: {$permission}");
            }
        }
    }

    private function formatProfilePayload(Request $request, User $user): array
    {
        $payload = $user->toArray();
        $accountType = $user->shipper ? 'shipper' : ($user->client ? 'client' : 'user');
        $payload['account_type'] = $this->accountTypeToCode($accountType);

        $result = [];

        foreach (AccountPermissionMap::PROFILE_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $payload['id'];
        }

        if (! array_key_exists('account_type', $result)) {
            $result['account_type'] = $payload['account_type'];
        }

        return $result;
    }

    private function accountTypeToCode(string $accountType): int
    {
        return match ($accountType) {
            'client' => 1,
            'shipper' => 2,
            default => 0,
        };
    }
}

<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions\AccountPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Toggle user block status (is_blocked).
     */
    /**
     * Update user roles.
     */
    public function updateRoles(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'user.update');
        
        $data = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->syncRoles($data['roles']);

        return response()->json([
            'message' => 'User roles updated successfully.',
            'data' => $user->load('roles:id,name,label'),
        ]);
    }

    public function toggleBlock(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'user.update');
        $user->is_blocked = !$user->is_blocked;
        $user->save();
        return response()->json([
            'message' => 'User block status toggled.',
            'is_blocked' => $user->is_blocked,
        ]);
    }
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'user.page');
        $this->authorizePermission($request, 'user.view');

        $query = User::query()
            ->with([
                'roles:id,name,label',
                'shipper',
                'client.plan',
                'client.shippingContent',
                'loginSessions',
            ]);

        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Search query
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status (is_blocked)
        if ($request->filled('status')) {
            $isBlocked = $request->status === 'blocked';
            $query->where('is_blocked', $isBlocked);
        }

        // Sorting
        $sortBy = $request->input('sortBy', 'id');
        $orderBy = $request->input('orderBy', 'desc');
        $query->orderBy($sortBy, $orderBy);

        // Pagination
        $itemsPerPage = (int)$request->input('itemsPerPage', 10);
        
        if ($itemsPerPage === -1) {
            $users = $query->get();
            $data = $users->map(fn (User $user) => $this->formatUserPayload($request, $user));
            return response()->json(['data' => $data, 'total' => $data->count()]);
        }

        $paginator = $query->paginate($itemsPerPage);
        
        // Stats for widgets (Safe counts to avoid RoleDoesNotExist exception)
        $totalUsersCount = User::count();
        $shippersCount = User::whereHas('roles', fn($q) => $q->where('name', 'shipper'))->count();
        $clientsCount = User::whereHas('roles', fn($q) => $q->where('name', 'client'))->count();
        $blockedCount = User::where('is_blocked', true)->count();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (User $user) => $this->formatUserPayload($request, $user)),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'stats' => [
                'total' => $totalUsersCount,
                'shippers' => $shippersCount,
                'clients' => $clientsCount,
                'blocked' => $blockedCount,
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'user.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'is_blocked' => ['nullable', 'boolean'],
            'account_type' => ['nullable', Rule::in([0, 1, 2, '0', '1', '2'])],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $normalizedAccountType = $this->normalizeAccountType($data['account_type'] ?? '0');

        $typeSpecificData = $request->validate([
            'commission_rate' => [
                Rule::requiredIf($normalizedAccountType === 'shipper'),
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],
            'address' => [
                Rule::requiredIf($normalizedAccountType === 'client'),
                'nullable',
                'string',
                'max:255',
            ],
            'plan_id' => [
                Rule::requiredIf($normalizedAccountType === 'client'),
                'nullable',
                'integer',
                'exists:plans,id',
            ],
            'shipping_content_id' => [
                Rule::requiredIf($normalizedAccountType === 'client'),
                'nullable',
                'integer',
                'exists:content,id',
            ],
            'can_settle_before_shipper_collected' => ['nullable', 'boolean'],
        ]);

        $data = array_merge($data, $typeSpecificData);

        $user = DB::transaction(function () use ($data, $normalizedAccountType): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'phone' => $data['phone'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'password' => $data['password'],
                'is_blocked' => $data['is_blocked'] ?? false,
            ]);

            // Sync specifically selected roles if provided, otherwise fallback to account_type logic
            if (!empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            // Always apply account type logic to ensure client/shipper tables are populated
            $this->applyAccountType($user, $normalizedAccountType, $data);

            return $user;
        });

        $user->load([
            'roles:id,name,label',
            'shipper',
            'client.plan',
            'client.shippingContent',
            'loginSessions:id,user_id,session_id,ip_address,user_agent,device_name,device_type,browser,platform,country,city,login_at,last_seen_at,logout_at,is_active,is_current,created_at,updated_at',
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $this->formatUserPayload($request, $user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $request = request();
        $this->authorizePermission($request, 'user.page');
        $this->authorizePermission($request, 'user.view');

        $user->load([
            'roles:id,name,label',
            'shipper',
            'client.plan',
            'client.shippingContent',
            'loginSessions:id,user_id,session_id,ip_address,user_agent,device_name,device_type,browser,platform,country,city,login_at,last_seen_at,logout_at,is_active,is_current,created_at,updated_at',
        ]);

        return response()->json($this->formatUserPayload($request, $user));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorizePermission($request, 'user.update');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->ignore($user->id)],
            'avatar' => ['nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'is_blocked' => ['nullable', 'boolean'],
            'account_type' => ['sometimes', 'required', Rule::in([0, 1, 2, '0', '1', '2'])],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'address' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'shipping_content_id' => ['nullable', 'integer', 'exists:content,id'],
            'can_settle_before_shipper_collected' => ['nullable', 'boolean'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        if (array_key_exists('account_type', $data)) {
            $this->authorizeAccountTypeButton($request, (int) $data['account_type']);
        }

        DB::transaction(function () use ($data, $user): void {
            $userData = collect($data)->only([
                'name',
                'username',
                'phone',
                'avatar',
                'password',
                'is_blocked',
            ])->toArray();

            if (! empty($userData)) {
                $user->update($userData);
            }

            if (array_key_exists('account_type', $data)) {
                $this->applyAccountType($user, $this->normalizeAccountType($data['account_type']), $data);
            } elseif ($user->shipper && array_key_exists('commission_rate', $data)) {
                $user->shipper()->update([
                    'commission_rate' => $data['commission_rate'],
                ]);
            } elseif ($user->client) {
                $clientData = collect($data)->only(['address', 'plan_id', 'shipping_content_id', 'can_settle_before_shipper_collected'])->toArray();
 
                 if (! empty($clientData)) {
                     $user->client()->update($clientData);
                 }
            }
        });

        $user->load([
            'roles:id,name,label',
            'shipper',
            'client.plan',
            'client.shippingContent',
            'loginSessions:id,user_id,session_id,ip_address,user_agent,device_name,device_type,browser,platform,country,city,login_at,last_seen_at,logout_at,is_active,is_current,created_at,updated_at',
        ]);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $this->formatUserPayload($request, $user),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorizePermission(request(), 'user.delete');

        $user->shipper()?->delete();
        $user->client()?->delete();
        $user->forceDelete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    private function applyAccountType(User $user, string $accountType, array $data): void
    {
        if ($accountType === 'shipper') {
            $user->client()?->delete();

            $user->shipper()->updateOrCreate(
                ['user_id' => $user->id],
                ['commission_rate' => $data['commission_rate'] ?? 0]
            );

            $this->syncRoleIfExists($user, 'shipper');

            return;
        }

        if ($accountType === 'client') {
            $user->shipper()?->delete();

            $user->client()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'address' => $data['address'] ?? null,
                    'plan_id' => $data['plan_id'] ?? null,
                    'shipping_content_id' => $data['shipping_content_id'] ?? null,
                    'can_settle_before_shipper_collected' => $data['can_settle_before_shipper_collected'] ?? false,
                ]
            );

            $this->syncRoleIfExists($user, 'client');

            return;
        }

        $user->shipper()?->delete();
        $user->client()?->delete();
        $this->syncRoleIfExists($user, 'user');
    }

    private function syncRoleIfExists(User $user, string $roleName): void
    {
        $role = Role::query()->where('name', $roleName)->first();

        if ($role) {
            $user->syncRoles([$roleName]);
        }
    }

    private function formatUserPayload(Request $request, User $user): array
    {
        $payload = $user->toArray();
        $payload['login_sessions'] = $user->loginSessions
            ? $user->loginSessions->values()->toArray()
            : [];

        $accountType = $user->shipper ? 'shipper' : ($user->client ? 'client' : 'user');
        $payload['account_type'] = $this->accountTypeToCode($accountType);
        $payload['role'] = $user->roles->first()?->name ?? 'user';

        $result = [];

        foreach (AccountPermissionMap::USER_VIEW_COLUMNS as $column => $permission) {
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
        if (! array_key_exists('role', $result)) {
            $result['role'] = $payload['role'];
        }

        return $result;
    }

    private function normalizeAccountType(string|int $accountType): string
    {
        return match ((string) $accountType) {
            '0' => 'user',
            '1' => 'client',
            '2' => 'shipper',
            default => (string) $accountType,
        };
    }

    private function accountTypeToCode(string $accountType): int
    {
        return match ($accountType) {
            'client' => 1,
            'shipper' => 2,
            default => 0,
        };
    }

    /**
     * @param array<int, string> $columns
     */
    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = AccountPermissionMap::USER_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission for column [{$column}]: {$permission}");
            }
        }
    }

    private function authorizeAccountTypeButton(Request $request, int $accountTypeCode): void
    {
        $permission = AccountPermissionMap::ACCOUNT_TYPE_BUTTON_PERMISSIONS[$accountTypeCode] ?? null;

        if ($permission && ! $request->user()?->can($permission)) {
            abort(403, "Missing button permission for account type [{$accountTypeCode}]: {$permission}");
        }
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\AreaRole;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $actor = $request->user();
        $manageableIds = $actor->manageableAreaIds();

        $users = User::query()
            ->with(['areas' => fn ($query) => $query->orderBy('name')])
            ->when(
                ! $actor->isSuperAdmin(),
                fn ($query) => $query
                    ->where('is_super_admin', false)
                    ->whereHas('areas', fn ($q) => $q->whereIn('areas.id', $manageableIds)),
            )
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->transformUser($user));

        return Inertia::render('users/index', [
            'users' => $users,
            'canCreate' => $actor->can('create', User::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/create', [
            'areas' => $this->assignableAreas($request->user()),
            'roles' => $this->roleOptions(),
            'canGrantSuperAdmin' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'phone' => $request->validated('phone'),
                'notify_via_whatsapp' => $request->boolean('notify_via_whatsapp', true),
                'notify_via_sms' => $request->boolean('notify_via_sms', false),
                'is_super_admin' => $request->user()->isSuperAdmin()
                    ? $request->boolean('is_super_admin')
                    : false,
                'email_verified_at' => now(),
            ]);

            $this->syncMemberships($user, $request->validated('memberships'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('areas');

        return Inertia::render('users/edit', [
            'user' => $this->transformUser($user),
            'areas' => $this->assignableAreas($request->user()),
            'roles' => $this->roleOptions(),
            'canGrantSuperAdmin' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $data = [
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'notify_via_whatsapp' => $request->boolean('notify_via_whatsapp', true),
                'notify_via_sms' => $request->boolean('notify_via_sms', false),
            ];

            if ($request->filled('password')) {
                $data['password'] = $request->validated('password');
            }

            if ($request->user()->isSuperAdmin()) {
                $data['is_super_admin'] = $request->boolean('is_super_admin');
            }

            $user->update($data);
            $this->syncMemberships($user, $request->validated('memberships'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    /**
     * @param  list<array{area_id: int|string, role: string}>  $memberships
     */
    private function syncMemberships(User $user, array $memberships): void
    {
        $sync = [];

        foreach ($memberships as $membership) {
            $sync[(int) $membership['area_id']] = [
                'role' => $membership['role'] instanceof AreaRole
                    ? $membership['role']->value
                    : $membership['role'],
            ];
        }

        $user->areas()->sync($sync);
    }

    /**
     * @return list<array{id: int, name: string, code: string}>
     */
    private function assignableAreas(User $actor): array
    {
        if ($actor->isSuperAdmin()) {
            return Area::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->all();
        }

        return Area::query()
            ->whereIn('id', $actor->manageableAreaIds())
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return collect(AreaRole::cases())
            ->map(fn (AreaRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'notify_via_whatsapp' => $user->notify_via_whatsapp,
            'notify_via_sms' => $user->notify_via_sms,
            'is_super_admin' => $user->isSuperAdmin(),
            'memberships' => $user->areas->map(fn (Area $area) => [
                'area_id' => $area->id,
                'area_name' => $area->name,
                'area_code' => $area->code,
                'role' => $area->pivot->role instanceof AreaRole
                    ? $area->pivot->role->value
                    : $area->pivot->role,
            ])->values()->all(),
        ];
    }
}

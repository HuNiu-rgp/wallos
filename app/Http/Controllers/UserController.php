<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        return Inertia::render('Users/Index', [
            'users' => User::query()
                ->select(['id', 'name', 'email', 'role', 'email_verified_at', 'created_at'])
                ->withCount(['subscriptions', 'categories'])
                ->orderByRaw('CASE WHEN email = ? THEN 0 ELSE 1 END', ['admin@qq.com'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate($this->rules());

        $user = User::query()->create([
            ...$validated,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return back()->with('success', __('User created.'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate($this->rules($user));

        if ($user->email === 'admin@qq.com') {
            $validated['email'] = 'admin@qq.com';
            $validated['role'] = 'admin';
        }

        $this->guardLastAdministrator($user, $validated['role']);

        if (! ($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', __('User updated.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($user->email === 'admin@qq.com', 422, __('The default administrator cannot be deleted.'));
        $this->guardLastAdministrator($user, null);

        $user->delete();

        return back()->with('success', __('User deleted.'));
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($user?->id)],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function guardLastAdministrator(User $user, ?string $newRole): void
    {
        if ($user->isAdmin() && $newRole !== 'admin' && User::query()->where('role', 'admin')->count() === 1) {
            abort(422, __('At least one administrator is required.'));
        }
    }
}

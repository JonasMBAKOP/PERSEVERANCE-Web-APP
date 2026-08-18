<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // ── LISTE DES UTILISATEURS ────────────────────────────────────────────────
    public function index(Request $request)
    {
        /** @var \App\Models\User $authUser */
        $authUser    = Auth::user();
        $isSuperAdmin = $authUser->hasRole('super-admin');

        $query = User::with(['roles', 'staff:id,user_id,photo'])->orderBy('name');

        // Masquer le super-admin pour les non-super-admins
        if (!$isSuperAdmin) {
            $query->whereDoesntHave('roles', fn($q) =>
                $q->where('name', 'super-admin')
            );
        }

        // Filtres
        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) =>
                $q->where('name', $request->role)
            );
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
            );
        }

        $users = $query->paginate(15)->withQueryString();

        // Statistiques — exclure super-admin pour les non-super-admins
        $statsQuery = User::query();
        if (!$isSuperAdmin) {
            $statsQuery->whereDoesntHave('roles', fn($q) =>
                $q->where('name', 'super-admin')
            );
        }
        $totalUsers  = $statsQuery->count();
        $activeUsers = (clone $statsQuery)->where('is_active', true)->count();

        // Rôles disponibles dans les filtres
        $roles = $isSuperAdmin
            ? Role::orderBy('name')->get()
            : Role::where('name', '!=', 'super-admin')->orderBy('name')->get();

        return view('users.index', compact(
            'users', 'roles', 'totalUsers', 'activeUsers'
        ));
    }

    // ── FORMULAIRE DE CRÉATION ────────────────────────────────────────────────
    public function create()
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // Un directeur ne peut créer que des comptes de niveau inférieur au sien
        $roles = Role::orderBy('name')->get()->filter(fn($role) =>
            $authUser->hasRole('super-admin') ||
            ($authUser->getRoleLevel() > (new User)->fill([])->getRoleLevelByName($role->name))
        );

        return view('users.create', compact('roles'));
    }

    // ── ENREGISTREMENT ────────────────────────────────────────────────────────
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'phone'     => $request->phone,
            'is_active' => true,
        ]);

        $user->syncRoles($request->roles);

        return redirect()->route('users.index')
                         ->with('success',
                             "Compte de {$user->name} créé avec succès.");
    }

    // ── FORMULAIRE DE MODIFICATION ────────────────────────────────────────────
    public function edit(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // Vérification hiérarchique
        if ($authUser->id !== $user->id && !$authUser->canManage($user)) {
            abort(403, 'Vous ne pouvez pas modifier ce compte.');
        }

        $roles     = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('name')->toArray();

        return view('users.edit', compact('user', 'roles', 'userRoles'));
    }

    // ── MISE À JOUR ───────────────────────────────────────────────────────────
    public function update(UpdateUserRequest $request, User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if ($authUser->id !== $user->id && !$authUser->canManage($user)) {
            abort(403, 'Vous ne pouvez pas modifier ce compte.');
        }

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'is_active' => $request->boolean('is_active'),
        ];

        // Nouveau mot de passe uniquement si renseigné
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles($request->roles);

        return redirect()->route('users.index')
                         ->with('success',
                             "Compte de {$user->name} mis à jour.");
    }

    // ── SUPPRESSION PHYSIQUE DIRECTE ───────────────────────────
    public function destroy(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        // Empêcher la suppression de son propre compte
        if ($user->id === $authUser->id) {
            return back()->with('error',
                'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if (!$authUser->canManage($user)) {
            return back()->with('error',
                'Vous ne pouvez pas supprimer ce compte.');
        }

        // Empêcher la suppression du seul Super Admin
        if ($user->hasRole('super-admin') &&
            User::role('super-admin')->count() <= 1) {
            return back()->with('error',
                'Impossible de désactiver le seul Super Administrateur.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success',
            "Compte de {$name} désactivé et supprimé défnitivement.");
    }

    // ── TOGGLE ACTIF / INACTIF ────────────────────────────────────────────────
    public function toggleActive(User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if ($user->id === $authUser->id) {
            return back()->with('error',
                'Vous ne pouvez pas modifier le statut de votre propre compte.');
        }

        if (!$authUser->canManage($user)) {
            return back()->with('error',
                'Vous ne pouvez pas modifier le statut de ce compte.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activé' : 'désactivé';
        return back()->with('success',
            "Compte de {$user->name} {$status}.");
    }

    // ── RÉINITIALISATION DU MOT DE PASSE ──────────────────────────────────────
    public function resetPassword(Request $request, User $user)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if (!$authUser->canManage($user)) {
            abort(403, 'Vous ne pouvez pas réinitialiser ce mot de passe.');
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success',
            "Mot de passe de {$user->name} réinitialisé.");
    }
}

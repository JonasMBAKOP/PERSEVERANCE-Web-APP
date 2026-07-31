<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{

    // ── AFFICHAGE DU PROFIL ───────────────────────────────────────────────
    public function show()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $user->load('roles', 'staff');

        return view('profile.show', compact('user'));
    }

    /**
     * Display the user's profile form.
     */
    // public function edit(Request $request): View
    // {
    //     return view('profile.edit', [
    //         'user' => $request->user(),
    //     ]);
    // }

    // ── FORMULAIRE DE MODIFICATION ────────────────────────────────────────
    public function edit()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $user->load('staff');

        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    // public function update(ProfileUpdateRequest $request): RedirectResponse
    // {
    //     $request->user()->fill($request->validated());

    //     if ($request->user()->isDirty('email')) {
    //         $request->user()->email_verified_at = null;
    //     }

    //     $request->user()->save();

    //     return Redirect::route('profile.edit')->with('status', 'profile-updated');
    // }
    // ── MISE À JOUR ────────────────────────────────────────────────────────
    public function update(UpdateProfileRequest $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Vérifier le mot de passe actuel si changement demandé
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors([
                    'current_password' => 'Le mot de passe actuel est incorrect.',
                ])->withInput();
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        $photosToDelete = [];
        if ($request->hasFile('photo')) {
            $staff = $user->staff;
            $newPhotoPath = $request->file('photo')->store(
                $staff ? 'staff/photos' : 'users/photos',
                'public'
            );
            $photosToDelete = array_filter([$user->photo, $staff?->photo], fn ($path) => $path && $path !== $newPhotoPath);
            $user->photo = $newPhotoPath;
            if ($staff) {
                $staff->update(['photo' => $newPhotoPath]);
            }
        }

        // Traiter le cachet/signature (sauf pour les enseignants)
        if ($request->hasFile('signature_seal') && !$user->hasRole('enseignant')) {
            if ($user->signature_seal) {
                Storage::disk('public')->delete($user->signature_seal);
            }
            $user->signature_seal = $request->file('signature_seal')->store('users/seals', 'public');
        }

        // Mettre à jour l'établissement d'origine sur la fiche personnel
        if ($user->staff && $request->has('origin_school')) {
            $user->staff->update(['origin_school' => $request->origin_school]);
        }

        $user->save();

        foreach (array_unique($photosToDelete) as $photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return redirect()->route('profile.show')
            ->with('success', 'Votre profil a été mis à jour avec succès.');
    }

    // ── SUPPRIMER LE CACHET ────────────────────────────────────────────────
    public function deleteSeal()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        if ($user->hasRole('enseignant')) {
            abort(403, 'Non autorisé');
        }
        
        if ($user->signature_seal) {
            Storage::disk('public')->delete($user->signature_seal);
            $user->update(['signature_seal' => null]);
        }
        return back()->with('success', 'Cachet supprimé.');
    }

    // ── SUPPRIMER LA PHOTO ─────────────────────────────────────────────────
    public function deletePhoto()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $staff = $user->staff;
        $photosToDelete = array_filter([$user->photo, $staff?->photo]);
        $user->update(['photo' => null]);
        if ($staff) {
            $staff->update(['photo' => null]);
        }
        foreach (array_unique($photosToDelete) as $photoPath) {
            Storage::disk('public')->delete($photoPath);
        }
        return back()->with('success', 'Photo supprimée.');
    }

    // ── DÉCONNECTER LES AUTRES SESSIONS ───────────────────────────────────
    // public function logoutOtherSessions(Request $request)
    // {
    //     $request->validate([
    //         'password' => ['required', 'current_password'],
    //     ]);

    //     auth()->guard('web')->logoutOtherDevices($request->password);

    //     return back()->with('success', 'Toutes les autres sessions ont été déconnectées.');
    // }

    /**
     * Delete the user's account.
     */
    // public function destroy(Request $request): RedirectResponse
    // {
    //     $request->validateWithBag('userDeletion', [
    //         'password' => ['required', 'current_password'],
    //     ]);

    //     $user = $request->user();

    //     Auth::logout();

    //     $user->delete();

    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();

    //     return Redirect::to('/');
    // }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Peternak memberi akun ABK hak entri harian (F8.2). Akun ABK tidak melihat kontrak, biaya, atau rekonsiliasi (R13).
 */
class AbkController extends Controller
{
    public function index(Request $request): View
    {
        return view('abk.index', ['abk' => $request->user()->abk()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['name' => 'nama', 'password' => 'kata sandi']);

        // role dan peternak_id ditentukan server, tidak pernah dari isian form.
        $abk = (new User)->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_ABK,
            'peternak_id' => $request->user()->id,
            'email_verified_at' => now(),
        ]);
        $abk->save();

        event(new Registered($abk));

        return redirect()->route('abk.index')->with('status', "Akun ABK {$abk->name} dibuat. Beri tahu email dan kata sandinya.");
    }

    public function destroy(Request $request, User $abk): RedirectResponse
    {
        // Hanya ABK milik peternak ini yang bisa dicabut.
        Gate::authorize('kelolaAbk', $abk);

        $abk->delete();

        return redirect()->route('abk.index')->with('status', 'Akun ABK dicabut.');
    }
}

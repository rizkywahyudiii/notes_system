<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile');
    }

    public function destroy(Request $request)
    {
        $user = \App\Models\User::find(Auth::id());

        // Hapus semua catatan pengguna
        \App\Models\Note::where('user_id', $user->id)->delete();

        // Hapus akun pengguna
        $user->delete();

        // Logout pengguna
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Akun berhasil dihapus');
    }
}

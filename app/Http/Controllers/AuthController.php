<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'gender' => 'required|in:Laki-Laki,Perempuan,Tidak Ingin Memberitahu',
                'birth_date' => 'required|date',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'gender' => $validated['gender'],
                'birth_date' => $validated['birth_date'],
            ]);

            DB::commit();

            Auth::login($user);

            return redirect()->route('dashboard')->with('success', 'Registrasi berhasil! Selamat datang di Notes System.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat registrasi: ' . $e->getMessage()])->withInput();
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard')->with('success', 'Selamat datang kembali!');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }

    public function updateFaceData(Request $request)
    {
        try {
            $request->validate([
                'face_data' => 'required|string',
            ]);

            $user = Auth::user();
            User::where('id', $user->id)->update([
                'face_data' => $request->face_data
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updatePin(Request $request)
    {
        try {
            $request->validate([
                'pin' => 'required|string|min:4|max:6|regex:/^[0-9]+$/',
            ]);

            $user = Auth::user();
            User::where('id', $user->id)->update([
                'pin' => Hash::make($request->pin)
            ]);

            return back()->with('success', 'PIN berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal memperbarui PIN: ' . $e->getMessage()]);
        }
    }
}

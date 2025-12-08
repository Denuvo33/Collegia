<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CommunityController extends Controller
{


    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Akun tidak ditemukan.',
            ]);
        }

        if ($user->role == 'admin' || $user->role == 'community' || $user->role == 'moderator') {
            if (!Hash::check($request->password, $user->password)) {
                return back()->withErrors([
                    'password' => 'Password salah.',
                ]);
            }
            Auth::login($user, true);

            // Redirect to dashboard
            switch ($user->role) {
                case 'admin':
                    return redirect()->route('admin.dashboard');
                case 'community':
                    return redirect()->route('user.home');
                case 'moderator':
                    return redirect()->route('moderator.dashboard');
            }
        }
        return back()->withErrors([
            'email' => 'Akses ditolak. Akun ini bukan akun komunitas.',
        ]);
    }
}

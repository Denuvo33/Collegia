<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
                    return redirect()->route('user.home');
            }
        }
        return back()->withErrors([
            'email' => 'Akses ditolak. Akun ini bukan akun komunitas.',
        ]);
    }

    public function fetchUsers(Request $request)
    {
        try {
            $users = User::latest()->paginate(10);
            // dd($users->toArray());
            return Inertia::render('admin/users/UsersPage', [
                'users' => $users->toArray()
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function storeUsers(Request $request)
    {

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'required|in:admin,community,moderator,user',
            ]);
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);
            return redirect()->back()->with('success', 'User berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat user: ' . $e->getMessage());
        }
    }

    public function updateUsers(Request $request, $id)
    {

        try {
            $role =  $request->validate([
                'role' => 'required|in:admin,community,moderator,user',
            ]);
            $user = User::findOrFail($id);
            $user->role = $role['role'];
            $user->save();

            return redirect()->back()->with('success', 'Role user berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Update role error: ' . $e->getMessage());

            return redirect()->back()->with(
                'error',
                'Gagal memperbarui role user: ' . $e->getMessage()
            );
        }
    }


    public function deleteUsers($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return redirect()->back()->with('success', 'User berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus user');
        }
    }
}

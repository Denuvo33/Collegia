<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EventModel;
use App\Models\ReportComment;
use App\Models\ReportEvent;
use App\Models\ReportModel;
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
            $query = User::query();

            // Search by name or email
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            // Filter by role
            if ($request->has('role') && !empty($request->role)) {
                $query->where('role', $request->role);
            }

            $users = $query->latest()->paginate(10);
            
            return Inertia::render('admin/users/UsersPage', [
                'users' => $users->toArray(),
                'filters' => [
                    'search' => $request->search ?? '',
                    'role' => $request->role ?? '',
                ]
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

    public function reportEvents(Request $request)
    {
        try {
            $events = ReportEvent::with('event')->latest()->paginate(10);

            return Inertia::render('admin/reports/ReportEvent', [
                'events' => $events->toArray()
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteReportEvent($id)
    {
        try {
            $report = ReportEvent::findOrFail($id);
            $report->delete();

            return redirect()->back()->with('success', 'Laporan event berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus laporan event');
        }
    }

    public function reportPosts(Request $request)
    {
        try {
            $posts = ReportModel::with('post')->latest()->paginate(10);


            return Inertia::render('admin/reports/ReportPost', [
                'posts' => $posts->toArray()
            ]);
        } catch (\Exception $e) {
            \Log::error('Update role error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteReportPost($id)
    {
        try {
            $report = ReportModel::findOrFail($id);
            $report->delete();

            return redirect()->back()->with('success', 'Laporan post berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus laporan post');
        }
    }

    public function fetchEvents(Request $request)
    {
        try {
            $events = EventModel::latest()->paginate(10);
            // dd($users->toArray());
            return Inertia::render('admin/events/Event', [
                'events' => $events->toArray()
            ]);
        } catch (\Exception $e) {
            \Log::error('Fetch events error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteEvents($id)
    {
        try {
            $event = EventModel::findOrFail($id);
            $event->delete();

            return redirect()->back()->with('success', 'Event berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus event');
        }
    }

    public function deleteAllCompletedEvents()
    {
        try {
            $today = now()->format('Y-m-d');

            $deletedCount = EventModel::where('date_event', '<', $today)->delete();

            return redirect()->back()->with('success', "$deletedCount event selesai berhasil dihapus.");
        } catch (\Exception $e) {
            \Log::error('Delete completed events error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghapus event selesai: ' . $e->getMessage());
        }
    }


    public function reportComments(Request $request)
    {
        try {
            $comments = ReportComment::with(['comment.user'])->latest()->paginate(10);
            return Inertia::render('admin/reports/ReportComment', [
                'comments' => $comments->toArray()
            ]);
        } catch (\Exception $e) {
            \Log::error('Fetch reported comments error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    public function deleteReportComment($id)
    {
        try {
            $report = ReportComment::findOrFail($id);
            $report->delete();

            return redirect()->back()->with('success', 'Laporan komentar berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus laporan komentar');
        }
    }
}

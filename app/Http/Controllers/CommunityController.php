<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CommentModel;
use App\Models\EventModel;
use App\Models\ReportComment;
use App\Models\ReportEvent;
use App\Models\ReportModel;
use App\Models\Post;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CommunityController extends Controller
{

    public function adminDashboard(Request $request)
    {
        // Get selected year or default to current year
        $selectedYear = $request->input('year', now()->year);

        // Get all available years from transactions
        $availableYears = Transaction::selectRaw('DISTINCT YEAR(created_at) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // If no transactions exist, use current year
        if (empty($availableYears)) {
            $availableYears = [now()->year];
        }

        $totalUsers = User::count();
        $adminUsers = User::where('role', 'admin')->count();
        $moderatorUsers = User::where('role', 'moderator')->count();
        $communityUsers = User::where('role', 'community')->count();
        $userUsers = User::where('role', 'user')->count();

        $totalPosts = Post::count();
        $totalComments = CommentModel::count();

        $totalEvents = EventModel::count();
        $visibleEvents = EventModel::where('is_visible', 1)->count();
        $notVisibleEvents = EventModel::where('is_visible', 0)->count();

        // Transaction statistics - all possible statuses
        $totalTransactions = Transaction::count();
        $successTransactions = Transaction::where('transaction_status', 'success')->count();
        $pendingTransactions = Transaction::where('transaction_status', 'pending')->count();
        $failedTransactions = Transaction::where('transaction_status', 'failed')->count();
        $expiredTransactions = Transaction::where('transaction_status', 'expired')->count();
        $cancelledTransactions = Transaction::where('transaction_status', 'cancelled')->count();

        // Monthly transaction data for selected year
        $monthlyTransactions = Transaction::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->whereYear('created_at', $selectedYear)
            ->where('transaction_status', 'success')
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Prepare monthly data for all 12 months
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[] = [
                'month' => $i,
                'name' => date('M', mktime(0, 0, 0, $i, 1)),
                'amount' => $monthlyTransactions->get($i)?->total_amount ?? 0,
                'count' => $monthlyTransactions->get($i)?->transaction_count ?? 0,
            ];
        }

        $totalReportPosts = ReportModel::count();
        $totalReportComments = ReportComment::count();
        $totalReportEvents = ReportEvent::count();
        $totalReports = $totalReportPosts + $totalReportComments + $totalReportEvents;

        // Recent users (last 5)
        $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get();

        // Recent transactions (last 5)
        $recentTransactions = Transaction::with(['user', 'event'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                    'user_name' => $transaction->user ? $transaction->user->name : 'N/A',
                    'event_title' => $transaction->event ? $transaction->event->title : 'N/A',
                    'amount' => $transaction->amount,
                    'status' => $transaction->transaction_status,
                    'created_at' => $transaction->created_at,
                ];
            });

        // Recent events (last 5) with calculated status based on date
        $recentEvents = EventModel::with('user')->orderBy('created_at', 'desc')->take(5)->get()->map(function ($event) {
            $today = now()->format('Y-m-d');
            $eventDate = date('Y-m-d', strtotime($event->date_event));

            // Calculate status based on date comparison
            if ($eventDate > $today) {
                $event->status = 'Upcoming';
            } elseif ($eventDate === $today) {
                $event->status = 'Today';
            } else {
                $event->status = 'Complete';
            }

            return $event;
        });

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users' => [
                    'total' => $totalUsers,
                    'admin' => $adminUsers,
                    'moderator' => $moderatorUsers,
                    'community' => $communityUsers,
                    'user' => $userUsers,
                ],
                'posts' => $totalPosts,
                'comments' => $totalComments,
                'events' => [
                    'total' => $totalEvents,
                    'visible' => $visibleEvents,
                    'notVisible' => $notVisibleEvents,
                ],
                'transactions' => [
                    'total' => $totalTransactions,
                    'success' => $successTransactions,
                    'pending' => $pendingTransactions,
                    'failed' => $failedTransactions,
                    'expired' => $expiredTransactions,
                    'cancelled' => $cancelledTransactions,
                ],
                'reports' => [
                    'total' => $totalReports,
                    'posts' => $totalReportPosts,
                    'comments' => $totalReportComments,
                    'events' => $totalReportEvents,
                ],
            ],
            'monthlyTransactions' => $monthlyData,
            'recentUsers' => $recentUsers,
            'recentEvents' => $recentEvents,
            'recentTransactions' => $recentTransactions,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
        ]);
    }


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
            $query = EventModel::query();


            // Get today's date for status filtering
            $today = now()->format('Y-m-d');

            // Debug: Check all events in database
            // dd([
            //     'today' => $today,
            //     'all_events' => EventModel::select('id', 'title', 'date_event')->get()->toArray(),
            //     'request_status' => $request->status,
            // ]);

            // Filter by status
            if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
                $status = $request->status;

                if ($status === 'akan datang') {
                    // Events in the future
                    $query->whereDate('date_event', '>', $today);
                } elseif ($status === 'berjalan') {
                    // Events happening today
                    $query->whereDate('date_event', '=', $today);
                } elseif ($status === 'selesai') {
                    // Events in the past
                    $query->whereDate('date_event', '<', $today);
                }
            }

            // Search by title or organizer
            if ($request->has('search') && $request->search && $request->search !== 'null') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('organizer', 'like', '%' . $search . '%');
                });
            }


            // Debug: Log the query to see what's happening
            \Log::info('Query SQL: ' . $query->toSql());
            \Log::info('Query Bindings: ', $query->getBindings());
            \Log::info('Today date: ' . $today);

            $events = $query->latest()->paginate(10);

            // Debug: Show what we got from the query
            // dd([
            //     'today' => $today,
            //     'request_status' => $request->status,
            //     'query_result_count' => $events->total(),
            //     'query_results' => $events->items(),
            //     'all_events_raw' => EventModel::select('id', 'title', 'date_event')->get()->map(function ($e) {
            //         return [
            //             'id' => $e->id,
            //             'title' => $e->title,
            //             'date_event' => $e->date_event,
            //             'date_event_formatted' => $e->date_event ? date('Y-m-d', strtotime($e->date_event)) : null,
            //         ];
            //     })->toArray(),
            // ]);

            return Inertia::render('admin/events/Event', [
                'events' => $events->toArray(),
                'filters' => [
                    'status' => $request->status ?? '',
                    'search' => $request->search ?? '',
                ]
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

<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Models\ReportComment;
use App\Models\ReportEvent;
use App\Models\ReportModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('user/Report', [
            'data' => ReportModel::with('posts')->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReportRequest $request)
    {
        try {
            $data = $request->validated();
            ReportModel::create($data);
            return back()->with([
                'success' => 'Laporan berhasil dikirim. Terima kasih telah berkontribusi menjaga komunitas lebih baik.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to submit post report: ' . $e->getMessage());
            return back()->withErrors([
                'error' => 'Terjadi kesalahan, coba lagi nanti.'
            ]);
        }
    }

    public function reportComment(Request $request)
    {
        try {
            $data = $request->validate([
                'comment_id' => 'required|exists:comment,id',
                'reason' => 'required|in:spam,abuse,harassment,abusive,inappropriate,porn,pretend to be other people',
            ]);

            ReportComment::create($data);

            Log::info('Comment reported', [
                'comment_id' => $data['comment_id'],
                'reason' => $data['reason'],
            ]);

            return back()->with([
                'success' => 'Laporan berhasil dikirim. Terima kasih telah berkontribusi menjaga komunitas lebih baik.'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to submit comment report: ' . $e->getMessage());
            return back()->withErrors([
                'error' => 'Terjadi kesalahan, coba lagi nanti.'
            ]);
        }
    }

    public function reportEvent(Request $request)
    {
        try {
            $data = $request->validate([
                'event_id' => 'required|exists:event,id',
                'reason' => 'required|in:spam,abuse,abusive,harassment,inappropriate,porn,pretend to be other people'
            ]);

            ReportEvent::create($data);

            Log::info('Event reported', [
                'event_id' => $data['event_id'],
                'reason' => $data['reason'],
            ]);

            return back()->with([
                'success' => 'Laporan berhasil dikirim. Terima kasih telah berkontribusi menjaga komunitas lebih baik.'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to submit event report: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan, coba lagi nanti.']);
        }
    }

    public function reportCollaboration(Request $request)
    {
        try {
            $data = $request->validate([
                'collaboration_id' => 'required|exists:collaborations,id',
                'reason' => 'required|in:spam,abuse,abusive,harassment,inappropriate,porn,pretend to be other people'
            ]);

            \App\Models\ReportCollaboration::create($data);

            Log::info('Collaboration reported', [
                'collaboration_id' => $data['collaboration_id'],
                'reason' => $data['reason'],
            ]);

            return back()->with([
                'success' => 'Laporan berhasil dikirim. Terima kasih telah berkontribusi menjaga komunitas lebih baik.'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to submit collaboration report: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan, coba lagi nanti.' . $e->getMessage()]);
        }
    }





    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

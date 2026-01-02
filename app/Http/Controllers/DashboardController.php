<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\ConversionJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get statistics
        $totalUploads = Upload::where('user_id', $user->id)->count();
        $totalConversions = ConversionJob::whereHas('upload', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'completed')->count();

        // Calculate success rate
        $totalJobs = ConversionJob::whereHas('upload', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        $successRate = $totalJobs > 0 ? round(($totalConversions / $totalJobs) * 100, 1) : 0;

        // Get recent uploads (last 10)
        $recentUploads = Upload::where('user_id', $user->id)
            ->with('latestConversionJob')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($upload) {
                return [
                    'id' => $upload->id,
                    'original_filename' => $upload->original_name,
                    'xml_type' => $upload->xml_type,
                    'conversion_status' => $upload->latestConversionJob ? $upload->latestConversionJob->status : 'pending',
                    'created_at' => $upload->created_at,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalUploads' => $totalUploads,
                'totalConversions' => $totalConversions,
                'successRate' => $successRate,
                'recentUploads' => $recentUploads,
            ]
        ]);
    }
}

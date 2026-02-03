<?php

namespace App\Http\Controllers;

use App\Models\Ministat;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard');
    }

    /**
     * Get mini stats dashboard card report
     */
    public function ministats()
    {
        $stat = Ministat::where('user_id', auth()->user()->id)->first();

        return response()->json([
            'numConnections' => $stat?->connections ?? 0, 
            'sentInvites' => $stat?->pending_invites ?? 0,
            'profileViews' => $stat?->profile_views ?? 0
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function piestats()
    {
        $query = "sum(case when module_name='Invitation sent' then 1 else 0 end) as 'Invitation sent',
        sum(case when module_name='Profile viwed' then 1 else 0 end) as 'Profile viwed',
        sum(case when module_name='Profile viewed' then 1 else 0 end) as 'Profile viewed',
        sum(case when module_name='Anniversary greetings' then 1 else 0 end) as 'Anniversary greetings'";

        $stats = UserActivity::select(DB::raw($query))
            ->where('user_id', auth()->user()->id)
            ->first();

        // Ensure we always return an object, even if null
        if (!$stats) {
            $stats = (object)[
                'Invitation sent' => 0,
                'Profile viwed' => 0,
                'Profile viewed' => 0,
                'Anniversary greetings' => 0
            ];
        }

        return response()->json([
            'stats' => $stats
        ]);
    }

    public function linestats()
    {
        $query = "DATE_FORMAT(created_at,'%b') datee, sum(case when module_name='Message sent' then 1 else 0 end) as 'Message sent', 
        sum(case when module_name='Connections endorsed' then 1 else 0 end) as 'Connections endorsed', 
        sum(case when module_name='New job greetings sent' then 1 else 0 end) as 'New job greetings sent', 
        sum(case when module_name='Invitation accepted' then 1 else 0 end) as 'Invitation accepted', 
        sum(case when module_name='Connection followed' then 1 else 0 end) as 'Connection followed'";

        $stats = UserActivity::select(DB::raw($query))
            ->where('user_id', auth()->user()->id)
            ->groupBy('datee')
            ->get();

        return response()->json([
            'stats' => $stats
        ]);
    }

    public function barstats()
    {
        $query = "DATE_FORMAT(created_at,'%b') datee,
        sum(case when module_name='Connections removed' then 1 else 0 end) as 'Connections removed',
        sum(case when module_name='Comments liked' then 1 else 0 end) as 'Comments liked',
        sum(case when module_name='Profiles scraped' then 1 else 0 end) as 'Profiles scraped',
        sum(case when module_name='Birthday greetings' then 1 else 0 end) as 'Birthday greetings',
        sum(case when module_name='Invitation withdrawn' then 1 else 0 end) as 'Invitation withdrawn'";

        $stats = UserActivity::select(DB::raw($query))
            ->where('user_id', auth()->user()->id)
            ->groupBy('datee')
            ->get();

        return response()->json([
            'stats' => $stats
        ]);
    }
}

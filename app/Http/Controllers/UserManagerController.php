<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Audience;
use App\Models\AudienceList;
use App\Models\SnLeadList;
use App\Models\SnLead;
use App\Models\SnLeadsCompany;
use App\Models\Campaign;
use App\Models\CampaignLeadgenRunning;
use App\Models\CampaignList;
use App\Models\CampaignSequenceEndorse;
use App\Models\UserActivity;
use App\Models\CallReminder;
use App\Models\CallReminderMessage;
use App\Models\CallStatus;
use App\Models\AiContent;
use App\Models\Post;
use App\Models\Integration;
use App\Models\ModelHasPermission;
use Spatie\Permission\Models\Permission;
use App\Helpers\DeleteUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Query\JoinClause;
use DB;
use Log;

class UserManagerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('email');

        if(isset($search)){
            $users = User::where('email','like','%'.$search.'%')->latest()->paginate(15);
        }else {
            $users = User::latest()->paginate(15);
        }

        return view('admin.users', compact('users'));
    }

    public function resellerindex(Request $request)
    {
        $search = $request->query('email');

        if(isset($search)){
            $users = User::where('email','like','%'.$search.'%')
                ->where('created_by', auth()->user()->id)
                ->latest()
                ->paginate(15);
        }else {
            $users = User::where('created_by', auth()->user()->id)
                ->latest()
                ->paginate(15);
        }

        return view('admin.reseller', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'string', 'unique:users,email'],
            'password' => ['required'],
        ]);

        $user = User::create([
            'name' => $data['name'], 
            'email' => $data['email'], 
            'password' => bcrypt($data['password']), 
            'created_by' => auth()->user()->id
        ])->assignRole('User');

        $user->givePermissionTo('FE');

        notify()->success('User created successfully.');

        if(auth()->user()->hasRole('Admin')){
            return redirect()->route('users.index');
        }else {
            return redirect()->route('reseller.index');
        }
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'string'],
            'linkedin_id' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        $data['linkedin_id'] = $request->linkedin_id ?? null;

        $user->update($data);

        notify()->success('User updated successfully.');
        
        if(auth()->user()->hasRole('Admin')){
            return redirect()->route('users.index');
        }else {
            return redirect()->route('reseller.index');
        }
    }

    public function destroy(string $id)
    {
        try {
            DeleteUserResource::handle($id);

            notify()->success('User deleted successfully.');
        } catch (\Throwable $th) {
            notify()->success($th->getMessage());
        }
        
        if(auth()->user()->hasRole('Admin')){
            return redirect()->route('users.index');
        }else {
            return redirect()->route('reseller.index');
        }
    }

    public function userPermissions(Request $request)
    {
        $userId = $request->query('userid');

        // $permissions = Permission::select(DB::raw("permissions.id, permissions.name, up.model_id"))
        //     ->leftJoin('model_has_permissions as up', function (JoinClause $join) use ($userId) {
        //         $join->on('permissions.id','=','up.permission_id')
        //             ->on('up.model_id','=',$userId);
        //     })
        //     ->whereNotIn('permissions.name', ['view_user_manager_menu','view_permissions_menu'])
        //     ->orderBy('permissions.id')
        //     ->get();

        if(auth()->user()->hasRole('Admin')) {
            $query = sprintf("SELECT permissions.id, permissions.name, up.model_id 
            FROM permissions
            LEFT JOIN model_has_permissions AS up ON permissions.id = up.permission_id AND up.model_id = %s 
            WHERE permissions.name != 'view_permissions_menu'
            ORDER BY permissions.id", (int)$userId);
        }else{
            $query = sprintf("SELECT permissions.id, permissions.name, up.model_id 
            FROM permissions
            LEFT JOIN model_has_permissions AS up ON permissions.id = up.permission_id AND up.model_id = %s 
            WHERE permissions.name NOT IN ('view_user_manager_menu','view_permissions_menu')
            ORDER BY permissions.id", (int)$userId);
        }

        $permissions = DB::select($query);

        return response()->json([
            'perm' => $permissions,
            'message' => 'success'
        ]);
    }

    public function assignPermissions(Request $request)
    {
        $permissions = $request->permissions;
        $userId = $request->userId;

        ModelHasPermission::where('model_id', $userId)->delete();

        if(count($permissions)>0){
            $user = User::findOrFail($userId);

            $user->givePermissionTo($permissions);
        }

        notify()->success('Permission assigned successfully.');
        
        if(auth()->user()->hasRole('Admin')){
            return redirect()->route('users.index');
        }else {
            return redirect()->route('reseller.index');
        }
    }

    public function impersonate(string $id)
    {
        $currentUser = auth()->user();

        // if (!$currentUser || !$currentUser->hasRole('Admin')) {
        //     abort(403, 'You are not authorized to impersonate users.');
        // }

        $user = User::findOrFail($id);

        if ($user->id === $currentUser->id) {
            return redirect()->route('users.index');
        }

        Auth::logout();

        Auth::login($user);
        session()->regenerate();

        return redirect()->route('dashboard');
    }
}
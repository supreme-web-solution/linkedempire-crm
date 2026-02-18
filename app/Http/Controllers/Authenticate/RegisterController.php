<?php

namespace App\Http\Controllers\Authenticate;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function bundleSignup()
    {
        return view('auth.bundle-access');
    }

    public function bundleSignupAuth(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'email' => ['required','email'],
            'password' => ['required']
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'created_by' => 1,
            'user_type' => 'owner'
        ])->assignRole('User');

        // Assign permission FE
        $user->givePermissionTo(['FE','OTO1','OTO2','OTO3','OTO4','OTO5','OTO6','OTO7','OTO8','Bundle']);

        notify()->success('You have successfully signed up.');
        return redirect()->route('auth.login');
    }

    public function resellerSignup()
    {
        return view('auth.reseller-access');
    }

    public function resellerSignupAuth(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'email' => ['required','email'],
            'password' => ['required']
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'created_by' => 1,
            'user_type' => 'owner'
        ])->assignRole('User');

        // Assign permission FE
        $user->givePermissionTo(['FE','OTO5']);

        notify()->success('You have successfully signed up.');
        return redirect()->route('auth.login');
    }

    public function feSignup()
    {
        return view('auth.fe');
    }

    public function feSignupAuth(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'email' => ['required','email'],
            'password' => ['required']
        ]);

        // Check if user already exists
        $user = User::where('email', $data['email'])->first();

        if ($user) {
            // Update existing user's password
            $user->update([
                'password' => bcrypt($data['password']),
                'name' => $data['name']
            ]);
            
            // Ensure user has FE permission
            if (!$user->hasPermissionTo('FE')) {
                $user->givePermissionTo('FE');
            }
            
            notify()->success('Your password has been updated successfully.');
        } else {
            // Create new user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'created_by' => 1,
                'user_type' => 'owner'
            ])->assignRole('User');

            // Assign FE permission
            $user->givePermissionTo('FE');

            notify()->success('You have successfully signed up.');
        }

        return redirect()->route('auth.login');
    }

    public function bundleSignupNew()
    {
        return view('auth.bundle');
    }

    public function bundleSignupAuthNew(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'],
            'email' => ['required','email'],
            'password' => ['required']
        ]);

        // Check if user already exists
        $user = User::where('email', $data['email'])->first();

        if ($user) {
            // Update existing user's password
            $user->update([
                'password' => bcrypt($data['password']),
                'name' => $data['name']
            ]);
            
            // Ensure user has bundle permissions
            $bundlePermissions = ['FE','OTO1','OTO2','OTO3','OTO4','OTO5','OTO6','OTO7','OTO8','Bundle'];
            foreach ($bundlePermissions as $permission) {
                if (!$user->hasPermissionTo($permission)) {
                    $user->givePermissionTo($permission);
                }
            }
            
            notify()->success('Your password has been updated successfully.');
        } else {
            // Create new user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'created_by' => 1,
                'user_type' => 'owner'
            ])->assignRole('User');

            // Assign bundle permissions
            $user->givePermissionTo(['FE','OTO1','OTO2','OTO3','OTO4','OTO5','OTO6','OTO7','OTO8','Bundle']);

            notify()->success('You have successfully signed up.');
        }

        return redirect()->route('auth.login');
    }
}

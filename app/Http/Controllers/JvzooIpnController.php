<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductTransaction;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Helpers\DeleteUserResource;
use App\Notifications\UserCreationNotification;
use App\Notifications\UserUpgradeNotification;
use App\Notifications\UserRefundNotification;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use \Carbon\Carbon;

class JvzooIpnController extends Controller
{
    public function JVZoo(Request $request)
    {
        $data = $request->all();

        if(!isset($_POST["cverify"])){
            return response(['message' => 'Verification Failed!']);
            exit(1);
        }

        $TYPE = isset($data['ctransaction']) ? $data['ctransaction'] : 'SALE';
        $d = array("TYPE"           => $TYPE,
                "EMAIL"             => isset($data['ccustemail']) ? $data['ccustemail'] : null,
                "TRANSACTION_ID"    => isset($data["ctransreceipt"]) ? $data["ctransreceipt"] : null,
                "PRODUCT_ID"        => isset($data['cproditem']) ? $data['cproditem'] : null );

        if(!$d['PRODUCT_ID'] || !$d['EMAIL'] || !$d['TRANSACTION_ID']  ){
            return response()->json(['message' => 'Item does not exist.']);
            exit(1);
        }

        switch ($TYPE) {
            case 'SALE':
                $response = $this->createUser($d);
                return response(['message' => $response]);
                break;

            case 'RFND':
                $response = $this->detachUser($d);
                return response(['message' => $response]);
                break;
        }
    }

    private function createUser($data)
    {
        $product = Product::where('product_id', $data['PRODUCT_ID'])->first();

        if(!$product){
            return response()->json(['message' => 'Product Validation Failed!']);
        }

        $email = $data['EMAIL'];
        $password = false;
        $user = User::where('email', $email)->first();
        $userId;

        if(!$user){
            $password = Str::random(10);
            $userModel;
            $username;

            $newUser = User::create([
                'name'      => substr($email, 0, strpos($email, '@')),
                'email'     => $email,
                'password'  => bcrypt($password),
                'created_by'    => 1,
            ]);

            $newUser->assignRole('User');
            $newUser->givePermissionTo($product->funnel);
            $userModel = $newUser;
            $username = $newUser->name;
            $userId = $newUser->id;

            $userInfo = [
                'username'=> $username, 
                'email' => $email,
                'password'=> $password,
                'product' => $product->name
            ];

            ProductTransaction::create([
                'user_id'           => $userId ,
                'product_id'        => $data['PRODUCT_ID'],
                'transaction_id'    => $data['TRANSACTION_ID'],
                'transaction_type'  => 'SALE'
            ]);

            Notification::send($userModel, new UserCreationNotification($userInfo));

            // Send copy to test email address
            try {
                $testEmail = 'vicken408@gmail.com';
                Notification::route('mail', $testEmail)->notify(new UserCreationNotification($userInfo));
            } catch (\Exception $e) {
                // Log error but don't fail the main process
                Log::warning('Failed to send test email copy: ' . $e->getMessage());
            }

            return 'User Created Successfully!';
        }else {
            // if user exist update role
            $user->givePermissionTo($product->funnel);

            $userInfo = [
                'username'=> $user->name, 
                'product' => $product->funnel
            ];
            $userId = $user->id;

            ProductTransaction::create([
                'user_id'           => $userId ,
                'product_id'        => $data['PRODUCT_ID'],
                'transaction_id'    => $data['TRANSACTION_ID'],
                'transaction_type'  => 'SALE'
            ]);
            
            Notification::send($user, new UserUpgradeNotification($userInfo));

            return 'User Bundle Upgraded!';
        }
    }

    private function detachUser($d)
    {
        $user = User::where('email', $d['EMAIL'])->first(); 
        $product = Product::where('product_id', $d['PRODUCT_ID'])->first();

        if ($user){            
            try {
                DeleteUserResource::handle($user->id);
            } catch (\Throwable $th) {
                throw $th;
            }

            $userInfo = [
                'username'=> $user->name, 
                'product' => $product->name
            ];

            ProductTransaction::create([
                'user_id'           => $user->id,
                'product_id'        => $d['PRODUCT_ID'],
                'transaction_id'    => $d['TRANSACTION_ID'],
                'transaction_type'  => 'RFND'
            ]);

            Notification::send($user, new UserRefundNotification($userInfo));

            return 'User Detached';
        }
    }

    public function jvzipnVerification() 
    {
        $secretKey = "lckn6Mez43k5df";
        $pop = "";
        $ipnFields = array();

        foreach ($_POST AS $key => $value) {
            if ($key == "cverify") {
                continue;
            }
            $ipnFields[] = $key;
        }
        sort($ipnFields);
        foreach ($ipnFields as $field) {
            // if Magic Quotes are enabled $_POST[$field] will need to be
            // un-escaped before being appended to $pop
            $pop = $pop . $_POST[$field] . "|";
        }
        $pop = $pop . $secretKey;
        if ('UTF-8' != mb_detect_encoding($pop))
        {
            $pop = mb_convert_encoding($pop, "UTF-8");
        }
        $calcedVerify = sha1($pop);
        $calcedVerify = strtoupper(substr($calcedVerify,0,8));
        return $calcedVerify == $_POST["cverify"];
    }
}

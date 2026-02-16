<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductTransaction;
use App\Helpers\DeleteUserResource;
use App\Mail\WelcomeMail;
use App\Notifications\UserRefundNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class JVZooWebhookController extends Controller
{
    //

    public function JVZoo(Request $request)
    {

        // Verify the JVZoo IPN request
        if (!$this->jvzipnVerification($request)) {
            return response()->json(['message' => 'Verification Failed!']);
        }

        $data = $request->all();

        if (!isset($_POST["cverify"])) {
            return response()->json(['message' => 'Verification Failed!']);
        }

        $TYPE = isset($data['ctransaction']) ? $data['ctransaction'] : 'SALE';

        // Collect necessary data from the request
        $d = array(
            "TYPE"            => $TYPE,
            "EMAIL"           => isset($data['ccustemail']) ? $data['ccustemail'] : null,
            "TRANSACTION_ID"  => isset($data["ctransreceipt"]) ? $data["ctransreceipt"] : null,
            "PRODUCT_ID"      => isset($data['cproditem']) ? $data['cproditem'] : null
        );

        // Check if required data exists
        if (!$d['PRODUCT_ID'] || !$d['EMAIL'] || !$d['TRANSACTION_ID']) {
            return response()->json(['message' => 'Item does not exist.']);
        }

        // Get user email, product ID, and transaction ID
        $email = $d['EMAIL'];
        $productID = $d['PRODUCT_ID'];
        $transactionID = $d['TRANSACTION_ID'];

        // Find the product (permission bundle) by product ID
        $product = Product::where('product_id', $productID)->first();

        // If the product doesn't exist, return an error
        if (!$product) {
            return response()->json(['message' => 'Product not found!']);
        }

        // Process based on transaction type (SALE or RFND)
        switch ($TYPE) {
            case 'SALE':
                $user = User::where('email', $email)->first();

                if (!$user) {
                    $password = Str::random(10);
                    // Create the user using the same structure as the authentication registration
                    $user = User::create([
                        'name' => substr($email, 0, strpos($email, '@')),
                        'email' => $email,
                        'password' => bcrypt($password),
                        'created_by' => 1,
                        'user_type' => 'owner',
                    ]);

                    $user->assignRole('User');
                    $user->givePermissionTo($product->funnel);

                    // Create product transaction
                    ProductTransaction::create([
                        'user_id'           => $user->id,
                        'product_id'        => $productID,
                        'transaction_id'   => $transactionID,
                        'transaction_type' => 'SALE'
                    ]);

                    // Send welcome email with login details to user
                    Mail::to($email)->send(new WelcomeMail($user, $password));

                    return response()->json(['message' => 'User created successfully!']);
                } else {
                    $user->assignRole('User');
                    $user->givePermissionTo($product->funnel);

                    // Create product transaction for existing user
                    ProductTransaction::create([
                        'user_id'           => $user->id,
                        'product_id'        => $productID,
                        'transaction_id'   => $transactionID,
                        'transaction_type' => 'SALE'
                    ]);

                    return response()->json(['message' => 'User role updated successfully!']);
                }

                break;

            case 'RFND':
                // Handle refund transaction
                $user = User::where('email', $email)->first();

                if (!$user) {
                    return response()->json(['message' => 'User not found!'], 404);
                }

                try {
                    // Store user info before deletion for email notification
                    $userInfo = [
                        'username' => $user->name,
                        'product'  => $product->name
                    ];

                    // Create product transaction record
                    ProductTransaction::create([
                        'user_id'           => $user->id,
                        'product_id'        => $productID,
                        'transaction_id'   => $transactionID,
                        'transaction_type' => 'RFND'
                    ]);

                    // Send email notification before deletion
                    Notification::send($user, new UserRefundNotification($userInfo));

                    // Delete user and all related resources
                    DeleteUserResource::handle($user->id);

                    return response()->json(['message' => 'User refunded and deleted successfully!'], 200);
                } catch (QueryException $e) {
                    return response()->json(['error' => 'Cannot delete user due to related data: ' . $e->getMessage()], 400);
                } catch (\Throwable $th) {
                    return response()->json(['error' => 'Error deleting user: ' . $th->getMessage()], 500);
                }

                break;

            default:
                return response()->json(['message' => 'Invalid transaction type!']);
        }
    }


    // This method verifies the JVZoo IPN request
    public function jvzipnVerification(Request $request)
    {
        $secretKey = "yP5LoM3o0NZVxHSU0QmC"; // Replace with your actual JVZoo secret key
        $pop = "";
        $ipnFields = array();

        foreach ($_POST as $key => $value) {
            if ($key == "cverify") {
                continue;
            }
            $ipnFields[] = $key;
        }

        sort($ipnFields);
        foreach ($ipnFields as $field) {
            $pop = $pop . $_POST[$field] . "|";
        }

        $pop = $pop . $secretKey;
        if ('UTF-8' != mb_detect_encoding($pop)) {
            $pop = mb_convert_encoding($pop, "UTF-8");
        }

        $calcedVerify = sha1($pop);
        $calcedVerify = strtoupper(substr($calcedVerify, 0, 8));

        return $calcedVerify == $_POST["cverify"];
    }
} 
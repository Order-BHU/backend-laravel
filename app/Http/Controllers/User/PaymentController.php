<?php

namespace App\Http\Controllers\User;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Transactions;
use App\Models\DriverTransfers;
use App\Services\BrevoMailer;
use App\Models\Wallet;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Menu;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class PaymentController extends Controller
{
    public function __construct(private BrevoMailer $brevo)
    {
        $this->brevo = $brevo;
    }

    public function bankList()
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        ])->get('https://api.paystack.co/bank');

        $banks = $response->json();
        return response()->json($banks);
    }

    public function resolveBank(Request $request)
    {
        $request->validate([
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        ])->get('https://api.paystack.co/bank/resolve', [
                    'account_number' => $request->account_number,
                    'bank_code' => $request->bank_code,
                ]);

        return response()->json($response->json());
    }

    public function transactionList(Request $request)
    {
        $user = $request->user();

        $transactions = [];

        if ($user->account_type == 'restaurant') {
            $restaurant = Restaurant::where('user_id', $user->id)->first();
            $transactions = Transactions::where('restaurant_id', $restaurant->id)
                ->orderBy('created_at', 'desc')
                ->get(['amount', 'type', 'status', 'reference', 'created_at']);
        } elseif ($user->account_type == 'customer') {
            $transactions = Transactions::where('customer_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get(['amount', 'type', 'status', 'reference', 'created_at']);
        }

        return response()->json([
            'transactions' => $transactions
        ]);
    }
    public function transferWebhook(Request $request)
    {
        try {
            // Verify the webhook signature
            $signature = $request->header('x-paystack-signature');
            $payload = $request->getContent();

            // Check if signature exists
            // if (!$signature) {
            //     return response()->json(['message' => 'Missing signature'], 400);
            // }

            // Verify the signature
            // $hash = hash_hmac('sha512', $payload, env('PAYSTACK_SECRET_KEY'));

            // if ($hash !== $signature) {
            //     return response()->json(['message' => 'Invalid signature'], 400);
            // }

            $event = $request->input('event');
            $data = $request->input('data');

            // Validate required data
            if (!$event || !$data) {
                return response()->json(['message' => 'Invalid payload'], 400);
            }

            // Handle different events
            switch ($event) {
                case 'charge.success':
                    return $this->handleChargeSuccess($data);

                case 'transfer.success':
                    return $this->handleTransferSuccess($data);

                case 'transfer.failed':
                    return $this->handleTransferFailed($data);

                case 'transfer.reversed':
                    return $this->handleTransferReversed($data);

                default:
                    return response()->json(['message' => 'Event not handled'], 200);
            }

        } catch (\Exception $e) {
            // Log the error
            Log::error('Webhook processing error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    private function handleChargeSuccess($data)
    {
        $metaData = $data['metadata'] ?? [];

        // Validate required metadata
        $requiredFields = ['user_id', 'restaurant_id', 'total', 'items', 'location'];
        foreach ($requiredFields as $field) {
            if (!isset($metaData[$field])) {
                return response()->json(['message' => "Missing required field: $field"], 400);
            }
        }

        DB::beginTransaction();

        try {
            // Create transaction record
            $transaction = Transactions::create([
                'customer_id' => $metaData['user_id'],
                'restaurant_id' => $metaData['restaurant_id'],
                'amount' => $metaData['total'],
                'type' => 'credit',
                'status' => 'completed',
                'reference' => $data['reference'], // Fixed: removed extra 'data' level
            ]);

            // Update wallet balance
            $wallet = Wallet::where('user_id', $metaData['restaurant_id'])->first();
            if (!$wallet) {
                throw new \Exception('Wallet not found for restaurant');
            }

            $wallet->balance += $metaData['total'];
            $wallet->save();

            // Generate order code
            $randomCode = rand(1000, 9999);

            // Create order
            $order = Order::create([
                'user_id' => $metaData['user_id'],
                'items' => $metaData['items'],
                'restaurant_id' => $metaData['restaurant_id'],
                'total' => $metaData['total'],
                'customer_location' => $metaData['location'],
                'status' => 'pending',
                'code' => $randomCode,
            ]);

            // Clear cart
            Cart::where('user_id', $metaData['user_id'])->delete();

            // Send notifications (if needed)
            $this->sendOrderNotification($order, $metaData, $this->brevo);

            DB::commit();

            return response()->json(['message' => 'Charge processed successfully'], 200);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Charge processing error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to process charge'], 500);
        }
    }

    private function handleTransferSuccess($data)
    {
        $transfer = DriverTransfers::where('reference', $data['reference'])->first();
        if ($transfer && $transfer != 'success') {
            $transfer->status = 'success';
            if (isset($data['transfer_code'])) {
                $transfer->transfer_code = $data['transfer_code'];
            }
            $transfer->save();

            // Optionally, you can also update the driver's wallet or notify them
            // Update wallet balance
            $wallet = Wallet::where('user_id', $transfer->user_id)->first();
            if (!$wallet) {
                throw new \Exception('Wallet not found for restaurant');
            }

            $wallet->balance += $data['amount']; 
            $wallet->save();

        }

        return response()->json(['message' => 'Transfer success processed'], 200);
    }

    private function handleTransferFailed($data)
    {
        $transfer = DriverTransfers::where('reference', $data['reference'])->first();
        if ($transfer) {
            $transfer->status = 'failed';
            $transfer->save();
        }

        return response()->json(['message' => 'Transfer failure processed'], 200);
    }

    private function handleTransferReversed($data)
    {
        $transfer = DriverTransfers::where('reference', $data['reference'])->first();
        if ($transfer) {
            $transfer->status = 'reversed';
            $transfer->save();
        }

        return response()->json(['message' => 'Transfer reversal processed'], 200);
    }

    private function sendOrderNotification($order, $metaData, BrevoMailer $brevo)
    {
        try {
            $restaurantDetails = Restaurant::find($metaData['restaurant_id']);
            Log::info('metaData: ' . json_encode($metaData));
            $user = User::where('id',$metaData['user_id'])->first();

            if (!$restaurantDetails || !$user) {
                return;
            }

            $details = [
                'order_id' => $order->id,
                'order_date' => $order->created_at->format('Y-m-d H:i:s'),
                'orderItems' => $order->items,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone_number,
                'customer_email' => $user->email,
                'pickup_location' => $restaurantDetails->name,
                'delivery_address' => $order->customer_location,
            ];

            $htmlContent = view('emails.user.order', $details)->render();

            // Uncomment and configure your email service
            $email = User::where('account_type', 'admin')->pluck('email');
            foreach ($email as $recipient) {
                $brevo->sendMail(
                    $recipient,
                    'Admin',
                    'New Order',
                  $htmlContent,
                  config("mail.from.address", "support@bhuorder.com"),
                  'BHU Order',
                );
            }
        

        } catch (\Exception $e) {
            Log::error('Notification sending failed: ' . $e->getMessage());
        }
    }

}

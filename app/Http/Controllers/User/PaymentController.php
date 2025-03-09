<?php

namespace App\Http\Controllers\User;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Transactions;


class PaymentController extends Controller
{
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
}

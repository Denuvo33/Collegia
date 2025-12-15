<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EventModel;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class TransactionController extends Controller
{


    public function index()
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('user/Transaction', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Get paginated transactions for infinite scroll
     */
    public function getTransactions(Request $request)
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($transactions);
    }
    public function __construct()
    {
        // Set Midtrans configuration
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    /**
     * Initiate payment for event creation
     */
    public function initiatePayment(Request $request)
    {
        try {
            // Validate event data
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'price' => 'nullable|numeric|min:0',
                'location' => 'required|string|max:255',
                'organizer' => 'required|string|max:255',
                'date_event' => 'required|date',
                'status' => 'required|in:upcoming,ongoing,finished',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $user = Auth::user();

            // Check if user role is allowed to create event with payment
            if ($user->role !== 'user') {
                return response()->json([
                    'error' => 'Only users with "user" role need to pay for event creation'
                ], 403);
            }

            // Check for existing pending transaction (prevent duplicates when user retries)
            $existingTransaction = Transaction::where('user_id', $user->id)
                ->where('transaction_status', Transaction::STATUS_PENDING)
                ->where('created_at', '>=', now()->subMinutes(2)) // Check within expiry window
                ->with('event')
                ->orderBy('created_at', 'desc')
                ->first();

            // If pending transaction exists and not expired, reuse it
            if ($existingTransaction && $existingTransaction->snap_token) {
                // Check if transaction is still valid (not expired by Midtrans)
                $transactionAge = now()->diffInMinutes($existingTransaction->created_at);

                if ($transactionAge < 1) { // Still within 1 minute expiry
                    Log::info('Reusing existing pending transaction', [
                        'order_id' => $existingTransaction->order_id,
                        'user_id' => $user->id,
                        'age_minutes' => $transactionAge,
                    ]);

                    return response()->json([
                        'snap_token' => $existingTransaction->snap_token,
                        'order_id' => $existingTransaction->order_id,
                        'event_id' => $existingTransaction->event_id,
                        'amount' => $existingTransaction->amount,
                        'message' => 'Melanjutkan pembayaran yang sedang pending',
                    ]);
                } else {
                    // Transaction expired, mark it and create new one
                    $existingTransaction->transaction_status = Transaction::STATUS_EXPIRED;
                    $existingTransaction->save();

                    Log::info('Transaction expired, creating new one', [
                        'old_order_id' => $existingTransaction->order_id,
                    ]);
                }
            }

            DB::beginTransaction();

            // Handle image upload if exists
            if ($request->hasFile('image')) {
                $validated['image'] = $request->file('image')->store('images', 'public');
            }

            // Create event but set as invisible
            $validated['user_id'] = $user->id;
            $validated['is_visible'] = false;
            $event = EventModel::create($validated);

            // Generate unique order ID
            $orderId = 'EVENT-' . $event->id . '-' . time();

            // Set payment amount (Rp 5,000)
            $paymentAmount = 5000;

            // Create transaction record
            $transaction = Transaction::create([
                'order_id' => $orderId,
                'user_id' => $user->id,
                'event_id' => $event->id,
                'amount' => $paymentAmount,
                'transaction_status' => Transaction::STATUS_PENDING,
            ]);

            // Update event with transaction ID
            $event->user_pay_transaction_id = $transaction->id;
            $event->save();

            // Prepare transaction details for Midtrans
            $transactionDetails = [
                'order_id' => $orderId,
                'gross_amount' => (int) $paymentAmount,
            ];

            $itemDetails = [
                [
                    'id' => 'EVENT_CREATION_FEE',
                    'price' => (int) $paymentAmount,
                    'quantity' => 1,
                    'name' => 'Biaya Pembuatan Event: ' . $event->title,
                ],
            ];

            $customerDetails = [
                'first_name' => $user->name,
                'email' => $user->email,
            ];

            // UPDATED: Limit payment methods to specific options only
            $params = [
                'transaction_details' => $transactionDetails,
                'item_details' => $itemDetails,
                'customer_details' => $customerDetails,
                'enabled_payments' => [
                    // E-wallets
                    'gopay',
                    'shopeepay',
                    'qris',
                    // Bank transfers (Virtual Account)
                    'bca_va',
                    'bri_va',
                    'bni_va',
                    'permata_va', // BSI
                    'echannel', // Mandiri Bill Payment
                ],
                'expiry' => [
                    'unit' => 'minutes',
                    'duration' => 1,
                ],
                // Callbacks will be handled in frontend
            ];

            // Get Snap Token
            $snapToken = Snap::getSnapToken($params);

            // Save snap token to transaction
            $transaction->snap_token = $snapToken;
            $transaction->save();

            DB::commit();

            Log::info('Payment initiated successfully', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'event_id' => $event->id,
            ]);

            return response()->json([
                'snap_token' => $snapToken,
                'order_id' => $orderId,
                'event_id' => $event->id,
                'amount' => $paymentAmount,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to initiate payment',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate payment for event promotion
     */
    public function initiatePromotionPayment(Request $request)
    {
        try {
            // Validate promotion data
            $validated = $request->validate([
                'event_id' => 'required|exists:event,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
            ]);

            $user = Auth::user();
            $event = EventModel::findOrFail($validated['event_id']);

            // Check if user is the event owner
            if ($event->user_id !== $user->id) {
                return response()->json([
                    'error' => 'Anda tidak memiliki izin untuk mempromosikan event ini'
                ], 403);
            }

            // Check if event is visible
            if (!$event->is_visible) {
                return response()->json([
                    'error' => 'Event harus terlihat sebelum dapat dipromosikan'
                ], 400);
            }

            // Calculate promotion days
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);
            $promotionDays = $startDate->diffInDays($endDate);
            if ($promotionDays === 0) {
                $promotionDays = 1; // Minimum 1 day for same day promotion
            }

            // Calculate promotion cost (Rp 5,000 per day)
            $costPerDay = 5000;
            $promotionAmount = $promotionDays * $costPerDay;

            DB::beginTransaction();

            // Generate unique order ID for promotion
            $orderId = 'PROMO-' . $event->id . '-' . time();

            // Create transaction record for promotion
            $transaction = Transaction::create([
                'order_id' => $orderId,
                'transaction_type' => Transaction::TYPE_EVENT_PROMOTION,
                'user_id' => $user->id,
                'event_id' => $event->id,
                'amount' => $promotionAmount,
                'transaction_status' => Transaction::STATUS_PENDING,
                'promotion_days' => $promotionDays,
                'promotion_start_date' => $startDate,
                'promotion_end_date' => $endDate,
            ]);

            // Prepare transaction details for Midtrans
            $transactionDetails = [
                'order_id' => $orderId,
                'gross_amount' => (int) $promotionAmount,
            ];

            $itemDetails = [
                [
                    'id' => 'EVENT_PROMOTION',
                    'price' => (int) $promotionAmount,
                    'quantity' => 1,
                    'name' => "Promosi Event ({$promotionDays} hari): " . $event->title,
                ],
            ];

            $customerDetails = [
                'first_name' => $user->name,
                'email' => $user->email,
            ];

            $params = [
                'transaction_details' => $transactionDetails,
                'item_details' => $itemDetails,
                'customer_details' => $customerDetails,
                'enabled_payments' => [
                    'gopay',
                    'shopeepay',
                    'qris',
                    'bca_va',
                    'bri_va',
                    'bni_va',
                    'permata_va',
                    'echannel',
                ],
                'expiry' => [
                    'unit' => 'minutes',
                    'duration' => 1,
                ],
            ];

            // Get Snap Token
            $snapToken = Snap::getSnapToken($params);

            // Save snap token to transaction
            $transaction->snap_token = $snapToken;
            $transaction->save();

            DB::commit();

            Log::info('Promotion payment initiated successfully', [
                'order_id' => $orderId,
                'user_id' => $user->id,
                'event_id' => $event->id,
                'promotion_days' => $promotionDays,
                'amount' => $promotionAmount,
            ]);

            return response()->json([
                'snap_token' => $snapToken,
                'order_id' => $orderId,
                'event_id' => $event->id,
                'amount' => $promotionAmount,
                'promotion_days' => $promotionDays,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Promotion payment initiation failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to initiate promotion payment',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel transaction when user closes payment without selecting method
     */
    public function cancelTransaction($orderId)
    {
        try {
            $transaction = Transaction::where('order_id', $orderId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Only cancel if transaction is still pending and has no payment_type
            // (meaning user hasn't selected a payment method yet)
            if ($transaction->transaction_status === Transaction::STATUS_PENDING && !$transaction->payment_type) {
                $transaction->transaction_status = Transaction::STATUS_CANCELLED;
                $transaction->save();

                Log::info('Transaction cancelled by user', [
                    'order_id' => $orderId,
                    'user_id' => Auth::id(),
                ]);

                return response()->json(['message' => 'Transaction cancelled successfully']);
            }

            return response()->json(['message' => 'Transaction cannot be cancelled'], 400);
        } catch (Exception $e) {
            Log::error('Failed to cancel transaction: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to cancel transaction',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh expired snap token for pending transaction
     */
    public function refreshSnapToken($orderId)
    {
        try {
            $transaction = Transaction::where('order_id', $orderId)
                ->where('user_id', Auth::id())
                ->with('event')
                ->first();

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            if ($transaction->transaction_status !== Transaction::STATUS_PENDING) {
                return response()->json([
                    'error' => 'Transaction is not pending',
                    'status' => $transaction->transaction_status,
                ], 400);
            }

            if (!$transaction->event) {
                return response()->json(['error' => 'Event not found'], 404);
            }

            // Check if snap token is expired (older than expiry time)
            $tokenAge = now()->diffInMinutes($transaction->created_at);

            if ($tokenAge < 1) {
                // Token still valid, return existing one
                return response()->json([
                    'snap_token' => $transaction->snap_token,
                    'order_id' => $transaction->order_id,
                    'message' => 'Token masih valid',
                ]);
            }

            // Token expired, generate new snap token
            $transactionDetails = [
                'order_id' => $transaction->order_id,
                'gross_amount' => (int) $transaction->amount,
            ];

            $itemDetails = [
                [
                    'id' => 'EVENT_CREATION_FEE',
                    'price' => (int) $transaction->amount,
                    'quantity' => 1,
                    'name' => 'Biaya Pembuatan Event: ' . $transaction->event->title,
                ],
            ];

            $customerDetails = [
                'first_name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ];

            $params = [
                'transaction_details' => $transactionDetails,
                'item_details' => $itemDetails,
                'customer_details' => $customerDetails,
                'enabled_payments' => [
                    'gopay',
                    'shopeepay',
                    'qris',
                    'bca_va',
                    'bri_va',
                    'bni_va',
                    'permata_va',
                    'echannel',
                ],
                'expiry' => [
                    'unit' => 'minutes',
                    'duration' => 1,
                ],
            ];

            // Generate new Snap Token
            $newSnapToken = Snap::getSnapToken($params);

            // Update transaction with new snap token
            $transaction->snap_token = $newSnapToken;
            $transaction->updated_at = now();
            $transaction->save();

            Log::info('Snap token refreshed', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'snap_token' => $newSnapToken,
                'order_id' => $transaction->order_id,
                'message' => 'Token berhasil diperbaharui',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to refresh snap token: ' . $e->getMessage());

            return response()->json([
                'error' => 'Gagal memperbaharui token pembayaran',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Midtrans webhook notification
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Get raw POST data
            $rawData = $request->getContent();
            $notificationData = json_decode($rawData, true);

            // Validate essential fields exist
            if (!isset($notificationData['order_id'])) {
                Log::error('Webhook missing order_id', ['data' => $notificationData]);
                return response()->json(['message' => 'Missing order_id'], 400);
            }

            $transactionStatus = $notificationData['transaction_status'] ?? 'unknown';
            $fraudStatus = $notificationData['fraud_status'] ?? null;
            $orderId = $notificationData['order_id'];

            Log::info('Midtrans webhook received', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'payment_type' => $notificationData['payment_type'] ?? null,
            ]);

            // Find transaction
            $transaction = Transaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                Log::warning('Transaction not found for order_id: ' . $orderId);
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            // Store raw Midtrans response
            $transaction->response_midtrans = $notificationData;
            $transaction->payment_type = $notificationData['payment_type'] ?? null;

            // Handle transaction status
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    $this->updateTransactionSuccess($transaction);
                }
            } elseif ($transactionStatus == 'settlement') {
                $this->updateTransactionSuccess($transaction);
            } elseif ($transactionStatus == 'pending') {
                $transaction->transaction_status = Transaction::STATUS_PENDING;
            } elseif ($transactionStatus == 'deny' || $transactionStatus == 'cancel') {
                $transaction->transaction_status = Transaction::STATUS_FAILED;
            } elseif ($transactionStatus == 'expire') {
                $transaction->transaction_status = Transaction::STATUS_EXPIRED;
            }

            $transaction->save();

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update transaction to success and make event visible or promoted
     */
    private function updateTransactionSuccess(Transaction $transaction)
    {
        $transaction->transaction_status = Transaction::STATUS_SUCCESS;
        $transaction->paid_at = now();

        if ($transaction->event) {
            // Handle based on transaction type
            if ($transaction->transaction_type === Transaction::TYPE_EVENT_CREATION) {
                // Make event visible for event creation payments
                $transaction->event->is_visible = true;
                $transaction->event->save();

                Log::info('Event made visible', [
                    'event_id' => $transaction->event->id,
                    'event_title' => $transaction->event->title,
                ]);
            } elseif ($transaction->transaction_type === Transaction::TYPE_EVENT_PROMOTION) {
                // Update promoted_till for promotion payments
                $transaction->event->promoted_till = $transaction->promotion_end_date;
                $transaction->event->save();

                Log::info('Event promotion activated', [
                    'event_id' => $transaction->event->id,
                    'event_title' => $transaction->event->title,
                    'promoted_till' => $transaction->promotion_end_date,
                    'promotion_days' => $transaction->promotion_days,
                ]);
            }
        }
    }

    /**
     * Check transaction status
     */
    public function checkStatus($orderId)
    {
        try {
            $transaction = Transaction::where('order_id', $orderId)
                ->where('user_id', Auth::id())
                ->with('event')
                ->first();

            if (!$transaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Optionally query Midtrans API for latest status
            try {
                /** @var object $status */
                $status = \Midtrans\Transaction::status($orderId);

                // Update local database with latest status
                if ($status->transaction_status == 'settlement') {
                    $this->updateTransactionSuccess($transaction);
                    $transaction->save();
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch status from Midtrans: ' . $e->getMessage());
            }

            return response()->json([
                'order_id' => $transaction->order_id,
                'status' => $transaction->transaction_status,
                'amount' => $transaction->amount,
                'payment_type' => $transaction->payment_type,
                'paid_at' => $transaction->paid_at,
                'event' => $transaction->event ? [
                    'id' => $transaction->event->id,
                    'title' => $transaction->event->title,
                    'is_visible' => $transaction->event->is_visible,
                ] : null,
            ]);
        } catch (Exception $e) {
            Log::error('Status check failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to check status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

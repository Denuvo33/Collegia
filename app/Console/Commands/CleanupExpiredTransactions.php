<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\EventModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:cleanup 
                            {--dry-run : Run without making changes}
                            {--hours=48 : Hours after which pending transactions are considered expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired pending transactions and their invisible events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $hours = (int) $this->option('hours');

        $this->info("Starting transaction cleanup...");
        $this->info($dryRun ? "DRY RUN MODE - No changes will be made" : "LIVE MODE - Changes will be made");
        $this->newLine();

        // Find expired pending transactions
        $expiredTransactions = Transaction::where('transaction_status', Transaction::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours($hours))
            ->with('event')
            ->get();

        if ($expiredTransactions->isEmpty()) {
            $this->info('✓ No expired transactions found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$expiredTransactions->count()} expired pending transactions:");
        $this->newLine();

        $deletedEvents = 0;
        $updatedTransactions = 0;

        foreach ($expiredTransactions as $transaction) {
            $this->line("Order ID: {$transaction->order_id}");
            $this->line("  Created: {$transaction->created_at->diffForHumans()}");
            $this->line("  Amount: Rp " . number_format($transaction->amount, 0, ',', '.'));

            if ($transaction->event && !$transaction->event->is_visible) {
                $this->line("  Event: {$transaction->event->title} (invisible)");

                if (!$dryRun) {
                    // Delete the invisible event
                    DB::transaction(function () use ($transaction) {
                        $transaction->event->delete();
                        $transaction->transaction_status = Transaction::STATUS_EXPIRED;
                        $transaction->save();
                    });

                    $deletedEvents++;
                    $updatedTransactions++;
                    $this->info("  ✓ Event deleted, transaction marked as expired");
                } else {
                    $this->comment("  → Would delete event and mark transaction as expired");
                }
            } else {
                if (!$dryRun) {
                    // Just mark transaction as expired
                    $transaction->transaction_status = Transaction::STATUS_EXPIRED;
                    $transaction->save();
                    $updatedTransactions++;
                    $this->info("  ✓ Transaction marked as expired");
                } else {
                    $this->comment("  → Would mark transaction as expired");
                }
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('CLEANUP SUMMARY');
        $this->info('═══════════════════════════════════════════');

        if ($dryRun) {
            $this->warn("DRY RUN - No changes were made");
            $this->line("Would delete: {$expiredTransactions->whereNotNull('event')->where('event.is_visible', false)->count()} invisible events");
            $this->line("Would update: {$expiredTransactions->count()} transactions to 'expired' status");
        } else {
            $this->info("Deleted invisible events: {$deletedEvents}");
            $this->info("Updated transactions to expired: {$updatedTransactions}");
        }

        $this->newLine();

        // Log the cleanup
        if (!$dryRun) {
            Log::info('Transaction cleanup completed', [
                'deleted_events' => $deletedEvents,
                'expired_transactions' => $updatedTransactions,
                'hours_threshold' => $hours,
            ]);
        }

        return self::SUCCESS;
    }
}

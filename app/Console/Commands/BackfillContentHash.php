<?php

namespace App\Console\Commands;

use App\Helpers\SecurityHelper;
use App\Models\EventModel;
use App\Models\Post;
use Illuminate\Console\Command;

class BackfillContentHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:content-hash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill content_hash for existing posts and events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting content_hash backfill...');

        // Backfill posts
        $this->info('Backfilling posts...');
        $postsUpdated = 0;
        Post::whereNull('content_hash')->chunk(100, function ($posts) use (&$postsUpdated) {
            foreach ($posts as $post) {
                $post->content_hash = SecurityHelper::getContentHash($post->content);
                $post->save();
                $postsUpdated++;
            }
        });
        $this->info("Updated {$postsUpdated} posts");

        // Backfill events
        $this->info('Backfilling events...');
        $eventsUpdated = 0;
        EventModel::whereNull('content_hash')->chunk(100, function ($events) use (&$eventsUpdated) {
            foreach ($events as $event) {
                $event->content_hash = SecurityHelper::getContentHash($event->content);
                $event->save();
                $eventsUpdated++;
            }
        });
        $this->info("Updated {$eventsUpdated} events");

        $this->info('Content_hash backfill completed successfully!');
        $this->info("Total: {$postsUpdated} posts + {$eventsUpdated} events");

        return Command::SUCCESS;
    }
}

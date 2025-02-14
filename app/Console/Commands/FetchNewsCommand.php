<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\News\NewsAggregator;
use Illuminate\Console\Command;

final class FetchNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch news from all configured sources';

    /**
     * Execute the console command.
     */
    public function handle(NewsAggregator $newsAggregator): int
    {
        $this->info('Fetching news...');
        $newsAggregator->fetchNews();
        $this->info('News fetched successfully!');

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SaveProductsLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amz-product:link-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save amazon daily deal products link';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        exec('php ' . base_path('artisan') . ' dusk');
        return 0;
    }
}

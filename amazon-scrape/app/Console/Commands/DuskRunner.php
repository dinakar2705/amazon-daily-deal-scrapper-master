<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DuskRunner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes  the dusk command to fetch amazon products';

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
       exec('php ' . base_path('artisan') . ' dusk tests/Browser/ExampleTest.php --filter testFetchUrlFromTxt');
        return 0;
    }
}

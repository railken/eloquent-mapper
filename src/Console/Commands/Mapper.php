<?php

namespace Railken\EloquentMapper\Console\Commands;

use Illuminate\Console\Command;

class Mapper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapper:generate';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // app('eloquent.mapper')->regenerate();
        // $this->info("Generated!");
    }
}

<?php

namespace Webkul\Bagisto\Console\Commands;

use Illuminate\Console\Command;

class BagistoInstaller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bagisto-package:install';

    protected $description = 'Install the Unopim Bagisto package';

    public function handle(): void
    {
        $this->info('Installing Unopim Bagisto...');

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->call('migrate');
        }

        $this->call('vendor:publish', [
            '--tag' => 'unopim-bagisto-connector',
        ]);

        $this->call('optimize:clear');

        $this->info('Unopim Bagisto package installed successfully!');
    }
}

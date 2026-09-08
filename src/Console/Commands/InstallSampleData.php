<?php

namespace Webkul\Bagisto\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Bagisto\Http\Client\HttpClientFactory;
use Webkul\Bagisto\Traits\EncryptableTrait;

class InstallSampleData extends Command
{
    use EncryptableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bagisto:sample-config:generate
                            {--file= : Path to a custom sample-data JSON file (defaults to the bundled one)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load Bagisto connector sample data (credential, job instances, mappings, data mapping). The credential is verified against the store before it is encrypted and stored in the database.';

    /**
     * Default path to the bundled sample data (used when --file is not given).
     */
    protected string $dataFile = __DIR__.'/../../Database/SampleData/sample-data.json';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dataFile = $this->option('file') ?: $this->dataFile;

        if (! is_file($dataFile)) {
            $this->error("Sample data file not found: {$dataFile}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($dataFile), true);

        if (! is_array($data)) {
            $this->error('Sample data file is not valid JSON.');

            return self::FAILURE;
        }

        $this->info('Installing Bagisto sample data...');

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($data as $table => $rows) {
                foreach ($rows as $row) {
                    if ($table === 'wk_bagisto_credential') {
                        if ($this->credentialConfigured($row['id'])) {
                            $this->line(sprintf('  <info>%-42s</info> kept existing', $table));

                            continue 2;
                        }

                        $row = $this->resolveCredential($row);

                        if ($row === null) {
                            continue 2;
                        }
                    }

                    DB::table($table)->updateOrInsert(['id' => $row['id']], $row);
                }

                $this->line(sprintf('  <info>%-42s</info> %d rows', $table, count($rows)));
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->call('cache:clear');

        $this->info('Bagisto sample data installed. Credentials are stored only in the database (password encrypted).');

        return self::SUCCESS;
    }

    /**
     * Whether the credential already has shop URL, email and password set in the
     * database. If so the command keeps it and does not prompt or overwrite it.
     */
    protected function credentialConfigured($id): bool
    {
        $credential = DB::table('wk_bagisto_credential')->where('id', $id)->first();

        return $credential
            && ! empty($credential->shop_url)
            && ! empty($credential->email)
            && ! empty($credential->password);
    }

    /**
     * Prompt for any blank credential field, verify it against the store, and
     * encrypt the password. Returns null (and skips storing) when the credential
     * is not accepted by the store.
     */
    protected function resolveCredential(array $row): ?array
    {
        if (empty($row['shop_url'])) {
            $this->error('Shop URL is empty.');
            $row['shop_url'] = (string) $this->ask('Enter Shop URL');
        }

        if (empty($row['email'])) {
            $this->error('Email Address is empty.');
            $row['email'] = (string) $this->ask('Enter Email Address');
        }

        if (empty($row['password'])) {
            $this->error('Password is empty.');
            $row['password'] = (string) $this->secret('Enter Password');
        }

        if (! $this->credentialIsValid($row['shop_url'], $row['email'], $row['password'])) {
            $this->error('Credential is not correct.');

            return null;
        }

        $row['password'] = $this->encryptValue($row['password']);

        return $row;
    }

    /**
     * Verify the credential by attempting an admin login against the Bagisto store.
     */
    protected function credentialIsValid(string $shopUrl, string $email, string $password): bool
    {
        try {
            (new HttpClientFactory)
                ->withBaseUri($shopUrl)
                ->withEmail($email)
                ->withPassword($password)
                ->make();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

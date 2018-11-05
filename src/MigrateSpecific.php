<?php

/*
 * This file is part of the MigrateSpecific package.
 *
 * (c) Calos Kao <calos3257@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CalosKao;

use DB;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateSpecific extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:specific
                            {files* : File path, support multiple file. (Sperate by space)}
                            {--k|keep-batch : Keep batch number. (Only works in refresh mode)}
                            {--m|mode=default : Set migrate execution mode, supported mode have: default, refresh, reset }
                            {--y|assume-yes : Automatic yes to prompts; assume "yes" as answer to all prompts and run non-interactively. The process will be automatic assume yes as answer when  you used option "-n" or "-q". }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate, refresh, reset or rollback for specific migration files.';

    /**
     * Input migration files name.
     *
     * @var string
     */
    private $files;

    /**
     * MigrateSpecific temporary path.
     *
     * @var string
     */
    private $migratePath;

    /**
     * Temporary store history batch number to restore when option -k is enabled.
     *
     * @var string
     */
    private $batchHistory;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->printHeaderInfo();
        $mode = $this->option('mode');
        if ( !in_array($mode, ['default', 'rollback', 'refresh', 'reset']) ) {
            $this->error("Invalid migrate mode: {$mode}");
            return false;
        }

        $files = $this->argument('files');
        $tmpPath = tempnam(storage_path(), 'migrate-specific_');
        if ( file_exists($tmpPath) ) {
            unlink($tmpPath);
        }
        mkdir($tmpPath, 0777, true);
        $this->migratePath = str_replace(base_path(), '', $tmpPath);;
        try {
            foreach ($files as $pathSrc) {
                $pathDst = $tmpPath.'/'.basename($pathSrc);
                copy($pathSrc, $pathDst);
            }

            $migrationsFilename = self::parseFilename(glob("{$tmpPath}/*"));
            $isContinueConfirm = ( $this->option('assume-yes') || $this->option('quiet') || $this->option('no-interaction') );
            if ( false === $isContinueConfirm ) {
                $displayActionWord = 'migrated';
                $warningMsg = "Warning: You have switched to {$mode} mode, which means the migrate:specific command will ";
                switch ($mode) {
                    case 'refresh':
                        $displayActionWord = 'refreshed';
                        $warningMsg .= 'refresh specific migrations and then execute the migrate command.';
                        break;

                    case 'reset':
                        $displayActionWord = 'reset';
                        $warningMsg .= 'reset specific migrations.';
                        break;

                    case 'rollback':
                        $displayActionWord = 'rolled back';
                        $warningMsg .= 'roll back specific migrations.';
                        break;
                }

                $this->comment($warningMsg . PHP_EOL);
                $this->line("The following migrations will be {$displayActionWord}:");
                foreach ($migrationsFilename as $migration) {
                    $this->line("  {$migration}");
                }
                if ( false === $this->confirm('Do you want to continue?') ) {
                    $this->line('Abort.');
                    return false;
                }
            }

            switch ($mode) {
                default:
                case 'reset':
                case 'rollback':
                    $this->line($this->migrate($mode));
                    break;

                case 'refresh':
                    if ($this->option('keep-batch')) {
                        $this->backupBatchHistory($migrationsFilename);
                        $this->comment('');
                    }
                    $newBatchNumber = (int)DB::table('migrations')->max('batch') + 1;
                    $countExistsMigration = DB::table('migrations')
                        ->whereIn('migration', $migrationsFilename)
                        ->update(['batch' => $newBatchNumber]);

                    // If migration status is migrated, reset it first.
                    if ( 0 < $countExistsMigration ) {
                        $this->line($this->migrate('reset'));
                    }
                    $this->call('migrate', ['--path' => $this->migratePath]);

                    if ($this->option('keep-batch')) {
                        $this->restoreBatchHistory();
                    }
                    break;
            }

        } finally {
            array_map('unlink', glob($tmpPath."/*"));
            rmdir($tmpPath);
        }
    }

    /**
     * Print package infomation.
     *
     * @return void
     */
    private function printHeaderInfo() {
        $this->comment('MigrateSpecific v1.3.2');
        $this->line('Copyright (C) 2018 by Calos Kao');
        $this->line('If you have any problem or bug about the use, please come to Github to open the question.');
        $this->info('https://github.com/caloskao/migrate-specific'.PHP_EOL);
    }

    /**
     * Call artisan migrate command.
     *
     * @param  string $mode The artisan migrate command execute mode, avairable option: default, refresh, reset.
     *
     * @return string $value Sub command output.
     */
    private function migrate($mode = 'default') {
        $mode = ('default' === $mode ? '' : ":{$mode}");
        return $this->callArtisanBySymfony("migrate{$mode}", ['--path' => $this->migratePath], 'not found');
    }

    /**
     * Backup migration original batch.
     *
     * @param  array $migrations The database records of migrations.
     *
     * @return void
     */
    private function backupBatchHistory(array $migrations) {
        $this->batchHistory = DB::table('migrations')
            ->whereIn('migration', $migrations)
            ->get()
            ->toArray();
    }

    /**
     * Restore migration original batch and id.
     *
     * @return void
     */
    private function restoreBatchHistory() {
        foreach ($this->batchHistory as $item) {
            DB::table('migrations')
                ->where('migration', $item->migration)
                ->update([
                    'id'    => $item->id,
                    'batch' => $item->batch,
                ]);
        }
    }

    /**
     * Call artisan command by Symfony.
     *
     * @param  string $command           Artisan command name.
     * @param  array  $args              Command arguments.
     * @param  string $lineFilterKeyword Filter output line by keyword.
     *
     * @return string Command output.
     */
    private function callArtisanBySymfony($command, array $args = [], $lineFilterKeyword = null) {
        $output = new BufferedOutput;
        $instance = $this->getApplication()->find($command);
        $instance->run(new ArrayInput($args), $output);
        $outputText = $output->fetch();
        if ( null !== $lineFilterKeyword ) {
            $lines = collect(explode(PHP_EOL, $outputText));
            $outputText = $lines->filter(function($item) use ($lineFilterKeyword){
                return ( false === strpos($item, $lineFilterKeyword) );
            })->implode(PHP_EOL);
        }
        return $outputText;
    }

    /**
     * Strip path and extension for files.
     *
     * @param array $files The full path infomation of the file.
     *
     * @return array File names.
     */
    private static function parseFilename(array $files){
        return collect($files)->map(function($path){
            $basename = basename($path);
            return substr($basename, 0, strrpos($basename, '.'));
        })->toArray();
    }
}

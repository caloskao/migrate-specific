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
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateSpecific extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:specific
                            {files?* : File or directory path, support multiple file (Sperate by space)  [default: "database/migrations"]}
                            {--p|pretend : Dump the SQL queries that would be run}
                            {--f|skip-foreign-key-checks : Set FOREIGN_KEY_CHECKS=0 before migrate}
                            {--k|keep-batch : Keep batch number. (Only works in refresh mode)}
                            {--m|mode=default : Set migrate execution mode, supported mode have: default, rollback, refresh }
                            {--y|assume-yes : Automatically assumes "yes" to run commands in non-interactive mode. This option is automatically enabled if you use the option "-n" or "-q" }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate, rollback or refresh for specific migration files.';

    /**
     * Temporary store history batch number to restore when option -k is enabled.
     *
     * @var string
     */
    private $batchHistory;

    /**
     * Input migration files name.
     *
     * @var string
     */
    private $files;

    private $repositoryTableName;

    /**
     * MigrateSpecific temporary path.
     *
     * @var string
     */
    private $workingPath;

    /**
     * Package version.
     *
     * @var string
     */
    private $version = 'v2.0.0-beta.1';

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

        $this->checkMigrateMode();

        $this->output->write('<comment>Create temporary working directory ... </>');
        $this->preparePaths();
        $this->info('ok');

        $this->output->write('<comment>Copy files ... </>');
        $this->prepareFiles();
        $this->info('ok');

        $this->repositoryTableName = config('database.migrations');
        $this->migrator = app('migrator');
        $this->migrator->setOutput($this->output);

        $options = array_filter([
            'pretend' => $this->option('pretend'),
            // 'step' => $this->option('step'),
            'force' => true,
        ]);

        // When option 'pretend' is enabled then skip prompts.
        if ( $this->option('pretend') ) {
            $this->option('assume-yes', true);
        }

        $skipForeignKeyChecks = $this->option('skip-foreign-key-checks');

        try {
            
            if ( !$this->confirmExecution() ) {
                $this->comment('Abort.');
                return false;
            }

            switch ( $this->option('mode') ) {
                default:
                    $this->migrator->run($this->workingPath, $options);
                    break;

                case 'rollback':
                    if ( $skipForeignKeyChecks ) {
                        $this->setForeignKeyChecks(0);
                    }
                    $this->moveMigrationsToDdHead();
                    $this->migrator->rollback($this->workingPath, $options);
                    break;

                case 'refresh':
                    if ($this->option('keep-batch')) {
                        $this->output->write('<comment>Backup repository batches ... </>');
                        $this->backupBatchHistory();
                        $this->info('ok');
                    }

                    $this->moveMigrationsToDdHead();

                    if ( $skipForeignKeyChecks ) {
                        $this->setForeignKeyChecks(0);
                    }

                    $this->migrator->rollback($this->workingPath, $options);
                    
                    if ( $skipForeignKeyChecks ) {
                        $this->setForeignKeyChecks(1);
                    }
                    
                    $this->migrator->run($this->workingPath, $options);

                    if ($this->option('keep-batch')) {
                        $this->restoreBatchHistory();
                        $this->output->write('<comment>Restore repository batches ... </>');
                        $this->backupBatchHistory();
                        $this->info('ok');
                    }
                    break;
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            $this->comment("\nAbort.");
        } finally {
            if ( file_exists($this->workingPath) ) {
                $this->output->write('<comment>Clear temporary working directory ... </>');
                array_map('unlink', glob($this->workingPath."/*"));
                rmdir($this->workingPath);
                $this->info("done");
            }
            $this->line('');
        }
    }

    /**
     * Print package infomation.
     *
     * @return void
     */
    private function printHeaderInfo() {
        $this->comment('MigrateSpecific '.$this->version);
        $this->line('Copyright (C) 2019 by Calos Kao');
        $this->line('If you have any problems with your use, please visit the GitHub repository.');
        $this->info('https://github.com/caloskao/migrate-specific'.PHP_EOL);
    }

    /**
     * Validate input option 'mode'.
     *
     * @return void
     */
    private function checkMigrateMode() {
        $mode = $this->option('mode');
        if ( !in_array($mode, ['default', 'rollback', 'refresh']) ) {
            throw new \Exception("Invalid migrate mode: {$mode}");
        }
    }

    /**
     * Prepare temporary working directory paths.
     *
     * @return void
     */
    private function preparePaths() {
        $this->workingPath = tempnam(storage_path(), 'migrate-specific_');
        if ( file_exists($this->workingPath) ) {
            unlink($this->workingPath);
        }
        mkdir($this->workingPath, 0777, true);
    }

    /**
     * Copy target migration files to temporary working directory.
     *
     * @return void
     */
    private function prepareFiles($files = null) {
        if ( null === $files ) {
            $files = $this->argument('files');
            if ( [] === $files ) {
                $files = glob(base_path().'/database/migrations/*');
            }
        }
        foreach ($files as $pathSrc) {
            if ( is_dir($pathSrc) ) {
                $this->prepareFiles( glob($pathSrc.'/*') );
            } else {
                $pathDst = $this->workingPath.'/'.basename($pathSrc);
                copy($pathSrc, $pathDst);
            }
        }
    }

    /**
     * Print confirmation prompt of migrate plan.
     *
     * @return bool Confirm result..
     */
    private function confirmExecution() {
        $isContinueConfirm = ( $this->option('assume-yes') || $this->option('quiet') || $this->option('no-interaction') );
        if ( $isContinueConfirm ) {
            return true;
        } else {
            if ( $this->option('skip-foreign-key-checks') ) {
                $this->comment("Warning: Option 'skip-foreign-key-checks' is enabled.");
            }

            $mode = $this->option('mode');
            $displayActionWord = 'migrated';
            $warningMsg = "Warning: You have switched to {$mode} mode, the migrate:specific command will ";
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
            foreach ($this->getMigrations() as $migration) {
                $this->line("  {$migration}");
            }

            return $this->confirm('Do you want to continue?');
        }
    }

    /**
     * Retrive database query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function getRepository() {
        return DB::table($this->repositoryTableName);
    }

    /**
     * Backup migrations original batch.
     *
     * @return void
     */
    private function backupBatchHistory() {
        $this->batchHistory = $this->getRepository()
            ->whereIn('migration', $this->getMigrations())
            ->get()
            ->toArray();
    }

    /**
     * Restore migrations original batch.
     *
     * @return void
     */
    private function restoreBatchHistory() {
        foreach ($this->batchHistory as $item) {
            $this->getRepository()
                ->where('migration', $item->migration)
                ->update(['batch' => $item->batch]);
        }
    }

    /**
     * Move target migrations batch to head of repository.
     *
     * @return void
     */
    private function moveMigrationsToDdHead() {
        $nextBatchNumber = $this->migrator->getRepository()->getNextBatchNumber();
        $this->getRepository()
            ->whereIn('migration', $this->getMigrations())
            ->update(['batch' => $nextBatchNumber]);
    }

    /**
     * Set foreign key checks.
     *
     * @param  bool $turnOn Turn on.
     *
     * @return void
     */
    private function setForeignKeyChecks(bool $turnOn = true) {
        $this->output->write('<comment>'.($turnOn ? 'Enable' : 'Disable').' foreign key checkes ... </>');
        $turnOn
            ? Schema::enableForeignKeyConstraints()
            : Schema::disableForeignKeyConstraints();
        $this->info('ok');
    }

    /**
     * Retrieve migrations file path.
     *
     * @return array Migrations file path.
     */
    private function getMigrationFiles(){
        return $this->migrator->getMigrationFiles($this->workingPath);
    }

    /**
     * Retrieve migrations name.
     *
     * @return array Migrations name.
     */
    private function getMigrations(){
        return array_keys($this->getMigrationFiles());
    }
}

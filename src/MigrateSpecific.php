<?php

namespace CalosKao;

use DB;
use Illuminate\Console\Command;

class MigrateSpecific extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:specific {files* : File path, support multiple file (Sperate by space).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate specific files.';

    protected $files;

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
     * @return mixed
     */
    public function handle()
    {
        $files = $this->argument('files');
        $tmpPath = tempnam(database_path(), 'migrate_file_');
        if ( file_exists($tmpPath) ) {
            unlink($tmpPath);
        }
        mkdir($tmpPath, 0777, true);
        try {
            foreach ($files as $pathSrc) {
                $pathDst = $tmpPath.'/'.basename($pathSrc);
                $this->line("Copy {$pathSrc}");
                copy($pathSrc, $pathDst);
            }

            $list = collect(glob("{$tmpPath}/*"));
            $this->line('There is ready to migrate files:');
            $this->line($list->map(function($v){ return '  '.basename($v); })->implode(PHP_EOL));
            if ( $this->confirm('Is this correct?') ) {
                $this->line("Start migrate ...");
                $maxBatch = (int)DB::table('migrations')->max('batch') + 1;
                $countExistsMigration = DB::table('migrations')
                    ->whereIn('migration', $list->map(function($path){
                        $basename = basename($path);
                        return substr($basename, 0, strrpos($basename, '.'));
                    }))
                    ->update(['batch' => $maxBatch]);
                $pathForArtisan = str_replace(base_path(), '', $tmpPath);

                // If migration have migrated, rollback first.
                if ( 0 < $countExistsMigration ) {
                    $this->call('migrate:rollback', ['--path' => $pathForArtisan]);
                }
                $this->call('migrate', ['--path' => $pathForArtisan]);
            } else {
                $this->info('Abort.');
            }
        } finally {
            array_map('unlink', glob($tmpPath."/*"));
            rmdir($tmpPath);
        }
    }
}

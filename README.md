# Migrate Specific
Migrate Specific is a Laravel framework Artisan CLI extension command, when you only want to migrate some specific migration files, you can use it for database migration.

# Requirement
laravel/framework v5.0.0 or later.

# Installation

Run command in your Laravel project root directory:

```
composer require caloskao/migrate-specific
```

Register command at `app/Console/Kernel.php` :

```
protected $commands = [
    \CalosKao\MigrateSpecific::class
];
```

Now, run `php artisan` , you can see `migrate:specific` in the migrate section:

```
 migrate
  migrate:fresh        Drop all tables and re-run all migrations
  migrate:install      Create the migration repository
  migrate:refresh      Reset and re-run all migrations
  migrate:reset        Rollback all database migrations
  migrate:rollback     Rollback the last database migration
  migrate:specific     Migrate specific files.
  migrate:status       Show the status of each migration
```

# Usage

You can run command `php artisan help migrate:specific` to check command usage:

```
Description:
  Migrate specific files.

Usage:
  migrate:specific <files>...

Arguments:
  files                 File path, support multiple file (Sperate by space).

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
      --env[=ENV]       The environment the command should run under
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

# Examples

Migrate single file:

```
php artisan migrate:specific database/migrations/table.php
```

Migrate mutiple files:

```
php artisan migrate:specific database/migrations/table-1.php /home/caloskao/my_migration.php /other-migrations/*
```

Output is like below:

```
Copy database/migrations/2014_10_12_000000_create_users_table.php
Copy database/migrations/2018_07_31_174401_create_jobs_table.php
Copy database/migrations/2018_07_31_185911_create_failed_jobs_table.php
There is ready to migrate files:
  2014_10_12_000000_create_users_table.php
  2018_07_31_174401_create_jobs_table.php
  2018_07_31_185911_create_failed_jobs_table.php

 Is this correct? (yes/no) [no]:
 > yes

Start migrate ...
Rolling back: 2018_07_31_185911_create_failed_jobs_table
Rolled back:  2018_07_31_185911_create_failed_jobs_table
Rolling back: 2018_07_31_174401_create_jobs_table
Rolled back:  2018_07_31_174401_create_jobs_table
Rolling back: 2014_10_12_000000_create_users_table
Rolled back:  2014_10_12_000000_create_users_table
Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table
Migrating: 2018_07_31_174401_create_jobs_table
Migrated:  2018_07_31_174401_create_jobs_table
Migrating: 2018_07_31_185911_create_failed_jobs_table
Migrated:  2018_07_31_185911_create_failed_jobs_table
```

## Note

You can directly run migration with non-interative mode by `-n`, `--no-interactive`, `-q` or `--quiet` option.

# License
The Migrate Specific extension is open-sourced software licensed under the MIT license.

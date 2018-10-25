# MigrateSpecific
MigrateSpecific is a Laravel framework Artisan CLI extension command that helps you easily perform database migrations of specific migration files in the Laravel framework.

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
  migrate:specific     Migrate, refresh or reset for specific database migration files.
  migrate:status       Show the status of each migration
```

# Usage

You can run `php artisan help migrate:specific` to check command usage:

```
Description:
  Easily execute database migration of specific files in the Laravel framework.

Usage:
  migrate:specific [options] [--] <files>...

Arguments:
  files                 File path, support multiple file (Sperate by space).

Options:
  -m, --mode[=MODE]     Set migrate exection mode, supported mode have: default, refresh, rollback, new-batch [default: "default"]
  -y, --assume-yes      Automatic yes to prompts; assume "yes" as answer to all prompts and run non-interactively. The process will be automatic assume yes as answer when  you used option "-n" or "-q".
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
      --env[=ENV]       The environment the command should run under
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

# Basic usage

### Migrate single file:

```
php artisan migrate:specific database/migrations/table.php
```

### Migrate mutiple files:

```
php artisan migrate:specific database/migrations/2014_10_12_000000_create_users_table.php /home/caloskao/2018*
```

Output is like below:

```
MigrateSpecific v1.2.1
Copyright (C) 2018 by CalosKao
If you have any problem or bug about the use, please come to Github to open the question.
https://github.com/caloskao/migrate-specific

The following migration files will be migrated:
  2014_10_12_000000_create_users_table.php
  2018_07_31_174401_create_jobs_table.php
  2018_07_31_185911_create_failed_jobs_table.php

 Is this correct? (yes/no) [no]:
 > yes

Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table
Migrating: 2018_07_31_174401_create_jobs_table
Migrated:  2018_07_31_174401_create_jobs_table
Migrating: 2018_07_31_185911_create_failed_jobs_table
Migrated:  2018_07_31_185911_create_failed_jobs_table
```

# Migrate mode

MigrateSpecific support you execute `migrate:refresh` and `migrate:reset` command for specific database migration file, call `migrate:specific` with option `-m`.

### Refresh specific database migration

```
php artisan migrate:specific -m refresh /path/to/migration.php
```

### Reset specific database migration

```
php artisan migrate:specific -m reset /path/to/migration.php
```

# Using MigrateSpecific in non-interactive mode

Sometimes we need to perform a database migration many times, or we need to deploy it into an automated process. At this time, we can use the option `-y` to directly perform database migration without confirmation.

```
php artisan migrate:specific -y /path/to/migration.php
```

**Note:**

 * If you call the option `-n` or `-q`, MigrateSpecific will be automatically enable option `-y`.
 * **If you are not using MigrateSpecific in the above situations, we recommend that you do not perform database migration in non-interactive mode to avoid accidental loss of data.**

# License
The Migrate Specific extension is open-sourced software licensed under the MIT license.

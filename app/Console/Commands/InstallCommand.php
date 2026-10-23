<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'app:install {--demo : Добавить демо-пользователя с историей тренировок}';

    protected $description = 'Первичная настройка: миграции, справочники (каталог, шаблоны, разминка), опционально демо-данные';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        if ($this->option('demo')) {
            $this->call('app:demo');
        }

        $this->info('Готово.');

        return self::SUCCESS;
    }
}

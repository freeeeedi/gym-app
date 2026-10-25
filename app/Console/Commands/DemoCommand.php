<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    protected $signature = 'app:demo {--fresh : Удалить демо-пользователя со всеми его тренировками и создать заново}';

    protected $description = 'Создать демо-пользователя demo@example.com с историей за 6 недель (существующего не трогает без --fresh)';

    public function handle(): int
    {
        if (User::where('email', DemoSeeder::EMAIL)->exists() && ! $this->option('fresh')) {
            $this->info('Демо-пользователь уже есть, его данные не тронуты. Пересоздать с нуля: php artisan app:demo --fresh');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
        $this->info('Демо-пользователь: '.DemoSeeder::EMAIL.' / password');

        return self::SUCCESS;
    }
}

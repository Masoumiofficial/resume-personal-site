<?php

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Schedule;

// On shared hosting the scheduler runs from one DirectAdmin cron entry:
//   * * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
// Without cron nothing here runs; retention can then be applied manually with
//   php artisan model:prune --model="App\Models\ContactMessage"

// Contact-message retention (settings.message_retention_days; spam after 30 days).
Schedule::command('model:prune', ['--model' => [ContactMessage::class]])->daily()->at('03:30');

// Backups (D35): database daily, database + media weekly. Kept in
// storage/app/backups (config/backup.php); download copies off the server.
Schedule::command('app:backup')->daily()->at('03:00');
Schedule::command('app:backup', ['--media'])->weekly()->sundays()->at('03:10');

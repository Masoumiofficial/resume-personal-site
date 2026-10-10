<?php

return [
    'path' => storage_path('app/backups'),

    // How many backups of each kind to keep in storage/app/backups
    // (database backups run daily, media archives weekly — routes/console.php).
    // Backups on the same host are NOT a disaster-recovery copy: download them
    // (or use DirectAdmin's remote backup) regularly. See docs/phase-6.
    'keep' => (int) env('BACKUP_KEEP', 14),
    'keep_media' => (int) env('BACKUP_KEEP_MEDIA', 4),
];

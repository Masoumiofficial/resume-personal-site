<?php

// Scheduled tasks (backups, retention clean-up) are added in later phases.
// On shared hosting they run from one DirectAdmin cron entry:
//   * * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1

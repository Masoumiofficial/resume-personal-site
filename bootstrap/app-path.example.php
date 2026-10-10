<?php

// Only for a split layout where the project folder is NOT a sibling named
// "site-app" (D36). Copy to app-path.php in the WEB ROOT (next to index.php)
// and return the absolute path of the project folder (the one with artisan).
// It must be outside every web root. Requested over HTTP this file prints nothing.
return '/home/USERNAME/domains/example.com/site-app';

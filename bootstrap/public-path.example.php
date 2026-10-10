<?php

// Only for hosts where the document root cannot be pointed at public/ (D36).
// Copy to bootstrap/public-path.php (git-ignored) and set the absolute path of
// the folder that holds index.php, .htaccess, assets/ and media/.
return '/home/USERNAME/domains/example.com/public_html';

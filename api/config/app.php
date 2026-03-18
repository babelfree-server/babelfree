<?php
// Proxy to config outside webroot — credentials are not stored here
return require dirname(__DIR__, 3) . '/config/app.php';

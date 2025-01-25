<?php

use Symfony\Component\Dotenv\Dotenv;
use Waldhacker\Pseudify\Core\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

if (!file_exists(Kernel::USER_DATA_PATH.'/.env')) {
    throw new RuntimeException('The userdata directory does not contain an .env file. Please create it first.', 1737031939);
}

(new Dotenv())->loadEnv(path: Kernel::USER_DATA_PATH.'/.env', defaultEnv: 'prod');
$_SERVER['APP_RUNTIME_OPTIONS']['disable_dotenv'] = true;

$isInDevMode = ($_SERVER['APP_ENV'] ?? null) === 'dev';
if ($isInDevMode) {
    opcache_reset();
}

return fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

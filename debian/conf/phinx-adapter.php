<?php

/**
 * Multi AbraFlexi Setup - Phinx database adapter.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021 Vitex Software
 */

/**
 * Get configuration from constant or environment
 * 
 * @param string $constant
 * 
 * @return string
 */
function cfg($constant) {
    $cfg = null;
    if (!empty($constant) && defined($constant)) {
        $cfg = constant($constant);
    } elseif (isset($_ENV) && array_key_exists($constant, $_ENV)) {
        $cfg = $_ENV[$constant];
    } elseif (($env = getenv($constant)) && !empty($env)) {
        $cfg = getenv($constant);
    }
    return $cfg;
}

/**
 * Load Configuration values from json file $this->configFile and define UPPERCASE keys
 *
 * @param string  $configFile      Path to file with configuration
 *
 * @return array full configuration array
 */
function loadConfig($configFile, $defineConstants) {
    foreach (file($configFile) as $cfgRow) {
        if (strchr($cfgRow, '=')) {
            list($key, $value) = explode('=', $cfgRow);
            $configuration[$key] = trim($value, " \t\n\r\0\x0B'\"");
            define($key, $configuration[$key]);
        }
    }
}

if (file_exists('/etc/multi-abraflexi-setup/.env')) {
    loadConfig('/etc/multi-abraflexi-setup/.env', true);
}

$prefix = "/usr/lib/multi-abraflexi-setup/db/";

$sqlOptions = [];

if (strstr(cfg('DB_CONNECTION'), 'sqlite')) {
    $sqlOptions["database"] = "/var/lib/dbconfig-common/sqlite3/multi-abraflexi-setup/" . basename(cfg("DB_DATABASE"));
}
$engine = new \Ease\SQL\Engine(null, $sqlOptions);
$cfg = [
    'paths' => [
        'migrations' => [$prefix . 'migrations'],
        'seeds' => [$prefix . 'seeds']
    ],
    'environments' =>
    [
        'default_database' => 'development',
        'development' => [
            'adapter' => \Ease\Functions::cfg('DB_CONNECTION'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ],
        'default_database' => 'production',
        'production' => [
            'adapter' => \Ease\Functions::cfg('DB_CONNECTION'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ],
    ]
];

return $cfg;

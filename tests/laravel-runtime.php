<?php declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Phessage\HeadlessCommerce\Client;
use Phessage\HeadlessCommerce\Laravel\HeadlessCommerceServiceProvider;

$base = sys_get_temp_dir().'/headless-commerce-laravel-runtime';
if (!is_dir($base.'/config') && !mkdir($base.'/config', 0777, true) && !is_dir($base.'/config')) {
    throw new RuntimeException('Unable to create disposable Laravel base path');
}
$app = new Application($base);
$app->instance('config', new Repository([
    'headless-commerce' => [
        'base_url' => 'https://sandbox.test',
        'publishable_key' => 'pk_test_laravel',
        'max_retries' => 1,
    ],
]));
$provider = new HeadlessCommerceServiceProvider($app);
$provider->register();
$provider->boot();
$first = $app->make(Client::class);
$second = $app->make(Client::class);
if (!$first instanceof Client || $first !== $second) throw new RuntimeException('Laravel singleton binding failed');
$published = HeadlessCommerceServiceProvider::pathsToPublish(HeadlessCommerceServiceProvider::class, 'headless-commerce-config');
if (!in_array($app->configPath('headless-commerce.php'), $published, true)) throw new RuntimeException('Laravel publishable configuration path is missing');
echo 'Laravel '.Application::VERSION." runtime gate passed\n";

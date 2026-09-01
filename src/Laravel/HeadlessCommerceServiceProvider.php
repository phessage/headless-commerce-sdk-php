<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce\Laravel;
use Illuminate\Support\ServiceProvider;use Phessage\HeadlessCommerce\Client;
final class HeadlessCommerceServiceProvider extends ServiceProvider {
 public function register():void{$this->mergeConfigFrom(__DIR__.'/../../config/headless-commerce.php','headless-commerce');$this->app->singleton(Client::class,function(){ $store=(string)config('headless-commerce.store_id');return $store!==''?Client::forStore($store,(string)config('headless-commerce.bootstrap_url','https://api.1ecomm.com'),null,(int)config('headless-commerce.max_retries',2)):new Client((string)config('headless-commerce.base_url'),(string)config('headless-commerce.publishable_key'),null,(int)config('headless-commerce.max_retries',2));});}
 public function boot():void{$this->publishes([__DIR__.'/../../config/headless-commerce.php'=>config_path('headless-commerce.php')],'headless-commerce-config');}
}

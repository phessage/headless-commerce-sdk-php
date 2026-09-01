<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce\Laravel;
use Illuminate\Support\ServiceProvider;use Phessage\HeadlessCommerce\Client;
final class HeadlessCommerceServiceProvider extends ServiceProvider {
 public function register():void{$this->mergeConfigFrom(__DIR__.'/../../config/headless-commerce.php','headless-commerce');$this->app->singleton(Client::class,fn()=>new Client((string)config('headless-commerce.base_url'),(string)config('headless-commerce.publishable_key'),null,(int)config('headless-commerce.max_retries',2)));}
 public function boot():void{$this->publishes([__DIR__.'/../../config/headless-commerce.php'=>config_path('headless-commerce.php')],'headless-commerce-config');}
}

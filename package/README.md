
<div align="center">
<h1>Cluster Cache</h1>
Speed up your application using Cluster Cache 
</div>
<hr>
<br />

## Requirements

* Laravel >= 9.0
* [SHMOP](https://www.php.net/manual/en/ref.shmop.php)

### Optional Requirements
* [Igbinary](https://www.php.net/manual/en/book.igbinary.php) - it isn't mandatory but influences on the performance

## Installation
```bash
composer require sopamo/clustercache
```

## Setup
You need to add the `clustercache` driver into your `config/cache.php`:
```php
'clustercache' => [
    'driver' => 'clustercache',
]
```

You can publish the package configuration using the command listed below. Then, you can change the configuration.
```bash
php artisan vendor:publish --provider="Sopamo\ClusterCache\ClusterCacheServiceProvider" --tag="config"
```

## Examples of usage
Cluster Cache is a driver of Laravel's cache. You can read [the cache usage section](https://laravel.com/docs/9.x/cache#cache-usage) in Laravel's docs.

Putting data:
```php
Cache::store('clustercache')->put('foo', 'test');
```
Getting data:
```php
Cache::store('clustercache')->get('foo');
```
Deleting data:
```php
Cache::store('clustercache')->delete('foo');
```
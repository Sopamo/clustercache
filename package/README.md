
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

## SHMOP installation

### Ubuntu / Debian

On Ubuntu / Debian based distributions, you can install the extension via apt:

```bash
sudo apt-get update 
sudo apt-get install php-shmop
```

### Docker
You can use [Docker PHP Extension Installer](https://github.com/mlocati/docker-php-extension-installer) to install SHMOP extension in the image:
```bash 
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions shmop
```

## Installation
```bash
composer require sopamo/clustercache
```

## Setup
You need to add a new `clustercache` store to your `config/cache.php`:
```php
'stores' => [
  'clustercache' => [
      'driver' => 'clustercache',
  ],
  // Your existing stores stay here
],
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
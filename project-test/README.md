
<div align="center">
Improve your application using ClusterCache 
</div>
<hr>
<br />

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
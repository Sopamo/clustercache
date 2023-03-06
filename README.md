<div align="center">
<h1>Cluster Cache</h1>
Speed up your application using Cluster Cache 
</div>
<hr>
<br />

## Instalation and usage

Please open `package/README.md` to read details about the package installation and usage.

## Testing

If you want to run tests, follow the below instruction.

1. Clone the project 
2. Go into the project-test folder 
3. Run `cp .env.example .env `
4. Set a database user and a password in the .env file 
5. Run `docker run --rm -v "$(pwd)":/var/www/html -v "$(pwd)/../package":/var/www/package -w /opt composer bash -c "composer install" `
6. Run `vendor/bin/sail up -d`
7. Run `vendor/bin/sail shell`
8. Run `php artisan key:generate`
9. Run `php artisan migrate`
10. Run `php artisan test`
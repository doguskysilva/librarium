# Authentication operations

Librarium has one administrative account. Public registration and email
verification are disabled. Password reset remains available, but it requires a
configured mail transport.

## Initial administrator provisioning

Set these values as Dokploy secrets:

```dotenv
ADMIN_EMAIL=dogusky@protonmail.com
ADMIN_PASSWORD=<random password stored in a password manager>
```

After migrations have completed, run the provisioning command once:

```bash
php artisan app:provision-admin
```

The command is idempotent. It creates the administrator when absent and does
not change the password or other authentication data when the account already
exists. It never prints the password or its hash.

Do not put `ADMIN_PASSWORD` in the repository, Dockerfile, Docker build args,
image labels, logs, or shared documentation. Do not add the provisioning
command to the container entrypoint or run it on every deploy.

## Production session settings

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://librarium.dogusky.cloud
SESSION_SECURE_COOKIE=true
```

The application trusts the Dokploy reverse proxy so Laravel generates HTTPS
URLs correctly.

## Password reset

Password reset routes are enabled, but the application must have a working mail
transport before reset links can be delivered. Until then, use an authenticated
administrative procedure to change the password manually; never put a new
password in shell history or logs.

# Configuring your WebServer

If you are using a shared hosting server which uses apache and has rewrite rules enabled, then you can upload all the files either to your root public directory, or under a sub-folder. E.G If you upload the files to a ./billing/ directory, then you can access the site using [http://yourdomain.com/billing](http://yourdomain.com/billing)

### Configuring Apache[¶](/broken/pages/nkeLFp2QBAm22b8ysDJK)

To run SolidInvoice on apache, you need to create a custom virtual host.

```apacheconf
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /opt/solidinvoice/public
    <Directory /opt/solidinvoice/public>
        # enable the .htaccess rewrites
        AllowOverride All
        Order allow,deny
        Allow from All
    </Directory>

    ErrorLog /var/log/apache2/solidinvoice.error.log
    CustomLog /var/log/apache2/solidinvoice.access.log combined
</VirtualHost>
```

{% hint style="warning" %}
The above configurations might be different depending on the OS you are using on your server. For specific details on setting up Apache on your OS, please view the respective documentation for your operating system.
{% endhint %}

### Configuring Nginx[¶](/broken/pages/nkeLFp2QBAm22b8ysDJK)

To run SolidInvoice on Nginx, you need to create a custom virtual host.

```nginx
server {
    server_name yourdomain.com www.yourdomain.com;
    root /opt/solidinvoice/public;

    location / {
        # try to serve file directly, fallback to app.php
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS off;
    }

    error_log /var/log/nginx/project_error.log;
    access_log /var/log/nginx/project_access.log;
}
```

{% hint style="warning" %}
The above configurations might be different depending on the OS you are using on your server. For specific details on setting up Nginx on your OS, please view the respective documentation for your operating system.
{% endhint %}

<br>

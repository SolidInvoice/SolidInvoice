.. toctree::

==========================
Configuring your WebServer
==========================

If you are using a shared hosting server which uses apache and has rewrite rules enabled, then you can upload all the files either to your root public directory, or under a sub-folder.
E.G If you upload the files to a ./billing/ directory, then you can access the site using http://yourdomain.com/billing

.. tip::

    You can also use the :ref:`built-in server <built-in-server-label>`, although it is not recommended for production use.

.. _apache-server-label:

------------------
Configuring Apache
------------------

To run SolidInvoice on apache, you need to create a custom virtual host.

.. code-block:: apache

    <VirtualHost *:80>
        ServerName yourdomain.com
        ServerAlias www.yourdomain.com

        DocumentRoot /opt/solidinvoice/web
        <Directory /opt/solidinvoice/web>
            # enable the .htaccess rewrites
            AllowOverride All
            Order allow,deny
            Allow from All
        </Directory>

        ErrorLog /var/log/apache2/solidinvoice.error.log
        CustomLog /var/log/apache2/solidinvoice.access.log combined
    </VirtualHost>

.. warning::

    The above configurations might be different depending on the OS you are using on your server.
    For specific details on setting up Apache on your OS, please view the respective documentation for your operating system.

.. _nginx-server-label:

-----------------
Configuring NginX
-----------------

To run SolidInvoice on NginX, you need to create a custom virtual host.

.. code-block:: nginx

    server {
        server_name yourdomain.com www.yourdomain.com;
        root /opt/solidinvoice/web;

        location / {
            # try to serve file directly, fallback to app.php
            try_files $uri /app.php$is_args$args;
        }

        location ~ ^/(app|app_dev|config)\.php(/|$) {
            fastcgi_pass unix:/var/run/php5-fpm.sock;
            fastcgi_split_path_info ^(.+\.php)(/.*)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param HTTPS off;
        }

        error_log /var/log/nginx/project_error.log;
        access_log /var/log/nginx/project_access.log;
    }

.. warning::

    The above configurations might be different depending on the OS you are using on your server.
    For specific details on setting up NginX on your OS, please view the respective documentation for your operating system.

.. _built-in-server-label:

-------------------
PHP built-in server
-------------------

To start the built-in web server, run the following command:

.. code-block:: bash

    $ php app/console server:run

This will start the local web server, which is accessible at http://localhost:8080

.. danger::

    The built-in web server is not meant to be used for production.
    If you want to run SolidInvoice in a production environment, rather use :ref:`apache <apache-server-label>` or :ref:`nginx <nginx-server-label>`

For more info on the built-in server,
or options you can use when using the built-in server see http://php.net/manual/en/features.commandline.webserver.php or http://symfony.com/doc/current/cookbook/web_server/built_in.html
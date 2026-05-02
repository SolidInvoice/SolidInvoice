# Downloading

There are different ways of obtaining SolidInvoice.

The recommended option is [#installing-distribution-package](downloading.md#installing-distribution-package "mention"), but you can choose any option that works best for your environment.

### Installing distribution package[¶](/broken/pages/UypBAziU4JNdex6SGAEs)

To install a packaged version of SolidInvoice, download the latest version from [https://github.com/SolidInvoice/SolidInvoice/releases/latest](https://github.com/SolidInvoice/SolidInvoice/releases/latest).

Extract the archive to a folder that is accessible from your web-server (view the [Configuring your WebServer](/broken/pages/nkeLFp2QBAm22b8ysDJK) document for more information)

{% hint style="info" %}
If you are on a shared hosting environment, or only have ftp access to a server, then you can use ftp to upload all the files to the server
{% endhint %}

Once you have extracted the package contents, then you can configure your [web server](configuring-your-webserver.md).

### Docker Container

SolidInvoice has an official Docker container located at [https://hub.docker.com/r/solidinvoice/solidinvoice](https://hub.docker.com/r/solidinvoice/solidinvoice).

If you do not have docker installed yet, follow the instructions at [https://www.docker.com/get-started](https://www.docker.com/get-started/).

#### Run the docker image

```bash
docker run -d -p 8080:80 solidinvoice/solidinvoice
```

Then you can open [http://127.0.0.1:8080](http://127.0.0.1:8080) to [complete the installation.](system-installation.md)

{% hint style="info" %}
You can change the `8080` with any port that you want SolidInvoice to run on.
{% endhint %}



### Installing from source[¶](/broken/pages/UypBAziU4JNdex6SGAEs)

In order to install SolidInvoice from source, you will need [composer](http://getcomposer.org/), a package and dependency manager for PHP. If you do not yet have composer installed, follow the guide on [Installing Composer](/broken/pages/UypBAziU4JNdex6SGAEs).

#### Installing Composer[¶](/broken/pages/UypBAziU4JNdex6SGAEs)

If you do not yet have composer installed on your system, you can use the following command to get composer

```bash
$ curl -sS http://getcomposer.org/installer | php
```

Once composer is downloaded, you can use it from the command line using the following command

#### Using Composer[¶](/broken/pages/UypBAziU4JNdex6SGAEs)

To install SolidInvoice using [composer](http://getcomposer.org/), run the following commands

```bash
$ php composer.phar create-project solidinvoice/solidinvoice
```

This will download SolidInvoice into a directory called _solidinvoice_, and will also install all the dependencies. If you encounter any issues while trying to install, please submit a [bug report](https://github.com/SolidInvoice/SolidInvoice/issues).

The last step is to install the Node packages and dump all the web assets

```sh
$ yarn install
$ yarn build
```

#### Using Git[¶](/broken/pages/UypBAziU4JNdex6SGAEs)

If you want to install SolidInvoice using git, you can clone the repository using the following command:

```bash
$ git clone https://github.com/SolidInvoice/SolidInvoice.git
```

You will then need [composer](http://getcomposer.org/) to install the required dependencies. To install Composer, please refer to the [installing-composer](/broken/pages/UypBAziU4JNdex6SGAEs) section.

Go into the repository directory and install all the dependencies

```bash
$ cd SolidInvoice
$ php composer.phar install
```

If you encounter any issues while trying to install, please submit a [bug report](https://github.com/SolidInvoice/SolidInvoice/issues).

The last step is to install the Node packages and dump all the web assets

```sh
$ yarn install
$ yarn build
```

<br>

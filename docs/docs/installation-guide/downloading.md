---
title: Downloading
description: Download SolidInvoice via distribution package, Docker, or source.
sidebar_position: 2
---

# Downloading

There are different ways of obtaining SolidInvoice.

The recommended option is the [distribution package](#installing-the-distribution-package), but you can choose any option that works best for your environment.

## Installing the distribution package

To install a packaged version of SolidInvoice, download the latest release from [github.com/SolidInvoice/SolidInvoice/releases/latest](https://github.com/SolidInvoice/SolidInvoice/releases/latest).

Extract the archive to a folder that is accessible from your web server (see [Configuring your WebServer](./configuring-your-webserver.md) for more information).

:::info
If you're on a shared hosting environment, or only have FTP access to a server, you can use FTP to upload all the files to the server.
:::

Once you have extracted the package contents, [configure your web server](./configuring-your-webserver.md).

## Docker container

SolidInvoice has an official Docker container at [hub.docker.com/r/solidinvoice/solidinvoice](https://hub.docker.com/r/solidinvoice/solidinvoice).

If you don't have Docker installed yet, follow the instructions at [docker.com/get-started](https://www.docker.com/get-started/).

### Run the Docker image

```bash
docker run -d -p 8080:80 solidinvoice/solidinvoice
```

Then open [http://127.0.0.1:8080](http://127.0.0.1:8080) to [complete the installation](./system-installation.md).

:::tip
You can change `8080` to any port that you want SolidInvoice to run on.
:::

## Installing from source

To install SolidInvoice from source, you'll need [Composer](https://getcomposer.org/), a package and dependency manager for PHP. If you don't yet have Composer installed, follow the [Installing Composer](#installing-composer) section below.

### Installing Composer

If you don't yet have Composer installed on your system, you can use the following command to download it:

```bash
curl -sS https://getcomposer.org/installer | php
```

Once Composer is downloaded, you can use it from the command line.

### Using Composer

To install SolidInvoice using [Composer](https://getcomposer.org/), run the following command:

```bash
php composer.phar create-project solidinvoice/solidinvoice
```

This will download SolidInvoice into a directory called `solidinvoice` and install all the dependencies. If you encounter any issues while trying to install, please submit a [bug report](https://github.com/SolidInvoice/SolidInvoice/issues).

The last step is to install the Node packages and build the web assets:

```bash
yarn install
yarn build
```

### Using Git

If you want to install SolidInvoice using Git, clone the repository:

```bash
git clone https://github.com/SolidInvoice/SolidInvoice.git
```

You'll then need [Composer](https://getcomposer.org/) to install the required dependencies. To install Composer, refer to the [Installing Composer](#installing-composer) section.

Go into the repository directory and install all the dependencies:

```bash
cd SolidInvoice
php composer.phar install
```

If you encounter any issues, please submit a [bug report](https://github.com/SolidInvoice/SolidInvoice/issues).

The last step is to install the Node packages and build the web assets:

```bash
yarn install
yarn build
```

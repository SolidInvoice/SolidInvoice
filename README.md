<p align="center">
    <a href="https://github.com/SolidInvoice/SolidInvoice" target="_blank">
        <img src="https://avatars.githubusercontent.com/u/25333451?s=200&v=4" height="100px">
    </a>
    <h1 align="center">SolidInvoice</h1>
    <br>
</p>

SolidInvoice is a simple open source invoicing application aimed to help small businesses and freelancers manage their day-to-day billing.
It's a feature rich elegant online application to ensure that you can always get paid on time.

## Features

* Clients and Contacts management
* Create and manage Quotes
* Create and manage Invoices
* Accept payments online
* Tax and discount handling
* RESTful API
* Receive Notifications either via text message, email or through HipChat
* More to come

## Requirements

PHP >= 8.1 is required. Use the latest version of PHP for best experience.

## Installation

### Docker

Docker makes it really easy to get started as quickly as possible in running SolidInvoice.

The docker image is available at [https://hub.docker.com/r/solidinvoice/solidinvoice/](https://hub.docker.com/r/solidinvoice/solidinvoice/) with instructions on how to get started.

### Archived Package

Download the latest release from [https://github.com/SolidInvoice/SolidInvoice/releases](https://github.com/SolidInvoice/SolidInvoice/releases) in either `zip` or `tar.gz` format,
and extract the contents of the archive under your webserver directory.

### For developers

To install from source, you first need to clone the repository, then you need [composer][1] in order to install all the dependencies.

To clone the repository, issue the following command. Remember to clone the repository to the path you want, that is accessible from your webserver.

```bash
git clone https://github.com/SolidInvoice/SolidInvoice.git
```

Then go into the repository directory

```bash
cd SolidInvoice
```

Now you need to get composer

```bash
curl -s http://getcomposer.org/installer | php
```

When composer is finished downloading, you can install the required dependencies:

```bash
php composer.phar install
```


The final step is to install the Node packages, and compile all the assets

```bash
yarn install
yarn dev
```

Now you should have a fully working copy of SolidInvoice.

If you want to run SolidInvoice in production mode, some additional steps is required:

```bash
yarn build
```

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md)

## License

SolidInvoice is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

Please see the [LICENSE](LICENSE) file for the full license.

## Donate

Thank you for supporting the project!

[![Paypal](https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EQLK2P3VBW2LC)

[1]: http://getcomposer.org

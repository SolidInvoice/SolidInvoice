CSBill
======

[![Build Status](https://travis-ci.org/CSBill/CSBill.png?branch=master)](https://travis-ci.org/CSBill/CSBill)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/CSBill/CSBill/badges/quality-score.png?s=fdd7a5f5080807e95a317b9c0db07e8d5ce8cb63)](https://scrutinizer-ci.com/g/CSBill/CSBill/)
[![Dependencies](https://www.versioneye.com/user/projects/539ec1c183add760b0000002/badge.svg)](https://www.versioneye.com)

Open-Source General Billing Manager

CSBill is an open-source application that allows you to manage clients and contacts and send invoices and quotes.

Requirements
------------

CSBill is built on [Symfony2][1] which is build for PHP 5.3.3 and up, but CSBill only support PHP 5.4.0 and up.

*Note:* The latest version of PHP is always recommended

## Installation

### Docker

Docker makes it really easy to get started as quickly as possible in running CSBill.

The docker image is available at https://hub.docker.com/r/csbill/csbill/ with instructions on how to get started.

### Archived Package

Download the latest release from https://github.com/CSBill/CSBill/releases in either `zip` or `tar.gz` format,
and extract the contents of the archive under your webserver directory. 

### For developers

To install from source, you first need to clone the repository, then you need [composer][2] in order to install all the dependencies.

To clone the repository, issue the following command. Remember to clone the repository to the path you want, that is accessible from your webserver.

```bash
$ git clone https://github.com/CSBill/CSBill.git
```

Then go into the repository directory

```bash
$ cd CSBill
```

Now you need to get composer

```bash
$ curl -s http://getcomposer.org/installer | php
```

When composer is finished downloading, you can install the optional dependencies:

```bash
$ php composer.phar install
```

After all the depencies has been installed, the last step is to install all the web assets

```bash
$ php app/console assets:install --symlink web
```

Now you should have a fully working copy of CSBill.

#### Lesscss

The stylesheets are built using [lesscss][3], and uses LessPHP to compile the stylesheets to plain CSS.

Features
--------

Some of the basic features included in CSBill is:

* Clients and Contacts management
* Create and manage Quotes
* Create and manage Invoices
* Accept payments online
* Tax and discount handling
* RESTful API
* Receive Notifications either via text message, email or through HipChat
* More to come


Contributing
------------

See [CONTRIBUTING](CONTRIBUTING.md)

License
------------

CSBill is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

Please see the [LICENSE](LICENSE) file for the full license.

Demo
------------

[http://demo.csbill.org](http://demo.csbill.org)


[1]: http://symfony.com
[2]: http://getcomposer.org
[3]: http://lesscss.org

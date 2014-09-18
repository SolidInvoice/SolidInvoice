CSBill
======

[![Build Status](https://travis-ci.org/CSBill/CSBill.png?branch=master)](https://travis-ci.org/CSBill/CSBill)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/CSBill/CSBill/badges/quality-score.png?s=fdd7a5f5080807e95a317b9c0db07e8d5ce8cb63)](https://scrutinizer-ci.com/g/CSBill/CSBill/)
[![Dependencies](https://www.versioneye.com/user/projects/539ec1c183add760b0000002/badge.svg)](https://www.versioneye.com)
[![Stories in Ready](https://badge.waffle.io/csbill/csbill.png?label=ready)](https://waffle.io/csbill/csbill)

Open-Source General Billing Manager

CSBill is an open-source application that allows you to manage clients and contacts and send invoices and quotes.

Requirements
------------

CSBill is built on [Symfony2][1] which is build for PHP 5.3.3 and up, but CSBill only support PHP 5.4.0 and up.

*Note:* The latest version of PHP is always recommended

Installation
------------

To install the from source, you first need to clone the repository, then you need [composer][2] in order to install all the dependencies.

To clone the repository, issue the following command. Remember to clone the repository to the path you want, that is accessible from Apache.

    git clone https://github.com/CSBill/CSBill.git

Then go into the repository directory

    cd CSBill

Now you need to get composer

    curl -s http://getcomposer.org/installer | php

When composer is finished downloading, you can install the optional dependencies:

    php composer.phar install
    
After all the depencies has been installed, the last step is to install all the web assets

    php app/console assets:install --symlink web

Now you have a fully working copy of CSBill, which you can use to modify or dig around in the code.

**Note:** This is not the recommended way to install and use CSBill. This is only for developers who wish to look through the code, submit patches, customise the code etc. or for anybody that wish to poke through the source code.

#### Lesscss

The stylesheets is built using [lesscss][3], and uses LessPHP to compile the stylesheets to plain CSS.

Features
--------

Some of the basic features included in CSBill is:

* Clients & Contacts management
* Send Quotes
* Send Invoices
* More to come

*Note:* This list is only the planned features so far. Some (or all) of the mentioned features may not be complete or even started. As the features grow, the list will be updated to include the actual features available.


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

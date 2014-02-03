CSBill
======

[![Build Status](https://travis-ci.org/CSBill/CSBill.png?branch=master)](https://travis-ci.org/CSBill/CSBill)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/CSBill/CSBill/badges/quality-score.png?s=fdd7a5f5080807e95a317b9c0db07e8d5ce8cb63)](https://scrutinizer-ci.com/g/CSBill/CSBill/)
[![Stories in Ready](https://badge.waffle.io/csbill/csbill.png?label=ready)](https://waffle.io/csbill/csbill)  

Open-Source General Billing Manager

CSBill is an open-source application that allows you to manage clients and contacts and send invoices and quotes.

Requirements
------------

CSBill is built on [Symfony2][1] which is only supported on PHP 5.3.3 and up.

Be warned that PHP versions before 5.3.8 are known to be buggy and might not
work for you:

 * before PHP 5.3.4, if you get "Notice: Trying to get property of
   non-object", you've hit a known PHP bug (see
   https://bugs.php.net/bug.php?id=52083 and
   https://bugs.php.net/bug.php?id=50027);

 * before PHP 5.3.8, if you get an error involving annotations, you've hit a
   known PHP bug (see https://bugs.php.net/bug.php?id=55156).

*Note:* The latest version of PHP is always recommended

Installation
------------

To install the from source, you first need to clone the repository, then you need [composer][2] in order to install all the dependencies.

To clone the repository, issue the following command. Remember to clone the repository to the path you want, that is accessible from Apache.

    git clone https://github.com/CustomScripts/CSBill.git

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

The stylesheets is built with [lesscss][3], so you need nodejs and less installed in your system to be able to parse the less files to CSS.
(This is only for the development version. Once a stable version is released, it will included the pre-compiled css file)

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

Please see the [LICENSE](LICENSE.md) file for the full license.


[1]: http://symfony.com
[2]: http://getcomposer.org
[3]: http://lesscss.org

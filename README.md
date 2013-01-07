CSBill
======

[![Build Status](https://secure.travis-ci.org/pierredup/CSBill.png?branch=master)](http://travis-ci.org/pierredup/CSBill)

Open-Source General Billing Manager

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

Features
--------

Some of the basic features included in CSBill is:

* Client Management
* Project Management
* Send Quotes
* Send Invoices

*Note:* This list is only the planned features so far. Some (or all) of the mentioned features may not be complete or even started. As the features grow, the list will be updated to include the actual features available.


Contributing
------------

If you wish to contribute to CSBill, please fork it, make your changes, and submit a pull request.

All pull requests **must** pass the unit tests, unless specified. If a pull request does not pass existing unit tests, then new unit tests must acompany the pull request, with a description as to why the unit tests fail.

All pull requests must conform to the standards of coding currently in the application. Pull requests that do not follow standards, won't be denied, but we will ask you to change the code before we accept the pull request.

If you encounter any bug or inconsitency, please submit a bug report, so we can fix it as quickly as possible.

[1]: http://symfony.com
[2]: http://getcomposer.org
[3]: http://lesscss.org

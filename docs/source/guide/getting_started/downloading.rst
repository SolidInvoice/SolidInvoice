.. toctree::

===========
Downloading
===========

There are different ways of obtaining SolidInvoice.

If you plan to contribute to SolidInvoice (E.G write patches, you should follow the :ref:`installing-from-source` section.
Otherwise follow the :ref:`installing-distribution-package` section.

.. _installing-distribution-package:

-------------------------------
Installing distribution package
-------------------------------

To install a packaged version of SolidInvoice, download the latest version from https://github.com/SolidInvoice/SolidInvoice/releases.

Extract the archive to a folder that is accessible from your web-server (view the :doc:`webserver` document for more information)

.. tip::

    If you are on a shared hosting environment, or only have ftp access to a server, then you can use ftp to upload all the files to the server

.. _installing-from-source:

----------------------
Installing from source
----------------------

In order to install SolidInvoice from source, you will need `composer`_, a package and dependency manager for PHP.
If you do not yet have composer installed, follow the guide on :ref:`installing-composer`.

.. _installing-composer:

Installing Composer
-------------------

If you do not yet have composer installed on your system, you can follow the instructions at  the `composer download page`_ to get Composer.

Once composer is downloaded, you can use it from the command line using the following command

.. code-block:: bash

    $ php composer.phar


Using Composer
--------------

To install SolidInvoice using `composer`_, run the following commands

.. code-block:: bash

    $ php composer.phar create-project solidinvoice/solidinvoice

This will download SolidInvoice into a directory called `solidinvoice`, and will also install all the dependencies.
If you encounter any issues while trying to install, please submit a `bug report`_.

The last step is to install the Node packages and dump all the web assets

.. code-block:: bash

    $ npm install
    $ ./node_modules/.bin/gulp

Using Git
---------

If you want to install SolidInvoice using git, you can clone the repository using the following command:

.. code-block:: bash

    $ git clone https://github.com/SolidInvoice/SolidInvoice.git

You will then need `composer`_ to  install the required dependencies. To install Composer, please refer to the `installing-composer`_ section.

Go into the repository directory and install all the dependencies

.. code-block:: bash

    $ cd SolidInvoice
    $ composer install

If you encounter any issues while trying to install, please submit a `bug report`_.

The last step is to install the Node packages and dump all the web assets

.. code-block:: bash

    $ yarn install
    $ yarn build

.. _composer: https://getcomposer.org/
.. _composer download page: https://getcomposer.org/download/
.. _bug report: https://github.com/SolidInvoice/SolidInvoice/issues

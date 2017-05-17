sudo apt-get update
sudo apt-get install apache2 libapache2-mod-fastcgi

# set up php under apache
if [[ $TRAVIS_PHP_VERSION == 'hhvm' ]]; then
    sudo a2enmod rewrite actions fastcgi alias
    sudo cp -f travis/travis-ci-apache-hhvm.conf /etc/apache2/sites-available/default
    sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
    sudo service apache2 restart
    hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000 -vServer.FixPathInfo=true
else
    sudo cp travis/php-fpm.conf ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/www.conf
    sudo a2enmod rewrite actions fastcgi alias
    echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
    ~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm
    sudo cp -f travis/travis-ci-apache.conf /etc/apache2/sites-available/default
    sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
    sudo service apache2 restart
fi



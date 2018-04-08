Tor crawler
===========

Installation
------------

```
# Linux Packages
sudo apt install apache2 libapache2-mod-php7.0 mysql-server php7.0 php7.0-curl php7.0-intl php7.0-mbstring php7.0-mysql php7.0-tidy php7.0-xml php7.0-zip nodejs npm git acl tor  

# Mod rewrite
sudo a2enmod rewrite && sudo service apache2 restart  

# Composer
bash composer.sh
composer.phar install 

# Node packages
npm install  

# Database
php bin/console doctrine:database:create
php bin/console doctrine:schema:create

# ACL
sudo setfacl -R -m u:www-data:rwX var
sudo setfacl -dR -m u:www-data:rwX var

# Timezone
sudo dpkg-reconfigure tzdata

# App environment
echo "export SYMFONY_ENV=prod" >> ~/.bash_profile
```

Commands
--------

```
# Parse onions from Daniel's listing
php bin/console app:parse daniel

# Parse onions in database
php bin/console app:parse

# Parse onions in database and discover new ones
php bin/console app:parse --discover

# Parse URL
php bin/console app:parse http://xxxxxxxxxxxxxxxx.onion

# Parse with Node
nodejs multi-getter.js
nodejs multi-getter.js -s20 # 20 threads, default: 10
nodejs multi-getter.js -t30 # 30s timeout, default: 60
nodejs multi-getter.js -o url # Order by url/created/unchecked, default: created
nodejs multi-getter.js -l # Loop
nodejs multi-getter.js -l -o unchecked --first-only # My favorite

# Update words
php bin/console app:update:words
```

[CRON](https://crontab.guru/)
----

```
# Update words
0 1 * * * php /var/www/tor-crawler/bin/console app:update:words

# Parse onions
0 8,20 * * * nodejs /var/www/tor-crawler/multi-getter.js

```
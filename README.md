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
php composer.phar install 

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

# Parse URL/hash
php bin/console app:parse xxxxxxxxxxxxxxxx
php bin/console app:parse http://xxxxxxxxxxxxxxxx.onion/foobar

# Parse found onion URLs
php bin/console app:parse xxxxxxxxxxxxxxxx --depth X # Will follow and parse urls X times, default: 0
php bin/console app:parse xxxxxxxxxxxxxxxx --depth 3 --mode deep # Go 'deep', 'wide' or 'random', default: wide

# Parse with Node
nodejs multi-getter.js
nodejs multi-getter.js -s20 # 20 threads, default: 10
nodejs multi-getter.js -t30 # 30s timeout, default: 60
nodejs multi-getter.js -o url # Order by url/created/unchecked, default: created
nodejs multi-getter.js -l # Loop
nodejs multi-getter.js -l -o unchecked --first-only # My favorite
```

[CRON](https://crontab.guru/)
----

```
# Daily routine
0 0 * * * php /var/www/tor-crawler/bin/console app:daily

# Parse onions
0 12 * * * nodejs /var/www/tor-crawler/multi-getter.js

```
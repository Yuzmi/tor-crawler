Tor crawler
===========

Installation
------------

```
sudo apt install apache2 libapache2-mod-php7.0 mysql-server php7.0 php7.0-curl php7.0-intl php7.0-mbstring php7.0-mysql php7.0-tidy php7.0-xml php7.0-zip nodejs npm git acl tor  
sudo a2enmod rewrite && sudo service apache2 restart  
bash composer.sh
composer.phar install  
npm install  
```

Database
```
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

ACL permissions  
```shell
sudo setfacl -R -m u:www-data:rwX var
sudo setfacl -dR -m u:www-data:rwX var
```

Command
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
```
nodejs multi-getter.js
nodejs multi-getter.js -s20 # 20 threads, default: 10
nodejs multi-getter.js -t30 # 30s timeout, default: 60
```

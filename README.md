Tor crawler
===========

Installation
------------

Dependencies  
```shell
composer install
npm install
```

Database
```
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

ACL permissions  
```shell
sudo apt install acl
sudo setfacl -R -m u:www-data:rwX var
sudo setfacl -dR -m u:www-data:rwX var
```

Command
--------

```
# Parse onions in database
php bin/console app:parse

# Parse onions in database and discover new ones
php bin/console app:parse --discover

# Parse URL
php bin/console app:parse http://xxxxxxxxxxxxxxxx.onion

# Parse with Node
```
node multi-getter.js
node multi-getter.js -s20 # 20 threads, default: 10
node multi-getter.js -t30 # 30s timeout, default: 60
```

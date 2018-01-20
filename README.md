Tor crawler
===========

Installation
------------

```
git clone ssh://yuzmi@konasse:2222/volume1/git/tor-crawler
```

Dependencies  
```shell
composer install
npm install
```

ACL permissions  
```shell
sudo apt install acl
HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
```

Command
--------

```
# Parse onions in database and discover new ones
php bin/console app:discover
```
sudo bash  
apt -y install apache2  
apt -y install php libapache2-mod-php php-mbstring php-zip php-gd php-json php-curl  
apt -y install nano  
  
mkdir /var/www/html/logs  
nano /etc/apache2/sites-enabled/000-default.conf
```
<VirtualHost *:80>

	ServerAdmin webmaster@localhost
	DocumentRoot /var/www/html/httpdocs
	
	<Directory "/var/www/html/httpdocs">
		AllowOverride All
	</Directory>

	ErrorLog /var/www/html/logs/apache_error.log
	CustomLog /var/www/html/logs/apache_access.log "%h %t \"%r\" %>s %b"

</VirtualHost>
```
/etc/init.d/apache2 restart

## Help  
[Apache2 Log Config](https://httpd.apache.org/docs/2.4/mod/mod_log_config.html)  
  
## Openstreetmap via Caching Proxy Server  
sudo bash  
a2enmod cache  
a2enmod cache_disk  
a2enmod headers  
a2enmod expires  
a2enmod proxy  
a2enmod proxy_http  
a2enmod ssl  
```
Listen 8080
<VirtualHost *:8080>
   
# enable caching for all requests; cache content on local disk
CacheEnable disk /
CacheRoot /var/cache/apache2/mod_cache_disk/

# common caching directives
CacheQuickHandler off
CacheLock on
CacheLockPath /tmp/mod_cache-lock
CacheLockMaxAge 5
CacheHeader On

# cache control
CacheIgnoreNoLastMod On
CacheIgnoreCacheControl On
CacheStoreNoStore On

# unset headers from upstream server
Header unset Expires
Header unset Cache-Control
Header unset Pragma
   
# set expiration headers for static content
ExpiresActive On
ExpiresByType text/html "access plus 1 years"
ExpiresByType text/plain "access plus 1 years"
ExpiresByType image/png "access plus 1 years"
ExpiresByType application/javascript "access plus 1 years"

ProxyTimeout 600

# reverse proxy requests to upstream server
ProxyRequests On # used for forward proxying
SSLProxyEngine On # required if proxying to https
 
ProxyPass /a https://a.tile.openstreetmap.de/
ProxyPassReverse /a https://a.tile.openstreetmap.de/

ProxyPass /b https://b.tile.openstreetmap.de/
ProxyPassReverse /b https://b.tile.openstreetmap.de/
 
ProxyPass /c https://c.tile.openstreetmap.de/
ProxyPassReverse /c https://c.tile.openstreetmap.de/

</VirtualHost>
```

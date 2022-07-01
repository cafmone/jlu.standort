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
	LogFormat "%h %t \"%r\" %>s %b" custom
	ErrorLog "|/usr/bin/rotatelogs /var/www/html/logs/error-%Y-%m-%d.log 86400"
	CustomLog "|/usr/bin/rotatelogs /var/www/html/logs/access-%Y-%m-%d.log 86400" custom
</VirtualHost>
```
/etc/init.d/apache2 restart
  
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
  
mkdir /var/www/html/cache  
chmod 0777 /var/www/html/cache  
nano /etc/apache2/sites-enabled/proxy.conf
```
Listen 8080
<VirtualHost *:8080>
   
# enable caching for all requests; cache content on local disk
CacheEnable disk /
CacheRoot /var/www/html/cache/

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
/etc/init.d/apache2 restart  
nano /var/www/html/httpdocs/[jlu.map.php](https://github.com/cafmone/jlu.standort/blob/main/httpdocs/jlu.map.php)
```
$controller->tileserverurl = 'http://localhost:8080/{a-c}/{z}/{x}/{y}.png';
```

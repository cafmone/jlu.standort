sudo bash  
apt -y install apache2  
apt -y install php libapache2-mod-php php-mbstring php-zip php-gd php-json php-curl  
apt -y install mc  
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
	CustomLog /var/www/html/logs/apache_access.log combined

</VirtualHost>
```

# How to install it on Debian6/7

1. sudo apt-get install apache2 apache2-doc apache2-utils php5 libapache2-mod-php5  
2. Clone this repo under /var/www/phpapis - phpapis is the main directory of this repo
   git clone git://github.com/bellorinrobert/postgresphptestapi.git
3. Copy & Paste ./configs/apache2 to /etc/apache2/
4. sudo service apache2 restart

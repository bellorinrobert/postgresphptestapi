mkdir -p /var/www/meshapi
tar -zxvf /root/meshapi.tar.gz -C /var/www/meshapi/
WWW_PATH=/var/www
REPO_NAME=meshapi
MESH_API_DIR=$WWW_PATH/$REPO_NAME
PGCONF=/opt/livigent/crt/var/livigent/postgres/etc/9.0/livigent_cfg/postgresql.conf
PGHBACONF=/opt/livigent/crt/var/livigent/postgres/etc/9.0/livigent_cfg/pg_hba.conf
apt-get -y install git apache2 apache2-utils php5 libapache2-mod-php5 php5-pgsql --force-yes
sed '/ServerName 127.0.0.1/d' /etc/apache2/apache2.conf
echo 'ServerName 127.0.0.1' >> /etc/apache2/apache2.conf
cp $MESH_API_DIR/configs/apache2/sites-enabled/000-default /etc/apache2/sites-enabled/
cp $MESH_API_DIR/configs/apache2/httpd.conf /etc/apache2/
cp $MESH_API_DIR/configs/apache2/ports.conf /etc/apache2/ 
sed -i '/listen_addresses =/c\listen_addresses = \x27*\x27' $PGCONF
sed -i '/host    all             all             0.0.0.0\/0           md5/d' $PGHBACONF
sed -i '/host    all             all             127.0.0.1\/32            md5/a host    all             all             0.0.0.0\/0           md5' $PGHBACONF
chroot /opt/livigent/crt/ service postgresql restart 
service apache2 restart

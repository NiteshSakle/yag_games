Your art gallery - New site
===========================
This contains the source code for the Your art gallery new web site. This is written with ZF2 framework.

Installation Automatically
---------------------
cd /var/www
git clone git@git.riktamtech.com:vishwa/yag_games.git
cd yag_games
sudo bash ./bin/deploy.sh develop staging

Installation Manually
---------------------
cd /var/www
git clone git@git.riktamtech.com:vishwa/yag_games.git
cd yag_games
curl -s http://getcomposer.org/installer | php
php composer.phar install
Run build script: python bin/build.py
Modify the config/*.local.php files.
Giver permissions to data folder: chmod -R 775 data/logs


Configuration
-------------
Modify *.local.php files accordingly

Note:
You should also setup old your art gallery site & Enable ZF2Session in that.


#!/bin/sh
cd /var/www/html
php spark migrate && apache2ctl -D FOREGROUND

#!/bin/bash

  /usr/bin/php /var/www/html/wb_fbs_orders.php >> /var/www/html/wb_fbs_orders.php.log 2>&1
  /usr/bin/php /var/www/html/wb_fbs_orders_status.php >> /var/www/html/wb_fbs_orders_status.php.log 2>&1
  /usr/bin/php /var/www/html/wb_fbs_supplies.php >> /var/www/html/wb_fbs_supplies.php.log 2>&1


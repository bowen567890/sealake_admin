#!/bin/bash
step=10 #间隔的秒数
for (( i = 0; i < 60; i=(i+step) )); do
    /usr/bin/php /www/wwwroot/dogbee_admin/artisan command:SyncPowerEvent
    sleep $step
done
exit 0

#!/bin/bash

# Start supervisord
exec /usr/bin/supervisord -n -c /etc/supervisord.conf

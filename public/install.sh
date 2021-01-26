#!/bin/bash

echo "Installing PHP packages."
(cd /usr/share/public; composer install -q --no-scripts --no-interaction --optimize-autoloader --no-progress)

echo "Installing JavaScript packages"
(cd /usr/share/public; yarn install -s --prefer-offline --frozen-lockfile --non-interactive --cache-folder /var/www/.cache/yarn --emoji=true --ignore-optional)

echo "Current environment is set to '${APP_ENV}!'";

php artisan config:cache
sudo /etc/my_init.d/99_init_sonar.sh
php artisan config:cache

# Run tmux
chmod +x /usr/share/public/yarn.sh
tmux new-session \; \
  send-keys 'bash /usr/share/public/hello.sh' C-m \; \
  split-window -v \; \
  send-keys '/usr/share/public/yarn.sh' C-m \; \
  select-pane -t 0 \;

sudo /etc/my_init.d/99_init_laravel.sh && cd /var/www/html && setuser www-data php artisan sonar:settingskey
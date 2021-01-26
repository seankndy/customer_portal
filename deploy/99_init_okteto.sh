#!/bin/bash
set -e

init_sonar() {
    echo "Initializing Sonar Customer Portal, OKTETO STYLE!"
    /usr/sbin/usermod -a -G tty www-data

    if [[ "$(stat -c '%U' /usr/share/public)" != "www-data" ]]; then
      echo "Resetting /usr/share/public permissions."
      chown -R www-data:www-data /usr/share/public
    fi

    echo "Creating any missing directories."
    setuser www-data mkdir -p \
        /usr/share/public/bootstrap/cache \
        /usr/share/public/storage/framework/cache/data \
        /usr/share/public/storage/framework/sessions \
        /usr/share/public/storage/framework/views \
        /usr/share/public/storage/app/public \
        /usr/share/public/storage/app/purifier \
        /usr/share/public/storage/logs \
        /usr/share/public/storage/app/uploaded_files

    echo "Resetting cache permissions."
    chown -R www-data:www-data /var/www/

    chmod g+rws .

    if [[ ! -e /usr/share/public/.env ]]; then
      setuser www-data touch /usr/share/public/.env
      setuser www-data php /usr/share/public/artisan key:generate
    fi

    if [[ ! -e /usr/share/public/storage/oauth-private.key ]]; then
      setuser www-data php /usr/share/public/artisan passport:keys
    fi

    rm -rf /usr/share/public/storage/logs/swoole_http.pid

    if [[ ! -e /usr/share/public/bootstrap/cache/config.php ]]; then
      echo "Caching configuration and routes"
      setuser www-data php /usr/share/public/artisan config:cache
      setuser www-data php /usr/share/public/artisan route:cache
    fi

    # wait for postgres and elastic to be ready
    echo "Waiting for ElasticSearch and PostgreSQL to be available."
    dockerize -wait tcp://database:5432 -timeout 30s
    wait_for_elasticsearch

    if [[ ! -e /usr/share/public/SONAR_INITIALIZED ]] || [[ "$1" == "-f" ]]; then
        echo "First run, initializing Sonar."
        setuser www-data php /usr/share/public/artisan migrate:fresh --database=main
        setuser www-data php /usr/share/public/artisan sonar:dev:reset
        setuser www-data touch /usr/share/public/SONAR_INITIALIZED
        setuser www-data php /usr/share/public/artisan config:cache

        echo "Sonar initialization complete!"
    elif [ -z "$CI" ]; then
        # We're already initialized - rerun migrations and reindex
        setuser www-data php /usr/share/public/artisan sonar:migrate || {
            echo "Failed to migrate database."
            exit 0
        }

        setuser www-data php /usr/share/public/artisan sonar:upgradetasks:run || {
            echo "Failed to run upgrade tasks."
            exit 0
        }

        setuser www-data php /usr/share/public/artisan sonar:elastic:migrate || {
            echo "Elastic migrate failed, running a full reindex. This may take a while..."

            setuser www-data php /usr/share/public/artisan sonar:elastic:migrate --fresh || {
                echo "Failed to do a clean elastic migrate."
                exit 0
            }

            setuser www-data php /usr/share/public/artisan sonar:elastic:index || {
                echo "Elastic index failed. Your instance may not work."
            }
        }
    fi

    # Enable OPCache validate timestamps
    sed -e 's/opcache.validate_timestamps=0/opcache.validate_timestamps=1/g' \
        /etc/php/7.4/cli/conf.d/99-opcache-settings.ini > /etc/php/7.4/cli/conf.d/99-opcache-settings.ini.tmp \
        && mv /etc/php/7.4/cli/conf.d/99-opcache-settings.ini.tmp /etc/php/7.4/cli/conf.d/99-opcache-settings.ini
    echo "Enabled opcache.validate_timestamps"

    echo "Restarting supervised processes"
    supervisord -c /etc/supervisord.conf
    supervisorctl restart all
}

wait_for_elasticsearch() {
    until $(curl --output /dev/null --silent --head --fail "elasticsearch:9200"); do
        printf '.'
        sleep 1
    done

    # First wait for ES to start...
    response=$(curl elasticsearch:9200)

    until [ "$response" = "200" ]; do
        response=$(curl --write-out %{http_code} --silent --output /dev/null "elasticsearch:9200")
        >&2 echo "Elastic Search is unavailable - sleeping"
        sleep 1
    done

    # next wait for ES status to turn to Green
    health="$(curl -fsSL "elasticsearch:9200/_cat/health?h=status")"
    health="$(echo "$health" | sed -r 's/^[[:space:]]+|[[:space:]]+$//g')" # trim whitespace (otherwise we'll have "green ")

    until [ "$health" = 'green' ] || [ "$health" = 'yellow' ]; do
        health="$(curl -fsSL "elasticsearch:9200/_cat/health?h=status")"
        health="$(echo "$health" | sed -r 's/^[[:space:]]+|[[:space:]]+$//g')" # trim whitespace (otherwise we'll have "green ")
        >&2 echo "Elastic Search is unavailable - sleeping"
        sleep 1
    done
}

init_sonar

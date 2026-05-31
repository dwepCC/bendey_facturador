#!/bin/sh
set -e

cd /var/www/html

echo "Esperando base de datos..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
    echo "  MySQL aún no disponible, reintentando..."
    sleep 2
done

echo "Ejecutando migraciones..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Preparando cache de producción..."

mkdir -p var/cache var/log

chown -R www-data:www-data var

rm -rf var/cache/*

su -s /bin/sh www-data -c "php bin/console cache:clear --env=prod --no-warmup"
su -s /bin/sh www-data -c "php bin/console cache:warmup --env=prod"

chown -R www-data:www-data var
chmod -R 775 var

if [ "${RUN_ADMIN_SEED:-0}" = "1" ]; then
    echo "Ejecutando seed de admin..."
    php bin/console app:admin:seed --no-interaction || true
fi

if [ "${RUN_EMPRESAS_IMPORT:-0}" = "1" ] && [ -f data/empresas.json ]; then
    echo "Importando empresas desde JSON..."
    php bin/console app:empresas:import-from-json --no-interaction || true
fi

exec "$@"
#!/usr/bin/env bash
# Ejecutar en el VPS (primera vez o manual): bash scripts/vps-bootstrap.sh

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/bendey/facturador_sunat}"
REPO_URL="${REPO_URL:-}"

if [ -z "$REPO_URL" ]; then
  echo "Uso: REPO_URL=https://github.com/TU_USUARIO/facturador_sunat.git bash scripts/vps-bootstrap.sh"
  exit 1
fi

sudo mkdir -p "$(dirname "$APP_DIR")"
sudo chown -R "$USER:$USER" "$(dirname "$APP_DIR")"

if [ ! -d "$APP_DIR/.git" ]; then
  git clone "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"

if [ ! -f .env.prod ]; then
  cp .env.prod.example .env.prod
  echo "Edita $APP_DIR/.env.prod antes de continuar."
  exit 1
fi

docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build

echo ""
echo "Listo. API: http://$(curl -s ifconfig.me 2>/dev/null || echo TU_IP):8000"
echo "phpMyAdmin (solo localhost): ssh -L 8080:127.0.0.1:8080 user@vps"
echo "Seed admin: docker compose -f docker-compose.prod.yml exec app php bin/console app:admin:seed"

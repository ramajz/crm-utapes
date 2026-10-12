#!/usr/bin/env bash
# Deploy CRM-Utapes ke CE17A.
# Build image → migrate database saat startup → sync Vite assets ke host
# (nginx MiniBio membacanya dari /var/www/crm-utapes/public) → reload CRM nginx.
# Aman untuk data: tidak menghapus volume/database.
set -euo pipefail

cd "$(dirname "$0")"
COMPOSE="docker compose -f docker-compose.ce17.yml"
CONTAINER="crm-utapes-app"
HOST_PUBLIC="/var/www/crm-utapes/public"
TMP_PUBLIC="/var/www/crm-utapes/public.deploy-tmp"
NGINX_CONTAINER="minibio-nginx"

printf '\n[1/5] Build CRM image...\n'
$COMPOSE build

printf '\n[2/5] Start/recreate CRM (migrations run at startup)...\n'
$COMPOSE up -d --force-recreate

printf '\n[3/5] Wait for PHP-FPM readiness...\n'
ready=0
for i in $(seq 1 40); do
  if docker logs "$CONTAINER" 2>&1 | grep -q 'ready to handle connections'; then ready=1; break; fi
  sleep 2
done
if [[ "$ready" != 1 ]]; then
  echo 'ERROR: PHP-FPM belum ready. Log terakhir:'
  docker logs "$CONTAINER" --tail 40
  exit 1
fi

printf '\n[4/5] Sync public assets container → host...\n'
# Copy ke temp dulu agar tidak merusak aset lama bila copy gagal.
sudo rm -rf "$TMP_PUBLIC"
sudo mkdir -p "$TMP_PUBLIC"
sudo docker cp "$CONTAINER:/var/www/html/public/." "$TMP_PUBLIC/"
sudo rm -rf "$HOST_PUBLIC"
sudo mv "$TMP_PUBLIC" "$HOST_PUBLIC"
sudo chown -R rmjz:rmjz "$HOST_PUBLIC"

printf '\n[5/5] Reload nginx + verify...\n'
docker exec "$NGINX_CONTAINER" nginx -t
docker exec "$NGINX_CONTAINER" nginx -s reload
sleep 2
css=$(find "$HOST_PUBLIC/build/assets" -maxdepth 1 -name 'app-*.css' -printf '%f\n' | head -1)
if [[ -z "$css" ]]; then echo 'ERROR: CSS build asset tidak ditemukan'; exit 1; fi
printf 'Synced CSS: %s\n' "$css"
curl -fsS --max-time 15 "https://crm.rmjz.my.id/build/assets/$css" -o /dev/null
printf 'Live CSS: HTTP 200 ✅\n'
curl -fsS --max-time 15 'https://crm.rmjz.my.id/login' -o /dev/null
printf 'CRM login: HTTP 200 ✅\n'

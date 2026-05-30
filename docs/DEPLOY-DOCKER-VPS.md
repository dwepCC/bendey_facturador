# Despliegue Docker en VPS

Guía para desplegar `facturador_sunat` en un VPS con **Docker**, **MySQL**, **Redis**, **phpMyAdmin** y deploy automático desde **GitHub Actions**.

## Arquitectura

| Servicio     | Puerto (VPS)        | Función                          |
|-------------|---------------------|----------------------------------|
| `app`       | 8000 → 80           | API Symfony + dashboard          |
| `worker`    | (interno)           | Cola fiscal Redis                |
| `mysql`     | (interno)           | Base de datos                    |
| `redis`     | (interno)           | Colas async                      |
| `phpmyadmin`| 127.0.0.1:8080      | Admin BD (solo vía SSH túnel)    |

## 1. Preparar el VPS (una sola vez)

### Requisitos

- Ubuntu 22.04 / 24.04 (recomendado)
- 2 GB RAM mínimo (4 GB recomendado con worker + MySQL)
- Dominio apuntando al VPS (opcional pero recomendado para HTTPS)

### Instalar Docker

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y ca-certificates curl git ufw

curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
# Cierra sesión SSH y vuelve a entrar para aplicar el grupo docker
```

### Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
# NO abras 8080 (phpMyAdmin) ni 3306 al público
sudo ufw enable
```

### Clonar el proyecto

```bash
sudo mkdir -p /opt/bendey
sudo chown $USER:$USER /opt/bendey
git clone https://github.com/TU_USUARIO/facturador_sunat.git /opt/bendey/facturador_sunat
cd /opt/bendey/facturador_sunat
cp .env.prod.example .env.prod
nano .env.prod   # configura secretos, dominio, SUNAT, etc.
```

### Primer deploy manual

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build

# Crear admin (solo primera vez)
docker compose -f docker-compose.prod.yml exec app php bin/console app:admin:seed

# (Opcional) Importar empresas
docker compose -f docker-compose.prod.yml exec app php bin/console app:empresas:import-from-json
```

Verifica: `http://TU_IP:8000/`

---

## 2. Configurar GitHub

### Repositorio

El workflow está en `.github/workflows/deploy-vps.yml` y se ejecuta al push a `main` o `master`.

### Secrets (Settings → Secrets and variables → Actions)

| Secret                 | Ejemplo / descripción                          |
|------------------------|------------------------------------------------|
| `VPS_HOST`             | IP o dominio del VPS (`203.0.113.10`)          |
| `VPS_USER`             | Usuario SSH (`deploy` o `ubuntu`)              |
| `VPS_SSH_PRIVATE_KEY`  | Clave privada SSH (contenido completo)         |
| `VPS_PORT`             | `22` (opcional)                                |
| `VPS_APP_DIR`          | `/opt/bendey/facturador_sunat` (opcional)      |

### Clave SSH para deploy

En tu PC:

```bash
ssh-keygen -t ed25519 -C "github-deploy-facturador" -f ~/.ssh/facturador_deploy
```

En el VPS (`~/.ssh/authorized_keys` del usuario deploy):

```
contenido-de-facturador_deploy.pub
```

En GitHub → Secret `VPS_SSH_PRIVATE_KEY`:

```
contenido-de-facturador_deploy   (sin passphrase, o usa ssh-agent)
```

### Permisos del usuario deploy en VPS

```bash
sudo usermod -aG docker deploy
# El usuario debe poder hacer git pull en /opt/bendey/facturador_sunat
sudo chown -R deploy:deploy /opt/bendey/facturador_sunat
```

---

## 3. phpMyAdmin (acceso seguro)

Por defecto phpMyAdmin escucha **solo en localhost** del VPS (`127.0.0.1:8080`).

Desde tu PC:

```bash
ssh -L 8080:127.0.0.1:8080 deploy@TU_VPS
```

Abre: http://localhost:8080

Credenciales: las de `MYSQL_USER` / `MYSQL_PASSWORD` en `.env.prod` (o root con `MYSQL_ROOT_PASSWORD`).

---

## 4. HTTPS con Caddy (recomendado)

Instala Caddy en el VPS y crea `/etc/caddy/Caddyfile`:

```caddy
facturador.tudominio.com {
    reverse_proxy 127.0.0.1:8000
}
```

```bash
sudo systemctl reload caddy
```

Actualiza en `.env.prod`:

- `FISCAL_STORAGE_PUBLIC_URL=https://facturador.tudominio.com/fiscal-files`
- `TRUSTED_PROXIES=127.0.0.1,REMOTE_ADDR`

---

## 5. Comandos útiles en producción

```bash
cd /opt/bendey/facturador_sunat

# Ver logs
docker compose -f docker-compose.prod.yml logs -f app worker

# Migraciones (también corren al iniciar app)
docker compose -f docker-compose.prod.yml exec app php bin/console doctrine:migrations:migrate --no-interaction

# Reiniciar worker
docker compose -f docker-compose.prod.yml restart worker

# Actualizar manualmente (sin GitHub)
git pull && docker compose -f docker-compose.prod.yml --env-file .env.prod up -d --build
```

---

## 6. Certificados y datos persistentes

Monta certificados SUNAT en el volumen `app_data`:

```bash
docker compose -f docker-compose.prod.yml exec app ls -la data/
# Copia PEMs al volumen o súbelos vía API /api/v1/empresas
```

Volúmenes Docker persistentes: `mysql_data`, `redis_data`, `app_data`, `fiscal_storage`.

---

## 7. Checklist antes de producción

- [ ] `.env.prod` configurado (nunca en Git)
- [ ] `APP_SECRET` y `CLIENT_TOKEN` únicos y fuertes
- [ ] `RUN_ADMIN_SEED=0` después del primer deploy
- [ ] Contraseña admin cambiada tras login
- [ ] phpMyAdmin no expuesto públicamente
- [ ] HTTPS activo
- [ ] Worker fiscal corriendo (`docker compose ps`)
- [ ] Backups de volumen `mysql_data`

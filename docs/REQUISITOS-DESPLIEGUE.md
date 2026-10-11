# Requisitos de despliegue del facturador (checklist)

Tres piezas deben estar bien configuradas o la emision parece funcionar pero SUNAT no se refleja en el ERP.
Verificar en cada entorno (local, staging, VPS) antes de dar por bueno un despliegue.

## 1. Worker de colas (`app:fiscal:worker`)

- Sin worker, los comprobantes encolados se quedan en estado "Enviando" para siempre: la API solo
  encola, quien firma y envia a SUNAT es el worker (colas Redis `fiscal:emit`, `email`, `webhook_sync`, `status_poll`).
- Comando: `php bin/console app:fiscal:worker` (proceso de larga duracion; usar supervisor/systemd o el
  servicio `worker` de `docker-compose.prod.yml`). Requiere `REDIS_URL`.
- Verificar: `docker compose -f docker-compose.prod.yml ps worker` debe estar `Up`; el dashboard fiscal
  muestra el latido (heartbeat) del worker. Tras cambiar codigo o `.env`, reiniciar el worker (mantiene el
  contenedor en memoria).
- Si un fallo de Doctrine cierra el EntityManager, el worker lo recrea solo (`ManagerRegistry::resetManager`)
  y el siguiente job continua.

## 2. Clave del webhook hacia el ERP: `ERP_WEBHOOK_KEY` == `INTERNAL_API_KEY`

- El facturador notifica el resultado de SUNAT con `POST ERP_WEBHOOK_URL` (`/api/internal/fiscal/status` del
  backend) y cabecera `X-Internal-Key: ERP_WEBHOOK_KEY` + firma HMAC con la misma clave.
- El backend valida contra su variable `INTERNAL_API_KEY`. **Deben ser exactamente el mismo valor.** Si
  difieren, el backend responde 401, nunca se entera del resultado y la UI queda en "Enviando"/"Anulando...".
- `ERP_WEBHOOK_URL` debe ser alcanzable desde el contenedor del facturador (no `127.0.0.1` si el backend
  corre en otro host/contenedor). El webhook fallido se reintenta (hasta 8 veces con backoff).
- Nota: `.env.prod` no debe dejar `ERP_WEBHOOK_KEY` vacio.

## 3. wkhtmltopdf con `--enable-local-file-access`

- Los PDF se generan con wkhtmltopdf 0.12.6 desde HTML que referencia archivos locales (logo, QR). Esa
  version bloquea el acceso local por defecto: sin `--enable-local-file-access` el PDF falla (HTTP 500 /
  "exit 1") y los envios entran en reintento.
- Configurar `WKHTMLTOPDF_PATH` (`/usr/bin/wkhtmltopdf` en Docker; ruta completa `.exe` en Windows) y
  asegurar que el binario recibe la opcion: o bien agregarla a las opciones de `PdfReport` en
  `config/services.yaml` (`no-outline`, `print-media-type`, `enable-local-file-access`; solo si la version
  instalada es >= 0.12.6, las anteriores rechazan la opcion), o bien apuntar `WKHTMLTOPDF_PATH` a un wrapper
  que la anada.
- En Windows 0.12.6 ademas falla si esta definida la variable de entorno `NoDefaultCurrentDirectoryInExePath`:
  quitarla en el wrapper.
- Verificar: pedir el PDF de un comprobante aceptado y comprobar que devuelve bytes `%PDF`.

## Comprobacion rapida

1. Worker arriba y heartbeat reciente.
2. `ERP_WEBHOOK_KEY` del facturador == `INTERNAL_API_KEY` del backend (comparar sin imprimirlas en logs).
3. Emitir una boleta de prueba en SUNAT beta: pasa a "Aceptado" en el ERP y el PDF se descarga.

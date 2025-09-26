# Samuel — API SIAL + Sync (Laravel)
**Parte del ecosistema _Samuel + Lucy_**

Samuel es un servicio **API REST** en **Laravel** que:
- **Sincroniza** datos desde SIAL y los **persiste** en BD (historial completo + consolidado).
- **Expone endpoints propios** para listar historial por etapa y consultar el consolidado de alumnos.
- Usa **tokens Bearer (Sanctum)** para autenticación.
- Se integra con **Lucy** (panel en Filament) y **comparte la misma base de datos**.

---

## Tabla de contenidos
- [Arquitectura](#arquitectura)
- [Tecnologías](#tecnologías)
- [Esquema de datos](#esquema-de-datos)
- [Endpoints](#endpoints)
- [Autenticación](#autenticación)
- [Instalación y arranque (Samuel + Lucy)](#instalación-y-arranque-samuel--lucy)
  - [A. Crear Samuel (sin Composer local)](#a-crear-samuel-sin-composer-local)
  - [B. Agregar Samuel al docker-compose de Lucy](#b-agregar-samuel-al-docker-compose-de-lucy)
  - [C. Configuración de `.env`](#c-configuración-de-env)
  - [D. Migraciones](#d-migraciones)
  - [E. Generación de token](#e-generación-de-token)
  - [F. Verificación rápida](#f-verificación-rápida)
- [Uso desde Lucy](#uso-desde-lucy)
- [Resolución de problemas](#resolución-de-problemas)
- [Seguridad y buenas prácticas](#seguridad-y-buenas-prácticas)
- [Licencia](#licencia)

---

## Arquitectura

```
┌────────┐     HTTP (Bearer)     ┌──────────┐
│  Lucy  │  ───────────────────▶  │ Samuel   │
│(Filament)                      │(Laravel) │
└───┬────┘                       └────┬─────┘
    │   Lee/Escribe BD (compartida)   │
    └──────────────┬──────────────────┘
                   ▼
           MySQL 8 (DB compartida)
```

- **Samuel**: consulta SIAL, guarda **historial** (`sial_acciones`) y **consolidado** (`alumnos`), y expone API.
- **Lucy**: consume el endpoint de sincronización y **lee la misma BD** para listar por etapa y ver alumnos.

---

## Tecnologías

- **Laravel** (Samuel y Lucy)
- **Laravel Sanctum** para tokens Bearer
- **MySQL 8**
- **Docker / Docker Compose**
- **Apache + PHP 8.2** (imagen app)
- **Filament v3** (en Lucy)

---

## Esquema de datos

**1) `sial_acciones`** (historial, apend-only)
- `id`
- `tipodoc` (string)
- `nrodoc` (string)
- `estado` (enum textual: `preinscripto|aspirante|ingresante|alumno`)
- `fecha_inscri` (datetime) — fecha/hora del evento en SIAL
- `fecha_accion` (datetime) — cuándo se registró en nuestra BD
- `apellido`, `nombre`
- `email_personal`, `email_institucional`
- `raw` (json) — payload completo del upstream
- `timestamps`, índices por (`tipodoc`,`nrodoc`) y (`estado`,`fecha_inscri`)

**2) `alumnos`** (consolidado, 1 fila por persona)
- `id`
- `tipodoc`, `nrodoc` (**unique** compuesto)
- `apellido`, `nombre`
- `email_personal`, `email_institucional`
- `timestamps`

> El **último estado** de un alumno se obtiene consultando `sial_acciones` ordenado por `fecha_accion` desc.

---

## Endpoints

> Prefijo común: `/api/v1` — **requieren Bearer token** (Sanctum), salvo `GET /api/ping`.

- `GET /api/ping` → salud pública (`{"pong": true}`)
- `GET /api/v1/alumnos` → listado consolidado (paginado; filtros `q`, `per_page`, `sort`, `order`)
- (Opcional, si están habilitados)
  - `GET /api/v1/sial/{tipo}` → historial por etapa (`preinscripto|aspirante|ingresante|alumno`)
  - `POST /api/v1/sial/sync` → dispara sincronización contra SIAL (`tipo`, `desde`, `hasta`)
  - `GET /api/v1/sial/{tipo}/stats` → series por fecha
  - `GET /api/v1/sial/{tipo}/export?format=csv|json` → export

**Ejemplo (desde host):**
```bash
# Ping (sin token)
curl http://localhost:8086/api/ping

# Alumnos (con token)
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  http://localhost:8086/api/v1/alumnos
```

---

## Autenticación

- **Header**: `Authorization: Bearer <TOKEN>`
- Tokens generados con **Sanctum** en el modelo `User` (`HasApiTokens`).
- Se recomiendan **abilities** por endpoint (ej.: `read:sial`, `export:sial`, `sync:sial`).

---

## Instalación y arranque (Samuel + Lucy)

### A. Crear Samuel (sin Composer local)

Desde el directorio padre de ambos proyectos:

```bash
cd ~/work/Lucy-Laravel-Moodle

# Crear Samuel con Composer dockerizado
docker run --rm -it -v "$PWD":/app -w /app composer:2 create-project laravel/laravel samuel
docker run --rm -it -v "$PWD/samuel":/app -w /app composer:2 require laravel/sanctum
```

### B. Agregar Samuel al docker-compose de Lucy

En `lucy/docker-compose.yml`, agregar el servicio:

```yaml
services:
  # ... db y app de lucy ya existen

  samuel:
    build:
      context: .
      dockerfile: Dockerfile
    restart: unless-stopped
    depends_on: [db]
    environment:
      - DB_HOST=db
      - DB_DATABASE=lucy         # MISMA BD que usa Lucy
      - DB_USERNAME=lucy
      - DB_PASSWORD=lucypass
      - TZ=America/Argentina/Cordoba
    ports:
      - "8086:80"                # expone Samuel en el host
    volumes:
      - ../samuel:/app           # monta el código de Samuel
```

Levantar:
```bash
cd lucy
docker compose up -d samuel
```

### C. Configuración de `.env`

**Samuel (`samuel/.env`):**
```env
APP_NAME=Samuel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://samuel

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=lucy
DB_USERNAME=lucy
DB_PASSWORD=lucypass

# Opcional (si ya vas a sincronizar con SIAL):
SIAL_BASE_URL=https://sisinfo.unrc.edu.ar/webservice/sial/V1/04
SIAL_USER=...
SIAL_PASS=...
```

**Lucy (`lucy/.env`):**
```env
# Samuel accesible por hostname de servicio dentro del compose
SAMUEL_BASE_URL=http://samuel/api/v1
SAMUEL_TOKEN=<PEGAR_TOKEN_GENERADO>
```

> **Laravel 11**: asegurate que `bootstrap/app.php` incluya `api: __DIR__.'/../routes/api.php'`.

### D. Migraciones

- **Sanctum**: la tabla `personal_access_tokens` puede venir ya de Lucy.
  Si **ya existe**, **no** ejecutes la migración de Sanctum en Samuel (o borra `database/migrations/*create_personal_access_tokens_table.php` en Samuel).
- Migraciones de negocio (`alumnos`, `sial_acciones`):

```bash
# (si ya tenés los archivos de migración)
docker compose exec samuel php artisan migrate
```

### E. Generación de token

Asegurar el modelo `User`:

```php
// app/Models/User.php
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
  use HasApiTokens, HasFactory, Notifiable;
}
```

Generar token (queda impreso y lo podés guardar):
```bash
TOKEN=$(docker compose exec samuel bash -lc 'php -r '\''require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $u=\App\Models\User::firstOrCreate(["email"=>"sial-service@samuel.local"],["name"=>"Service SIAL","password"=>bcrypt(\Illuminate\Support\Str::random(32))]); echo $u->createToken("sial-api",["read:sial","export:sial","sync:sial"])->plainTextToken;'\''')
echo "$TOKEN"
```

Pegar en `lucy/.env` como `SAMUEL_TOKEN`.

### F. Verificación rápida

```bash
# Rutas cargadas
docker compose exec samuel php artisan route:list

# Salud
curl http://localhost:8086/api/ping

# Endpoint protegido
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  http://localhost:8086/api/v1/alumnos
```

---

## Uso desde Lucy

- En **Lucy/Filament**, el dashboard ofrece **botón “Sincronizar”** por etapa y listados filtrados.
- Lucy **consume** `POST /api/v1/sial/sync` de Samuel con `Authorization: Bearer <SAMUEL_TOKEN>`.
- Lucy **lee la misma BD** para listar `sial_acciones` por estado y la tabla `alumnos` consolidada.
- Variables en `lucy/.env`:
  ```env
  SAMUEL_BASE_URL=http://samuel/api/v1
  SAMUEL_TOKEN=<TOKEN>
  ```

---

## Resolución de problemas

- **404: “route could not be found”**
  Verifica `bootstrap/app.php` (debe cargar `routes/api.php`).
  Corre `php artisan optimize:clear` y `php artisan route:list`.

- **401 Unauthorized**
  Faltó el header `Authorization: Bearer <TOKEN>` o el token no es válido.

- **“Call to undefined method User::createToken()”**
  Falta `HasApiTokens` en `app/Models/User.php` o Sanctum no está instalado:
  ```bash
  composer require laravel/sanctum
  ```
  y luego `config:clear` / `cache:clear`.

- **“Table personal_access_tokens already exists”**
  Ya la creó Lucy. En Samuel **borra** la migración de Sanctum y no la vuelvas a correr.

- **Docker warning `version` en override**
  Quitar la clave `version:` de `docker-compose.override.yml`.

---

## Seguridad y buenas prácticas

- Usa **tokens de servicio** con **abilities** acotadas (`read:sial`, `sync:sial`, etc.).
- No publiques tokens ni credenciales SIAL en repositorios.
- Limita puertos expuestos en producción (idealmente detrás de un proxy/API gateway).
- Registra auditoría con `fecha_accion` y conserva `raw` (útil para trazabilidad).

---

## Licencia

Apache-2.0/GPL-3.0

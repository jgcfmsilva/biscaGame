# DAD Project – Main Commands

## Requirements

- Docker and Docker Compose

---

## Quick start (all services)

Start (prod build):
```bash
./start-services.sh
```

Start with frontend hot reload:

```bash
./start-services.sh --frontend-dev
```

Stop containers (matches the mode you started):

```bash
./start-services.sh --stop
```
(uses `docker compose stop`, containers are stopped but not removed)

If the script is not executable after cloning, run:

```bash
chmod +x start-services.sh
```

This setup uses Docker only (no local Composer or Node.js required).
On the first run, the script installs PHP dependencies inside the API container automatically.

---

## 1) API (Laravel Sail)

### Start containers

```bash
cd api
docker compose up -d --build
```

### Migrations and seed (inside container)

```bash
cd api
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test php artisan db:seed
```

### Update PHP dependencies (inside container)

```bash
cd api
docker compose exec laravel.test composer update
```

### Queue worker logs

```bash
cd api
docker compose logs -f queue-worker
```

---

## 2) Frontend (Vue + Vite)

### Docker mode (production build, no hot reload)
(Uses the same network as Sail: `api_sail`)

```bash
cd frontend
docker compose up -d --build
```

The application will be available at:  
http://localhost:5173

---

### Docker dev mode (hot reload enabled)

```bash
cd frontend
docker compose -f docker-compose.dev.yml up -d --build
```

The dev server will be available at:  
http://localhost:5173

### New Dependencies

The snapshot feature requires `html-to-image`:
```bash
cd frontend
npm install html-to-image
```

## 3) WebSocket (Node)

### Docker mode
(Uses the same network as Sail: `api_sail`)

```bash
cd websocket
docker compose up -d --build
```

This builds the image and installs dependencies inside the container (no local `npm install` needed).

WebSocket server available at:  
http://localhost:3000

---

## Ports (where everything runs)

- API: http://localhost (port set by `APP_PORT` in `api/.env`, default 80)
- Frontend (Docker prod build): http://localhost:5173
- Frontend (Dev server): http://localhost:5173
- WebSocket: http://localhost:3000
- Mailpit: http://localhost:8025
- PostgreSQL: localhost (port set by `FORWARD_DB_PORT` in `api/.env`, default 5433)
- Redis: localhost:6379

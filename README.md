# Smart Adama — AI Learning Platform

A luxury, production-ready digital learning platform for the Smart Adama book. Built as a strictly decoupled, API-first system: a **Laravel REST API** backend and an independent **Vue 3 SPA** frontend.

## Architecture

```
smart-adama/
├── backend/    # Laravel REST API (PHP 8.3, PostgreSQL + pgvector, Redis)
├── frontend/   # Vue 3 SPA (Vite, Pinia, vue-router, Tailwind CSS)
├── docs/       # requirements.md, design.md, tasks.md
└── docker-compose.yml
```

The two apps communicate **exclusively over versioned JSON REST endpoints** (`/api/v1/`) plus SSE for chat streaming. There are no Blade views, no Inertia, no server-side rendering — the backend is pure API.

## Prerequisites

- Docker & Docker Compose v2
- (For local dev outside Docker) PHP 8.3+, Composer, Node 20+, pnpm/npm

## Quick Start (Docker — recommended)

```bash
# 1. Clone the repo
git clone <repo-url> smart-adama
cd smart-adama

# 2. Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 3. Edit backend/.env — set ANTHROPIC_API_KEY, VOYAGE_API_KEY, etc.

# 4. Start everything
docker compose up --build

# 5. Run migrations + seed badges (first run only)
docker compose exec backend php artisan migrate --seed
```

Services:
- **Frontend SPA** → http://localhost:5173
- **Backend API** → http://localhost:8000/api/v1
- **Health check** → http://localhost:8000/api/v1/health

## Running the backend independently

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve          # API on :8000
php artisan queue:work     # Queue worker (separate terminal)
```

## Running the frontend independently

```bash
cd frontend
npm install
cp .env.example .env      # set VITE_API_BASE_URL=http://localhost:8000
npm run dev               # Dev server on :5173
```

## Running tests

```bash
# Backend (Pest)
cd backend && php artisan test

# Frontend (Vitest)
cd frontend && npm run test

# Frontend e2e (Playwright)
cd frontend && npm run test:e2e
```

## Environment Variables

See `backend/.env.example` and `frontend/.env.example` for the full list of required variables. **Never commit real secrets** — all sensitive values must be provided via environment variables only.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend API | Laravel 11, PHP 8.3, Pest |
| Database | PostgreSQL 16 + pgvector |
| Cache / Queue | Redis |
| LLM | Anthropic Claude API |
| Embeddings | Voyage AI |
| Object Storage | S3-compatible (AWS S3 / MinIO locally) |
| Frontend | Vue 3, Vite, TypeScript, Pinia, vue-router |
| Styling | Tailwind CSS (Palette 7 design tokens) |
| Testing (FE) | Vitest + Playwright |

## Manuscript Ingestion

Once the Smart Adama manuscript is received, see `docs/manuscript-ingestion.md` for the step-by-step admin flow (upload → chapter structuring → publish & ingest → verify embedding status).

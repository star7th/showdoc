co# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ShowDoc is a documentation sharing platform for IT teams. It provides tools for creating and managing API documentation, data dictionaries, and technical specifications with support for Markdown editing, version history, and permission management.

## Tech Stack

- **Backend**: PHP 7.4+ with Slim 4 framework + Illuminate/Database ORM
- **Frontend**: Vue 3 + TypeScript + Vite + Ant Design Vue
- **Database**: SQLite (file-based at `/Sqlite/showdoc.db.php`, no external DB setup needed)
- **Build**: Docker support with multi-stage builds

## Architecture

### Backend (Slim 4 Micro-framework)

Entry point: `/server/index.php`

- Uses PSR-7 (HTTP messages), PSR-11 (dependency injection), PSR-15 (middleware)
- Supports two routing modes:
  - Query parameter: `?s=Api/Item/info` (legacy, open-source version)
  - Path-based: `/server/api/item/info` (modern)
- Base path: `/server`

**Key directories**:
- `/server/app/Api/Controller/` - API endpoints (40+ controllers)
- `/server/app/Common/` - Bootstrap, DI container, helpers, database, cache
- `/server/app/Model/` - Eloquent models for database entities
- `/server/app/Runtime/Logs/` - Error logs

**Bootstrap flow**:
1. `/server/index.php` - Initializes Slim app, loads `.env`, sets up DI container
2. `/server/app/Common/bootstrap.php` - Initializes Database and Cache
3. `/server/app/Common/container.php` - Registers services in DI container

### Frontend (Vue 3 + Vite)

Entry point: `/web_src/src/main.ts`

- Vite dev server runs on `localhost:5173` (configurable)
- Built files output to `/web/` directory
- Uses Pinia for state management
- Ant Design Vue for UI components
- i18n for multi-language support (Chinese/English)
- Theme system (light/dark mode via `data-theme` attribute)

**Key directories**:
- `/web_src/src/components/` - Vue components
- `/web_src/src/views/` - Page components
- `/web_src/src/router/` - Vue Router configuration
- `/web_src/src/store/` - Pinia stores
- `/web_src/src/models/` - TypeScript interfaces/types
- `/web_src/src/utils/` - Utility functions
- `/web_src/src/i18n/` - Language packs

## Development Environment

**Important**: This project runs in Docker container `smy-develop-smy-backend-1`. All CLI commands must be executed inside this container. HTTP requests should use `https://localhost`.

### Docker Container Access

```bash
# Enter the container for CLI operations
docker exec -it smy-develop-smy-backend-1 bash
```

## Common Development Commands

### Frontend Development

```bash
# Inside container or on host (web_src is volume-mounted)
cd web_src
npm install              # Install dependencies
npm run dev             # Start Vite dev server (localhost:5173)
npm run build           # Build for production (outputs to /web)
npm run preview         # Preview production build
npm run type-check      # Run TypeScript type checking (vue-tsc)
```

### Backend Development

```bash
# All commands run inside container: docker exec -it smy-develop-smy-backend-1 bash

# PHP CLI testing
php server/index.php /api/endpoint

# Composer dependency management
composer install
composer update

# Test API endpoint
curl --resolve localhost:443:127.0.0.1 https://localhost/server/api/item/info
```

### Docker Management

```bash
# Build image (with China mirror support)
docker compose build
IN_CHINA=true docker compose build

# Start services
docker compose up -d

# Stop services
docker compose down

# View logs
docker compose logs -f showdoc
```

## Database

- **Type**: SQLite (file-based)
- **Location**: `/Sqlite/showdoc.db.php`
- **Auto-upgrade**: Database schema upgrades run automatically on first web request via `Upgrade::checkAndUpgrade()`
- **No external setup needed** - database file is included in the repository

## API Routing

### Query Parameter Mode (Legacy)
```
GET /index.php?s=Api/Item/info
GET /index.php?s=/api/item/info
```

### Path-based Mode (Modern)
```
GET /server/api/item/info
```

Both modes are supported. Controllers are in `/server/app/Api/Controller/` and follow the naming pattern `{Feature}Controller.php`.

## Key Implementation Details

### Database & ORM

- Uses Illuminate/Database (Laravel's database library)
- Models located in `/server/app/Model/`
- Database connection configured via environment variables or `.env` file
- Cache layer via `CacheManager` for performance

### Error Handling

- Slim's error middleware configured to return JSON responses
- Errors logged to `/server/app/Runtime/Logs/`
- Bot detection middleware blocks search engines and crawlers

### Frontend State Management

- Pinia stores in `/web_src/src/store/`
- API calls via Axios with base URL configuration
- Event bus via `mitt` for component communication

### Internationalization

- Language packs in `/web_src/src/i18n/` (Chinese/English)
- Backend language packs in `/server/app/Api/Lang/`
- Auto-detection of browser language on first visit

## Installation & Setup

1. First access triggers installation wizard at `/install/index.php`
2. Installation creates `./install/install.lock` file
3. After installation, app redirects to `/web/#/`
4. Database is auto-initialized from schema

## Important Notes

- **PHP Version**: Minimum PHP 7.4.0 (checked at startup)
- **File Uploads**: Stored in `/Public/Uploads/`
- **Session**: Native PHP sessions enabled for CAPTCHA and other features
- **Timezone**: Defaults to `Asia/Shanghai`, configurable via `APP_TIMEZONE` env var
- **Copyright**: Apache 2.0 license - retain copyright notices in UI when modifying

## Environment Configuration

Create `.env` file in project root (copy from `.env.example` if available):

```bash
# Database (SQLite is default, no setup needed)
DB_CONNECTION=sqlite
DB_DATABASE=/Sqlite/showdoc.db.php

# App settings
APP_TIMEZONE=Asia/Shanghai
APP_DEBUG=false

# Cache (optional, defaults to file-based)
CACHE_DRIVER=file
```

The SQLite database file is included in the repository at `/Sqlite/showdoc.db.php`. Schema upgrades run automatically on first web request.

## Internationalization (i18n) Workflow

When adding new UI strings:

1. **Frontend** - Add to language packs in `/web_src/src/i18n/`:
   - English: `/web_src/src/i18n/en-US/`
   - Chinese: `/web_src/src/i18n/zh-CN/`
   - Use consistent key naming: `module.action` (e.g., `item.create`, `item.delete`)

2. **Backend** - Add to language packs in `/server/app/Api/Lang/`:
   - Follows same structure as frontend
   - Used for API response messages

3. **Usage**:
   - Frontend: `$t('module.key')` in Vue components
   - Backend: Language strings returned in API responses

## Cache Layer

The `CacheManager` in `/server/app/Common/` provides caching:

- **Default**: File-based cache in `/server/app/Runtime/Cache/`
- **Usage**: Wrap expensive operations (DB queries, API calls) with cache checks
- **Pattern**: Check cache → if miss, compute → store → return
- **Invalidation**: Clear cache when data changes (create/update/delete operations)

## Debugging & Troubleshooting

**PHP Errors**: Check logs in `/server/app/Runtime/Logs/` (inside container)

```bash
docker exec -it smy-develop-smy-backend-1 tail -f /var/www/html/server/app/Runtime/Logs/error.log
```

**Frontend Issues**: Check browser console and Vite dev server output

**Database Issues**: SQLite database is file-based; check `/Sqlite/showdoc.db.php` exists and is readable

**API Testing**: Use curl with proper domain resolution:

```bash
# Test endpoint
curl --resolve localhost:443:127.0.0.1 \
  https://localhost/server/api/item/info
```

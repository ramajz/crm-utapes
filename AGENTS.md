# AGENTS.md — CRM-Utapes

## Project Overview
CRM untuk Utapes — lead management, customer tracking, webhook handling.
Stack: Laravel 11 + Livewire + Alpine.js + Tailwind CSS.

## Architecture
- **Production**: Deployed on Coolify (VPS), database: NeonDB PostgreSQL
- **Local Dev**: SQLite (file: `database/database.sqlite`)
- **Auth**: Laravel Breeze (Livewire)
- **Frontend**: Livewire components + Alpine.js

## Local Development

### Database (SQLite)
```bash
# Setup
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

### .env Configuration
```env
# LOCAL — SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/Users/rama/crm-utapes/database/database.sqlite

# PRODUCTION — PostgreSQL (NeonDB)
# DB_CONNECTION=pgsql
# DB_HOST=ep-xxx.us-east-2.aws.neon.tech
# DB_PORT=5432
# DB_DATABASE=neondb
```

### Login Default
| Role | Email | Password |
|--|--|--|
| Admin | admin@crm.com | password |
| Manager | manager@crm.com | password |
| CS | siti@crm.com | password |

## Important Rules

### Database Compatibility
- **MUST use Eloquent/Query Builder** — no raw SQL that's PostgreSQL-specific
- **DB::raw() is OK** for simple functions: `date()`, `count()`, `CASE WHEN`
- **AVOID**: `DB::select()`, raw PostgreSQL functions like `interval`, `:: casting`
- All migrations must work on both SQLite AND PostgreSQL

### Code Style
- PHP 8.2+ features (readonly, enums, named arguments)
- Livewire components for interactive UI
- Alpine.js for client-side interactivity
- Tailwind CSS for styling
- No jQuery, no Bootstrap

### File Structure
```
app/
├── Http/Controllers/    ← Web controllers
├── Livewire/           ← Livewire components
├── Models/             ← Eloquent models
├── Services/           ← Business logic
└── View/Components/    ← Blade components
```

## Testing Checklist
- [ ] `php artisan migrate:fresh --seed` passes
- [ ] `php artisan serve` → login works
- [ ] Dashboard loads with charts
- [ ] Lead CRUD works
- [ ] Webhook endpoint responds

## Deployment
- Push to `main` → Coolify auto-deploys
- Never push broken code to `main`
- Test locally first (SQLite), then push

# Shine Festivals

## Read this before anything else

**This is NOT `shine.net/belsonic`.** Two projects here answer to "shine" and
"belsonic". This one is the multi-venue CMS that deploys to crbntyp `/blsnc/`.
`shine.net/belsonic` is the live www.belsonic.com client site — different
folder, different container, different FTP account. Confusing the two is the
established failure mode, not a hypothetical.

### Deploy targets — TWO. crbntyp is STAGING for the client site.

**The order is not optional. Never deploy straight to the client.**

Every piece of work on this project, no matter how small, goes:

1. **Build and test locally.**
2. **`scripts/deploy-crbntyp.sh`** → our VPS at `/var/www/crbntyp/blsnc/`.
3. **Check it on crbntyp** — https://crbntyp.com/blsnc/. Get it right here.
   This is the staging environment. All iteration happens at this step.
4. **Sign-off.** Jonny confirms it is good to go. Do not skip or assume this.
5. **`scripts/deploy-client.sh --live`** → FTPS to the client's shine.net server.

If you are ever unsure which step you are on, you are on step 3.

| # | Target | How | Credentials |
|---|--------|-----|-------------|
| 1 | **Ours / staging** — crbntyp | `rsync` to `/var/www/crbntyp/blsnc/` as root | VPS key |
| 2 | **Client / live** | FTPS to `ftp.shine.net` | `.env` → `FTP_USER=jonny_shinefestivals` |

Target 2 lands **directly in the root of the folder that account is scoped to**.
Verified by connecting 2 Sep 2026: the landing directory is the site root and
already holds `index.php`, `accessibility.php`, `tickets.php`, `transport.php`,
`venue.php`, `info.php`, `location.php`, plus `admin/`, `includes/`, `styles/`,
`fonts/`, `img/`, `scripts/`. So the payload is the *contents* of `dist/` —
`dist/*` → remote root. There is no `dist/` folder on the far side.

**FTPS with explicit TLS, confirmed** — the server is ProFTPD and accepts
`AUTH SSL`, negotiating TLSv1.2. The account **owns** the files it would be
replacing (`-rw-r--r-- jonny_shinefestivals psacln`), so writes will work.
No upload has actually been run yet, so watch the first one.

**Connect to `mail.shine.net`, not `ftp.shine.net`.** The server presents a
valid Let's Encrypt certificate for `CN=mail.shine.net` with **no SAN**, so
connecting as `ftp.shine.net` fails verification (curl exit 60) even though
nothing is wrong with the cert. `deploy-client.sh` probes for this and uses the
matching hostname so TLS is properly verified; it only falls back to `-k` —
loudly — if that host is unreachable. Do not reach for `-k` by default.

**Never ship `.env`.** The build copies it into `dist/`, and it holds both DB
passwords, `ADMIN_PASSWORD`, the Mapbox token and this FTP password. Both
scripts exclude `.env*`, `*.sql` and `*.md`. crbntyp survives a past leak of it
only because `.htaccess` denies `^\.env`; the client's server may not.

Cruft noted on the client server, left alone: `db-test.php`, `index-bk.html`,
`holding-2025/`. Most files date from Nov/Dec 2025, `location.php` from June.

Nothing in the codebase has ever read `FTP_HOST`/`FTP_USER`/`FTP_PASS`; they sat
in `.env` unused until these scripts. `.env.example` still has no FTP section.

### The database is LOCKED

No schema changes, no migrations, no writes. Treat it read-only. This is a
client system. The `migrations/` folder and the "Future Enhancements" list
further down this file are history, not an invitation.

Two databases are configured in `.env`: `LOCAL_DB_*` (crbntyp) and
`PROD_DB_*` (the client's live one). The lock applies to both.

### Local dev

There is **no PHP on this machine**. `npm run serve` will not work.
Local dev is the `shine-festivals-web` container (php:8.1-apache, port 8080),
currently stopped — `docker start shine-festivals-web`.
Port 8080 is also claimed by `definitive-leagues-php-1`; only one at a time.

### Housekeeping

- Parked on branch `fix/location-leaflet-maps`, not master.
- **This file is tracked in git.** Never put credentials in it.
- `.env` is gitignored. `.env.example` has no FTP section — update it if the
  FTP setup is ever formalised.

---

## Codebase Architecture

## Application Type

**Multi-Venue Festival Management Platform** - A dynamic, database-driven web application for managing and displaying multiple music festival websites with shared infrastructure but venue-specific branding and content.

## Overview

This is a PHP-based content management system designed to power festival websites (modeled after Belsonic). It features a public-facing festival website with lineup, tickets, venue information, and transport details, plus a full-featured admin panel for content management. The system supports multiple venues (Belsonic, CHSQ, EMERGE) through a single codebase with venue-specific theming and content.

## Technology Stack

### Backend
- **PHP 8.1** - Server-side language
- **MySQL 8.0** - Primary database (with MariaDB compatibility)
- **PDO** - Database abstraction layer with prepared statements
- **Sessions** - User authentication management

### Frontend
- **SCSS/Sass** - CSS preprocessing (1,847 lines of compiled styles)
- **Vanilla JavaScript (ES6)** - Client-side interactivity
- **Line Awesome** - Icon library (1000+ icons)
- **Lora & Ms Madi** - Google Fonts

### Build System
- **Node.js** - Build environment
- **npm** - Package manager
- **Sass** - SCSS compilation
- **CPX** - File copying and watching
- **Concurrently** - Parallel task execution
- **LiveReload** - Auto-refresh during development

### Development Environment
- **PHP Built-in Server** - Local development (port 8080)
- **Docker Compose** - Containerized deployment
  - PHP 8.1 Apache container
  - MySQL 8.0 container
- **LiveReload Server** - Hot reload for development

## Project Structure

```
shine-festivals/
├── src/                          # Source files (not served directly)
│   ├── includes/                 # Shared PHP components
│   │   ├── config.php           # Database connection & config
│   │   ├── header.php           # Shared navigation header
│   │   └── footer.php           # Shared footer with social links
│   │
│   ├── admin/                    # Admin panel (session-protected)
│   │   ├── auth.php             # Authentication logic
│   │   ├── login.php            # Login page
│   │   ├── index.php            # Dashboard with statistics
│   │   ├── artists.php          # Artist CRUD operations
│   │   ├── gigs.php             # Gig/performance management
│   │   ├── performances.php     # Legacy performance management
│   │   ├── venues.php           # Venue management
│   │   ├── venues-manage.php    # Advanced venue configuration
│   │   ├── settings.php         # System settings
│   │   ├── upload-background.php # Background image uploader
│   │   ├── admin.css            # Admin-specific styles
│   │   └── includes/
│   │       ├── header.php       # Admin navigation
│   │       └── footer.php       # Admin footer
│   │
│   ├── Public Pages (PHP)       # Database-driven pages
│   │   ├── index.php            # Home/Lineup page
│   │   ├── venue.php            # Venue information
│   │   ├── location.php         # Location details with maps
│   │   ├── info.php             # General information
│   │   ├── accessibility.php    # Accessibility features
│   │   ├── transport.php        # Transport options
│   │   ├── tickets.php          # Ticket types & pricing
│   │   └── switch-venue.php     # Testing: switch between venues
│   │
│   ├── styles/                   # SCSS source
│   │   └── main.scss            # Main stylesheet (1,847 lines)
│   │
│   ├── scripts/                  # JavaScript
│   │   ├── main.js              # Entry point, burger menu
│   │   └── components/
│   │       └── background-rotator.js  # Background image carousel
│   │
│   └── img/                      # Static assets
│       ├── assets/              # Logos, static images
│       ├── artists/             # Artist photos
│       ├── backgrounds/         # Venue background images
│       └── uploads/
│           └── venues/          # User-uploaded venue images
│
├── dist/                         # Build output (served by web server)
│   ├── (mirrors src/ structure with compiled assets)
│   ├── styles/                  # Compiled CSS
│   ├── scripts/                 # Copied JavaScript
│   └── (all PHP files copied here)
│
├── database/                     # Database management
│   ├── schema.sql               # Complete database schema
│   ├── sample_data.sql          # Sample festival data
│   ├── config.example.php       # DB config template
│   ├── README.md                # Database documentation
│   ├── festival.db              # SQLite backup/test database
│   └── migrations/
│       ├── add_age_restriction_to_performances.sql
│       └── expand_venues_table.sql
│
├── node_modules/                 # NPM dependencies (gitignored)
├── docker-compose.yml           # Docker service definitions
├── Dockerfile                   # PHP 8.1 Apache image config
├── docker-dev.sh                # Docker management script
├── livereload-server.js         # Live reload server
├── package.json                 # NPM scripts & dependencies
├── .gitignore                   # Git exclusions
├── README.md                    # Main documentation
├── SETUP.md                     # Setup instructions
├── CONVERSION_SUMMARY.md        # HTML to PHP conversion notes
└── ADMIN_GUIDE.md               # Admin panel guide
```

## Database Architecture

### Schema Overview (9 Tables)

The database uses MySQL 8.0 with InnoDB storage engine, UTF-8 encoding, and referential integrity via foreign keys.

#### Core Tables

1. **festivals**
   - Festival events (Belsonic 2026, etc.)
   - Fields: name, description, year, dates, status, ticket_link
   - Links to: venues (via venue_id)
   - Status enum: 'upcoming', 'ongoing', 'completed', 'cancelled'

2. **venues**
   - Venue information with location data
   - Extended fields (via migration): venue_map_url, pubs_map_url, accommodation_map_url
   - Content fields: venue_intro, pubs_intro, accommodation_intro
   - Transport descriptions: bus, train, taxi, parking
   - Theme colors: primary_color, secondary_color, accent_color
   - Fields: name, address, city, postcode, capacity, latitude/longitude
   - is_active flag for multi-venue support

3. **artists**
   - Performer details with social media links
   - Fields: name, bio, genre, image_url
   - Social: facebook, twitter, instagram, spotify, website
   - 25 PHP files handle artist management

4. **performances**
   - Links artists to festival dates/times/venues
   - Fields: festival_id, artist_id, venue_id, performance_date, performance_time
   - Flags: is_headliner, supporting_act, has_afterparty
   - Extended: go_live_date (scheduled visibility), age_restriction, support_acts
   - ticket_link, ticket_url for direct sales
   - sort_order for lineup ordering

#### Supporting Tables

5. **accessibility_options**
   - Festival accessibility features
   - Categories: 'access', 'facilities', 'assistance', 'parking', 'other'
   - Fields: title, description, icon, sort_order

6. **transport_options**
   - Transport methods to venue
   - Types: 'bus', 'train', 'taxi', 'car', 'bike', 'walk', 'other'
   - Fields: title, description, provider, cost, duration, icon

7. **ticket_types**
   - Ticket categories and pricing
   - Fields: name, description, price, currency (default: GBP)
   - Availability: available_quantity, min_age, max_per_order
   - Sale dates: on_sale_date, off_sale_date
   - Status: 'available', 'sold_out', 'coming_soon', 'expired'

8. **age_restrictions**
   - Age policies per festival
   - Fields: min_age, requires_guardian, guardian_min_age, proof_required

9. **newsletter_subscribers**
   - Email subscription list
   - Fields: email (unique), first_name, last_name
   - Status: 'active', 'unsubscribed', 'bounced'
   - Timestamps: subscribed_at, unsubscribed_at

### Database Migrations

- Located in `/database/migrations/`
- **expand_venues_table.sql** - Adds multi-venue support with theming
- **add_age_restriction_to_performances.sql** - Per-performance age restrictions

### Database Connection Pattern

```php
// config.php
function getDB() {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    return new PDO($dsn, DB_USER, DB_PASS, $options);
}
```

All queries use PDO prepared statements for SQL injection prevention.

## Build System & Development Workflow

### NPM Scripts

**Build Commands:**
```bash
npm run build           # Full production build
npm run build:scss      # Compile SCSS to CSS (no source maps)
npm run build:php       # Copy PHP files to dist/
npm run build:admin-css # Copy admin CSS to dist/admin/
npm run build:js        # Copy JavaScript to dist/scripts/
npm run build:img       # Copy images to dist/img/
npm run build:fonts     # Copy Line Awesome fonts to dist/fonts/
npm run clean           # Remove all dist/ contents
```

**Watch Commands:**
```bash
npm run watch           # Watch all file types
npm run watch:scss      # Watch & compile SCSS
npm run watch:php       # Watch & copy PHP files
npm run watch:admin-css # Watch admin CSS
npm run watch:js        # Watch JavaScript
npm run watch:img       # Watch images
```

**Development:**
```bash
npm run dev             # Build + watch + serve + livereload
npm run serve           # PHP server (localhost:8080)
npm run serve:livereload # LiveReload server
```

**Docker:**
```bash
npm run docker:start    # Start containers
npm run docker:stop     # Stop containers
npm run docker:restart  # Restart containers
npm run docker:rebuild  # Rebuild & restart
npm run docker:logs     # View logs
```

### Build Pipeline

1. **Source** (`src/`) → **Distribution** (`dist/`)
2. SCSS compiled to CSS (no source maps for production)
3. PHP files copied directly (no transpilation)
4. JavaScript copied as-is (ES6, no bundling)
5. Images copied maintaining directory structure
6. Line Awesome fonts extracted from node_modules

### File Watching

The `dev` command runs concurrently:
- Sass watching `src/styles/` → compiling to `dist/styles/`
- CPX watching `src/**/*.php` → copying to `dist/`
- CPX watching `src/admin/**/*.css` → copying to `dist/admin/`
- CPX watching `src/scripts/**/*.js` → copying to `dist/scripts/`
- CPX watching `src/img/**/*` → copying to `dist/img/`
- PHP server serving `dist/` on port 8080
- LiveReload watching `dist/` for browser refresh

### LiveReload Configuration

```javascript
// livereload-server.js
const server = livereload.createServer({
  delay: 100,
  exts: ['html', 'php', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg']
});
server.watch('dist/');
```

## Key Architectural Patterns

### 1. Multi-Venue Architecture

The system supports multiple venues (Belsonic, CHSQ, EMERGE) through a single codebase:

```php
// config.php
define('DEFAULT_VENUE_ID', 2); // Belsonic

function getCurrentVenueId() {
    return $_SESSION['test_venue_id'] ?? DEFAULT_VENUE_ID;
}
```

- Each venue has custom colors (primary, secondary, accent)
- Venue-specific content (intros, transport descriptions)
- Venue-specific background image rotators
- switch-venue.php for testing different venue configurations

### 2. Modular PHP Architecture

**Shared Components:**
- `includes/config.php` - Database connection, session management
- `includes/header.php` - Navigation, meta tags, CSS links
- `includes/footer.php` - Social links, scripts

**Page Pattern:**
```php
<?php
require_once 'includes/config.php';
$pageTitle = 'Page Name';
$currentPage = 'page-slug';
$db = getDB();

// Fetch data with prepared statements
$stmt = $db->prepare("SELECT * FROM table WHERE id = ?");
$stmt->execute([getCurrentVenueId()]);
$data = $stmt->fetchAll();

include 'includes/header.php';
?>
<!-- HTML with PHP data interpolation -->
<?php include 'includes/footer.php'; ?>
```

### 3. Admin Panel Architecture

**Authentication:**
- Simple session-based auth in `admin/auth.php`
- Credentials: see `.env` (`ADMIN_USERNAME` / `ADMIN_PASSWORD`). Not recorded here — this file is tracked.
- All admin pages check `$_SESSION['admin_logged_in']`
- Logout destroys session

**CRUD Pattern:**
```php
// Example from admin/artists.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            // INSERT statement
        } elseif ($_POST['action'] === 'edit') {
            // UPDATE statement
        } elseif ($_POST['action'] === 'delete') {
            // DELETE statement
        }
    }
    header('Location: artists.php');
    exit;
}
```

**Admin Pages:**
- **Dashboard** (index.php) - Statistics, upcoming gigs
- **Artists** - Full CRUD with social media fields
- **Gigs** - Advanced performance management with venue/afterparty support
- **Venues** - Venue management with color themes
- **Settings** - Festival settings configuration

### 4. JavaScript Architecture

**Component-Based:**
```javascript
// main.js - Entry point
document.addEventListener('DOMContentLoaded', function() {
    // Load venue backgrounds from PHP
    let backgroundImages = window.venueBackgroundImages || [];
    
    // Dynamically load BackgroundRotator component
    const script = document.createElement('script');
    script.src = '/scripts/components/background-rotator.js';
    script.onload = function() {
        const rotator = new BackgroundRotator(backgroundImages, 8000, 2000);
        rotator.init();
    };
});
```

**BackgroundRotator Component:**
- Automatic image carousel for hero sections
- Fade transitions (2s duration)
- 8s rotation interval
- Receives images from PHP via JSON injection

### 5. Styling Architecture

**SCSS Organization** (1,847 lines):
- CSS Variables for theming (colors, spacing, shadows)
- Mobile-first responsive design
- Component-based structure (navbar, hero, lineup, cards, forms)
- Separate admin styles (`admin/admin.css`)

**Color Palette:**
- Primary: #ff6b35 (orange)
- Secondary: #f7931e (gold)
- Accent: #c13584 (pink)
- Dark: #1a1a2e
- Text: #1f2937

### 6. Security Patterns

- **PDO Prepared Statements** - All database queries use parameterized queries
- **Session Management** - PHP sessions for admin authentication
- **HTTPS Ready** - Configuration supports SSL in production
- **.gitignore Protection** - Database credentials excluded from version control
- **Input Validation** - Form data sanitized before database insertion

## Configuration Files

### package.json
```json
{
  "name": "shine-festivals",
  "scripts": {
    "dev": "build + watch + serve + livereload",
    "build": "scss + php + css + js + img + fonts"
  },
  "devDependencies": {
    "concurrently": "^9.2.1",
    "cpx": "^1.5.0",
    "live-server": "^1.2.2",
    "livereload": "^0.10.3",
    "sass": "^1.93.2"
  },
  "dependencies": {
    "line-awesome": "^1.3.0"
  }
}
```

### docker-compose.yml
- **web** service: PHP 8.1 Apache (port 8080 → 80)
- **db** service: MySQL 8.0 (port 3307 → 3306)
- Volume mounts: `./dist` → `/var/www/html`
- MySQL data persisted in `mysql_data` volume

### Dockerfile
- Base: `php:8.1-apache`
- Extensions: `pdo`, `pdo_mysql`
- Apache: `mod_rewrite` enabled
- Copies: `dist/` → `/var/www/html/`
- Permissions: `www-data:www-data`

### .gitignore
- `node_modules/` - NPM dependencies
- `dist/` - Build output (regenerated)
- `database/config.php` - Credentials
- `.DS_Store` - macOS metadata

### src/includes/config.php
```php
define('DB_HOST', getenv('DB_HOST') ?: 'host.docker.internal');
define('DB_NAME', 'shine_festivals');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DEFAULT_VENUE_ID', 2); // Belsonic
```

## Data Flow

### Public Page Request Flow

1. **Request:** Browser → `http://localhost:8080/index.php`
2. **Config:** PHP includes `config.php`, establishes DB connection
3. **Session:** Session started, venue ID determined
4. **Query:** PDO prepared statements fetch festival/lineup data
5. **Render:** PHP interpolates data into HTML templates
6. **Header:** `includes/header.php` generates navigation with active page
7. **Content:** Dynamic content rendered from database
8. **Footer:** `includes/footer.php` adds social links, scripts
9. **Response:** Complete HTML sent to browser
10. **JavaScript:** Background rotator initializes with venue images

### Admin CRUD Flow

1. **Login:** POST credentials → `admin/login.php` → session created
2. **Dashboard:** GET `admin/index.php` → statistics queries
3. **Edit Form:** GET `admin/artists.php?edit=5` → fetch artist data
4. **Update:** POST form data → prepared UPDATE statement
5. **Redirect:** Header redirect to listing page
6. **Verify:** Changes immediately visible on public site

### Build Flow

1. **Source Edit:** Developer edits `src/styles/main.scss`
2. **Watch Detect:** Sass watcher detects change
3. **Compile:** SCSS compiled to `dist/styles/main.css`
4. **LiveReload:** File change detected in `dist/`
5. **Inject:** LiveReload script sends refresh command
6. **Browser:** Page reloads automatically with new styles

## Deployment Architecture

### Development (Local)
```bash
npm run dev
# → localhost:8080 (PHP server)
# → dist/ served directly
# → Hot reload active
```

### Development (Docker)
```bash
npm run docker:start
# → localhost:8080 → Apache container
# → host.docker.internal:3306 → MySQL container
# → Volume-mounted dist/
```

### Production Recommendations
1. Build: `npm run build`
2. Upload: `dist/` → web server
3. Database: Import `database/schema.sql` to production MySQL
4. Config: Update `config.php` with production credentials
5. Server: Apache/Nginx with PHP-FPM
6. SSL: Configure HTTPS certificate
7. Security: Change admin credentials, enable password hashing
8. Backups: Automated MySQL backups
9. Monitoring: PHP error logs, database query performance

## Testing & Debugging

### Venue Switching (Development)
- Visit `switch-venue.php?venue_id=2` (Belsonic)
- Visit `switch-venue.php?venue_id=3` (CHSQ)
- Visit `switch-venue.php?venue_id=4` (EMERGE)
- Session stores test venue override

### Database Verification
```bash
docker exec -it shine-festivals-db mysql -uroot -proot shine_festivals
mysql> SELECT * FROM venues WHERE is_active = 1;
mysql> SELECT COUNT(*) FROM performances WHERE venue_id = 2;
```

### Log Files
- PHP errors: Check Docker logs `docker logs shine-festivals-web`
- MySQL queries: `SHOW PROCESSLIST;`
- JavaScript console: Browser DevTools

## Future Enhancements

Based on documentation:
- Multi-user admin system with roles
- Password hashing (bcrypt)
- Image upload functionality (currently URL-based)
- Drag-and-drop lineup ordering
- Email notification system
- Analytics dashboard
- API endpoints for mobile apps
- Automated backups
- CSRF protection
- Rate limiting on login

## Dependencies

**Runtime:**
- PHP 8.1+ with PDO MySQL extension
- MySQL 8.0+ or MariaDB 10.2+
- Apache 2.4+ or Nginx

**Development:**
- Node.js 14+
- npm 6+
- Docker & Docker Compose (optional)

## Author & License

- **Author:** Jonny Pyper / Carbontype (Belsonic, CHSQ, Shine.net)
- **License:** ISC
- **Reference:** Based on https://www.belsonic.com/ structure

## Quick Start

```bash
# 1. Install dependencies
npm install

# 2. Start Docker environment
npm run docker:start

# 3. Import database
docker exec -i shine-festivals-db mysql -uroot -proot shine_festivals < database/schema.sql
docker exec -i shine-festivals-db mysql -uroot -proot shine_festivals < database/sample_data.sql

# 4. Build & watch
npm run dev

# 5. Visit site
open http://localhost:8080/index.php

# 6. Admin panel
open http://localhost:8080/admin/login.php
# Login: credentials are in .env, not in this file
```

## Key Takeaways

1. **Multi-Venue Platform:** Single codebase powers multiple festival websites with venue-specific theming
2. **Database-Driven:** All content stored in MySQL, zero hardcoded content
3. **Admin Panel:** Full CRUD operations for artists, performances, venues
4. **Build System:** Modern npm-based workflow with hot reload
5. **Docker Ready:** Containerized deployment with docker-compose
6. **Security:** PDO prepared statements, session management, credential protection
7. **Scalable:** Add new venues, festivals, artists without code changes
8. **Developer-Friendly:** Clear separation of concerns, modular architecture, extensive documentation

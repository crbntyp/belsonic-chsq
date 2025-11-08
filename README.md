# Shine Festivals - Multi-Venue Festival Platform

<p align="center">
  <img src="src/belsonic-logo.png" alt="Belsonic" height="200" style="margin: 0 20px;" />
  <img src="src/chsq-logo.png" alt="Custom House Square" height="200" style="margin: 0 20px;" />
</p>

A modern, database-driven festival management platform powering multiple music festival websites with shared infrastructure but venue-specific branding and content.

## Features

- **Multi-Venue Support** - Single codebase powers multiple festival brands (Belsonic, Custom House Square, etc)
- **Dynamic Content Management** - Full-featured admin panel with WYSIWYG editors
- **Venue-Specific Theming** - Custom colors, logos, and backgrounds per venue
- **Performance Management** - Gig scheduling with go-live dates, support acts, and afterparty ticketing
- **Interactive Maps** - Google Maps integration with multi-pin displays for pubs and accommodation
- **Age Restrictions** - Per-gig age restriction content management
- **Transport & Location Info** - Comprehensive getting-there information with custom transport options - TBA
- **FAQ System** - Venue-specific frequently asked questions

## Prerequisites

- Node.js (v14 or higher)
- npm
- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.2+
- Docker & Docker Compose (optional, for containerized development)

## Installation

**For complete setup instructions, see [SETUP.md](SETUP.md)**

### Quick Start with Docker

```bash
# 1. Install Node dependencies
npm install

# 2. Start Docker containers
npm run docker:start

# 3. Import database schema
docker exec -i shine-festivals-db mysql -uroot -proot shine_festivals < database/schema.sql
docker exec -i shine-festivals-db mysql -uroot -proot shine_festivals < database/sample_data.sql

# 4. Build and run development server
npm run dev
```

### Quick Start without Docker

```bash
# 1. Install Node dependencies
npm install

# 2. Import database schema (requires local MySQL)
mysql -u root -p shine_festivals < database/schema.sql
mysql -u root -p shine_festivals < database/sample_data.sql

# 3. Configure database connection
# Edit src/includes/config.php with your MySQL credentials

# 4. Build and run
npm run dev
```

## Available Scripts

### Development

Start the development server with live reload and file watching:

```bash
npm run dev
```

This will:
- Build all assets (SCSS, PHP, JS, images, fonts)
- Watch for changes and automatically rebuild
- Start PHP built-in server at `http://localhost:8080`
- Start LiveReload server for automatic browser refresh

### Building

Build the entire project for production:

```bash
npm run build
```

This runs all build tasks in sequence:
1. Compile SCSS to CSS (no source maps)
2. Copy PHP files to dist/
3. Copy admin CSS to dist/admin/
4. Copy JavaScript files to dist/scripts/
5. Copy images to dist/img/
6. Copy Line Awesome icon fonts to dist/fonts/

### Individual Build Tasks

- `npm run build:scss` - Compile SCSS files
- `npm run build:php` - Copy PHP files
- `npm run build:admin-css` - Copy admin panel CSS
- `npm run build:js` - Copy JavaScript files
- `npm run build:img` - Copy images
- `npm run build:fonts` - Copy icon fonts

### Watch Tasks

Watch individual file types for changes (automatically run by `npm run dev`):

- `npm run watch:scss` - Watch and compile SCSS files
- `npm run watch:php` - Watch and copy PHP files
- `npm run watch:admin-css` - Watch admin CSS
- `npm run watch:js` - Watch JavaScript files
- `npm run watch:img` - Watch images

### Docker Commands

- `npm run docker:start` - Start Docker containers
- `npm run docker:stop` - Stop containers
- `npm run docker:restart` - Restart containers
- `npm run docker:rebuild` - Rebuild and restart
- `npm run docker:logs` - View container logs

### Clean

Remove all built files from the `dist/` directory:

```bash
npm run clean
```

## Project Structure

```
shine-festivals/
├── src/                          # Source files
│   ├── includes/                 # Shared PHP components
│   │   ├── config.php           # Database connection & settings
│   │   ├── header.php           # Site navigation
│   │   ├── footer.php           # Site footer
│   │   └── social-links.php     # Social media links
│   │
│   ├── admin/                    # Admin panel (protected)
│   │   ├── login.php            # Admin login
│   │   ├── index.php            # Dashboard with stats
│   │   ├── gigs.php             # Gig/performance management
│   │   ├── venues-manage.php    # Venue configuration
│   │   ├── venues.php           # Venue CRUD
│   │   ├── settings.php         # System settings
│   │   └── admin.css            # Admin panel styles
│   │
│   ├── Public Pages (PHP)        # Database-driven pages
│   │   ├── index.php            # Home/Lineup page
│   │   ├── location.php         # Location with maps
│   │   ├── info.php             # General info & age restrictions
│   │   ├── accessibility.php    # Accessibility features
│   │   └── switch-venue.php     # Testing: venue switcher
│   │
│   ├── styles/                   # SCSS source
│   │   └── main.scss            # Main stylesheet (~1,900 lines)
│   │
│   ├── scripts/                  # JavaScript
│   │   ├── main.js              # Entry point
│   │   └── components/
│   │       └── background-rotator.js  # Background carousel
│   │
│   ├── img/                      # Static assets
│   │   ├── assets/              # Logos, static images
│   │   ├── artists/             # Artist photos
│   │   ├── backgrounds/         # Venue backgrounds
│   │   └── uploads/             # User uploads
│   │
│   └── migrate-db.php            # Database migration script
│
├── database/                     # Database management
│   ├── schema.sql               # Complete database schema
│   ├── sample_data.sql          # Sample festival data
│   ├── config.example.php       # DB config template
│   ├── README.md                # Database documentation
│   └── migrations/              # Database migrations
│       ├── fix_go_live_date_datetime.sql
│       ├── add_pubs_accommodation_lists.sql
│       ├── add_google_maps_api_key.sql
│       └── add_age_restriction_content.sql
│
├── dist/                         # Build output (gitignored)
│   └── (mirrors src/ with compiled assets)
│
├── docker-compose.yml           # Docker service definitions
├── Dockerfile                   # PHP 8.1 Apache image
├── docker-dev.sh                # Docker management script
├── livereload-server.js         # Live reload server
├── package.json                 # NPM scripts & dependencies
└── README.md                    # This file
```

## Site Pages

All pages are PHP-based with MySQL database integration:

1. **index.php** - Festival lineup with venue-specific filtering and go-live scheduling
2. **location.php** - Venue location, pubs, accommodation with Google Maps integration
3. **info.php** - General information, age restrictions, FAQs, coach parking
4. **accessibility.php** - Accessibility features and contact information (Belsonic only)

## Admin Panel

The admin panel provides full content management capabilities:

- **Dashboard** - Statistics and upcoming gigs overview
- **Gigs Management** - Add/edit performances with WYSIWYG editors for support acts and age restrictions
- **Venues Management** - Configure venues, themes, colors, maps, transport options, FAQs
- **Settings** - Festival settings and configuration

Access the admin panel at `/admin/login.php`

## Database Schema

The database includes 9 tables:

- **festivals** - Festival events and details
- **venues** - Multi-venue configuration with theming and content
- **artists** - Performer details and social links
- **performances** - Gig scheduling with dates, times, age restrictions, go-live dates
- **accessibility_options** - Accessibility features
- **transport_options** - Transport methods
- **ticket_types** - Ticket categories and pricing
- **age_restrictions** - Age policies
- **newsletter_subscribers** - Email subscriptions
- **faqs** - Venue-specific frequently asked questions

See [database/README.md](database/README.md) for full schema details.

## Technologies Used

### Backend
- **PHP 8.1** - Server-side language
- **MySQL 8.0** - Primary database
- **PDO** - Database abstraction with prepared statements
- **Sessions** - User authentication

### Frontend
- **SCSS/Sass** - CSS preprocessing
- **Vanilla JavaScript (ES6)** - Client-side interactivity
- **Quill.js** - WYSIWYG rich text editor
- **Google Maps JavaScript API** - Interactive maps with geocoding
- **Line Awesome** - Icon library (1000+ icons)

### Build System
- **Node.js & npm** - Build environment and package management
- **Sass** - SCSS compilation
- **CPX** - File copying and watching
- **Concurrently** - Parallel task execution
- **LiveReload** - Auto-refresh during development

### Development Environment
- **PHP Built-in Server** - Local development (port 8080)
- **Docker Compose** - Containerized deployment
  - PHP 8.1 Apache container
  - MySQL 8.0 container

## Development Workflow

1. **Start development server**: `npm run dev`
2. **Edit files** in the `src/` directory
3. **Watch changes** automatically sync to `dist/` and reload browser
4. **Build for production**: `npm run build`

### File Watching

The development server watches for changes in:
- `src/styles/**/*.scss` → Compiles to `dist/styles/`
- `src/**/*.php` → Copies to `dist/`
- `src/admin/**/*.css` → Copies to `dist/admin/`
- `src/scripts/**/*.js` → Copies to `dist/scripts/`
- `src/img/**/*` → Copies to `dist/img/`

All changes trigger automatic browser reload via LiveReload.

## Multi-Venue Architecture

The platform supports multiple venues through a single codebase:

- **Venue Switching** - Test different venues via `/switch-venue.php?venue_id=X`
- **Venue-Specific Theming** - Custom colors, logos, logo heights per venue
- **Venue-Specific Content** - Separate lineups, FAQs, transport info, maps per venue
- **Dynamic Branding** - Header/footer automatically adapt to current venue
- **Production Domain Detection** - Automatic venue selection based on domain (belsonic.com, customhousesquare.com)

## Database Migrations

Migrations are tracked and applied via `migrate-db.php`:

1. Visit `/migrate-db.php` on your deployment
2. Script detects and applies pending migrations automatically
3. Safe to run multiple times (checks which migrations are already applied)

## Deployment

### Railway (Current Production)

The application is deployed on Railway with automatic deployments from GitHub:

1. Push changes to GitHub main branch
2. Railway automatically rebuilds and deploys
3. Visit production URL to run `/migrate-db.php` for database updates

**Environment Variables Required:**
- `MYSQLHOST` - Database host
- `MYSQLPORT` - Database port (default: 3306)
- `MYSQLDATABASE` - Database name
- `MYSQLUSER` - Database username
- `MYSQLPASSWORD` - Database password

### Alternative Hosting Providers

The application can be deployed to any hosting platform that supports:
- PHP 8.1+
- MySQL 8.0+
- Apache or Nginx web server

Popular options include:
- **Render.com** - Free tier with PostgreSQL (requires adapter)
- **DigitalOcean App Platform** - $5/month, production-ready
- **Traditional Hosting** - cPanel/FTP (Namecheap, Bluehost, etc.)

See [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) for platform-specific instructions.

## Security Considerations

- Database credentials stored in `src/includes/config.php` (gitignored in production)
- Admin authentication uses PHP sessions
- All database queries use PDO prepared statements
- Input sanitization with `htmlspecialchars()` for output
- `migrate-db.php` should be password-protected or removed in production

## Browser Support

Modern browsers that support:
- CSS Grid
- CSS Custom Properties (CSS variables)
- ES6 JavaScript
- Flexbox

Tested on:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Reference

This project is inspired by the structure of https://www.belsonic.com/

## Author

Jonny Pyper / Carbontype

## License

ISC

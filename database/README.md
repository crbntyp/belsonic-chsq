# Database Setup Guide

This directory contains the database schema and configuration files for the Shine Festivals project.

## Files

- `schema.sql` - Complete database schema with all tables
- `sample_data.sql` - Sample data based on Belsonic festival structure
- `config.example.php` - PHP database configuration example
- `README.md` - This file

## Prerequisites

- MySQL 5.7+ or MariaDB 10.2+
- PHP 7.4+ (if using PHP backend)
- Database user with CREATE, INSERT, UPDATE, DELETE privileges

## Installation Steps

### 1. Create Database User (if needed)

```sql
-- Login to MySQL as root
mysql -u root -p

-- Create database user
CREATE USER 'shine_user'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON shine_festivals.* TO 'shine_user'@'localhost';
FLUSH PRIVILEGES;

exit;
```

### 2. Import Database Schema

```bash
# Import the schema
mysql -u shine_user -p < database/schema.sql

# Verify tables were created
mysql -u shine_user -p shine_festivals -e "SHOW TABLES;"
```

### 3. Import Sample Data (Optional)

```bash
# Import sample data
mysql -u shine_user -p shine_festivals < database/sample_data.sql

# Verify data was imported
mysql -u shine_user -p shine_festivals -e "SELECT * FROM festivals;"
```

### 4. Configure Database Connection

```bash
# Copy the example config file
cp database/config.example.php database/config.php

# Edit config.php with your credentials
# Update DB_USER and DB_PASS values
```

**Important:** Add `database/config.php` to your `.gitignore` file to prevent committing credentials.

## Database Schema Overview

### Core Tables

- **festivals** - Main festival/event information
- **venues** - Venue details and location data
- **artists** - Artist/performer information
- **performances** - Links artists to specific festival dates/times

### Supporting Tables

- **accessibility_options** - Accessibility features and services
- **transport_options** - Transport methods and information
- **ticket_types** - Ticket categories and pricing
- **age_restrictions** - Age policies for events
- **newsletter_subscribers** - Email subscription list

## Common Queries

### Get upcoming festival with venue details

```sql
SELECT f.*, v.name as venue_name, v.city
FROM festivals f
LEFT JOIN venues v ON f.venue_id = v.id
WHERE f.status = 'upcoming'
ORDER BY f.start_date;
```

### Get lineup for a specific date

```sql
SELECT
    p.performance_date,
    p.performance_time,
    a.name as artist_name,
    a.genre,
    p.is_headliner,
    p.stage
FROM performances p
JOIN artists a ON p.artist_id = a.id
JOIN festivals f ON p.festival_id = f.id
WHERE f.id = 1
AND p.performance_date = '2026-06-18'
ORDER BY p.sort_order;
```

### Get all accessibility options for a festival

```sql
SELECT *
FROM accessibility_options
WHERE festival_id = 1
ORDER BY sort_order;
```

## Maintenance

### Backup Database

```bash
# Create backup
mysqldump -u shine_user -p shine_festivals > backup_$(date +%Y%m%d).sql

# Restore from backup
mysql -u shine_user -p shine_festivals < backup_20260618.sql
```

### Update Sample Data

Edit `sample_data.sql` and re-import:

```bash
# Clear existing data
mysql -u shine_user -p shine_festivals -e "SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE performances;
TRUNCATE TABLE artists;
TRUNCATE TABLE festivals;
TRUNCATE TABLE venues;
SET FOREIGN_KEY_CHECKS=1;"

# Re-import
mysql -u shine_user -p shine_festivals < database/sample_data.sql
```

## Security Notes

1. Never commit `database/config.php` to version control
2. Use environment variables for production credentials
3. Always use prepared statements to prevent SQL injection
4. Limit database user privileges to only what's needed
5. Regularly backup your database

## Troubleshooting

### Connection refused error
- Check MySQL service is running: `sudo systemctl status mysql`
- Verify host and port in config

### Access denied error
- Confirm username and password are correct
- Check user has proper privileges: `SHOW GRANTS FOR 'shine_user'@'localhost';`

### Table doesn't exist error
- Verify schema was imported successfully
- Check you're connecting to the correct database

## Next Steps

1. Set up PHP backend or API endpoints to serve data
2. Create data access layer / repository classes
3. Implement CRUD operations for each table
4. Add data validation and sanitization
5. Set up automated backups

For more information, see the main project README.

-- Shine Festivals Database Schema
-- Based on Belsonic festival website structure

-- Create database
CREATE DATABASE IF NOT EXISTS shine_festivals
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE shine_festivals;

-- Festivals/Events table
CREATE TABLE festivals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    year INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    venue_id INT,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    ticket_link VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_year (year),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Venues table
CREATE TABLE venues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100),
    postcode VARCHAR(20),
    capacity INT,
    description TEXT,
    venue_map_url TEXT,
    pubs_map_url TEXT,
    accommodation_map_url TEXT,
    venue_intro TEXT,
    pubs_intro TEXT,
    accommodation_intro TEXT,
    bus_description TEXT,
    train_description TEXT,
    taxi_description TEXT,
    parking_description TEXT,
    primary_color VARCHAR(7) DEFAULT '#ff006f',
    secondary_color VARCHAR(7) DEFAULT '#00ffff',
    accent_color VARCHAR(7) DEFAULT '#ff6400',
    is_active BOOLEAN DEFAULT FALSE,
    bg_image_1 VARCHAR(500),
    bg_image_2 VARCHAR(500),
    bg_image_3 VARCHAR(500),
    bg_image_4 VARCHAR(500),
    bg_image_5 VARCHAR(500),
    logo_url VARCHAR(500),
    logo_height VARCHAR(20) DEFAULT '60px',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_venue (is_active)
) ENGINE=InnoDB;

-- Artists table
CREATE TABLE artists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    bio TEXT,
    genre VARCHAR(100),
    image_url VARCHAR(500),
    website VARCHAR(500),
    facebook VARCHAR(255),
    twitter VARCHAR(255),
    instagram VARCHAR(255),
    spotify VARCHAR(500),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB;

-- Performances table (links artists to festival dates)
CREATE TABLE performances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    festival_id INT NOT NULL,
    artist_id INT NOT NULL,
    venue_id INT NOT NULL,
    performance_date DATE NOT NULL,
    performance_time TIME,
    stage VARCHAR(100),
    is_headliner BOOLEAN DEFAULT FALSE,
    supporting_act BOOLEAN DEFAULT FALSE,
    support_acts TEXT,
    has_afterparty BOOLEAN DEFAULT FALSE,
    ticket_link VARCHAR(500),
    button_text VARCHAR(100) DEFAULT 'Get Tickets',
    sold_out BOOLEAN DEFAULT FALSE,
    ticket_url VARCHAR(500),
    go_live_date DATETIME,
    age_restriction VARCHAR(50),
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE,
    FOREIGN KEY (artist_id) REFERENCES artists(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    INDEX idx_festival_date (festival_id, performance_date),
    INDEX idx_artist (artist_id),
    INDEX idx_venue (venue_id)
) ENGINE=InnoDB;

-- Accessibility options table
CREATE TABLE accessibility_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    festival_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('access', 'facilities', 'assistance', 'parking', 'other') DEFAULT 'other',
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE,
    INDEX idx_festival (festival_id)
) ENGINE=InnoDB;

-- Transport options table
CREATE TABLE transport_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    festival_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    transport_type ENUM('bus', 'train', 'taxi', 'car', 'bike', 'walk', 'other') DEFAULT 'other',
    provider VARCHAR(255),
    cost VARCHAR(100),
    duration VARCHAR(100),
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE,
    INDEX idx_festival (festival_id)
) ENGINE=InnoDB;

-- Ticket types table
CREATE TABLE ticket_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    festival_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2),
    currency VARCHAR(3) DEFAULT 'GBP',
    available_quantity INT,
    min_age INT,
    max_per_order INT DEFAULT 10,
    on_sale_date DATETIME,
    off_sale_date DATETIME,
    status ENUM('available', 'sold_out', 'coming_soon', 'expired') DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE,
    INDEX idx_festival_status (festival_id, status)
) ENGINE=InnoDB;

-- Age restrictions table
CREATE TABLE age_restrictions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    festival_id INT NOT NULL,
    min_age INT NOT NULL,
    description TEXT,
    requires_guardian BOOLEAN DEFAULT FALSE,
    guardian_min_age INT,
    proof_required TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (festival_id) REFERENCES festivals(id) ON DELETE CASCADE,
    INDEX idx_festival (festival_id)
) ENGINE=InnoDB;

-- Newsletter subscribers table
CREATE TABLE newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME NULL,
    status ENUM('active', 'unsubscribed', 'bounced') DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- FAQs table
CREATE TABLE faqs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venue_id INT NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
    INDEX idx_venue (venue_id)
) ENGINE=InnoDB;

-- Add foreign key constraint to festivals table
ALTER TABLE festivals
ADD CONSTRAINT fk_festival_venue
FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL;

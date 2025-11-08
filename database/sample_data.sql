-- Sample data for Shine Festivals Database
-- Based on Belsonic 2026 structure

USE shine_festivals;

-- Insert venue
INSERT INTO venues (id, name, address, city, country, postcode, capacity, description, latitude, longitude, is_active, primary_color, secondary_color, accent_color, bg_image_1, bg_image_2, bg_image_3, bg_image_4, bg_image_5, logo_url) VALUES
(1, 'Ormeau Park', 'Ormeau Road', 'Belfast', 'Northern Ireland', 'BT7 3GG', 20000, 'Historic park in South Belfast, perfect for outdoor festivals', 54.581870, -5.927420, FALSE, '#ff006f', '#00ffff', '#ff6400', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Belsonic', 'Ormeau Park, Ormeau Road', 'Belfast', 'Northern Ireland', 'BT7 3GG', 20000, 'Belfast''s premier outdoor music festival', 54.581870, -5.927420, TRUE, '#ff006f', '#f7931e', '#c13584', '/img/backgrounds/belsonic-1.jpg', '/img/backgrounds/belsonic-2.jpg', '/img/backgrounds/belsonic-3.jpg', '/img/backgrounds/belsonic-4.jpg', '/img/backgrounds/belsonic-5.jpg', '/img/assets/logo.png'),
(3, 'Custom House Square', 'Custom House Square', 'Belfast', 'Northern Ireland', 'BT1 3ET', 5000, 'Iconic city centre venue in the heart of Belfast', 54.602360, -5.920970, TRUE, '#1a1a2e', '#f7931e', '#ff6b35', '/img/backgrounds/chsq-1.jpg', '/img/backgrounds/chsq-2.jpg', '/img/backgrounds/chsq-3.jpg', NULL, NULL, '/img/assets/chsq-logo.png');

-- Insert festival
INSERT INTO festivals (name, description, year, start_date, end_date, venue_id, status, ticket_link) VALUES
('Belsonic 2026', 'Belfast''s premier outdoor music festival featuring international and local acts', 2026, '2026-06-18', '2026-06-21', 2, 'upcoming', 'https://www.ticketmaster.ie');

-- Insert sample artists
INSERT INTO artists (name, bio, genre, image_url) VALUES
('The Strokes', 'American rock band from New York City', 'Indie Rock', '/img/artists/the-strokes.jpg'),
('Arctic Monkeys', 'English rock band formed in Sheffield', 'Indie Rock', '/img/artists/arctic-monkeys.jpg'),
('Disclosure', 'English electronic music duo', 'Electronic', '/img/artists/disclosure.jpg'),
('Stormzy', 'British rapper, singer and songwriter', 'Grime/Hip Hop', '/img/artists/stormzy.jpg'),
('Local Support Act 1', 'Rising Belfast talent', 'Alternative', '/img/artists/local-1.jpg'),
('Local Support Act 2', 'Emerging Northern Irish band', 'Rock', '/img/artists/local-2.jpg');

-- Insert performances (linking artists to dates)
INSERT INTO performances (festival_id, artist_id, venue_id, performance_date, performance_time, is_headliner, supporting_act, sort_order) VALUES
-- Day 1 - June 18
(1, 1, 2, '2026-06-18', '21:00:00', TRUE, FALSE, 1),
(1, 5, 2, '2026-06-18', '19:00:00', FALSE, TRUE, 2),
-- Day 2 - June 19
(1, 2, 2, '2026-06-19', '21:00:00', TRUE, FALSE, 1),
(1, 6, 2, '2026-06-19', '19:00:00', FALSE, TRUE, 2),
-- Day 3 - June 20
(1, 3, 2, '2026-06-20', '21:00:00', TRUE, FALSE, 1),
-- Day 4 - June 21
(1, 4, 2, '2026-06-21', '21:00:00', TRUE, FALSE, 1);

-- Insert accessibility options
INSERT INTO accessibility_options (festival_id, title, description, category, icon, sort_order) VALUES
(1, 'Wheelchair Access', 'The festival site is fully wheelchair accessible with dedicated viewing platforms', 'access', 'wheelchair', 1),
(1, 'Accessible Toilets', 'Accessible toilet facilities available throughout the venue', 'facilities', 'restroom', 2),
(1, 'BSL Interpreters', 'British Sign Language interpreters available on request', 'assistance', 'sign-language', 3),
(1, 'Accessible Parking', 'Dedicated accessible parking spaces near main entrance', 'parking', 'parking', 4),
(1, 'Assistance Dogs', 'Assistance dogs welcome at all areas of the festival', 'access', 'dog', 5);

-- Insert transport options
INSERT INTO transport_options (festival_id, title, description, transport_type, provider, cost, duration, icon, sort_order) VALUES
(1, 'Metro Bus', 'Regular bus services from Belfast city centre to Ormeau Road', 'bus', 'Translink', '£2.50', '15 mins', 'bus', 1),
(1, 'Train', 'Nearest station: Belfast Central (20 minute walk)', 'train', 'Translink', '£5-15', '30 mins total', 'train', 2),
(1, 'Taxi', 'Taxi rank available at festival entrance', 'taxi', 'Various', '£8-12', '10 mins', 'taxi', 3),
(1, 'Car Parking', 'Limited parking available nearby. Pre-booking recommended', 'car', 'NCP', '£10-15', 'N/A', 'car', 4);

-- Insert ticket types
INSERT INTO ticket_types (festival_id, name, description, price, currency, min_age, status) VALUES
(1, 'General Admission', 'Standard entry to the festival', 45.00, 'GBP', 16, 'available'),
(1, 'VIP Experience', 'Premium viewing area, dedicated bar and toilets', 85.00, 'GBP', 18, 'available'),
(1, 'Day Ticket', 'Access for single day', 25.00, 'GBP', 16, 'available'),
(1, 'Weekend Pass', 'Access to all 4 days', 120.00, 'GBP', 16, 'available');

-- Insert age restrictions
INSERT INTO age_restrictions (festival_id, min_age, description, requires_guardian, guardian_min_age, proof_required) VALUES
(1, 16, 'This is a 16+ event. Under 18s must be accompanied by an adult 25+', TRUE, 25, 'Valid photo ID required for entry. Acceptable forms: Passport, Driver\'s License, PASS card');

// 3D Map - Mapbox GL JS Implementation
// Requires MAPBOX_TOKEN to be set in window.mapboxConfig

(function() {
    'use strict';

    // Check if Mapbox is available
    if (typeof mapboxgl === 'undefined') {
        console.warn('Mapbox GL JS not loaded');
        return;
    }

    // Configuration
    const config = window.mapboxConfig || {};
    const MAPBOX_TOKEN = config.token || '';

    if (!MAPBOX_TOKEN) {
        console.warn('Mapbox token not configured');
        return;
    }

    mapboxgl.accessToken = MAPBOX_TOKEN;

    // Venue coordinates (Ormeau Park, Belfast)
    const venueCenter = config.center || [-5.9167, 54.5833];
    const venueName = config.venueName || 'Venue';

    // Wait for DOM
    document.addEventListener('DOMContentLoaded', function() {
        const mapContainer = document.getElementById('map-3d-container');
        if (!mapContainer) return;

        // Initialize map
        const map = new mapboxgl.Map({
            container: 'map-3d-container',
            style: 'mapbox://styles/mapbox/dark-v11',
            center: venueCenter,
            zoom: 15.5,
            pitch: 60,
            bearing: -17.6,
            antialias: true,
            interactive: true
        });

        // Add navigation controls
        map.addControl(new mapboxgl.NavigationControl(), 'top-right');

        // Add venue marker
        const markerEl = document.createElement('div');
        markerEl.className = 'venue-marker-3d';
        markerEl.innerHTML = `
            <div class="marker-pulse"></div>
            <div class="marker-pin">
                <i class="las la-map-marker"></i>
            </div>
        `;

        new mapboxgl.Marker(markerEl)
            .setLngLat(venueCenter)
            .addTo(map);

        // Add 3D buildings when style loads
        map.on('style.load', () => {
            // Add 3D building layer
            const layers = map.getStyle().layers;
            const labelLayerId = layers.find(
                (layer) => layer.type === 'symbol' && layer.layout['text-field']
            )?.id;

            map.addLayer(
                {
                    'id': '3d-buildings',
                    'source': 'composite',
                    'source-layer': 'building',
                    'filter': ['==', 'extrude', 'true'],
                    'type': 'fill-extrusion',
                    'minzoom': 14,
                    'paint': {
                        // Building color - matches site aesthetic
                        'fill-extrusion-color': [
                            'interpolate',
                            ['linear'],
                            ['get', 'height'],
                            0, '#1a1a2e',
                            50, '#2d2d44',
                            100, '#3d3d5c'
                        ],
                        // Height based on data
                        'fill-extrusion-height': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            14, 0,
                            15.5, ['get', 'height']
                        ],
                        'fill-extrusion-base': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            14, 0,
                            15.5, ['get', 'min_height']
                        ],
                        // Opacity - buildings fade based on distance from center
                        'fill-extrusion-opacity': 0.8
                    }
                },
                labelLayerId
            );

            // Add subtle glow effect layer for venue area
            map.addLayer({
                'id': 'venue-glow',
                'type': 'circle',
                'source': {
                    'type': 'geojson',
                    'data': {
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': venueCenter
                        }
                    }
                },
                'paint': {
                    'circle-radius': 150,
                    'circle-color': 'rgba(255, 0, 111, 0.15)',
                    'circle-blur': 1
                }
            }, '3d-buildings');

        });

        // Handle tab visibility
        document.querySelectorAll('.location-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                setTimeout(() => {
                    map.resize();
                }, 100);
            });
        });
    });
})();

/**
 * Main JavaScript for Shine Festivals
 * Initialize background rotator and other components
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {

  // Get venue-specific background images from PHP
  let backgroundImages = window.venueBackgroundImages || [];

  // Log what we found
  if (backgroundImages.length === 0) {
    console.log('No background images configured for this venue');
  } else {
    console.log('Using venue-specific backgrounds:', backgroundImages.length + ' images');
  }

  // Only initialize if we have background images
  if (backgroundImages.length > 0) {
    // Load BackgroundRotator class from components
    const script = document.createElement('script');
    const basePath = window.basePath || '';
    script.src = basePath + '/scripts/components/background-rotator.js';
    script.onload = function() {
      const rotator = new BackgroundRotator(backgroundImages, 8000, 2000);
      rotator.init();

      // Store globally for debugging
      window.backgroundRotator = rotator;
    };
    document.head.appendChild(script);
  }

  // Burger Menu Toggle
  const burgerMenu = document.getElementById('burgerMenu');
  const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

  if (burgerMenu && mobileMenuOverlay) {
    burgerMenu.addEventListener('click', function() {
      burgerMenu.classList.toggle('active');
      mobileMenuOverlay.classList.toggle('active');
    });

    // Close menu when clicking on a link
    const mobileNavLinks = mobileMenuOverlay.querySelectorAll('.mobile-nav-menu a');
    mobileNavLinks.forEach(link => {
      link.addEventListener('click', function() {
        burgerMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
      });
    });

    // Close menu when clicking on overlay (outside menu container)
    mobileMenuOverlay.addEventListener('click', function(event) {
      if (event.target === mobileMenuOverlay) {
        burgerMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
      }
    });
  }

  console.log('Shine Festivals initialized');
});

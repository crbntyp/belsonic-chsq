// Card Deck Animation - Vanilla JS
class CardDeck {
    constructor() {
        this.cards = document.querySelectorAll('.card-slide');
        this.progressDots = document.querySelectorAll('.progress-dot');
        this.arrowUp = document.querySelector('.nav-arrow-up');
        this.arrowDown = document.querySelector('.nav-arrow-down');
        this.totalCards = this.cards.length;
        this.currentIndex = 0;
        this.isAnimating = false;
        this.scrollTimeout = null;

        // Scroll thresholds - each card gets a portion of the scroll space
        this.scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        this.cardThreshold = this.scrollHeight / (this.totalCards - 1);

        this.init();
    }

    init() {
        // Set initial state
        this.updateCards(0);
        this.updateArrows();

        // Bind scroll event with throttling
        window.addEventListener('scroll', () => this.handleScroll());

        // Bind progress dot clicks
        this.progressDots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.navigateToCard(index));
        });

        // Bind arrow clicks
        this.arrowUp.addEventListener('click', () => this.previousCard());
        this.arrowDown.addEventListener('click', () => this.nextCard());

        // Keyboard navigation (disable when on game slide)
        document.addEventListener('keydown', (e) => {
            // Don't navigate if we're on the game slide (index 4)
            if (this.currentIndex === 4) return;

            if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                e.preventDefault();
                this.previousCard();
            } else if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                e.preventDefault();
                this.nextCard();
            }
        });

        // Handle resize
        window.addEventListener('resize', () => this.handleResize());
    }

    previousCard() {
        if (this.currentIndex > 0 && !this.isAnimating) {
            this.navigateToCard(this.currentIndex - 1);
        }
    }

    nextCard() {
        if (this.currentIndex < this.totalCards - 1 && !this.isAnimating) {
            this.navigateToCard(this.currentIndex + 1);
        }
    }

    navigateToCard(index) {
        if (this.isAnimating) return;

        this.isAnimating = true;
        this.scrollToCard(index);

        // Reset animation lock after scroll completes
        setTimeout(() => {
            this.isAnimating = false;
        }, 800);
    }

    handleScroll() {
        // Clear existing timeout
        if (this.scrollTimeout) {
            clearTimeout(this.scrollTimeout);
        }

        const scrollPosition = window.scrollY;

        // Calculate which card should be active based on scroll position
        let newIndex = Math.round(scrollPosition / this.cardThreshold);

        // Clamp to valid range
        newIndex = Math.max(0, Math.min(newIndex, this.totalCards - 1));

        // Only update if index changed
        if (newIndex !== this.currentIndex) {
            this.currentIndex = newIndex;
            this.updateCards(newIndex);
            this.updateArrows();
        }

        // Snap to nearest card after scrolling stops
        this.scrollTimeout = setTimeout(() => {
            if (!this.isAnimating) {
                this.snapToCard(newIndex);
            }
        }, 150);
    }

    snapToCard(index) {
        const targetScroll = index * this.cardThreshold;
        const currentScroll = window.scrollY;

        // Only snap if we're not already at the target
        if (Math.abs(currentScroll - targetScroll) > 5) {
            window.scrollTo({
                top: targetScroll,
                behavior: 'smooth'
            });
        }
    }

    updateCards(activeIndex) {
        this.cards.forEach((card, index) => {
            // Remove all state classes
            card.classList.remove('active', 'prev-1', 'prev-2', 'prev-3', 'prev-4', 'hidden', 'upcoming');

            if (index === activeIndex) {
                // Current active card
                card.classList.add('active');
            } else if (index < activeIndex) {
                // Previous cards (behind the active card)
                const distance = activeIndex - index;
                if (distance === 1) {
                    card.classList.add('prev-1');
                } else if (distance === 2) {
                    card.classList.add('prev-2');
                } else if (distance === 3) {
                    card.classList.add('prev-3');
                } else if (distance === 4) {
                    card.classList.add('prev-4');
                } else {
                    card.classList.add('hidden');
                }
            } else {
                // Upcoming cards (ahead of active card)
                card.classList.add('upcoming');
            }
        });

        // Update progress dots
        this.updateProgressDots(activeIndex);
    }

    updateProgressDots(activeIndex) {
        this.progressDots.forEach((dot, index) => {
            if (index === activeIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }

    updateArrows() {
        // Disable up arrow at first slide
        if (this.currentIndex === 0) {
            this.arrowUp.disabled = true;
        } else {
            this.arrowUp.disabled = false;
        }

        // Disable down arrow at last slide
        if (this.currentIndex === this.totalCards - 1) {
            this.arrowDown.disabled = true;
        } else {
            this.arrowDown.disabled = false;
        }
    }

    scrollToCard(index) {
        const targetScroll = index * this.cardThreshold;

        window.scrollTo({
            top: targetScroll,
            behavior: 'smooth'
        });
    }

    handleResize() {
        // Recalculate scroll thresholds on window resize
        this.scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        this.cardThreshold = this.scrollHeight / (this.totalCards - 1);

        // Re-evaluate current position
        this.handleScroll();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const deck = new CardDeck();
});

// Optional: Add smooth easing for scroll position
// This can enhance the animation feel
function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
}

// Optional: Add parallax or advanced effects here
// Example: slight rotation or 3D transforms on cards

    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-nav">
                <ul>
                    <li><a href="index.php">Lineups</a></li>
                    <li><a href="location.php">Location</a></li>
                    <li><a href="info.php">General Info & Age Restrictions</a></li>
                    <?php if ($currentVenueId == 2): // Belsonic only ?>
                    <li><a href="accessibility.php">Accessibility</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php include __DIR__ . '/social-links.php'; ?>
            <p>&copy; 2026 <?php echo htmlspecialchars($venueName ?? 'Festival'); ?>. All rights reserved.</p>
            <div class="app-credit">
                <span class="app-credit-logo">crbntyp</span>
            </div>
        </div>
    </footer>
    <!-- Newsletter Signup Widget -->
    <div class="newsletter-widget" id="newsletterTrigger">
        <i class="las la-envelope newsletter-icon"></i>
        <div class="newsletter-text">
            <span class="newsletter-title">Newsletter Sign Up</span>
        </div>
    </div>

    <!-- Newsletter Modal -->
    <div class="newsletter-modal" id="newsletterModal">
        <div class="newsletter-modal-overlay"></div>
        <div class="newsletter-modal-content">
            <button class="newsletter-modal-close" id="newsletterClose">
                <i class="las la-times"></i>
            </button>
            <div class="newsletter-modal-header">
                <i class="las la-envelope"></i>
                <h2>Newsletter Sign Up</h2>
                <p>Subscribe now for updates & latest news</p>
            </div>
            <form id="subscribeForm" class="newsletter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="subscribeFirstName">First Name</label>
                        <input type="text" id="subscribeFirstName" name="firstName" placeholder="Enter your first name" required>
                    </div>
                    <div class="form-group">
                        <label for="subscribeLastName">Last Name</label>
                        <input type="text" id="subscribeLastName" name="lastName" placeholder="Enter your last name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="subscribeEmail">Email Address</label>
                    <input type="email" id="subscribeEmail" name="email" placeholder="Enter your email address" required>
                </div>
                <button type="submit" class="btn btn-primary newsletter-submit" id="mailerSubscribeBtn">
                    <i class="las la-paper-plane"></i> Subscribe
                </button>
            </form>
            <div class="newsletter-success" id="newsletterSuccess" style="display: none;">
                <i class="las la-check-circle"></i>
                <p>Thank you - you have now been subscribed!</p>
            </div>
        </div>
    </div>

    <script src="<?php echo asset_url('scripts/mailer.js'); ?>"></script>

    <?php if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false): ?>
    <script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] + ':35729/livereload.js?snipver=1"></' + 'script>')</script>
    <?php endif; ?>
</body>
</html>

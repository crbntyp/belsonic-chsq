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
            <p>&copy; 2026 <?php echo htmlspecialchars($venueName ?? 'Festival'); ?>. All rights reserved. | <a href="#" id="privacyTrigger">Privacy Policy</a></p>
            <div class="app-credit">
                <a href="https://crbntyp.com" class="app-credit-logo" target="_blank" rel="noopener">crbntyp</a>
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

    <!-- Privacy Policy Modal -->
    <div class="privacy-modal" id="privacyModal">
        <div class="privacy-modal-overlay"></div>
        <div class="privacy-modal-content">
            <button class="privacy-modal-close" id="privacyClose">
                <i class="las la-times"></i>
            </button>
            <div class="privacy-modal-header">
                <i class="las la-shield-alt"></i>
                <h2>Privacy Policy</h2>
            </div>
            <div class="privacy-modal-body">
                <p>In this policy, "we", "us" or "our" refers to <?php echo htmlspecialchars($venueName ?? 'the festival'); ?>. "The site" means this website. "You" and "your" refers to visitors and event registrants.</p>

                <h3>Personal Identification Information</h3>
                <p>We may collect personal identification information from you through registration and mailing list forms. This may include your name, email address, and phone number. Submission is voluntary, and you may visit our site anonymously. Refusing to provide information may prevent event registration.</p>

                <h3>Non-Personal Identification Information</h3>
                <p>We may collect non-personal identification information about you whenever you interact with our site. This may include the browser name, the type of computer and technical information about your means of connection to our site, such as the operating system.</p>

                <h3>Web Browser Cookies</h3>
                <p>Our site may use cookies to enhance your experience. You can configure your browser to refuse cookies, though some parts of the site may not function properly without them.</p>

                <h3>How We Use Your Information</h3>
                <p>We may use the information we collect from you for the following purposes:</p>
                <ul>
                    <li>To deliver requested services and event attendance</li>
                    <li>To provide event-related communications</li>
                    <li>To share information about additional events or services</li>
                    <li>To handle customer service, inquiries, and service notifications</li>
                </ul>

                <h3>Third-Party Sharing</h3>
                <p>We may share your personal information with selected third parties that we work with, where necessary for the purposes of delivering services to you.</p>

                <h3>Data Protection</h3>
                <p>We implement appropriate technological and operational security measures to protect your information. We retain information as long as necessary for service delivery or as required by law.</p>

                <h3>Policy Updates</h3>
                <p>We reserve the right to update this privacy policy at any time. Any changes will be reflected in the revision date below.</p>

                <p><em>Last updated: November 2025</em></p>
            </div>
        </div>
    </div>

    <script>
    // Privacy Modal
    (function() {
        const trigger = document.getElementById('privacyTrigger');
        const modal = document.getElementById('privacyModal');
        const closeBtn = document.getElementById('privacyClose');
        const overlay = modal.querySelector('.privacy-modal-overlay');

        function openModal(e) {
            e.preventDefault();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        trigger.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });
    })();
    </script>

    <script src="<?php echo asset_url('scripts/mailer.js'); ?>"></script>

    <?php if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false): ?>
    <script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] + ':35729/livereload.js?snipver=1"></' + 'script>')</script>
    <?php endif; ?>
</body>
</html>

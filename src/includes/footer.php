    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-nav">
                <ul>
                    <li><a href="index.php">Lineups</a></li>
                    <li><a href="location.php">Location</a></li>
                    <li><a href="info.php">General Info & Age Restrictions</a></li>
                    <li><a href="accessibility.php">Accessibility</a></li>
                </ul>
            </div>
            <?php include __DIR__ . '/social-links.php'; ?>
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($venueName ?? 'Festival'); ?>. All rights reserved. | <a href="#" id="privacyTrigger">Privacy Policy</a></p>
            <div class="app-credit">
                <!-- Shared crbntyp wordmark (_tools/wordmark/crbntyp.svg): outlines with the cut
                     baked in, so no Britanica download and no clip-path drift. -->
                <a href="https://crbntyp.com" class="app-credit-logo" target="_blank" rel="noopener" aria-label="crbntyp">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -781 3229 565.0" role="img" aria-label="crbntyp">
                      <title>crbntyp</title>
                      <path fill="currentColor" d="M18 -228Q18 -332 83.0 -396.0Q148 -460 261 -460Q320 -460 364.5 -444.0Q409 -428 430.5 -405.5Q452 -383 465.5 -360.5Q479 -338 482 -322L484 -306H369L367 -314Q365 -318 357.5 -329.5Q350 -341 339.5 -350.0Q329 -359 308.0 -366.0Q287 -373 261 -373Q199 -373 165.5 -333.5Q132 -294 132.0 -228.0Q132 -162 165.5 -119.5Q199 -77 261 -77Q302 -77 329.0 -98.5Q356 -120 362 -141L369 -162H484Q483 -155 481.0 -143.5Q479 -132 465.0 -102.5Q451 -73 429.5 -50.0Q408 -27 363.5 -8.5Q319 10 261 10Q146 10 82.0 -54.0Q18 -118 18 -228Z M643 -450V-382Q684 -450 764 -450H817V-356H767Q692 -356 669.5 -322.0Q647 -288 646 -180V0H536V-450Z M981 -643V-392Q1032 -456 1118 -459Q1124 -459 1131 -459Q1237 -459 1300.0 -394.0Q1363 -329 1363 -223Q1363 -115 1299.5 -52.5Q1236 10 1135 10Q1124 10 1113 9Q1028 5 978 -64V0H871V-643ZM1015 -118Q1050 -78 1112 -78Q1173 -78 1211.5 -118.0Q1250 -158 1250 -223Q1250 -292 1212.0 -332.0Q1174 -372 1112.0 -372.0Q1050 -372 1014.5 -334.0Q979 -296 979 -225Q979 -158 1015 -118Z M1417 -450H1523V-381Q1567 -459 1660 -460Q1664 -460 1668 -460Q1772 -460 1818.5 -405.0Q1865 -350 1865 -242V0H1755V-242Q1755 -308 1731.0 -338.5Q1707 -369 1655 -370Q1653 -370 1652 -370Q1589 -370 1558.0 -331.5Q1527 -293 1527 -213V0H1417Z M1902 -356V-450H1962V-581H2073V-450H2182V-356H2073V-124Q2073 -107 2079.0 -100.5Q2085 -94 2102 -94H2182V0H2102Q2024 0 1993.0 -25.5Q1962 -51 1962 -124V-356Z M2208 -450H2324L2443 -133L2567 -450H2683L2476 59Q2460 98 2451.0 115.0Q2442 132 2424.5 151.0Q2407 170 2383.0 176.5Q2359 183 2321 183H2244V89H2321Q2343 89 2350.0 84.0Q2357 79 2367 59L2393 -7Z M2719 -450H2826V-388Q2876 -456 2965 -459Q2971 -459 2978 -459Q3084 -459 3147.5 -394.0Q3211 -329 3211 -223Q3211 -115 3147.0 -52.5Q3083 10 2982 10Q2971 10 2960 9Q2879 5 2829 -60V183H2719ZM2826 -225Q2826 -158 2862.0 -118.0Q2898 -78 2960 -78Q3020 -78 3059.0 -118.0Q3098 -158 3098 -223Q3098 -292 3060.0 -332.0Q3022 -372 2960 -372Q2897 -372 2861.5 -334.0Q2826 -296 2826 -225Z"/>
                    </svg>
                </a>
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

    <script src="<?php echo versioned_asset_url('scripts/mailer.js'); ?>"></script>

    <?php if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false): ?>
    <script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] + ':35729/livereload.js?snipver=1"></' + 'script>')</script>
    <?php endif; ?>
</body>
</html>

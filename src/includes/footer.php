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
    <script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] + ':35729/livereload.js?snipver=1"></' + 'script>')</script>
</body>
</html>

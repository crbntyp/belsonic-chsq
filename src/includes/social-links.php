<?php
// Reusable social links component
// Expects $venue array to be available in the scope
if (!empty($venue['facebook_url']) || !empty($venue['twitter_url']) || !empty($venue['instagram_url'])): ?>
<div class="social-links">
    <?php if (!empty($venue['facebook_url'])): ?>
        <a href="<?php echo htmlspecialchars($venue['facebook_url']); ?>" aria-label="Facebook" target="_blank"><i class="lab la-facebook"></i></a>
    <?php endif; ?>
    <?php if (!empty($venue['twitter_url'])): ?>
        <a href="<?php echo htmlspecialchars($venue['twitter_url']); ?>" aria-label="Twitter" target="_blank"><i class="lab la-twitter"></i></a>
    <?php endif; ?>
    <?php if (!empty($venue['instagram_url'])): ?>
        <a href="<?php echo htmlspecialchars($venue['instagram_url']); ?>" aria-label="Instagram" target="_blank"><i class="lab la-instagram"></i></a>
    <?php endif; ?>
</div>
<?php endif; ?>

    </div>

    <footer class="text-center text-muted small py-3 d-none d-lg-block">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?> — Manga Trading System
    </footer>
</div>

<!-- Mobile bottom tab bar -->
<nav class="bottom-nav d-lg-none">
    <?php foreach ($__navItems as $item): ?>
        <a href="<?= BASE_URL ?>/index.php?url=<?= $item['url'] ?>"
           class="<?= __manga_is_active($item, $__seg, $__full) ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?>"></i>
            <span><?= $item['short'] ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/js/app.js"></script>
</body>
</html>

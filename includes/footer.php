<?php
/**
 * App layout — footer, mobile bottom navigation and scripts.
 */
clear_old();
?>
    </main>
  </div>
</div>

<nav class="bottom-nav">
  <?php foreach (bottom_nav_items($user->getRole()) as [$key, $label, $href, $ico]): ?>
    <a class="<?= ($active ?? '') === $key ? 'active' : '' ?>" href="<?= url($href) ?>"><?= icon($ico, '', 22) ?><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
</nav>

<script src="<?= asset('assets/js/app.js') ?>"></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
  <script src="<?= asset($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>

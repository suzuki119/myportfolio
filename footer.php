    <footer class="footer">

    <!-- ⑥ CONTACT ──────────────────────── -->
    <section class="contact">
      <h2 class="contact__title">Contact</h2>
      <div class="contact__email-wrap">
        <a href="mailto:suzukiyutaro119@gmail.com" class="contact__email">suzukiyutaro119@gmail.com</a>
        <button class="contact__copy" data-email="suzukiyutaro119@gmail.com" aria-label="メールアドレスをコピー">
          <img src="./img/copy.webp" alt="copy">
        </button>
      </div>

      <div class="contact__links">
        <a href="https://github.com/suzuki119/" class="contact__link">GitHub</a>
      </div>
        <div class="copyright">
            <a href="https://github.com/suzuki119/" class="contact__link"><div class="footer__octopus"><img src="./img/octopus.webp" alt="thanks"></div></a>
            <small>&copy;Suzuki Yutaro Portfolio</small>
          </div>
    </section>

  </main>
</footer>

<?php // three.js は bg.js が importmap 経由でESM版を読み込むため、ここでの読み込みは不要 ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<script src="script.js" defer></script>

<?php // 背景の3Dは重い（zintai.glb が約3.2MB）ため、TOPページでのみ読み込む ?>
<?php if (!empty($use_bg_3d)): ?>
<script type="module" src="bg.js"></script>
<?php endif; ?>

</body>
</html>

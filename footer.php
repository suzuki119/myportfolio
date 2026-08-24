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

<?php
// [three.js] script.js の背景クリスタルが global の THREE を使うので、script.js より前に読む。
// 読み込むのは $use_bg_3d を立てたページだけ（＝ index.php）。
// script.js 側は main.top の有無で判定しているので、両方そろって初めて描画される。
?>
<?php if (!empty($use_bg_3d)): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<?php
// 更新日時をクエリに付けて、書き換えたときだけブラウザに取り直させる。
// これがないと、修正しても古いキャッシュが実行され続けて原因が分からなくなる。
$script_ver = @filemtime(__DIR__ . '/script.js');
?>
<script src="script.js<?= $script_ver ? '?v=' . $script_ver : '' ?>" defer></script>


</body>
</html>

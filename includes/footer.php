<!-- ── Footer ── -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand"><i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado</div>
        <p>Salgados crocantes e doces irresistíveis, feitos com amor e carinho artesanal para tornar sua festa inesquecível.</p>
        <div class="flex gap-1 mt-3">
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.2rem;"><i class="bi bi-instagram"></i></a>
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.2rem; margin-left:.75rem;"><i class="bi bi-whatsapp"></i></a>
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.2rem; margin-left:.75rem;"><i class="bi bi-facebook"></i></a>
        </div>
      </div>
      <div>
        <h6>Navegação</h6>
        <ul class="footer-links">
          <li><a href="<?= $base ?? '' ?>/index.php"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Início</a></li>
          <li><a href="<?= $base ?? '' ?>/pages/salgados.php"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Salgados</a></li>
          <li><a href="<?= $base ?? '' ?>/pages/doces.php"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Doces</a></li>
          <li><a href="<?= $base ?? '' ?>/pages/pacotes.php"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Pacotes</a></li>
          <li><a href="<?= $base ?? '' ?>/pages/carrinho.php"><i class="bi bi-chevron-right" style="font-size:.7rem;"></i> Carrinho</a></li>
        </ul>
      </div>
      <div>
        <h6>Contato</h6>
        <ul class="footer-links">
          <li><a href="#"><i class="bi bi-telephone"></i> (11) 99999-9999</a></li>
          <li><a href="#"><i class="bi bi-envelope"></i> contato@docesalgado.com.br</a></li>
          <li><a href="#"><i class="bi bi-geo-alt"></i> São Paulo, SP</a></li>
          <li style="margin-top:.5rem; color:rgba(255,255,255,.4); font-size:.82rem;">
            <i class="bi bi-clock"></i> Seg–Sáb: 8h–18h
          </li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Doce &amp; Salgado. Todos os direitos reservados.</span>
      <span>Feito com <i class="bi bi-heart-fill" style="color:#F8BBD0;"></i> para você</span>
    </div>
  </div>
</footer>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- App JS -->
<script src="<?= $base ?? '' ?>/js/app.js"></script>
</body>
</html>
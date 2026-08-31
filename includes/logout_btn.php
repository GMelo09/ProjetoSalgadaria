<?php
/**
 * includes/logout_btn.php
 *
 * Inclua este partial em TODAS as navbars no lugar do antigo:
 *   <a href="login.php?sair=1">Sair</a>
 *
 * Uso:
 *   <?php require_once __DIR__ . '/../includes/logout_btn.php'; ?>
 *
 * O botão se parece com um link mas submete um form POST com CSRF.
 * O estilo pode ser sobrescrito via classe CSS no elemento pai.
 */
?>
<form action="<?= htmlspecialchars(appUrl('actions/logout.php'), ENT_QUOTES, 'UTF-8') ?>"
      method="POST"
      id="formLogout"
      style="display:inline;">
  <?= csrfField() ?>
  <button type="submit"
          class="dropdown-item"
          style="background:none;border:none;width:100%;text-align:left;cursor:pointer;padding:.25rem 1rem;">
    <i class="bi bi-box-arrow-right"></i> Sair
  </button>
</form>

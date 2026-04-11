<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/produto_class.php';
sessionStart();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finalizar Pedido | Doce &amp; Salgado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<!-- ══════════════════ NAVBAR ══════════════════ -->
<header>
  <nav class="navbar-main">
    <div class="container">
      <a href="../index.php" class="nav-brand">
        <i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado
      </a>
      <ul class="nav-links" id="navLinks">
        <li><a href="../index.php"><i class="bi bi-house"></i> Início</a></li>
        <li><a href="salgados.php"><i class="bi bi-egg-fried"></i> Salgados</a></li>
        <li><a href="doces.php"><i class="bi bi-cup-hot"></i> Doces</a></li>
        <li><a href="pacotes.php"><i class="bi bi-box-seam"></i> Pacotes</a></li>
        <li>
          <a href="carrinho.php" class="nav-cart-btn">
            <i class="bi bi-cart3"></i> Carrinho
            <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
          </a>
        </li>
        <?php if (!empty($_SESSION['usuario_nome'])): ?>
          <li class="dropdown">
            <a href="#" data-bs-toggle="dropdown" role="button">
              <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
              <i class="bi bi-chevron-down" style="font-size:.65rem;"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (!empty($_SESSION['eh_admin'])): ?>
                <li><a class="dropdown-item" href="../admin/dashboard.php"><i class="bi bi-speedometer2"></i> Painel Admin</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li>
                <form action="../actions/logout.php" method="POST" style="margin:0;">
                  <?= csrfField() ?>
                  <button type="submit" class="dropdown-item" style="background:none;border:none;width:100%;text-align:left;">
                    <i class="bi bi-box-arrow-right"></i> Sair
                  </button>
                </form>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li><a href="login.php"><i class="bi bi-person"></i> Entrar</a></li>
        <?php endif; ?>
      </ul>
      <button class="navbar-toggler-main" id="navToggler" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>

<!-- ══════════════════ PAGE HEADER ══════════════════ -->
<div class="page-header" style="background:linear-gradient(135deg,var(--rose-dark),var(--rose));">
  <div class="container">
    <div class="breadcrumb">
      <a href="../index.php">Início</a><span>/</span>
      <a href="carrinho.php">Carrinho</a><span>/</span>
      <span>Finalizar Pedido</span>
    </div>
    <h1>Finalizar Pedido</h1>
    <p>Você está quase lá! Preencha os dados abaixo para confirmar.</p>
  </div>
</div>

<!-- ══════════════════ STEPPER ══════════════════ -->
<div class="checkout-progress">
  <div class="container">
    <div class="checkout-steps">
      <span class="step done"><i class="bi bi-check-circle-fill"></i> Carrinho</span>
      <span class="step-arrow">›</span>
      <span class="step active"><i class="bi bi-pencil-square"></i> Dados</span>
      <span class="step-arrow">›</span>
      <span class="step"><i class="bi bi-patch-check"></i> Confirmação</span>
    </div>
  </div>
</div>

<!-- ══════════════════ MAIN ══════════════════ -->
<section class="section">
  <div class="container">
    <div id="checkoutLayout">

      <!-- ── Formulário ── -->
      <div class="checkout-form">

        <!-- Bloco: Dados pessoais -->
        <div class="form-block">
          <div class="form-block-header">
            <span class="form-block-icon"><i class="bi bi-person-fill"></i></span>
            <div>
              <h4 class="form-block-title">Dados Pessoais</h4>
              <p class="form-block-sub">Quem vai receber o pedido?</p>
            </div>
          </div>

          <form id="checkoutForm" action="../actions/pedido_cadastrar.php" method="POST">
            <?= csrfField() ?>
            <input type="hidden" id="itensInput" name="itens_carrinho">
            <?php if (!empty($_SESSION['usuario_id'])): ?>
              <input type="hidden" name="usuario_id" value="<?= (int) $_SESSION['usuario_id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label">Nome Completo <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-person input-icon"></i>
                <input type="text" class="form-control input-with-icon" name="nome" required maxlength="100"
                  placeholder="Seu nome completo"
                  value="<?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Telefone / WhatsApp <span class="required">*</span></label>
                <div class="input-icon-wrap">
                  <i class="bi bi-telephone input-icon"></i>
                  <input type="tel" class="form-control input-with-icon" name="telefone" id="telInput" required
                    maxlength="20" placeholder="(11) 99999-9999">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">E-mail <span class="label-optional">opcional</span></label>
                <div class="input-icon-wrap">
                  <i class="bi bi-envelope input-icon"></i>
                  <input type="email" class="form-control input-with-icon" name="email" maxlength="150" placeholder="seu@email.com">
                </div>
              </div>
            </div>
        </div><!-- /form-block -->

        <hr class="divider">

        <!-- Bloco: Endereço com ViaCEP -->
        <div class="form-block">
          <div class="form-block-header">
            <span class="form-block-icon"><i class="bi bi-geo-alt-fill"></i></span>
            <div>
              <h4 class="form-block-title">Endereço de Entrega</h4>
              <p class="form-block-sub">Digite o CEP para preencher automaticamente.</p>
            </div>
          </div>

          <!-- CEP Row -->
          <div class="form-row cep-row">
            <div class="form-group cep-group">
              <label class="form-label">CEP <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-search input-icon" id="cepIcon"></i>
                <input type="text" class="form-control input-with-icon" name="cep" id="cepInput"
                  required maxlength="9" placeholder="00000-000"
                  autocomplete="postal-code">
                <div class="cep-spinner" id="cepSpinner" style="display:none;">
                  <div class="spinner-border spinner-border-sm text-rose" role="status"></div>
                </div>
              </div>
              <span class="cep-feedback" id="cepFeedback"></span>
            </div>
            <div class="form-group" style="align-self:flex-end;">
              <a href="https://buscacepinter.correios.com.br/" target="_blank" rel="noopener"
                 class="btn-cep-link">
                <i class="bi bi-question-circle"></i> Não sei meu CEP
              </a>
            </div>
          </div>

          <!-- Campos preenchidos pelo ViaCEP -->
          <div id="enderecoFields" class="cep-fields-wrap" style="display:none;">
            <div class="cep-success-bar" id="cepSuccessBar">
              <i class="bi bi-check-circle-fill"></i>
              <span id="cepSuccessMsg">Endereço encontrado!</span>
            </div>

            <div class="form-row">
              <div class="form-group flex-3">
                <label class="form-label">Logradouro <span class="required">*</span></label>
                <input type="text" class="form-control" name="logradouro" id="logradouro" required maxlength="200" placeholder="Rua / Av. / Alameda">
              </div>
              <div class="form-group flex-1">
                <label class="form-label">Número <span class="required">*</span></label>
                <input type="text" class="form-control" name="numero" id="numInput" required maxlength="10" placeholder="Nº" autocomplete="off">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Complemento <span class="label-optional">opcional</span></label>
                <input type="text" class="form-control" name="complemento" id="complemento" maxlength="100" placeholder="Apto, bloco, casa...">
              </div>
              <div class="form-group">
                <label class="form-label">Bairro <span class="required">*</span></label>
                <input type="text" class="form-control" name="bairro" id="bairro" required maxlength="100" placeholder="Bairro">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group flex-2">
                <label class="form-label">Cidade <span class="required">*</span></label>
                <input type="text" class="form-control" name="cidade" id="cidade" required maxlength="100" placeholder="Cidade" readonly>
              </div>
              <div class="form-group flex-1">
                <label class="form-label">UF</label>
                <input type="text" class="form-control" name="uf" id="uf" maxlength="2" placeholder="SP" readonly>
              </div>
            </div>

            <!-- Campo oculto para compatibilidade com o backend -->
            <input type="hidden" name="endereco" id="enderecoFull">
          </div>

        </div><!-- /form-block -->

        <hr class="divider">

        <!-- Bloco: Entrega -->
        <div class="form-block">
          <div class="form-block-header">
            <span class="form-block-icon"><i class="bi bi-calendar-event-fill"></i></span>
            <div>
              <h4 class="form-block-title">Data &amp; Observações</h4>
              <p class="form-block-sub">Quando deseja receber?</p>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Data de Entrega <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-calendar3 input-icon"></i>
                <input type="date" class="form-control input-with-icon" name="data_entrega" required
                  min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Observações <span class="label-optional">opcional</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-chat-left-text input-icon"></i>
                <input type="text" class="form-control input-with-icon" name="obs" maxlength="300"
                  placeholder="Alguma instrução especial?">
              </div>
            </div>
          </div>
        </div><!-- /form-block -->

        <hr class="divider">

        <!-- Bloco: Pagamento -->
        <div class="form-block">
          <div class="form-block-header">
            <span class="form-block-icon"><i class="bi bi-credit-card-2-front-fill"></i></span>
            <div>
              <h4 class="form-block-title">Forma de Pagamento <span class="required">*</span></h4>
              <p class="form-block-sub">Escolha como prefere pagar.</p>
            </div>
          </div>

          <div class="payment-options">
            <label class="payment-option" for="payPix">
              <input type="radio" name="forma_pagamento" id="payPix" value="pix" required>
              <div class="payment-icon">⚡</div>
              <div class="payment-label">PIX</div>
              <div class="payment-desc">Instantâneo</div>
            </label>
            <label class="payment-option" for="payDinheiro">
              <input type="radio" name="forma_pagamento" id="payDinheiro" value="dinheiro">
              <div class="payment-icon">💵</div>
              <div class="payment-label">Dinheiro</div>
              <div class="payment-desc">Na entrega</div>
            </label>
            <label class="payment-option" for="payCartao">
              <input type="radio" name="forma_pagamento" id="payCartao" value="cartao">
              <div class="payment-icon">💳</div>
              <div class="payment-label">Cartão</div>
              <div class="payment-desc">Na entrega</div>
            </label>
          </div>

          <div id="pixInfo" style="display:none;margin-top:1rem;" class="info-bar">
            <i class="bi bi-qr-code" style="font-size:1.1rem;"></i>
            <div>
              Chave PIX: <strong>(11) 99999-9999</strong><br>
              <small>Envie o comprovante pelo WhatsApp após o pedido.</small>
            </div>
          </div>
          <div id="trocoInfo" style="display:none;margin-top:1rem;">
            <div class="form-group" style="max-width:240px;">
              <label class="form-label">Troco para quanto? <span class="label-optional">opcional</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-currency-dollar input-icon"></i>
                <input type="text" class="form-control input-with-icon" name="troco" placeholder="R$ 0,00" maxlength="12">
              </div>
            </div>
          </div>
        </div><!-- /form-block -->

        <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:2rem;" id="submitBtn">
          <i class="bi bi-bag-check-fill"></i> Confirmar Pedido
        </button>

        </form><!-- /checkoutForm -->
      </div><!-- /checkout-form -->

      <!-- ── Resumo lateral ── -->
      <aside class="cart-summary" id="orderSummary">
        <h4 style="margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem;">
          <i class="bi bi-receipt" style="color:var(--rose);"></i> Resumo do Pedido
        </h4>

        <div id="checkoutItems">
          <p class="text-muted" style="font-size:.85rem;">Carregando itens...</p>
        </div>

        <div class="divider" style="margin:.75rem 0;"></div>

        <div class="summary-row total" style="margin-top:.5rem;">
          <span>Total</span>
          <span class="amount" id="checkoutTotal">R$ 0,00</span>
        </div>

        <div class="checkout-info-box" style="margin-top:1rem;">
          <p style="margin:0;">
            <i class="bi bi-shield-check" style="color:var(--success);"></i>
            Pedido confirmado por WhatsApp em até 30 min.<br>
            <i class="bi bi-truck" style="color:var(--choco-light);"></i>
            Entrega realizada no dia e horário combinados.
          </p>
        </div>

        <!-- Segurança / selos -->
        <div style="display:flex;gap:.75rem;margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--cream-dark);">
          <div class="trust-badge"><i class="bi bi-lock-fill"></i><span>Dados Seguros</span></div>
          <div class="trust-badge"><i class="bi bi-patch-check-fill"></i><span>Pedido Garantido</span></div>
        </div>
      </aside>

    </div><!-- /checkoutLayout -->
  </div>
</section>

<!-- ══════════════════ FOOTER ══════════════════ -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand"><i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado</div>
        <p>Salgados crocantes e doces irresistíveis, feitos com amor artesanal para tornar sua festa inesquecível.</p>
        <div class="footer-social">
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-whatsapp"></i></a>
          <a href="#"><i class="bi bi-facebook"></i></a>
        </div>
      </div>
      <div>
        <h6>Navegação</h6>
        <ul class="footer-links">
          <li><a href="../index.php">Início</a></li>
          <li><a href="salgados.php">Salgados</a></li>
          <li><a href="doces.php">Doces</a></li>
          <li><a href="pacotes.php">Pacotes</a></li>
          <li><a href="carrinho.php">Carrinho</a></li>
        </ul>
      </div>
      <div>
        <h6>Contato</h6>
        <ul class="footer-links">
          <li><a href="#"><i class="bi bi-telephone"></i> (11) 99999-9999</a></li>
          <li><a href="#"><i class="bi bi-envelope"></i> contato@docesalgado.com.br</a></li>
          <li><a href="#"><i class="bi bi-geo-alt"></i> São Paulo, SP</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Doce &amp; Salgado. Todos os direitos reservados.</span>
      <span>Feito com <i class="bi bi-heart-fill" style="color:#F8BBD0;"></i> para você</span>
    </div>
  </div>
</footer>

<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/app.js"></script>

<script>
/* ═══════════════════════════════════════════════════
   CHECKOUT SCRIPT
═══════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {

  /* — Redireciona se carrinho vazio — */
  const items = Cart.get();
  if (items.length === 0) { window.location.href = 'carrinho.php'; return; }

  /* — Renderiza itens no resumo — */
  let html = '';
  items.forEach(item => {
    html += `<div class="summary-row">
      <span style="font-size:.85rem;">${item.nome}
        <small style="color:var(--muted);">×${item.quantidade}</small>
      </span>
      <span style="font-size:.85rem;font-weight:600;">${fmtBRL(item.preco * item.quantidade)}</span>
    </div>`;
  });
  document.getElementById('checkoutItems').innerHTML = html;
  document.getElementById('checkoutTotal').textContent = fmtBRL(Cart.total());

  /* — Serializa itens antes de enviar o form — */
  document.getElementById('checkoutForm').addEventListener('submit', function (e) {
    // Monta endereço completo para o campo oculto (retrocompatibilidade)
    const campos = ['logradouro','numInput','complemento','bairro','cidade','uf'];
    const [logr, num, comp, bairro, cidade, uf] = campos.map(id => document.getElementById(id)?.value.trim() ?? '');
    const endFull = [logr, num, comp, bairro, cidade + (uf ? '/' + uf : '')].filter(Boolean).join(', ');
    document.getElementById('enderecoFull').value = endFull;
    document.getElementById('itensInput').value = JSON.stringify(items);
  });

  /* ── Máscara de telefone ── */
  document.getElementById('telInput').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 6)      v = `(${v.slice(0,2)}) ${v.slice(2,7)}-${v.slice(7)}`;
    else if (v.length > 2) v = `(${v.slice(0,2)}) ${v.slice(2)}`;
    else if (v.length > 0) v = `(${v}`;
    this.value = v;
  });

  /* ── Pagamento: PIX info / Troco ── */
  document.querySelectorAll('input[name="forma_pagamento"]').forEach(r => {
    r.addEventListener('change', () => {
      const val = document.querySelector('input[name="forma_pagamento"]:checked')?.value;
      document.getElementById('pixInfo').style.display    = val === 'pix'      ? 'flex' : 'none';
      document.getElementById('trocoInfo').style.display  = val === 'dinheiro' ? 'block' : 'none';
    });
  });

  /* ══════════════════════════════════════════
     ViaCEP — Busca automática de endereço
  ══════════════════════════════════════════ */
  const cepInput      = document.getElementById('cepInput');
  const cepSpinner    = document.getElementById('cepSpinner');
  const cepIcon       = document.getElementById('cepIcon');
  const cepFeedback   = document.getElementById('cepFeedback');
  const enderecoFields = document.getElementById('enderecoFields');
  const cepSuccessBar = document.getElementById('cepSuccessBar');
  const cepSuccessMsg = document.getElementById('cepSuccessMsg');

  /* Máscara de CEP */
  cepInput.addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 8);
    if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
    this.value = v;

    // Limpa feedback
    cepFeedback.textContent = '';
    cepFeedback.className = 'cep-feedback';

    // Busca quando tiver 8 dígitos
    if (v.replace(/\D/g, '').length === 8) buscarCEP(v);
  });

  async function buscarCEP(cep) {
    const rawCEP = cep.replace(/\D/g, '');

    // UI: loading
    cepIcon.style.display    = 'none';
    cepSpinner.style.display = 'flex';
    enderecoFields.style.display = 'none';
    cepInput.classList.remove('is-valid', 'is-invalid');

    try {
      const res  = await fetch(`https://viacep.com.br/ws/${rawCEP}/json/`);
      const data = await res.json();

      // CEP não encontrado
      if (data.erro) {
        setCepError('CEP não encontrado. Verifique e tente novamente.');
        return;
      }

      // Preenche campos
      document.getElementById('logradouro').value  = data.logradouro  || '';
      document.getElementById('bairro').value       = data.bairro      || '';
      document.getElementById('cidade').value       = data.localidade  || '';
      document.getElementById('uf').value           = data.uf          || '';
      document.getElementById('complemento').value  = '';

      // Exibe campos e mensagem de sucesso
      enderecoFields.style.display = 'block';
      cepSuccessMsg.textContent = `${data.localidade} — ${data.uf}`;
      cepSuccessBar.className = 'cep-success-bar success';

      // Foca no número
      setTimeout(() => document.getElementById('numInput').focus(), 80);

      // UI: sucesso
      cepInput.classList.add('is-valid');
      setCepFeedback('✓ Endereço encontrado', 'success');

    } catch {
      setCepError('Erro ao buscar CEP. Verifique sua conexão.');
    } finally {
      cepIcon.style.display    = 'block';
      cepSpinner.style.display = 'none';
    }
  }

  function setCepError(msg) {
    cepInput.classList.add('is-invalid');
    enderecoFields.style.display = 'none';
    setCepFeedback(msg, 'error');
  }

  function setCepFeedback(msg, type) {
    cepFeedback.textContent  = msg;
    cepFeedback.className    = `cep-feedback ${type}`;
  }

});
</script>



</body>
</html>
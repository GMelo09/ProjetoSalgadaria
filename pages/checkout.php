<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/delivery.php';
require_once __DIR__ . '/../classes/usuario_class.php';
sessionStart();

if (!isLoggedIn()) {
  redirectTo('pages/login.php?erro=login_obrigatorio&tab=cadastro');
}

$checkoutErrors = [
  'campos_obrigatorios' => 'Preencha os campos obrigatórios para continuar.',
  'cep_invalido'        => 'Informe um CEP válido para a entrega.',
  'cep_fora_area'       => deliveryConfig()['out_of_area_message'],
  'pagamento_invalido'  => 'Escolha uma forma de pagamento válida.',
  'prazo_minimo'        => 'A data escolhida não respeita o prazo mínimo de encomenda.',
  'horario_invalido'    => 'Escolha um horário de entrega dentro da janela disponível.',
  'servidor'            => 'Não foi possível concluir o pedido agora. Tente novamente.',
];

$erro = $_GET['erro'] ?? '';
$msgErro = $checkoutErrors[$erro] ?? '';

$usuarioObj = new Usuario();
$cliente = $usuarioObj->BuscarPorId((int) ($_SESSION['usuario_id'] ?? 0));
$cliente = is_array($cliente) ? $cliente : [];

$deliveryConfig = deliveryConfig();
$minimumDate = deliveryMinimumDate();
$minimumDateLabel = date('d/m/Y', strtotime($minimumDate));
$savedCep = deliveryFormatCep($cliente['cep'] ?? '');
$savedArea = deliveryFindArea($savedCep);
$savedAddressReady = !empty($cliente['logradouro']) && !empty($cliente['numero']) && !empty($cliente['bairro']) && !empty($cliente['cidade']) && !empty($cliente['uf']);
$deliveryAreasForJs = array_map(
  static fn(array $area): array => [
    'nome' => $area['nome'],
    'cep_inicio' => $area['cep_inicio'],
    'cep_fim' => $area['cep_fim'],
    'taxa' => (float) $area['taxa'],
  ],
  $deliveryConfig['areas'] ?? []
);
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
              <li><a class="dropdown-item" href="meus_pedidos.php"><i class="bi bi-bag-heart"></i> Meus Pedidos</a></li>
              <?php if (!empty($_SESSION['eh_admin'])): ?>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <?php if (!empty($_SESSION['eh_admin'])): ?>
                <li><a class="dropdown-item" href="../admin/dashboard.php"><i class="bi bi-speedometer2"></i> Painel Admin</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
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
    <?php if ($msgErro): ?>
      <div class="alert alert-error" style="margin-bottom:1.5rem;">
        <i class="bi bi-exclamation-octagon"></i> <?= htmlspecialchars($msgErro) ?>
      </div>
    <?php endif; ?>

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

            <div class="form-group">
              <label class="form-label">Nome Completo <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-person input-icon"></i>
                <input type="text" class="form-control input-with-icon" name="nome" required maxlength="100"
                  placeholder="Seu nome completo"
                  value="<?= htmlspecialchars($cliente['nome'] ?? $_SESSION['usuario_nome'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Telefone / WhatsApp <span class="required">*</span></label>
                <div class="input-icon-wrap">
                  <i class="bi bi-telephone input-icon"></i>
                  <input type="tel" class="form-control input-with-icon" name="telefone" id="telInput" required
                    maxlength="20" placeholder="(11) 99999-9999"
                    value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">E-mail <span class="label-optional">opcional</span></label>
                <div class="input-icon-wrap">
                  <i class="bi bi-envelope input-icon"></i>
                  <input type="email" class="form-control input-with-icon" name="email" maxlength="150" placeholder="seu@email.com"
                    value="<?= htmlspecialchars($cliente['email'] ?? '') ?>">
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
                  autocomplete="postal-code"
                  value="<?= htmlspecialchars($savedCep) ?>">
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

          <div class="delivery-area-card" id="deliveryAreaCard" style="display:none;">
            <div class="delivery-area-copy">
              <span class="delivery-area-label">Entrega por CEP</span>
              <strong id="deliveryAreaName">Área de entrega</strong>
              <p id="deliveryAreaHint">Informe um CEP válido para ver área atendida e taxa.</p>
            </div>
            <div class="delivery-fee-pill" id="deliveryAreaFee">A calcular</div>
          </div>

          <!-- Campos preenchidos pelo ViaCEP -->
          <div id="enderecoFields" class="cep-fields-wrap" style="display:<?= $savedAddressReady ? 'block' : 'none' ?>;">
            <div class="cep-success-bar" id="cepSuccessBar">
              <i class="bi bi-check-circle-fill"></i>
              <span id="cepSuccessMsg"><?= $savedAddressReady ? htmlspecialchars(trim(($cliente['cidade'] ?? '') . ' - ' . ($cliente['uf'] ?? ''))) : 'Endereço encontrado!' ?></span>
            </div>

            <div class="form-row">
              <div class="form-group flex-3">
                <label class="form-label">Logradouro <span class="required">*</span></label>
                <input type="text" class="form-control" name="logradouro" id="logradouro" required maxlength="200" placeholder="Rua / Av. / Alameda"
                  value="<?= htmlspecialchars($cliente['logradouro'] ?? '') ?>">
              </div>
              <div class="form-group flex-1">
                <label class="form-label">Número <span class="required">*</span></label>
                <input type="text" class="form-control" name="numero" id="numInput" required maxlength="20" placeholder="Nº" autocomplete="off"
                  value="<?= htmlspecialchars($cliente['numero'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Complemento <span class="label-optional">opcional</span></label>
                <input type="text" class="form-control" name="complemento" id="complemento" maxlength="100" placeholder="Apto, bloco, casa..."
                  value="<?= htmlspecialchars($cliente['complemento'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Bairro <span class="required">*</span></label>
                <input type="text" class="form-control" name="bairro" id="bairro" required maxlength="100" placeholder="Bairro"
                  value="<?= htmlspecialchars($cliente['bairro'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group flex-2">
                <label class="form-label">Cidade <span class="required">*</span></label>
                <input type="text" class="form-control" name="cidade" id="cidade" required maxlength="100" placeholder="Cidade" readonly
                  value="<?= htmlspecialchars($cliente['cidade'] ?? '') ?>">
              </div>
              <div class="form-group flex-1">
                <label class="form-label">UF</label>
                <input type="text" class="form-control" name="uf" id="uf" maxlength="2" placeholder="SP" readonly
                  value="<?= htmlspecialchars($cliente['uf'] ?? '') ?>">
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
              <h4 class="form-block-title">Data, Horário &amp; Observações</h4>
              <p class="form-block-sub">Defina quando deseja receber sua encomenda.</p>
            </div>
          </div>

          <div class="delivery-guidelines">
            <div class="delivery-guideline">
              <i class="bi bi-hourglass-split"></i>
              <div>
                <strong>Prazo mínimo</strong>
                <span>Pedidos com antecedência mínima até <strong><?= htmlspecialchars($minimumDateLabel) ?></strong>.</span>
              </div>
            </div>
            <div class="delivery-guideline">
              <i class="bi bi-clock-history"></i>
              <div>
                <strong>Janela de entrega</strong>
                <span>Escolha um horário entre <?= htmlspecialchars($deliveryConfig['time_min']) ?> e <?= htmlspecialchars($deliveryConfig['time_max']) ?>.</span>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Data de Entrega <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-calendar3 input-icon"></i>
                <input type="date" class="form-control input-with-icon" name="data_entrega" required
                  min="<?= htmlspecialchars($minimumDate) ?>"
                  value="<?= htmlspecialchars($minimumDate) ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Horário de Entrega <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="bi bi-clock input-icon"></i>
                <input type="time" class="form-control input-with-icon" name="horario_entrega" required
                  min="<?= htmlspecialchars($deliveryConfig['time_min']) ?>"
                  max="<?= htmlspecialchars($deliveryConfig['time_max']) ?>"
                  step="1800"
                  value="16:00">
              </div>
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

        <div class="summary-row">
          <span>Subtotal dos itens</span>
          <span id="checkoutSubtotal">R$ 0,00</span>
        </div>

        <div class="summary-row">
          <span>Taxa de entrega</span>
          <span id="checkoutDeliveryFee">Calcule pelo CEP</span>
        </div>

        <div class="summary-note" id="checkoutDeliveryMeta">
          A taxa e a área de entrega são definidas conforme o CEP informado.
        </div>

        <div class="summary-row total" style="margin-top:.5rem;">
          <span>Total previsto</span>
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
  const items = Cart.get();
  if (items.length === 0) {
    window.location.href = 'carrinho.php';
    return;
  }

  const checkoutForm = document.getElementById('checkoutForm');
  const subtotalEl = document.getElementById('checkoutSubtotal');
  const feeEl = document.getElementById('checkoutDeliveryFee');
  const totalEl = document.getElementById('checkoutTotal');
  const metaEl = document.getElementById('checkoutDeliveryMeta');
  const submitBtn = document.getElementById('submitBtn');
  const cepInput = document.getElementById('cepInput');
  const cepSpinner = document.getElementById('cepSpinner');
  const cepIcon = document.getElementById('cepIcon');
  const cepFeedback = document.getElementById('cepFeedback');
  const enderecoFields = document.getElementById('enderecoFields');
  const cepSuccessBar = document.getElementById('cepSuccessBar');
  const cepSuccessMsg = document.getElementById('cepSuccessMsg');
  const deliveryAreaCard = document.getElementById('deliveryAreaCard');
  const deliveryAreaName = document.getElementById('deliveryAreaName');
  const deliveryAreaHint = document.getElementById('deliveryAreaHint');
  const deliveryAreaFee = document.getElementById('deliveryAreaFee');
  const savedAddressReady = <?= $savedAddressReady ? 'true' : 'false' ?>;
  const savedCepValue = <?= json_encode($savedCep) ?>;
  const savedCity = <?= json_encode((string) ($cliente['cidade'] ?? '')) ?>;
  const savedUf = <?= json_encode((string) ($cliente['uf'] ?? '')) ?>;
  const deliveryConfig = {
    areas: <?= json_encode($deliveryAreasForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    outOfAreaMessage: <?= json_encode($deliveryConfig['out_of_area_message'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    timeMin: <?= json_encode($deliveryConfig['time_min']) ?>,
    timeMax: <?= json_encode($deliveryConfig['time_max']) ?>,
    minimumDateLabel: <?= json_encode($minimumDateLabel) ?>,
  };

  let currentDeliveryArea = null;
  let deliveryState = 'pending';

  renderOrderSummary();
  updateOrderTotals();

  checkoutForm.addEventListener('submit', event => {
    const rawCep = normalizeCep(cepInput.value);
    if (deliveryState === 'invalid' || rawCep.length !== 8 || !findDeliveryArea(rawCep)) {
      event.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Revise o CEP da entrega',
        text: deliveryConfig.outOfAreaMessage,
        confirmButtonColor: '#c2185b',
      });
      return;
    }

    const logr = document.getElementById('logradouro').value.trim();
    const numero = document.getElementById('numInput').value.trim();
    const comp = document.getElementById('complemento').value.trim();
    const bairro = document.getElementById('bairro').value.trim();
    const cidade = document.getElementById('cidade').value.trim();
    const uf = document.getElementById('uf').value.trim();
    const primeiraLinha = [logr, numero].filter(Boolean).join(', ');
    const enderecoPrincipal = comp ? `${primeiraLinha} - ${comp}` : primeiraLinha;
    const endFull = [enderecoPrincipal, bairro, cidade + (uf ? '/' + uf : '')].filter(Boolean).join(', ');

    document.getElementById('enderecoFull').value = endFull;
    document.getElementById('itensInput').value = JSON.stringify(items);
  });

  document.getElementById('telInput').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 11);
    if (v.length > 6) v = `(${v.slice(0, 2)}) ${v.slice(2, 7)}-${v.slice(7)}`;
    else if (v.length > 2) v = `(${v.slice(0, 2)}) ${v.slice(2)}`;
    else if (v.length > 0) v = `(${v}`;
    this.value = v;
  });

  document.querySelectorAll('input[name="forma_pagamento"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const val = document.querySelector('input[name="forma_pagamento"]:checked')?.value;
      document.getElementById('pixInfo').style.display = val === 'pix' ? 'flex' : 'none';
      document.getElementById('trocoInfo').style.display = val === 'dinheiro' ? 'block' : 'none';
    });
  });

  cepInput.addEventListener('input', function () {
    let v = normalizeCep(this.value).slice(0, 8);
    if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
    this.value = v;

    cepFeedback.textContent = '';
    cepFeedback.className = 'cep-feedback';

    if (normalizeCep(v).length < 8) {
      cepInput.classList.remove('is-valid', 'is-invalid');
      resetDeliveryAreaState();
      return;
    }

    buscarCEP(v);
  });

  hydrateSavedAddress();

  function renderOrderSummary() {
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
  }

  function updateOrderTotals() {
    const subtotal = Cart.total();
    subtotalEl.textContent = fmtBRL(subtotal);

    if (deliveryState === 'valid' && currentDeliveryArea) {
      feeEl.textContent = fmtBRL(currentDeliveryArea.taxa);
      totalEl.textContent = fmtBRL(subtotal + currentDeliveryArea.taxa);
      metaEl.textContent = `${currentDeliveryArea.nome} • Taxa calculada conforme o CEP informado.`;
      return;
    }

    totalEl.textContent = fmtBRL(subtotal);

    if (deliveryState === 'invalid') {
      feeEl.textContent = 'Indisponível';
      metaEl.textContent = deliveryConfig.outOfAreaMessage;
      return;
    }

    feeEl.textContent = 'Calcule pelo CEP';
    metaEl.textContent = 'A taxa e a área de entrega são definidas conforme o CEP informado.';
  }

  function setCheckoutBlocked(blocked, reason = '') {
    submitBtn.disabled = blocked;
    submitBtn.style.opacity = blocked ? '0.68' : '1';
    submitBtn.style.cursor = blocked ? 'not-allowed' : '';
    submitBtn.title = blocked ? reason : '';
  }

  function normalizeCep(cep) {
    return (cep || '').replace(/\D/g, '');
  }

  function formatCep(rawCep) {
    return rawCep.length === 8 ? `${rawCep.slice(0, 5)}-${rawCep.slice(5)}` : rawCep;
  }

  function findDeliveryArea(rawCep) {
    return deliveryConfig.areas.find(area => rawCep >= area.cep_inicio && rawCep <= area.cep_fim) || null;
  }

  function markCepFound(city, uf) {
    enderecoFields.style.display = 'block';
    cepSuccessMsg.textContent = `${city} — ${uf}`;
    cepSuccessBar.className = 'cep-success-bar success';
    cepInput.classList.remove('is-invalid');
    cepInput.classList.add('is-valid');
  }

  function setDeliveryArea(area, rawCep) {
    deliveryState = 'valid';
    currentDeliveryArea = area;
    deliveryAreaCard.style.display = 'flex';
    deliveryAreaCard.classList.remove('error');
    deliveryAreaName.textContent = area.nome;
    deliveryAreaHint.textContent = `Taxa para o CEP ${formatCep(rawCep)}. Entregas a partir de ${deliveryConfig.minimumDateLabel}, entre ${deliveryConfig.timeMin} e ${deliveryConfig.timeMax}.`;
    deliveryAreaFee.textContent = fmtBRL(area.taxa);
    setCheckoutBlocked(false);
    updateOrderTotals();
  }

  function setDeliveryUnavailable(message) {
    deliveryState = 'invalid';
    currentDeliveryArea = null;
    deliveryAreaCard.style.display = 'flex';
    deliveryAreaCard.classList.add('error');
    deliveryAreaName.textContent = 'CEP fora da área de entrega';
    deliveryAreaHint.textContent = message;
    deliveryAreaFee.textContent = 'Indisponível';
    setCheckoutBlocked(true, message);
    updateOrderTotals();
  }

  function resetDeliveryAreaState() {
    deliveryState = 'pending';
    currentDeliveryArea = null;
    deliveryAreaCard.style.display = 'none';
    deliveryAreaCard.classList.remove('error');
    setCheckoutBlocked(false);
    updateOrderTotals();
  }

  async function buscarCEP(cep) {
    const rawCEP = normalizeCep(cep);

    cepIcon.style.display = 'none';
    cepSpinner.style.display = 'flex';
    cepInput.classList.remove('is-valid', 'is-invalid');
    resetDeliveryAreaState();

    try {
      const res = await fetch(`https://viacep.com.br/ws/${rawCEP}/json/`);
      const data = await res.json();

      if (data.erro) {
        setCepError('CEP não encontrado. Verifique e tente novamente.');
        return;
      }

      document.getElementById('logradouro').value = data.logradouro || '';
      document.getElementById('bairro').value = data.bairro || '';
      document.getElementById('cidade').value = data.localidade || '';
      document.getElementById('uf').value = data.uf || '';
      if (rawCEP !== normalizeCep(savedCepValue)) {
        document.getElementById('numInput').value = '';
        document.getElementById('complemento').value = '';
      }

      markCepFound(data.localidade || 'Endereço encontrado', data.uf || '');

      const area = findDeliveryArea(rawCEP);
      if (area) {
        setDeliveryArea(area, rawCEP);
        setCepFeedback('✓ Endereço encontrado e entrega disponível.', 'success');
      } else {
        cepInput.classList.remove('is-valid');
        cepInput.classList.add('is-invalid');
        setDeliveryUnavailable(deliveryConfig.outOfAreaMessage);
        setCepFeedback('CEP localizado, mas fora da área de entrega.', 'error');
      }

      if (!document.getElementById('numInput').value.trim()) {
        setTimeout(() => document.getElementById('numInput').focus(), 80);
      }
    } catch {
      setCepError('Erro ao buscar CEP. Verifique sua conexão.');
    } finally {
      cepIcon.style.display = 'block';
      cepSpinner.style.display = 'none';
    }
  }

  function setCepError(msg) {
    cepInput.classList.add('is-invalid');
    if (!savedAddressReady || normalizeCep(cepInput.value) !== normalizeCep(savedCepValue)) {
      enderecoFields.style.display = 'none';
    }
    resetDeliveryAreaState();
    setCepFeedback(msg, 'error');
  }

  function setCepFeedback(msg, type) {
    cepFeedback.textContent = msg;
    cepFeedback.className = `cep-feedback ${type}`;
  }

  function hydrateSavedAddress() {
    const savedCep = normalizeCep(cepInput.value);
    if (savedCep.length !== 8) return;

    if (savedAddressReady) {
      markCepFound(savedCity || 'Endereço salvo', savedUf || '');
      setCepFeedback('✓ Endereço salvo pronto para uso.', 'success');
      const savedArea = findDeliveryArea(savedCep);

      if (savedArea) {
        setDeliveryArea(savedArea, savedCep);
      } else {
        cepInput.classList.remove('is-valid');
        cepInput.classList.add('is-invalid');
        setDeliveryUnavailable(deliveryConfig.outOfAreaMessage);
        setCepFeedback('O CEP salvo precisa ser atualizado para uma área atendida.', 'error');
      }
      return;
    }

    buscarCEP(cepInput.value);
  }

});
</script>



</body>
</html>

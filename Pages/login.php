<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$erros = [
    'nome_vazio'        => 'O nome é obrigatório.',
    'email_invalido'    => 'Informe um e-mail válido.',
    'email_vazio'       => 'O e-mail é obrigatório.',
    'email_existente'   => 'Este e-mail já está cadastrado.',
    'senha_vazia'       => 'A senha é obrigatória.',
    'senha_curta'       => 'A senha deve ter no mínimo 6 caracteres.',
    'senhas_diferentes' => 'As senhas não coincidem.',
    'credenciais'       => 'E-mail ou senha incorretos.',
    'servidor'          => 'Erro interno. Tente novamente.',
    'bloqueado'         => 'Muitas tentativas. Aguarde alguns minutos.',
];

$erro    = $_GET['erro'] ?? '';
$msgErro = $erros[$erro] ?? '';

if ($erro === 'bloqueado' && !empty($_GET['min'])) {
    $min     = max(1, (int) $_GET['min']);
    $msgErro = "Muitas tentativas. Aguarde {$min} minuto(s) antes de tentar novamente.";
}

$flash    = getFlash();
$tabAtiva = ($_GET['tab'] ?? 'login') === 'cadastro' ? 'cadastro' : 'login';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrar | Doce &amp; Salgado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
  <style>
    body { background: var(--white); }
    .auth-layout { min-height: calc(100vh - var(--nav-h) - 1px); }
  </style>
</head>
<body>

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
        <li><a href="login.php" class="active"><i class="bi bi-person"></i> Entrar</a></li>
      </ul>
      <button class="navbar-toggler-main" id="navToggler" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>

<div class="auth-layout">
  <div class="auth-aside">
    <div class="auth-aside-content">
      <div style="font-size:3rem;margin-bottom:1rem;">🍩</div>
      <h2>Bem-vindo de volta!</h2>
      <p>Entre na sua conta para acompanhar pedidos, salvar endereços e garantir sua encomenda mais rápido.</p>
      <div class="auth-aside-features">
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="bi bi-clock-history"></i></div>
          <span>Histórico de pedidos completo</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="bi bi-geo-alt"></i></div>
          <span>Endereços salvos para entrega rápida</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="bi bi-bell"></i></div>
          <span>Notificações sobre seu pedido</span>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon"><i class="bi bi-star"></i></div>
          <span>Ofertas e promoções exclusivas</span>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-main">
    <div style="max-width:400px;width:100%;margin:0 auto;">

      <a href="../index.php" style="display:flex;align-items:center;gap:.4rem;color:var(--muted);font-size:.82rem;margin-bottom:2rem;">
        <i class="bi bi-arrow-left"></i> Voltar para o início
      </a>

      <?php if ($msgErro): ?>
        <div class="alert alert-error mb-3">
          <i class="bi bi-x-circle"></i> <?= htmlspecialchars($msgErro) ?>
        </div>
      <?php endif; ?>

      <?php if ($flash && $flash['tipo'] === 'sucesso'): ?>
        <div class="alert alert-success mb-3">
          <i class="bi bi-check-circle"></i> <?= htmlspecialchars($flash['mensagem']) ?>
        </div>
      <?php endif; ?>

      <div class="auth-tabs" role="tablist">
        <button class="auth-tab <?= $tabAtiva === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">
          <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
        <button class="auth-tab <?= $tabAtiva === 'cadastro' ? 'active' : '' ?>" onclick="switchTab('cadastro')">
          <i class="bi bi-person-plus"></i> Criar Conta
        </button>
      </div>

      <div class="auth-panel <?= $tabAtiva === 'login' ? 'active' : '' ?>" id="panelLogin">
        <h3>Entrar na conta</h3>
        <p class="subtitle">Acesse seu histórico de pedidos e preferências</p>

        <form action="../actions/usuario_logar.php" method="POST">
          <?= csrfField() ?>
          <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" class="form-control" name="email" required placeholder="seu@email.com" autocomplete="email">
          </div>
          <div class="form-group">
            <label class="form-label">Senha</label>
            <div style="position:relative;">
              <input type="password" class="form-control" name="senha" id="loginSenha" required
                placeholder="••••••••" autocomplete="current-password">
              <button type="button" onclick="toggleSenha('loginSenha','toggleLoginIcon')"
                style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;">
                <i class="bi bi-eye" id="toggleLoginIcon"></i>
              </button>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-bottom:1.25rem;">
            <a href="#" style="font-size:.82rem;color:var(--muted);">Esqueci minha senha</a>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg">
            <i class="bi bi-box-arrow-in-right"></i> Entrar
          </button>
        </form>

        <p style="text-align:center;margin-top:1.5rem;font-size:.85rem;color:var(--muted);">
          Não tem conta?
          <button onclick="switchTab('cadastro')" style="background:none;border:none;color:var(--rose);font-weight:600;cursor:pointer;">
            Criar agora →
          </button>
        </p>
      </div>

      <div class="auth-panel <?= $tabAtiva === 'cadastro' ? 'active' : '' ?>" id="panelCadastro">
        <h3>Criar conta</h3>
        <p class="subtitle">Rápido e gratuito. Comece a encomendar hoje!</p>

        <form action="../actions/usuario_cadastrar.php" method="POST">
          <?= csrfField() ?>
          <div class="form-group">
            <label class="form-label">Nome completo <span class="required">*</span></label>
            <input type="text" class="form-control" name="nome" required maxlength="100" placeholder="Seu nome">
          </div>
          <div class="form-group">
            <label class="form-label">E-mail <span class="required">*</span></label>
            <input type="email" class="form-control" name="email" required maxlength="150" placeholder="seu@email.com" autocomplete="email">
          </div>
          <div class="form-group">
            <label class="form-label">Telefone / WhatsApp</label>
            <input type="tel" class="form-control" name="telefone" maxlength="20" placeholder="(11) 99999-9999" id="cadTel">
          </div>
          <div class="form-group">
            <label class="form-label">Senha <span class="required">*</span></label>
            <div style="position:relative;">
              <input type="password" class="form-control" name="senha" id="cadSenha" required minlength="6" placeholder="Mín. 6 caracteres">
              <button type="button" onclick="toggleSenha('cadSenha','toggleCadIcon')"
                style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;">
                <i class="bi bi-eye" id="toggleCadIcon"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar Senha <span class="required">*</span></label>
            <input type="password" class="form-control" name="senha_confirmacao" id="cadSenha2" required placeholder="Repita a senha">
          </div>
          <div id="senhaErro" class="alert alert-error mb-2" style="display:none;">
            <i class="bi bi-x-circle"></i> As senhas não coincidem.
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg">
            <i class="bi bi-person-plus"></i> Criar Conta
          </button>
        </form>

        <p style="text-align:center;margin-top:1.5rem;font-size:.85rem;color:var(--muted);">
          Já tem conta?
          <button onclick="switchTab('login')" style="background:none;border:none;color:var(--rose);font-weight:600;cursor:pointer;">
            Entrar →
          </button>
        </p>
      </div>

    </div>
  </div>
</div>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand"><i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado</div>
        <p>Salgados crocantes e doces irresistíveis, feitos com amor artesanal para tornar sua festa inesquecível.</p>
      </div>
      <div>
        <h6>Navegação</h6>
        <ul class="footer-links">
          <li><a href="../index.php">Início</a></li>
          <li><a href="salgados.php">Salgados</a></li>
          <li><a href="doces.php">Doces</a></li>
        </ul>
      </div>
      <div>
        <h6>Contato</h6>
        <ul class="footer-links">
          <li><a href="#"><i class="bi bi-telephone"></i> (11) 99999-9999</a></li>
          <li><a href="#"><i class="bi bi-envelope"></i> contato@docesalgado.com.br</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Doce &amp; Salgado.</span>
    </div>
  </div>
</footer>

<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/app.js"></script>
<script>
function switchTab(tab) {
  document.getElementById('panelLogin').classList.toggle('active', tab === 'login');
  document.getElementById('panelCadastro').classList.toggle('active', tab === 'cadastro');
  document.querySelectorAll('.auth-tab').forEach((btn, i) => {
    btn.classList.toggle('active', (i === 0) === (tab === 'login'));
  });
}

function toggleSenha(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const isPassword = inp.type === 'password';
  inp.type = isPassword ? 'text' : 'password';
  document.getElementById(iconId).className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
}

function validarSenhas() {
  const s1 = document.getElementById('cadSenha').value;
  const s2 = document.getElementById('cadSenha2').value;
  document.getElementById('senhaErro').style.display = (s2 && s1 !== s2) ? 'block' : 'none';
}
document.getElementById('cadSenha').addEventListener('input', validarSenhas);
document.getElementById('cadSenha2').addEventListener('input', validarSenhas);

document.querySelector('#panelCadastro form').addEventListener('submit', function(e) {
  if (document.getElementById('cadSenha').value !== document.getElementById('cadSenha2').value) {
    e.preventDefault();
    Swal.fire({ toast:true, position:'top-end', icon:'error', title:'As senhas não coincidem!',
      showConfirmButton:false, timer:3000, timerProgressBar:true });
  }
});

<?php if ($msgErro): ?>
Swal.fire({ toast:true, position:'top-end', icon:'<?= $erro === 'bloqueado' ? 'warning' : 'error' ?>',
  title: <?= json_encode($msgErro) ?>,
  showConfirmButton:false, timer:4000, timerProgressBar:true });
<?php endif; ?>

<?php if ($flash && $flash['tipo'] === 'sucesso'): ?>
Swal.fire({ toast:true, position:'top-end', icon:'success',
  title: <?= json_encode($flash['mensagem']) ?>,
  showConfirmButton:false, timer:3500, timerProgressBar:true });
<?php endif; ?>
</script>
</body>
</html>
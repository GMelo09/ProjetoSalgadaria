<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart(); // ← isso estava faltando!
requireAdmin();  // ← já faz a verificação + redirect automaticamente
require_once __DIR__ . '/../classes/pedido_class.php';
require_once __DIR__ . '/../classes/produto_class.php';
require_once __DIR__ . '/../classes/usuario_class.php';
require_once __DIR__ . '/../classes/relatorio_class.php';
require_once __DIR__ . '/../classes/categoria_class.php';

$pedidoObj    = new Pedido();
$produtoObj   = new Produto();
$usuarioObj   = new Usuario();
$relatorioObj = new Relatorio();
$categoriaObj = new Categoria();

$pedidosRec   = $pedidoObj->ListarRecentes(10) ?? [];
$todosPedidos = $pedidoObj->ListarTodos() ?? [];
$produtos     = $produtoObj->ListarTodos() ?? [];
$clientes    = $usuarioObj->ListarTodos() ?? [];
$categorias  = $categoriaObj->ListarTodas() ?? [];
$topProdutos = $relatorioObj->ProdutosMaisVendidos(5) ?? [];
$fatDiario   = $relatorioObj->FaturamentoPorDia(date('Y-m-01'), date('Y-m-d')) ?? [];
$faturMes    = $relatorioObj->FaturamentoPorDia(date('Y-01-01'), date('Y-12-31')) ?? [];

$resumo = [
  'faturamento_hoje'  => $relatorioObj->ResumoHoje()['faturamento'] ?? 0,
  'faturamento_mes'   => $relatorioObj->FaturamentoPorPeriodo(date('Y-m-01'), date('Y-m-d'))['faturamento_total'] ?? 0,
  'pedidos_hoje'      => $relatorioObj->ResumoHoje()['total_pedidos'] ?? 0,
  'pedidos_pendentes' => $relatorioObj->ResumoHoje()['pendentes'] ?? 0,
  'total_clientes'    => count($clientes),
  'total_produtos'    => count($produtos),
];

$statusLabels = [
  'pendente'   => 'Pendente',
  'confirmado' => 'Confirmado',
  'producao'   => 'Em Produção',
  'entregue'   => 'Entregue',
  'cancelado'  => 'Cancelado',
];
$statusBadge = [
  'pendente'   => 'badge-warning',
  'confirmado' => 'badge-info',
  'producao'   => 'badge-primary',
  'entregue'   => 'badge-success',
  'cancelado'  => 'badge-danger',
];
$contStatus = [];
foreach ($statusLabels as $key => $_) {
  $contStatus[$key] = count(array_filter($todosPedidos, fn($p) => $p['status'] === $key));
}

$flash = null;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin | Doce &amp; Salgado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&family=Italiana&display=swap" rel="stylesheet">
  <link href="../css/admin.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

</head>

<body class="admin-body">

  <!-- ── Overlay mobile ── -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ═══════════════ SIDEBAR ═══════════════ -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <a href="../index.php">
        <i class="bi bi-cake2"></i>
        <span>Doce<strong>&amp;</strong>Salgado</span>
      </a>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section">GERAL</div>
      <a href="#secDashboard" class="sidebar-link active" onclick="showSection('secDashboard',this)">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
      </a>

      <div class="sidebar-section">PEDIDOS</div>
      <a href="#secPedidos" class="sidebar-link" onclick="showSection('secPedidos',this)">
        <i class="bi bi-bag-check"></i> <span>Pedidos</span>
        <?php if (!empty($resumo['pedidos_pendentes'])): ?>
          <span class="sidebar-badge"><?= (int)$resumo['pedidos_pendentes'] ?></span>
        <?php endif; ?>
      </a>

      <div class="sidebar-section">CATÁLOGO</div>
      <a href="#secProdutos" class="sidebar-link" onclick="showSection('secProdutos',this)">
        <i class="bi bi-box-seam"></i> <span>Produtos</span>
      </a>

      <div class="sidebar-section">CLIENTES</div>
      <a href="#secClientes" class="sidebar-link" onclick="showSection('secClientes',this)">
        <i class="bi bi-people"></i> <span>Clientes</span>
      </a>

      <div class="sidebar-section">FINANCEIRO</div>
      <a href="#secRelatorios" class="sidebar-link" onclick="showSection('secRelatorios',this)">
        <i class="bi bi-graph-up-arrow"></i> <span>Relatórios</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="../index.php" class="sidebar-link">
        <i class="bi bi-globe2"></i> <span>Ver Site</span>
      </a>
      <a href="../Pages/login.php?sair=1" class="sidebar-link sidebar-link-danger" id="btnSair">
        <i class="bi bi-box-arrow-right"></i> <span>Sair</span>
      </a>
    </div>
  </aside>

  <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
  <div class="admin-main" id="adminMain">

    <!-- Topbar -->
    <header class="admin-topbar">
      <button class="btn-sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
      </button>

      <div style="flex:1;padding-left:.75rem;">
        <h1 class="admin-page-title" id="pageTitle">Dashboard</h1>
      </div>

      <div class="admin-topbar-right" style="gap:1rem;">
        <div class="topbar-date">
          <i class="bi bi-calendar3" style="color:var(--rose);"></i>
          <span id="topbarDate"></span>
        </div>
        <div style="display:flex;align-items:center;gap:.65rem;">
          <div class="topbar-avatar">
            <?= strtoupper(substr($_SESSION['usuario_nome'] ?? 'A', 0, 1)) ?>
          </div>
          <div class="admin-user-info">
            <span class="admin-user-name"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?></span>
            <span class="topbar-greeting">Administrador</span>
          </div>
        </div>
      </div>
    </header>

    <!-- ══════════════════════════════════════
       SEÇÃO: DASHBOARD
  ══════════════════════════════════════ -->
    <section class="admin-section active" id="secDashboard">

      <!-- Welcome Banner -->
      <div class="welcome-banner">
        <div class="welcome-banner-text">
          <h3>Olá, <?= htmlspecialchars(explode(' ', $_SESSION['usuario_nome'] ?? 'Admin')[0]) ?>! 👋</h3>
          <p>Aqui está o resumo do seu negócio hoje.</p>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card kpi-rose">
          <div class="kpi-icon"><i class="bi bi-currency-dollar"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Faturamento Hoje</div>
            <div class="kpi-value">R$ <?= number_format((float)($resumo['faturamento_hoje'] ?? 0), 2, ',', '.') ?></div>
            <div class="kpi-trend"><i class="bi bi-arrow-up-short"></i> hoje</div>
          </div>
        </div>
        <div class="kpi-card kpi-choco">
          <div class="kpi-icon"><i class="bi bi-calendar-month"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Faturamento do Mês</div>
            <div class="kpi-value">R$ <?= number_format((float)($resumo['faturamento_mes'] ?? 0), 2, ',', '.') ?></div>
            <div class="kpi-trend" style="color:var(--choco-light);"><i class="bi bi-calendar2"></i> mês atual</div>
          </div>
        </div>
        <div class="kpi-card kpi-warning">
          <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Pedidos Pendentes</div>
            <div class="kpi-value"><?= (int)($resumo['pedidos_pendentes'] ?? 0) ?></div>
            <div class="kpi-trend" style="color:var(--warning);"><i class="bi bi-clock"></i> aguardando</div>
          </div>
        </div>
        <div class="kpi-card kpi-success">
          <div class="kpi-icon"><i class="bi bi-bag-check"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Pedidos Hoje</div>
            <div class="kpi-value"><?= (int)($resumo['pedidos_hoje'] ?? 0) ?></div>
            <div class="kpi-trend"><i class="bi bi-check2-circle"></i> recebidos</div>
          </div>
        </div>
        <div class="kpi-card kpi-blue">
          <div class="kpi-icon"><i class="bi bi-people"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Total de Clientes</div>
            <div class="kpi-value"><?= (int)($resumo['total_clientes'] ?? 0) ?></div>
            <div class="kpi-trend" style="color:#1E88E5;"><i class="bi bi-person-check"></i> cadastrados</div>
          </div>
        </div>
        <div class="kpi-card kpi-purple">
          <div class="kpi-icon"><i class="bi bi-box-seam"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Produtos Ativos</div>
            <div class="kpi-value"><?= (int)($resumo['total_produtos'] ?? 0) ?></div>
            <div class="kpi-trend" style="color:#8E24AA;"><i class="bi bi-grid"></i> no catálogo</div>
          </div>
        </div>
      </div>

      <!-- Gráficos -->
      <div class="charts-grid">
        <div class="chart-card">
          <h5>Faturamento — Últimos 6 Meses</h5>
          <canvas id="chartFaturamento" height="80"></canvas>
        </div>
        <div class="chart-card">
          <h5>Top 5 Produtos Mais Vendidos</h5>
          <canvas id="chartProdutos" height="80"></canvas>
        </div>
      </div>

      <!-- Últimos Pedidos -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h5><i class="bi bi-clock-history"></i> Últimos 10 Pedidos</h5>
          <button class="btn btn-sm btn-outline" onclick="showSection('secPedidos', document.querySelector('[href=\'#secPedidos\']'))">
            Ver todos <i class="bi bi-arrow-right"></i>
          </button>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Entrega</th>
                <th>Total</th>
                <th>Status</th>
                <th>Ação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pedidosRec as $ped): ?>
                <tr>
                  <td><span style="font-weight:700;color:var(--rose);">#<?= (int)$ped['id'] ?></span></td>
                  <td>
                    <strong><?= htmlspecialchars($ped['nome']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($ped['telefone']) ?></small>
                  </td>
                  <td>
                    <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.82rem;">
                      <i class="bi bi-calendar2-event" style="color:var(--rose);"></i>
                      <?= date('d/m/Y', strtotime($ped['data_entrega'])) ?>
                    </span>
                  </td>
                  <td><strong>R$ <?= number_format((float)$ped['total'], 2, ',', '.') ?></strong></td>
                  <td>
                    <span class="badge-status <?= $statusBadge[$ped['status']] ?? 'badge-secondary' ?>">
                      <?= htmlspecialchars($statusLabels[$ped['status']] ?? $ped['status']) ?>
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-xs btn-outline" onclick="abrirPedido(<?= (int)$ped['id'] ?>)" title="Ver detalhes">
                      <i class="bi bi-eye"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($pedidosRec)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                    Nenhum pedido encontrado.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════
       SEÇÃO: PEDIDOS
  ══════════════════════════════════════ -->
    <section class="admin-section" id="secPedidos">
      <div class="admin-section-header">
        <div>
          <h2><i class="bi bi-bag-check" style="color:var(--rose);font-size:1.3rem;"></i> Gerenciar Pedidos</h2>
          <p class="section-subtitle">Visualize e gerencie todos os pedidos do sistema</p>
        </div>
      </div>

      <div class="filter-bar-admin">
        <span class="filter-bar-label"><i class="bi bi-funnel"></i> Filtrar:</span>
        <button class="filter-chip active" data-status="">Todos</button>
        <?php foreach ($statusLabels as $key => $label): ?>
          <button class="filter-chip" data-status="<?= htmlspecialchars($key) ?>">
            <?= htmlspecialchars($label) ?>
            <span class="chip-count"><?= (int)($contStatus[$key] ?? 0) ?></span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="admin-card">
        <div class="table-responsive">

          <table class="admin-table" id="tabelaPedidos">
            <thead>
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Telefone</th>
                <th>Entrega</th>
                <th>Pagamento</th>
                <th>Total</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($todosPedidos as $ped): ?>
                <tr data-status="<?= htmlspecialchars($ped['status']) ?>">
                  <td><span style="font-weight:700;color:var(--rose);">#<?= (int)$ped['id'] ?></span></td>
                  <td><?= htmlspecialchars($ped['nome']) ?></td>
                  <td><?= htmlspecialchars($ped['telefone']) ?></td>
                  <td>
                    <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.82rem;">
                      <i class="bi bi-calendar2-event" style="color:var(--rose);"></i>
                      <?= date('d/m/Y', strtotime($ped['data_entrega'])) ?>
                    </span>
                  </td>
                  <td>
                    <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.82rem;">
                      <?php
                      $icons = ['pix' => 'bi-qr-code', 'dinheiro' => 'bi-cash', 'cartao' => 'bi-credit-card'];
                      $ic = $icons[$ped['forma_pagamento']] ?? 'bi-credit-card';
                      ?>
                      <i class="bi <?= $ic ?>" style="color:var(--choco-light);"></i>
                      <?= ucfirst(htmlspecialchars($ped['forma_pagamento'])) ?>
                    </span>
                  </td>
                  <td><strong>R$ <?= number_format((float)$ped['total'], 2, ',', '.') ?></strong></td>
                  <td>
                    <span class="badge-status <?= $statusBadge[$ped['status']] ?? 'badge-secondary' ?>">
                      <?= htmlspecialchars($statusLabels[$ped['status']] ?? $ped['status']) ?>
                    </span>
                  </td>
                  <td style="display:flex;gap:.35rem;">
                    <button class="btn btn-xs btn-outline" onclick="abrirPedido(<?= (int)$ped['id'] ?>)" title="Ver detalhes">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-xs btn-primary" onclick="abrirAlterarStatus(<?= (int)$ped['id'] ?>,'<?= htmlspecialchars($ped['status']) ?>')" title="Alterar status">
                      <i class="bi bi-pencil"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($todosPedidos)): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                    Nenhum pedido encontrado.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════
       SEÇÃO: PRODUTOS
  ══════════════════════════════════════ -->
    <section class="admin-section" id="secProdutos">
      <div class="admin-section-header">
        <div>
          <h2><i class="bi bi-box-seam" style="color:var(--rose);font-size:1.3rem;"></i> Gerenciar Produtos</h2>
          <p class="section-subtitle">Adicione, edite ou remova produtos do catálogo</p>
        </div>
        <button class="btn btn-primary" onclick="abrirModalProduto()">
          <i class="bi bi-plus-lg"></i> Novo Produto
        </button>
      </div>
      <div class="admin-card">
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Emoji</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Tag</th>
                <th>Preço</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($produtos as $prod): ?>
                <tr>
                  <td><span style="color:var(--muted);font-size:.8rem;"><?= (int)$prod['id'] ?></span></td>
                  <td style="font-size:1.4rem;"><?= htmlspecialchars($prod['emoji'] ?? '') ?></td>
                  <td><strong><?= htmlspecialchars($prod['nome']) ?></strong></td>
                  <td>
                    <span style="background:var(--choco-pale);color:var(--choco);padding:.2rem .65rem;border-radius:50px;font-size:.75rem;font-weight:600;">
                      <?= htmlspecialchars($prod['categoria_nome']) ?>
                    </span>
                  </td>
                  <td>
                    <span style="background:var(--rose-pale);color:var(--rose);padding:.2rem .65rem;border-radius:50px;font-size:.75rem;font-weight:600;">
                      <?= htmlspecialchars($prod['tag'] ?? '—') ?>
                    </span>
                  </td>
                  <td><strong style="color:var(--rose);">R$ <?= number_format((float)$prod['preco'], 2, ',', '.') ?></strong></td>
                  <td>
                    <?php if ($prod['ativo']): ?>
                      <span class="badge-status badge-success">Ativo</span>
                    <?php else: ?>
                      <span class="badge-status badge-danger">Inativo</span>
                    <?php endif; ?>
                  </td>
                  <td style="display:flex;gap:.35rem;">
                    <button class="btn btn-xs btn-outline"
                      onclick='abrirModalProduto(<?= htmlspecialchars(json_encode($prod, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES) ?>)'
                      title="Editar">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-danger"
                      onclick="excluirProduto(<?= (int)$prod['id'] ?>, '<?= addslashes(htmlspecialchars($prod['nome'])) ?>')"
                      title="Excluir">
                      <i class="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════
       SEÇÃO: CLIENTES
  ══════════════════════════════════════ -->
    <section class="admin-section" id="secClientes">
      <div class="admin-section-header">
        <div>
          <h2><i class="bi bi-people" style="color:var(--rose);font-size:1.3rem;"></i> Clientes Cadastrados</h2>
          <p class="section-subtitle">Gerencie os clientes da plataforma</p>
        </div>
      </div>
      <div class="admin-card">
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Pedidos</th>
                <th>Cadastro</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($clientes as $cli): ?>
                <?php if ($cli['eh_admin']) continue; ?>
                <tr>
                  <td><span style="color:var(--muted);font-size:.8rem;"><?= (int)$cli['id'] ?></span></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                      <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--rose-pale),var(--rose-blush));display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:var(--rose);">
                        <?= strtoupper(substr($cli['nome'], 0, 1)) ?>
                      </div>
                      <?= htmlspecialchars($cli['nome']) ?>
                    </div>
                  </td>
                  <td style="color:var(--muted);font-size:.82rem;"><?= htmlspecialchars($cli['email']) ?></td>
                  <td style="font-size:.82rem;"><?= htmlspecialchars($cli['telefone'] ?? '—') ?></td>
                  <td>
                    <span style="background:var(--cream);padding:.2rem .65rem;border-radius:50px;font-size:.78rem;font-weight:700;">
                      <?= (int)$cli['total_pedidos'] ?>
                    </span>
                  </td>
                  <td style="font-size:.82rem;color:var(--muted);"><?= date('d/m/Y', strtotime($cli['criado_em'])) ?></td>
                  <td>
                    <?php if ($cli['bloqueado']): ?>
                      <span class="badge-status badge-danger">Bloqueado</span>
                    <?php else: ?>
                      <span class="badge-status badge-success">Ativo</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="btn btn-xs <?= $cli['bloqueado'] ? 'btn-success' : 'btn-danger' ?>"
                      onclick="alterarBloqueioCliente(<?= (int)$cli['id'] ?>, <?= $cli['bloqueado'] ? 0 : 1 ?>, '<?= addslashes(htmlspecialchars($cli['nome'])) ?>')">
                      <i class="bi bi-<?= $cli['bloqueado'] ? 'unlock' : 'lock' ?>"></i>
                      <?= $cli['bloqueado'] ? 'Desbloquear' : 'Bloquear' ?>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════
       SEÇÃO: RELATÓRIOS
  ══════════════════════════════════════ -->
    <section class="admin-section" id="secRelatorios">
      <div class="admin-section-header">
        <div>
          <h2><i class="bi bi-graph-up-arrow" style="color:var(--rose);font-size:1.3rem;"></i> Relatórios &amp; Financeiro</h2>
          <p class="section-subtitle">Análise de faturamento e desempenho do negócio</p>
        </div>
      </div>

      <div class="kpi-grid">
        <div class="kpi-card kpi-rose">
          <div class="kpi-icon"><i class="bi bi-currency-dollar"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Faturamento Total</div>
            <div class="kpi-value">R$ <?= number_format((float)($resumo['faturamento_mes'] ?? 0), 2, ',', '.') ?></div>
            <div class="kpi-trend"><i class="bi bi-bar-chart"></i> acumulado</div>
          </div>
        </div>
        <div class="kpi-card kpi-choco">
          <div class="kpi-icon"><i class="bi bi-calendar-month"></i></div>
          <div class="kpi-info">
            <div class="kpi-label">Faturamento do Mês</div>
            <div class="kpi-value">R$ <?= number_format((float)($resumo['faturamento_mes'] ?? 0), 2, ',', '.') ?></div>
            <div class="kpi-trend" style="color:var(--choco-light);"><i class="bi bi-calendar2"></i> mês atual</div>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <h5 style="font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--choco);display:flex;align-items:center;gap:.5rem;margin-bottom:1.5rem;">
          <span style="display:inline-block;width:4px;height:18px;background:linear-gradient(180deg,var(--rose),var(--rose-dark));border-radius:4px;"></span>
          Faturamento Diário — Últimos 30 Dias
        </h5>
        <canvas id="chartFaturamentoDiario" height="60"></canvas>
      </div>

      <div class="admin-card" style="margin-top:1.5rem;">
        <div class="admin-card-header">
          <h5><i class="bi bi-trophy"></i> 🏆 Produtos Mais Vendidos</h5>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Produto</th>
                <th>Un. Vendidas</th>
                <th>Pedidos</th>
                <th>Faturamento</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topProdutos as $tp): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($tp['nome']) ?></strong></td>
                  <td><?= (int)$tp['total_vendido'] ?></td>
                  <td><?= (int)$tp['total_pedidos'] ?></td>
                  <td><strong style="color:var(--rose);">R$ <?= number_format((float)$tp['total_faturado'], 2, ',', '.') ?></strong></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($topProdutos)): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Sem dados de vendas ainda.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-card" style="margin-top:1.5rem;">
        <div class="admin-card-header">
          <h5><i class="bi bi-calendar3"></i> Faturamento por Mês</h5>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Mês</th>
                <th>Pedidos</th>
                <th>Faturamento</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($faturMes) as $mes): ?>
                <tr>
                  <td><?= htmlspecialchars($mes['mes_label']) ?></td>
                  <td><?= (int)$mes['total_pedidos'] ?></td>
                  <td><strong style="color:var(--rose);">R$ <?= number_format((float)$mes['faturamento'], 2, ',', '.') ?></strong></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($faturMes)): ?>
                <tr>
                  <td colspan="3" class="text-center text-muted py-4">Sem dados ainda.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </div><!-- /admin-main -->

  <!-- ═══════════════ MODAL: DETALHES DO PEDIDO ═══════════════ -->
  <div class="modal fade" id="modalPedido" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-receipt"></i> Detalhes do Pedido</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modalPedidoBody">
          <div class="text-center py-4">
            <div class="spinner-border text-rose"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════ MODAL: ALTERAR STATUS ═══════════════ -->
  <div class="modal fade" id="modalStatus" tabindex="-1">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil"></i> Alterar Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="formStatus" action="actions/alterar_status.php" method="POST">
            <input type="hidden" name="pedido_id" id="statusPedidoId">
            <div class="form-group">
              <label class="form-label">Novo Status</label>
              <select class="form-control" name="status" id="selectStatus">
                <?php foreach ($statusLabels as $key => $label): ?>
                  <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-full mt-3">
              <i class="bi bi-check2"></i> Salvar
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════ MODAL: PRODUTO ═══════════════ -->
  <div class="modal fade" id="modalProduto" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalProdutoTitulo"><i class="bi bi-box-seam"></i> Produto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="formProduto" action="actions/produto_salvar.php" method="POST">
            <input type="hidden" name="id" id="produtoId">
            <div class="form-row">
              <div class="form-group" style="flex:2;">
                <label class="form-label">Nome <span class="required">*</span></label>
                <input type="text" class="form-control" name="nome" id="produtoNome" required maxlength="100">
              </div>
              <div class="form-group">
                <label class="form-label">Emoji</label>
                <input type="text" class="form-control" name="emoji" id="produtoEmoji" maxlength="10" placeholder="🍗">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Descrição</label>
              <textarea class="form-control" name="descricao" id="produtoDescricao" rows="2" maxlength="300"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Categoria <span class="required">*</span></label>
                <select class="form-control" name="categoria_id" id="produtoCat" required>
                  <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Tag</label>
                <input type="text" class="form-control" name="tag" id="produtoTag" maxlength="30" placeholder="Clássico">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Preço (R$) <span class="required">*</span></label>
                <input type="number" class="form-control" name="preco" id="produtoPreco" required min="0.01" step="0.01">
              </div>
              <div class="form-group d-flex align-items-end pb-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="ativo" id="produtoAtivo" value="1" checked>
                  <label class="form-check-label" for="produtoAtivo">Produto ativo</label>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg">
              <i class="bi bi-check2-circle"></i> Salvar Produto
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    /* ── Data na topbar ── */
    document.getElementById('topbarDate').textContent = new Date().toLocaleDateString('pt-BR', {
      weekday: 'short',
      day: '2-digit',
      month: 'short'
    });

    /* ── Sidebar toggle (desktop collapse / mobile slide) ── */
    const sidebar = document.getElementById('adminSidebar');
    const mainEl = document.getElementById('adminMain');
    const overlay = document.getElementById('sidebarOverlay');

    document.getElementById('sidebarToggle').addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
      } else {
        sidebar.classList.toggle('collapsed');
        mainEl.classList.toggle('expanded');
      }
    });
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('mobile-open');
      overlay.classList.remove('active');
    });

    /* ── Logout com SweetAlert ── */
    document.getElementById('btnSair').addEventListener('click', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Deseja sair?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--rose)',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, sair',
        cancelButtonText: 'Cancelar'
      }).then(r => {
        if (r.isConfirmed) window.location.href = this.href;
      });
    });

    /* ── Mostrar seção ── */
    function showSection(id, el) {
      document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      document.getElementById(id).classList.add('active');
      if (el) el.classList.add('active');
      const titles = {
        secDashboard: 'Dashboard',
        secPedidos: 'Pedidos',
        secProdutos: 'Produtos',
        secClientes: 'Clientes',
        secRelatorios: 'Relatórios'
      };
      document.getElementById('pageTitle').textContent = titles[id] || '';
      // fecha sidebar no mobile ao navegar
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
      }
      return false;
    }

    /* ── Filtro de status nos pedidos ── */
    document.querySelectorAll('.filter-bar-admin .filter-chip').forEach(chip => {
      chip.addEventListener('click', function() {
        document.querySelectorAll('.filter-bar-admin .filter-chip').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        const status = this.dataset.status;
        document.querySelectorAll('#tabelaPedidos tbody tr').forEach(row => {
          row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
        });
      });
    });

    /* ── Abrir detalhes do pedido ── */
    function abrirPedido(id) {
      const modal = new bootstrap.Modal(document.getElementById('modalPedido'));
      document.getElementById('modalPedidoBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border" style="color:var(--rose);"></div></div>';
      modal.show();
      fetch(`actions/pedido_detalhe.php?id=${id}`)
        .then(r => r.text())
        .then(html => {
          document.getElementById('modalPedidoBody').innerHTML = html;
        })
        .catch(() => {
          document.getElementById('modalPedidoBody').innerHTML = '<p class="text-danger">Erro ao carregar pedido.</p>';
        });
    }

    /* ── Alterar status do pedido ── */
    function abrirAlterarStatus(id, statusAtual) {
      document.getElementById('statusPedidoId').value = id;
      document.getElementById('selectStatus').value = statusAtual;
      new bootstrap.Modal(document.getElementById('modalStatus')).show();
    }

    document.getElementById('formStatus').addEventListener('submit', function(e) {
      e.preventDefault();
      const pedidoId = document.getElementById('statusPedidoId').value;
      const novoStatus = document.getElementById('selectStatus').value;
      fetch('actions/alterar_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `pedido_id=${pedidoId}&status=${novoStatus}`
        })
        .then(r => r.json())
        .then(data => {
          bootstrap.Modal.getInstance(document.getElementById('modalStatus')).hide();
          if (data.sucesso) {
            Swal.fire({
              icon: 'success',
              title: 'Status atualizado!',
              timer: 2000,
              showConfirmButton: false,
              toast: true,
              position: 'top-end'
            });
            setTimeout(() => location.reload(), 1500);
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Erro ao atualizar status.',
              confirmButtonColor: 'var(--rose)'
            });
          }
        });
    });

    /* ── Modal de produto ── */
    function abrirModalProduto(prod = null) {
      document.getElementById('modalProdutoTitulo').textContent = prod ? '✏️ Editar Produto' : '➕ Novo Produto';
      document.getElementById('produtoId').value = prod?.id ?? '';
      document.getElementById('produtoNome').value = prod?.nome ?? '';
      document.getElementById('produtoEmoji').value = prod?.emoji ?? '';
      document.getElementById('produtoDescricao').value = prod?.descricao ?? '';
      document.getElementById('produtoCat').value = prod?.categoria_id ?? '1';
      document.getElementById('produtoTag').value = prod?.tag ?? '';
      document.getElementById('produtoPreco').value = prod?.preco ?? '';
      document.getElementById('produtoAtivo').checked = prod ? prod.ativo == 1 : true;
      new bootstrap.Modal(document.getElementById('modalProduto')).show();
    }

    document.getElementById('formProduto').addEventListener('submit', function(e) {
      e.preventDefault();
      const fd = new FormData(this);
      fetch('actions/produto_salvar.php', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(data => {
          bootstrap.Modal.getInstance(document.getElementById('modalProduto')).hide();
          if (data.sucesso) {
            Swal.fire({
              icon: 'success',
              title: data.mensagem ?? 'Produto salvo!',
              timer: 2000,
              showConfirmButton: false,
              toast: true,
              position: 'top-end'
            });
            setTimeout(() => location.reload(), 1500);
          } else {
            Swal.fire({
              icon: 'error',
              title: data.mensagem ?? 'Erro ao salvar produto.',
              confirmButtonColor: 'var(--rose)'
            });
          }
        });
    });

    /* ── Excluir produto ── */
    function excluirProduto(id, nome) {
      Swal.fire({
        title: `Desativar "${nome}"?`,
        text: 'O produto não aparecerá mais no site.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53935',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, desativar',
        cancelButtonText: 'Cancelar'
      }).then(r => {
        if (!r.isConfirmed) return;
        fetch('actions/produto_excluir.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}`
          })
          .then(r => r.json())
          .then(data => {
            if (data.sucesso) {
              Swal.fire({
                icon: 'success',
                title: 'Produto desativado!',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
              });
              setTimeout(() => location.reload(), 1500);
            } else {
              Swal.fire({
                icon: 'error',
                title: data.mensagem ?? 'Erro ao desativar.',
                confirmButtonColor: 'var(--rose)'
              });
            }
          });
      });
    }

    /* ── Bloquear/desbloquear cliente ── */
    function alterarBloqueioCliente(id, bloquear, nome) {
      const acao = bloquear ? 'bloquear' : 'desbloquear';
      Swal.fire({
        title: `${bloquear ? 'Bloquear' : 'Desbloquear'} "${nome}"?`,
        icon: bloquear ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: bloquear ? '#e53935' : '#2e7d32',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sim, ${acao}`,
        cancelButtonText: 'Cancelar'
      }).then(r => {
        if (!r.isConfirmed) return;
        fetch('actions/cliente_bloquear.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}&bloqueado=${bloquear}`
          })
          .then(r => r.json())
          .then(data => {
            if (data.sucesso) {
              Swal.fire({
                icon: 'success',
                title: `Cliente ${acao}do com sucesso!`,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
              });
              setTimeout(() => location.reload(), 1500);
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Erro na operação.',
                confirmButtonColor: 'var(--rose)'
              });
            }
          });
      });
    }

    /* ── Flash message ── */
    <?php if ($flash): ?>
      document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
          icon: '<?= $flash['tipo'] === 'sucesso' ? 'success' : 'error' ?>',
          title: '<?= addslashes(htmlspecialchars($flash['mensagem'])) ?>',
          timer: 3000,
          showConfirmButton: false,
          toast: true,
          position: 'top-end'
        });
      });
    <?php endif; ?>

    /* ── Charts ── */
    document.addEventListener('DOMContentLoaded', () => {
      const meses = <?= json_encode(array_column($faturMes, 'mes_label')) ?>;
      const fatMes = <?= json_encode(array_column($faturMes, 'faturamento')) ?>;

      const ctxFat = document.getElementById('chartFaturamento');
      if (ctxFat) {
        new Chart(ctxFat, {
          type: 'bar',
          data: {
            labels: meses.slice(-6),
            datasets: [{
              label: 'Faturamento (R$)',
              data: fatMes.slice(-6),
              backgroundColor: [
                'rgba(194,24,91,.85)', 'rgba(194,24,91,.75)', 'rgba(194,24,91,.65)',
                'rgba(194,24,91,.55)', 'rgba(194,24,91,.45)', 'rgba(194,24,91,.85)'
              ],
              borderColor: '#C2185B',
              borderWidth: 0,
              borderRadius: 8,
              borderSkipped: false,
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(0,0,0,.04)'
                },
                ticks: {
                  font: {
                    size: 11
                  }
                }
              },
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  font: {
                    size: 11
                  }
                }
              }
            }
          }
        });
      }

      const topNomes = <?= json_encode(array_column($topProdutos, 'nome')) ?>;
      const topQtds = <?= json_encode(array_column($topProdutos, 'total_vendido')) ?>;
      const ctxProd = document.getElementById('chartProdutos');
      if (ctxProd && topNomes.length) {
        new Chart(ctxProd, {
          type: 'doughnut',
          data: {
            labels: topNomes,
            datasets: [{
              data: topQtds,
              backgroundColor: ['#C2185B', '#880E4F', '#5D4037', '#FB8C00', '#43A047'],
              borderWidth: 3,
              borderColor: '#fff',
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 16,
                  font: {
                    size: 12
                  }
                }
              }
            },
            cutout: '65%'
          }
        });
      }

      const diasLabel = <?= json_encode(array_column(array_reverse($fatDiario), 'dia')) ?>;
      const diasFat = <?= json_encode(array_column(array_reverse($fatDiario), 'faturamento')) ?>;
      const ctxDiario = document.getElementById('chartFaturamentoDiario');
      if (ctxDiario) {
        new Chart(ctxDiario, {
          type: 'line',
          data: {
            labels: diasLabel,
            datasets: [{
              label: 'Faturamento Diário (R$)',
              data: diasFat,
              borderColor: '#C2185B',
              backgroundColor: 'rgba(194,24,91,.08)',
              tension: 0.4,
              fill: true,
              pointRadius: 4,
              pointBackgroundColor: '#C2185B',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: 'rgba(0,0,0,.04)'
                },
                ticks: {
                  font: {
                    size: 11
                  }
                }
              },
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  font: {
                    size: 10
                  },
                  maxTicksLimit: 10
                }
              }
            }
          }
        });
      }
    });
  </script>
</body>

</html>
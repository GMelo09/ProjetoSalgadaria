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
require_once __DIR__ . '/../classes/pacote_class.php';
$pacoteObj    = new Pacote();
$pacotes      = $pacoteObj->ListarTodos() ?? [];

$pedidosRec   = $pedidoObj->ListarRecentes(10) ?? [];
$todosPedidos = $pedidoObj->ListarTodos() ?? [];
$produtos     = $produtoObj->ListarTodos() ?? [];
$clientes    = $usuarioObj->ListarTodos() ?? [];
$categorias  = $categoriaObj->ListarTodas() ?? [];
$topProdutos = $relatorioObj->ProdutosMaisVendidos(5) ?? [];
$fatDiario   = $relatorioObj->FaturamentoPorDia(date('Y-m-01'), date('Y-m-d')) ?? [];
$faturMes    = $relatorioObj->FaturamentoPorMes(date('Y-01-01'), date('Y-12-31')) ?? [];


// ── Período selecionado via GET ───────────────────────────────
$periodoSelecionado = $_GET['periodo'] ?? 'mes';

$periodos = [
  'semana'    => ['label' => 'Semanal',   'inicio' => date('Y-m-d', strtotime('-7 days')),   'fim' => date('Y-m-d')],
  'mes'       => ['label' => 'Mensal',    'inicio' => date('Y-m-01'),                         'fim' => date('Y-m-d')],
  'trimestre' => ['label' => 'Semestral', 'inicio' => date('Y-m-d', strtotime('-6 months')), 'fim' => date('Y-m-d')],
  'ano'       => ['label' => 'Anual',     'inicio' => date('Y-01-01'),                        'fim' => date('Y-12-31')],
];

if (!isset($periodos[$periodoSelecionado])) {
  $periodoSelecionado = 'mes';
}

$periodoConfig = $periodos[$periodoSelecionado];
$nomePeriodo   = $periodoConfig['label'];
$dataInicio    = $periodoConfig['inicio'];
$dataFim       = $periodoConfig['fim'];

// ── Dados do período selecionado ─────────────────────────────
$fatPeriodo = $relatorioObj->FaturamentoPorPeriodo($dataInicio, $dataFim);

// Clientes novos no período
$banco   = Banco::conectar();
$cmd     = $banco->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE DATE(criado_em) BETWEEN ? AND ? AND id_tipo = 2");
$cmd->execute([$dataInicio, $dataFim]);
$clientesNovos = (int) $cmd->fetch(PDO::FETCH_ASSOC)['total'];
Banco::desconectar();

$_totalPedidos = (int)($fatPeriodo['total_pedidos'] ?? 0);
$_totalReceita = (float)($fatPeriodo['faturamento_total'] ?? 0);
$_ticketMedio  = $_totalPedidos > 0 ? $_totalReceita / $_totalPedidos : 0;
$dadosPeriodo = [
  'receita'       => number_format($_totalReceita, 2, ',', '.'),
  'atendimentos'  => $_totalPedidos,
  'ticket_medio'  => number_format($_ticketMedio, 2, ',', '.'),
  'clientesNovos' => $clientesNovos,
];

// ── Resumo dos últimos 6 meses com variação ──────────────────
$resumoMeses = [];
$receitaAnterior = null;

for ($i = 5; $i >= 0; $i--) {
  $mesInicio = date('Y-m-01', strtotime("-{$i} months"));
  $mesFim    = date('Y-m-t',  strtotime("-{$i} months"));
  $mesLabel = date('M/Y', strtotime($mesInicio));
  $fat = $relatorioObj->FaturamentoPorPeriodo($mesInicio, $mesFim);
  $receita = (float)($fat['faturamento_total'] ?? 0);
  $pedidos = (int)($fat['total_pedidos'] ?? 0);

  // Variação em relação ao mês anterior
  $variacao = 0;
  $variacaoFormatada = '—';
  if ($receitaAnterior !== null && $receitaAnterior > 0) {
    $variacao = (($receita - $receitaAnterior) / $receitaAnterior) * 100;
    $sinal    = $variacao >= 0 ? '+' : '';
    $variacaoFormatada = $sinal . number_format($variacao, 1) . '%';
  } elseif ($receitaAnterior === 0 && $receita > 0) {
    $variacaoFormatada = '+100%';
    $variacao = 100;
  }

  $resumoMeses[] = [
    'mes'               => ucfirst($mesLabel),
    'receita'           => 'R$ ' . number_format($receita, 2, ',', '.'),
    'atendimentos'      => $pedidos,
    'variacao'          => $variacao,
    'variacaoFormatada' => $variacaoFormatada,
  ];

  $receitaAnterior = $receita;
}

// ResumoHoje() chamado UMA vez e resultado reutilizado (era 3 queries idênticas)
$resumoHoje = $relatorioObj->ResumoHoje();

$resumo = [
  'faturamento_hoje'  => $resumoHoje['faturamento']    ?? 0,
  'faturamento_mes'   => $relatorioObj->FaturamentoPorPeriodo(date('Y-m-01'), date('Y-m-d'))['faturamento_total'] ?? 0,
  'pedidos_hoje'      => $resumoHoje['total_pedidos']  ?? 0,
  'pedidos_pendentes' => $resumoHoje['pendentes']      ?? 0,
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

$flash = getFlash(); // lê e limpa a flash message da sessão

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

      <div class="sidebar-section">CADASTROS</div>
      <a href="#secPacotes" class="sidebar-link" onclick="showSection('secPacotes',this)">
        <i class="bi bi-box-seam"></i> <span>Pacotes</span>
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
      <form action="../actions/logout.php" method="POST" style="margin:0;">
        <?= csrfField() ?>
        <button type="submit" class="sidebar-link sidebar-link-danger" id="btnSair"
          style="background:none;border:none;width:100%;text-align:left;cursor:pointer;">
          <i class="bi bi-box-arrow-right"></i> <span>Sair</span>
        </button>
      </form>
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
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.75rem;">
        <div class="chart-card">
          <h5>Top 5 Produtos Mais Vendidos</h5>
          <div style="position:relative;height:220px;">
            <canvas id="chartProdutos"></canvas>
          </div>
        </div>
        <div class="chart-card">
          <h5>Distribuição de Pedidos por Status</h5>
          <?php $totalPed = array_sum($contStatus); ?>
          <?php if ($totalPed > 0): ?>
            <div style="display:flex;align-items:center;gap:1.25rem;">
              <div style="position:relative;height:200px;width:200px;flex-shrink:0;">
                <canvas id="chartStatus"></canvas>
              </div>
              <div style="flex:1;display:flex;flex-direction:column;gap:.5rem;">
                <?php
                $statusColors = [
                  'pendente'   => ['#FB8C00', '⏳'],
                  'confirmado' => ['#1E88E5', '✅'],
                  'producao'   => ['#C2185B', '🔧'],
                  'entregue'   => ['#43A047', '📦'],
                  'cancelado'  => ['#E53935', '✖'],
                ];
                foreach ($contStatus as $key => $cnt):
                  $pct = $totalPed > 0 ? round($cnt / $totalPed * 100) : 0;
                  [$cor, $emoji] = $statusColors[$key];
                ?>
                  <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:<?= $cor ?>;flex-shrink:0;"></span>
                    <span style="flex:1;color:var(--muted);"><?= $statusLabels[$key] ?></span>
                    <span style="font-weight:700;color:var(--dark);"><?= $cnt ?></span>
                    <span style="color:var(--muted);font-size:.72rem;">(<?= $pct ?>%)</span>
                  </div>
                <?php endforeach; ?>
                <div style="margin-top:.5rem;padding-top:.5rem;border-top:1px solid var(--cream);font-size:.78rem;color:var(--muted);">
                  Total: <strong style="color:var(--dark);"><?= $totalPed ?> pedidos</strong>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:200px;color:var(--muted);gap:.5rem;">
              <i class="bi bi-inbox" style="font-size:2rem;"></i>
              <span style="font-size:.85rem;">Nenhum pedido registrado ainda</span>
            </div>
          <?php endif; ?>
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
                <th>Foto</th>
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
                  <td>
                    <?php if (!empty($prod['imagem'])): ?>
                      <img src="../uploads/produtos/<?= htmlspecialchars($prod['imagem']) ?>"
                        alt="<?= htmlspecialchars($prod['nome']) ?>"
                        style="width:42px;height:42px;object-fit:cover;border-radius:8px;border:1px solid var(--cream);">
                    <?php else: ?>
                      <span style="font-size:1.4rem;display:inline-block;width:42px;height:42px;line-height:42px;text-align:center;">
                        <?= htmlspecialchars($prod['emoji'] ?? '📦') ?>
                      </span>
                    <?php endif; ?>
                  </td>
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
                    <?php if ($prod['ativo'] ?? 1): ?>
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
        <button class="btn btn-primary" onclick="abrirModalNovoUsuario()">
          <i class="bi bi-person-plus"></i> Novo Usuário
        </button>
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
                <?php if ((int)($cli['id_tipo'] ?? 0) === 1) continue; ?>
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
                      <?= (int)($cli['total_pedidos'] ?? 0) ?>
                    </span>
                  </td>
                  <td style="font-size:.82rem;color:var(--muted);"><?= date('d/m/Y', strtotime($cli['criado_em'])) ?></td>
                  <td>
                    <?php if ($cli['bloqueado'] ?? 0): ?>
                      <span class="badge-status badge-danger">Bloqueado</span>
                    <?php else: ?>
                      <span class="badge-status badge-success">Ativo</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="btn btn-xs <?= ($cli['bloqueado'] ?? 0) ? 'btn-success' : 'btn-danger' ?>"
                      onclick="alterarBloqueioCliente(<?= (int)$cli['id'] ?>, <?= ($cli['bloqueado'] ?? 0) ? 0 : 1 ?>, <?= htmlspecialchars(json_encode($cli['nome']), ENT_QUOTES) ?>)">
                      <i class="bi bi-<?= ($cli['bloqueado'] ?? 0) ? 'unlock' : 'lock' ?>"></i>
                      <?= ($cli['bloqueado'] ?? 0) ? 'Desbloquear' : 'Bloquear' ?>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <?php
    /*
 ┌─────────────────────────────────────────────────────────────────────────┐
 │  SEÇÃO: RELATÓRIOS  — substitui a <section id="secRelatorios"> atual    │
 │  Funciona com as variáveis PHP já calculadas no topo do dashboard.php   │
 └─────────────────────────────────────────────────────────────────────────┘
*/
    ?>

    <!-- ══════════════════════════════════════
     SEÇÃO: RELATÓRIOS & FINANCEIRO
══════════════════════════════════════ -->
    <section class="admin-section" id="secRelatorios">

      <!-- Cabeçalho + seletor de período -->
      <div class="admin-section-header" style="flex-wrap:wrap;gap:1rem;">
        <div>
          <h2>
            <i class="bi bi-graph-up-arrow" style="color:var(--rose);font-size:1.3rem;"></i>
            Relatórios &amp; Financeiro
          </h2>
          <p class="section-subtitle">
            Análise de desempenho — período:
            <strong style="color:var(--rose);"><?= htmlspecialchars($nomePeriodo) ?></strong>
            (<?= date('d/m/Y', strtotime($dataInicio)) ?> a <?= date('d/m/Y', strtotime($dataFim)) ?>)
          </p>
        </div>

        <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
          <!-- Seletor de período estilizado -->
          <div style="display:flex;background:#fff;border:1.5px solid var(--cream);border-radius:12px;overflow:hidden;" id="seletorPeriodo">
            <?php foreach ($periodos as $key => $cfg): ?>
              <button
                data-periodo="<?= $key ?>"
                onclick="mudarPeriodo('<?= $key ?>')"
                style="padding:.45rem 1rem;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .15s;border:none;cursor:pointer;
                    <?= $periodoSelecionado === $key
                      ? 'background:var(--rose);color:#fff;'
                      : 'color:var(--muted);background:transparent;' ?>">
                <?= htmlspecialchars($cfg['label']) ?>
              </button>
            <?php endforeach; ?>
          </div>

          <!-- Botão exportar (placeholder — conecte ao seu backend) -->
          <button class="btn btn-outline" onclick="exportarRelatorio()" style="border-radius:12px;font-size:.84rem;">
            <i class="bi bi-download"></i> Exportar CSV
          </button>
        </div>
      </div>

      <!-- ── KPIs do período ──────────────────────────────────── -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">

        <!-- Receita -->
        <div style="background:linear-gradient(135deg,var(--rose-dark),var(--rose));border-radius:18px;padding:1.5rem;color:#fff;position:relative;overflow:hidden;">
          <div style="position:absolute;top:-15px;right:-15px;width:90px;height:90px;background:rgba(255,255,255,.1);border-radius:50%;"></div>
          <i class="bi bi-currency-dollar" style="font-size:1.6rem;opacity:.8;"></i>
          <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-top:.75rem;opacity:.8;">
            Receita <?= htmlspecialchars($nomePeriodo) ?>
          </div>
          <div style="font-size:2rem;font-weight:900;line-height:1.15;font-family:'Cormorant Garamond',serif;">
            R$ <?= $dadosPeriodo['receita'] ?>
          </div>
          <div style="font-size:.75rem;margin-top:.3rem;opacity:.75;">
            <i class="bi bi-bar-chart"></i> faturamento total no período
          </div>
        </div>

        <!-- Atendimentos -->
        <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;position:relative;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Pedidos</div>
              <div style="font-size:2rem;font-weight:900;color:var(--dark);font-family:'Cormorant Garamond',serif;line-height:1.15;">
                <?= $dadosPeriodo['atendimentos'] ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted);margin-top:.3rem;">atendimentos no período</div>
            </div>
            <div style="width:48px;height:48px;border-radius:14px;background:var(--choco-pale);display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-bag-check" style="font-size:1.3rem;color:var(--choco);"></i>
            </div>
          </div>
        </div>

        <!-- Ticket Médio -->
        <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Ticket Médio</div>
              <div style="font-size:2rem;font-weight:900;color:var(--dark);font-family:'Cormorant Garamond',serif;line-height:1.15;">
                R$ <?= $dadosPeriodo['ticket_medio'] ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted);margin-top:.3rem;">valor médio por pedido</div>
            </div>
            <div style="width:48px;height:48px;border-radius:14px;background:#e3f2fd;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-receipt" style="font-size:1.3rem;color:#1E88E5;"></i>
            </div>
          </div>
        </div>

        <!-- Clientes Novos -->
        <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);">Novos Clientes</div>
              <div style="font-size:2rem;font-weight:900;color:var(--dark);font-family:'Cormorant Garamond',serif;line-height:1.15;">
                <?= $dadosPeriodo['clientesNovos'] ?>
              </div>
              <div style="font-size:.75rem;color:var(--muted);margin-top:.3rem;">cadastrados no período</div>
            </div>
            <div style="width:48px;height:48px;border-radius:14px;background:#e8f5e9;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-person-plus" style="font-size:1.3rem;color:#43A047;"></i>
            </div>
          </div>
        </div>

      </div>

      <!-- ── Gráficos lado a lado ──────────────────────────────── -->
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">

        <!-- Faturamento diário (linha) -->
        <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <div>
              <h5 style="font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--choco);margin:0;display:flex;align-items:center;gap:.5rem;">
                <span style="display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,var(--rose),var(--rose-dark));border-radius:4px;"></span>
                Faturamento Diário
              </h5>
              <p style="font-size:.75rem;color:var(--muted);margin:.25rem 0 0;">Últimos 30 dias do mês atual</p>
            </div>
            <div style="font-size:.75rem;color:var(--muted);">
              <i class="bi bi-circle-fill" style="color:var(--rose);font-size:.5rem;"></i> Receita diária
            </div>
          </div>
          <canvas id="chartFaturamentoDiario" height="90"
            data-labels='<?= json_encode(array_column(array_reverse($fatDiario), 'dia')) ?>'
            data-values='<?= json_encode(array_column(array_reverse($fatDiario), 'faturamento')) ?>'></canvas>
        </div>

        <!-- Distribuição de status dos pedidos -->
        <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;">
          <h5 style="font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--choco);margin:0 0 1.25rem;display:flex;align-items:center;gap:.5rem;">
            <span style="display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,#1E88E5,#1565C0);border-radius:4px;"></span>
            Status dos Pedidos
          </h5>
          <?php $totalPed = array_sum($contStatus); ?>
          <?php if ($totalPed > 0): ?>
            <div style="position:relative;height:160px;margin-bottom:1rem;">
              <canvas id="chartStatusRel"
                data-labels='<?= json_encode(array_values($statusLabels)) ?>'
                data-values='<?= json_encode(array_values($contStatus)) ?>'></canvas>
            </div>
            <?php
            $statusColors = [
              'pendente'   => ['#FB8C00', 'bi-hourglass-split'],
              'confirmado' => ['#1E88E5', 'bi-check-circle'],
              'producao'   => ['#C2185B', 'bi-hammer'],
              'entregue'   => ['#43A047', 'bi-box-seam'],
              'cancelado'  => ['#E53935', 'bi-x-circle'],
            ];
            foreach ($contStatus as $key => $cnt):
              $pct = $totalPed > 0 ? round($cnt / $totalPed * 100) : 0;
              [$cor, $icon] = $statusColors[$key];
            ?>
              <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;">
                <div style="width:8px;height:8px;border-radius:50%;background:<?= $cor ?>;flex-shrink:0;"></div>
                <span style="font-size:.78rem;color:var(--muted);flex:1;"><?= $statusLabels[$key] ?></span>
                <strong style="font-size:.78rem;color:var(--dark);"><?= $cnt ?></strong>
                <span style="font-size:.72rem;color:var(--muted);background:var(--cream);border-radius:50px;padding:.1rem .4rem;"><?= $pct ?>%</span>
              </div>
            <?php endforeach; ?>
            <div style="border-top:1px solid var(--cream);margin-top:.5rem;padding-top:.5rem;font-size:.75rem;color:var(--muted);text-align:right;">
              Total: <strong style="color:var(--dark);"><?= $totalPed ?> pedidos</strong>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:200px;color:var(--muted);gap:.5rem;">
              <i class="bi bi-inbox" style="font-size:2rem;"></i>
              <span style="font-size:.85rem;">Nenhum pedido ainda</span>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- ── Faturamento mensal (barras) ──────────────────────── -->
      <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
          <div>
            <h5 style="font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--choco);margin:0;display:flex;align-items:center;gap:.5rem;">
              <span style="display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,var(--rose),var(--rose-dark));border-radius:4px;"></span>
              Faturamento Mensal
            </h5>
            <p style="font-size:.75rem;color:var(--muted);margin:.25rem 0 0;">Últimos 6 meses</p>
          </div>
        </div>
        <canvas id="chartFaturamento" height="60"
          data-labels='<?= json_encode(array_column($faturMes, 'mes_label')) ?>'
          data-values='<?= json_encode(array_column($faturMes, 'faturamento')) ?>'></canvas>
      </div>

      <!-- ── Tabela: últimos 6 meses ──────────────────────────── -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.5rem;">

        <!-- Resumo mensal -->
        <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;">
          <h5 style="font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--choco);margin:0 0 1.25rem;display:flex;align-items:center;gap:.5rem;">
            <span style="display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,#43A047,#2E7D32);border-radius:4px;"></span>
            Resumo — Últimos 6 Meses
          </h5>
          <table class="admin-table" style="font-size:.83rem;">
            <thead>
              <tr>
                <th>Mês</th>
                <th>Receita</th>
                <th>Pedidos</th>
                <th>Variação</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($resumoMeses) as $mes): ?>
                <tr>
                  <td style="font-weight:600;color:var(--dark);"><?= htmlspecialchars($mes['mes']) ?></td>
                  <td><strong style="color:var(--rose);"><?= htmlspecialchars($mes['receita']) ?></strong></td>
                  <td><?= (int)$mes['atendimentos'] ?></td>
                  <td>
                    <?php if ($mes['variacao'] > 0): ?>
                      <span style="background:#e8f5e9;color:#43A047;font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:50px;display:inline-flex;align-items:center;gap:.2rem;">
                        <i class="bi bi-arrow-up-short"></i> <?= htmlspecialchars($mes['variacaoFormatada']) ?>
                      </span>
                    <?php elseif ($mes['variacao'] < 0): ?>
                      <span style="background:#ffebee;color:#E53935;font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:50px;display:inline-flex;align-items:center;gap:.2rem;">
                        <i class="bi bi-arrow-down-short"></i> <?= htmlspecialchars($mes['variacaoFormatada']) ?>
                      </span>
                    <?php else: ?>
                      <span style="color:var(--muted);font-size:.8rem;">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($resumoMeses)): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Sem dados ainda.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Top produtos -->
        <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;">
          <h5 style="font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--choco);margin:0 0 1.25rem;display:flex;align-items:center;gap:.5rem;">
            <span style="display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,#FB8C00,#E65100);border-radius:4px;"></span>
            🏆 Produtos Mais Vendidos
          </h5>

          <?php if (empty($topProdutos)): ?>
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;color:var(--muted);gap:.5rem;">
              <i class="bi bi-trophy" style="font-size:2rem;"></i>
              <span style="font-size:.85rem;">Sem dados de vendas ainda</span>
            </div>
          <?php else: ?>
            <?php $maxVendas = max(array_column($topProdutos, 'total_vendido')) ?: 1; ?>
            <?php foreach ($topProdutos as $i => $tp): ?>
              <?php $pct = round(($tp['total_vendido'] / $maxVendas) * 100); ?>
              <div style="margin-bottom:1rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem;">
                  <div style="display:flex;align-items:center;gap:.5rem;">
                    <span style="width:22px;height:22px;border-radius:50%;background:<?= ['#C2185B', '#FB8C00', '#1E88E5', '#43A047', '#9C27B0'][$i] ?? '#9e9e9e' ?>;color:#fff;font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center;"><?= $i + 1 ?></span>
                    <span style="font-size:.84rem;font-weight:600;color:var(--dark);"><?= htmlspecialchars($tp['nome_produto']) ?></span>
                  </div>
                  <div style="text-align:right;">
                    <div style="font-size:.78rem;font-weight:700;color:var(--rose);">R$ <?= number_format((float)$tp['receita_total'], 2, ',', '.') ?></div>
                    <div style="font-size:.7rem;color:var(--muted);"><?= (int)$tp['total_vendido'] ?> un.</div>
                  </div>
                </div>
                <div style="background:var(--cream);border-radius:50px;height:6px;overflow:hidden;">
                  <div style="background:<?= ['#C2185B', '#FB8C00', '#1E88E5', '#43A047', '#9C27B0'][$i] ?? '#9e9e9e' ?>;height:100%;width:<?= $pct ?>%;border-radius:50px;transition:width .6s;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>

      <!-- ── Tabela completa de pedidos do período ─────────────── -->
      <div style="background:#fff;border:1.5px solid var(--cream);border-radius:18px;padding:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
          <h5 style="font-size:.9rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--choco);margin:0;display:flex;align-items:center;gap:.5rem;">
            <span style="display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,var(--rose),var(--rose-dark));border-radius:4px;"></span>
            Todos os Pedidos
          </h5>
          <span style="font-size:.78rem;color:var(--muted);background:var(--cream);padding:.3rem .75rem;border-radius:50px;">
            <?= count($todosPedidos) ?> pedido(s) no total
          </span>
        </div>

        <!-- Mini filtro de status inline -->
        <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem;" id="filtroRelatorio">
          <button class="rel-filtro-btn active" data-status="" onclick="filtrarRelatorio('')">Todos</button>
          <?php foreach ($statusLabels as $key => $label): ?>
            <button class="rel-filtro-btn" data-status="<?= $key ?>" onclick="filtrarRelatorio('<?= $key ?>')">
              <?= htmlspecialchars($label) ?>
              <span style="background:rgba(0,0,0,.1);border-radius:50px;padding:.05rem .4rem;font-size:.7rem;margin-left:.2rem;"><?= $contStatus[$key] ?? 0 ?></span>
            </button>
          <?php endforeach; ?>
        </div>

        <div class="table-responsive">
          <table class="admin-table" id="tabelaRelatorio">
            <thead>
              <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Data Entrega</th>
                <th>Pagamento</th>
                <th>Status</th>
                <th>Total</th>
                <th>Criado em</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($todosPedidos as $ped): ?>
                <tr data-status="<?= htmlspecialchars($ped['status']) ?>">
                  <td><strong style="color:var(--rose);">#<?= (int)$ped['id'] ?></strong></td>
                  <td>
                    <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($ped['nome']) ?></div>
                    <div style="font-size:.74rem;color:var(--muted);"><?= htmlspecialchars($ped['telefone']) ?></div>
                  </td>
                  <td>
                    <span style="font-size:.82rem;display:inline-flex;align-items:center;gap:.3rem;">
                      <i class="bi bi-calendar2-event" style="color:var(--rose);"></i>
                      <?= date('d/m/Y', strtotime($ped['data_entrega'])) ?>
                    </span>
                  </td>
                  <td>
                    <?php
                    $icons = ['pix' => 'bi-qr-code', 'dinheiro' => 'bi-cash', 'cartao' => 'bi-credit-card'];
                    $ic = $icons[$ped['forma_pagamento']] ?? 'bi-credit-card';
                    ?>
                    <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.82rem;">
                      <i class="bi <?= $ic ?>" style="color:var(--choco-light);"></i>
                      <?= ucfirst(htmlspecialchars($ped['forma_pagamento'])) ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge-status <?= $statusBadge[$ped['status']] ?? 'badge-secondary' ?>">
                      <?= htmlspecialchars($statusLabels[$ped['status']] ?? $ped['status']) ?>
                    </span>
                  </td>
                  <td><strong style="color:var(--rose);">R$ <?= number_format((float)$ped['total'], 2, ',', '.') ?></strong></td>
                  <td style="font-size:.78rem;color:var(--muted);">
                    <?= date('d/m/Y H:i', strtotime($ped['criado_em'])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($todosPedidos)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                    Nenhum pedido registrado.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Rodapé com totais -->
        <div style="display:flex;justify-content:flex-end;gap:2rem;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--cream);">
          <div style="font-size:.82rem;color:var(--muted);">
            Pedidos exibidos: <strong style="color:var(--dark);" id="totalFiltrado"><?= count($todosPedidos) ?></strong>
          </div>
          <div style="font-size:.82rem;color:var(--muted);">
            Total filtrado: <strong style="color:var(--rose);" id="totalReceita">
              R$ <?= number_format(array_sum(array_column($todosPedidos, 'total')), 2, ',', '.') ?>
            </strong>
          </div>
        </div>
      </div>

    </section>

    <!-- Estilos locais para a seção de relatórios -->
    <style>
      .rel-filtro-btn {
        background: var(--cream);
        border: none;
        border-radius: 50px;
        padding: .3rem .85rem;
        font-size: .78rem;
        font-weight: 600;
        color: var(--muted);
        cursor: pointer;
        transition: all .15s;
      }

      .rel-filtro-btn:hover {
        background: var(--rose-pale);
        color: var(--rose);
      }

      .rel-filtro-btn.active {
        background: var(--rose);
        color: #fff;
      }
    </style>

    <!-- Scripts da seção de relatórios -->
    <script>
      /* Filtro inline de status na tabela do relatório */
      function filtrarRelatorio(status) {
        document.querySelectorAll('#filtroRelatorio .rel-filtro-btn').forEach(btn => {
          btn.classList.toggle('active', btn.dataset.status === status);
        });

        let total = 0,
          count = 0;
        document.querySelectorAll('#tabelaRelatorio tbody tr[data-status]').forEach(tr => {
          const show = !status || tr.dataset.status === status;
          tr.style.display = show ? '' : 'none';
          if (show) {
            count++;
            // Soma o valor da coluna "Total" (última coluna com R$)
            const celula = tr.querySelector('td:nth-child(6) strong');
            if (celula) {
              const val = parseFloat(celula.textContent.replace('R$', '').replace('.', '').replace(',', '.').trim());
              if (!isNaN(val)) total += val;
            }
          }
        });

        document.getElementById('totalFiltrado').textContent = count;
        document.getElementById('totalReceita').textContent =
          'R$ ' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      }

      /* Exportar CSV (básico — adaptável) */
      function exportarRelatorio() {
        const rows = [
          ['#', 'Cliente', 'Entrega', 'Pagamento', 'Status', 'Total', 'Criado em']
        ];
        document.querySelectorAll('#tabelaRelatorio tbody tr[data-status]').forEach(tr => {
          if (tr.style.display === 'none') return;
          const cells = [...tr.querySelectorAll('td')].map(td => '"' + td.innerText.trim().replace(/\n/g, ' ') + '"');
          rows.push(cells);
        });
        const csv = rows.map(r => r.join(',')).join('\n');
        const blob = new Blob(['\ufeff' + csv], {
          type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `relatorio_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
      }
    </script>

    <?php
    /*
 ┌─────────────────────────────────────────────────────────────────────────┐
 │  SEÇÃO: GERENCIAR PACOTES  — substitui a <section id="secPacotes"> atual │
 │  Requer ALTER TABLE no SQL (preco + titulo) — veja test_inserts.sql      │
 └─────────────────────────────────────────────────────────────────────────┘
*/
    ?>

    <!-- ══════════════════════════════════════
     SEÇÃO: PACOTES
══════════════════════════════════════ -->
    <section class="admin-section" id="secPacotes">

      <!-- Cabeçalho -->
      <div class="admin-section-header">
        <div>
          <h2>
            <i class="bi bi-gift" style="color:var(--rose);font-size:1.3rem;"></i>
            Gerenciar Pacotes
          </h2>
          <p class="section-subtitle">Crie e edite os pacotes exibidos no site para seus clientes</p>
        </div>
        <div style="display:flex;gap:.6rem;align-items:center;">
          <button class="btn btn-outline" id="btnToggleView" onclick="togglePacoteView(this)" title="Alternar visualização">
            <i class="bi bi-grid-3x3-gap"></i> Cards
          </button>
          <button class="btn btn-primary" onclick="abrirModalPacote()">
            <i class="bi bi-plus-lg"></i> Novo Pacote
          </button>
        </div>
      </div>

      <!-- Resumo rápido -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <?php
        $totalPac   = count($pacotes);
        $ativosPac  = count(array_filter($pacotes, fn($p) => $p['ativo']));
        $popularPac = array_values(array_filter($pacotes, fn($p) => $p['popular']));
        ?>
        <div style="background:#fff;border:1px solid var(--cream);border-radius:14px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.85rem;">
          <div style="width:42px;height:42px;border-radius:12px;background:var(--rose-pale);display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-gift" style="color:var(--rose);font-size:1.2rem;"></i>
          </div>
          <div>
            <div style="font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Total</div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--dark);line-height:1;"><?= $totalPac ?></div>
          </div>
        </div>
        <div style="background:#fff;border:1px solid var(--cream);border-radius:14px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.85rem;">
          <div style="width:42px;height:42px;border-radius:12px;background:#e8f5e9;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-check-circle-fill" style="color:#43A047;font-size:1.2rem;"></i>
          </div>
          <div>
            <div style="font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Ativos</div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--dark);line-height:1;"><?= $ativosPac ?></div>
          </div>
        </div>
        <div style="background:#fff;border:1px solid var(--cream);border-radius:14px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.85rem;">
          <div style="width:42px;height:42px;border-radius:12px;background:#fff8e1;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-star-fill" style="color:#FB8C00;font-size:1.2rem;"></i>
          </div>
          <div>
            <div style="font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Popular</div>
            <div style="font-size:1rem;font-weight:700;color:var(--dark);line-height:1.3;">
              <?= !empty($popularPac) ? (int)$popularPac[0]['quantidade'] . ' un' : '—' ?>
            </div>
          </div>
        </div>
        <div style="background:#fff;border:1px solid var(--cream);border-radius:14px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.85rem;">
          <div style="width:42px;height:42px;border-radius:12px;background:var(--choco-pale);display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-box-seam" style="color:var(--choco);font-size:1.2rem;"></i>
          </div>
          <div>
            <div style="font-size:.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Inativos</div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--dark);line-height:1;"><?= $totalPac - $ativosPac ?></div>
          </div>
        </div>
      </div>

      <!-- VIEW: CARDS (padrão) -->
      <div id="viewCards">
        <?php if (empty($pacotes)): ?>
          <div style="background:#fff;border:1px solid var(--cream);border-radius:16px;padding:3rem;text-align:center;color:var(--muted);">
            <i class="bi bi-box-seam" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
            <p>Nenhum pacote cadastrado ainda. Clique em <strong>Novo Pacote</strong> para começar.</p>
          </div>
        <?php else: ?>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;">
            <?php foreach ($pacotes as $pac):
              $isPopular = (bool)$pac['popular'];
              $isAtivo   = (bool)$pac['ativo'];
              $precoUnit = isset($pac['preco']) ? (float)$pac['preco'] : null;
              $titulo    = !empty($pac['titulo']) ? $pac['titulo'] : null;
              // Gera cor de fundo baseada na quantidade
              $cores = [
                50  => ['bg' => '#fdf2f8', 'border' => '#f3c6e0', 'accent' => '#C2185B'],
                100 => ['bg' => '#fff8f0', 'border' => '#ffd9a8', 'accent' => '#FB8C00'],
                200 => ['bg' => '#f0f7ff', 'border' => '#a8d4ff', 'accent' => '#1E88E5'],
                300 => ['bg' => '#f2fdf4', 'border' => '#a8e6b4', 'accent' => '#43A047'],
              ];
              $qtd = (int)$pac['quantidade'];
              $cor = $cores[$qtd] ?? ['bg' => '#fafafa', 'border' => '#e0e0e0', 'accent' => '#5D4037'];
            ?>
              <div class="pacote-card <?= $isPopular ? 'pacote-popular' : '' ?> <?= !$isAtivo ? 'pacote-inativo' : '' ?>"
                style="background:<?= $cor['bg'] ?>;border:1.5px solid <?= $isPopular ? $cor['accent'] : $cor['border'] ?>;border-radius:18px;padding:1.5rem;position:relative;transition:transform .18s,box-shadow .18s;cursor:default;"
                onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.09)'"
                onmouseleave="this.style.transform='';this.style.boxShadow=''">

                <!-- Badges topo -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                  <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                    <?php if ($isPopular): ?>
                      <span style="background:<?= $cor['accent'] ?>;color:#fff;font-size:.68rem;font-weight:700;padding:.25rem .65rem;border-radius:50px;letter-spacing:.04em;display:inline-flex;align-items:center;gap:.3rem;">
                        <i class="bi bi-star-fill"></i> MAIS POPULAR
                      </span>
                    <?php endif; ?>
                    <?php if (!$isAtivo): ?>
                      <span style="background:#e0e0e0;color:#757575;font-size:.68rem;font-weight:700;padding:.25rem .65rem;border-radius:50px;letter-spacing:.04em;">
                        INATIVO
                      </span>
                    <?php endif; ?>
                  </div>
                  <!-- Ações rápidas -->
                  <div style="display:flex;gap:.35rem;">
                    <button class="btn btn-xs btn-outline"
                      onclick='abrirModalPacote(<?= htmlspecialchars(json_encode($pac, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)'
                      title="Editar pacote" style="border-radius:8px;">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-xs btn-danger"
                      onclick="excluirPacote(<?= (int)$pac['id'] ?>, <?= (int)$pac['quantidade'] ?>)"
                      title="Desativar" style="border-radius:8px;">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>

                <!-- Quantidade destaque -->
                <div style="margin-bottom:.5rem;">
                  <span style="font-size:3rem;font-weight:900;color:<?= $cor['accent'] ?>;line-height:1;font-family:'Cormorant Garamond',serif;">
                    <?= (int)$pac['quantidade'] ?>
                  </span>
                  <span style="font-size:.9rem;font-weight:600;color:var(--muted);margin-left:.2rem;">unidades</span>
                </div>

                <!-- Título opcional -->
                <?php if ($titulo): ?>
                  <div style="font-size:.9rem;font-weight:700;color:var(--dark);margin-bottom:.35rem;"><?= htmlspecialchars($titulo) ?></div>
                <?php endif; ?>

                <!-- Descrição -->
                <p style="font-size:.82rem;color:var(--muted);margin-bottom:.85rem;line-height:1.45;min-height:36px;">
                  <?= htmlspecialchars($pac['descricao']) ?: '<em>Sem descrição</em>' ?>
                </p>

                <!-- Detalhes -->
                <div style="display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem;">
                  <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;">
                    <i class="bi bi-palette" style="color:<?= $cor['accent'] ?>;"></i>
                    <span style="color:var(--muted);">Até</span>
                    <strong style="color:var(--dark);"><?= (int)$pac['max_sabores'] ?> sabores</strong>
                  </div>
                  <?php if ($precoUnit !== null): ?>
                    <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;">
                      <i class="bi bi-tag" style="color:<?= $cor['accent'] ?>;"></i>
                      <span style="color:var(--muted);">Preço unitário</span>
                      <strong style="color:var(--dark);">R$ <?= number_format($precoUnit, 2, ',', '.') ?></strong>
                    </div>
                    <?php if ($precoUnit > 0): ?>
                      <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;">
                        <i class="bi bi-currency-dollar" style="color:<?= $cor['accent'] ?>;"></i>
                        <span style="color:var(--muted);">Total do pacote</span>
                        <strong style="color:<?= $cor['accent'] ?>;">R$ <?= number_format($precoUnit * $qtd, 2, ',', '.') ?></strong>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                  <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;">
                    <i class="bi bi-calendar3" style="color:<?= $cor['accent'] ?>;"></i>
                    <span style="color:var(--muted);">Criado em</span>
                    <span style="color:var(--dark);"><?= date('d/m/Y', strtotime($pac['criado_em'])) ?></span>
                  </div>
                </div>

                <!-- Barra visual de sabores -->
                <div style="margin-top:auto;">
                  <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--muted);margin-bottom:.3rem;">
                    <span>Variedade de sabores</span>
                    <span><?= (int)$pac['max_sabores'] ?></span>
                  </div>
                  <div style="background:rgba(0,0,0,.07);border-radius:50px;height:5px;overflow:hidden;">
                    <div style="background:<?= $cor['accent'] ?>;height:100%;width:<?= min(100, (int)$pac['max_sabores'] * 8) ?>%;border-radius:50px;transition:width .5s;"></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- VIEW: TABELA (oculta por padrão) -->
      <div id="viewTabela" style="display:none;">
        <div class="admin-card">
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Título / Qtd.</th>
                  <th>Máx. Sabores</th>
                  <th>Descrição</th>
                  <th>Preço Unit.</th>
                  <th>Total</th>
                  <th>Popular</th>
                  <th>Status</th>
                  <th>Criado em</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pacotes as $pac):
                  $precoUnit = isset($pac['preco']) ? (float)$pac['preco'] : null;
                ?>
                  <tr>
                    <td><span style="color:var(--muted);font-size:.8rem;"><?= (int)$pac['id'] ?></span></td>
                    <td>
                      <?php if (!empty($pac['titulo'])): ?>
                        <div style="font-weight:700;font-size:.9rem;color:var(--dark);"><?= htmlspecialchars($pac['titulo']) ?></div>
                      <?php endif; ?>
                      <strong style="color:var(--rose);font-size:1.05rem;"><?= (int)$pac['quantidade'] ?></strong>
                      <small class="text-muted"> un</small>
                    </td>
                    <td><?= (int)$pac['max_sabores'] ?> sabores</td>
                    <td style="max-width:200px;font-size:.83rem;color:var(--muted);"><?= htmlspecialchars($pac['descricao']) ?: '—' ?></td>
                    <td>
                      <?= $precoUnit !== null ? '<strong>R$ ' . number_format($precoUnit, 2, ',', '.') . '</strong>' : '<span style="color:var(--muted);">—</span>' ?>
                    </td>
                    <td>
                      <?php if ($precoUnit !== null && $precoUnit > 0): ?>
                        <strong style="color:var(--rose);">R$ <?= number_format($precoUnit * (int)$pac['quantidade'], 2, ',', '.') ?></strong>
                      <?php else: ?>
                        <span style="color:var(--muted);">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($pac['popular']): ?>
                        <span class="badge-status badge-warning"><i class="bi bi-star-fill"></i> Popular</span>
                      <?php else: ?>
                        <span style="color:var(--muted);font-size:.82rem;">—</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($pac['ativo']): ?>
                        <span class="badge-status badge-success">Ativo</span>
                      <?php else: ?>
                        <span class="badge-status badge-danger">Inativo</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;color:var(--muted);"><?= date('d/m/Y', strtotime($pac['criado_em'])) ?></td>
                    <td style="display:flex;gap:.35rem;">
                      <button class="btn btn-xs btn-outline"
                        onclick='abrirModalPacote(<?= htmlspecialchars(json_encode($pac, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)'
                        title="Editar">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-xs btn-danger"
                        onclick="excluirPacote(<?= (int)$pac['id'] ?>, <?= (int)$pac['quantidade'] ?>)"
                        title="Desativar">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (empty($pacotes)): ?>
                  <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                      <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                      Nenhum pacote cadastrado.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </section>


    <!-- ═══════════════════════════════════════════════════════
     MODAL: CRIAR / EDITAR PACOTE  (versão reformulada)
═══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="modalPacote" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15);">

          <!-- Header decorativo -->
          <div style="background:linear-gradient(135deg,var(--rose-dark) 0%,var(--rose) 100%);padding:1.5rem 1.75rem;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.07);border-radius:50%;"></div>
            <div style="position:absolute;bottom:-30px;right:60px;width:80px;height:80px;background:rgba(255,255,255,.05);border-radius:50%;"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;position:relative;">
              <div>
                <h5 class="modal-title" id="modalPacoteTitulo" style="color:#fff;font-weight:700;font-size:1.15rem;margin:0;display:flex;align-items:center;gap:.5rem;">
                  <i class="bi bi-gift"></i> Novo Pacote
                </h5>
                <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin:.25rem 0 0;">Preencha os dados do pacote que aparecerá no site</p>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
          </div>

          <div class="modal-body" style="padding:1.75rem;background:#fafafa;">
            <form id="formPacote">
              <input type="hidden" name="id" id="pacoteId">

              <!-- Preview card ao vivo -->
              <div id="pacotePreview" style="background:linear-gradient(135deg,#fdf2f8,#fff8f0);border:1.5px solid #f3c6e0;border-radius:16px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1.25rem;">
                <div style="flex-shrink:0;text-align:center;min-width:80px;">
                  <div id="prevQtd" style="font-size:2.5rem;font-weight:900;color:var(--rose);font-family:'Cormorant Garamond',serif;line-height:1;">—</div>
                  <div style="font-size:.7rem;color:var(--muted);font-weight:600;">UNIDADES</div>
                </div>
                <div style="flex:1;border-left:1px solid #f3c6e0;padding-left:1.25rem;">
                  <div id="prevTitulo" style="font-size:.95rem;font-weight:700;color:var(--dark);margin-bottom:.2rem;">—</div>
                  <div id="prevDesc" style="font-size:.8rem;color:var(--muted);margin-bottom:.5rem;">Sem descrição</div>
                  <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <span style="font-size:.75rem;color:var(--muted);"><i class="bi bi-palette" style="color:var(--rose);"></i> <span id="prevSabores">—</span> sabores</span>
                    <span style="font-size:.75rem;color:var(--muted);"><i class="bi bi-tag" style="color:var(--rose);"></i> R$ <span id="prevPreco">—</span>/un</span>
                  </div>
                </div>
                <div style="flex-shrink:0;text-align:right;">
                  <div style="font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem;">Total</div>
                  <div id="prevTotal" style="font-size:1.3rem;font-weight:800;color:var(--rose);">—</div>
                </div>
              </div>

              <!-- Linha 1: Quantidade + Título -->
              <div class="form-row" style="gap:1rem;">
                <div class="form-group" style="flex:1;">
                  <label class="form-label">
                    <i class="bi bi-123" style="color:var(--rose);"></i>
                    Quantidade (un.) <span class="required">*</span>
                  </label>
                  <input type="number" class="form-control" name="quantidade" id="pacoteQtd"
                    required min="1" placeholder="Ex: 100"
                    oninput="atualizarPreview()">
                  <small style="color:var(--muted);font-size:.75rem;">Número total de salgados/doces no pacote</small>
                </div>
                <div class="form-group" style="flex:1;">
                  <label class="form-label">
                    <i class="bi bi-card-text" style="color:var(--rose);"></i>
                    Título do Pacote
                  </label>
                  <input type="text" class="form-control" name="titulo" id="pacoteTitulo"
                    maxlength="60" placeholder="Ex: Kit Festa, Mini Kit…"
                    oninput="atualizarPreview()">
                  <small style="color:var(--muted);font-size:.75rem;">Nome que aparece como destaque (opcional)</small>
                </div>
              </div>

              <!-- Linha 2: Sabores + Preço -->
              <div class="form-row" style="gap:1rem;">
                <div class="form-group" style="flex:1;">
                  <label class="form-label">
                    <i class="bi bi-palette" style="color:var(--rose);"></i>
                    Máx. de Sabores <span class="required">*</span>
                  </label>
                  <input type="number" class="form-control" name="max_sabores" id="pacoteSabores"
                    required min="1" max="50" placeholder="Ex: 5"
                    oninput="atualizarPreview()">
                  <small style="color:var(--muted);font-size:.75rem;">Quantidade máxima de sabores diferentes</small>
                </div>
                <div class="form-group" style="flex:1;">
                  <label class="form-label">
                    <i class="bi bi-currency-dollar" style="color:var(--rose);"></i>
                    Preço por Unidade (R$)
                  </label>
                  <input type="number" class="form-control" name="preco" id="pacotePreco"
                    min="0" step="0.01" placeholder="Ex: 4.50"
                    oninput="atualizarPreview()">
                  <small style="color:var(--muted);font-size:.75rem;">Valor unitário de cada item do pacote</small>
                </div>
              </div>

              <!-- Descrição -->
              <div class="form-group">
                <label class="form-label">
                  <i class="bi bi-chat-quote" style="color:var(--rose);"></i>
                  Descrição / Slogan
                </label>
                <textarea class="form-control" name="descricao" id="pacoteDesc"
                  maxlength="100" rows="2"
                  placeholder="Ex: Ideal para festas médias com muita variedade!"
                  oninput="atualizarPreview()" style="resize:none;"></textarea>
                <div style="display:flex;justify-content:space-between;margin-top:.2rem;">
                  <small style="color:var(--muted);font-size:.75rem;">Aparece como subtítulo no card do site</small>
                  <small id="descContador" style="color:var(--muted);font-size:.75rem;">0/100</small>
                </div>
              </div>

              <!-- Opções: popular + ativo lado a lado -->
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <label style="display:flex;align-items:center;gap:.75rem;background:#fff;border:1.5px solid var(--cream);border-radius:12px;padding:1rem;cursor:pointer;transition:border-color .15s;"
                  id="lblPopular"
                  onmouseenter="this.style.borderColor='var(--warning)'"
                  onmouseleave="this.style.borderColor=document.getElementById('pacotePopular').checked?'var(--warning)':'var(--cream)'">
                  <input class="form-check-input" type="checkbox" name="popular" id="pacotePopular" value="1"
                    onchange="toggleLabel('lblPopular','pacotePopular','#FB8C00')"
                    style="width:20px;height:20px;margin:0;accent-color:#FB8C00;">
                  <div>
                    <div style="font-weight:700;font-size:.88rem;color:var(--dark);display:flex;align-items:center;gap:.4rem;">
                      <i class="bi bi-star-fill" style="color:#FB8C00;"></i> Destacar como Popular
                    </div>
                    <div style="font-size:.74rem;color:var(--muted);">Exibe o selo "Mais Popular" no site</div>
                  </div>
                </label>

                <label style="display:flex;align-items:center;gap:.75rem;background:#fff;border:1.5px solid var(--cream);border-radius:12px;padding:1rem;cursor:pointer;transition:border-color .15s;"
                  id="lblAtivo"
                  onmouseenter="this.style.borderColor='#43A047'"
                  onmouseleave="this.style.borderColor=document.getElementById('pacoteAtivo').checked?'#43A047':'var(--cream)'">
                  <input class="form-check-input" type="checkbox" name="ativo" id="pacoteAtivo" value="1" checked
                    onchange="toggleLabel('lblAtivo','pacoteAtivo','#43A047')"
                    style="width:20px;height:20px;margin:0;accent-color:#43A047;">
                  <div>
                    <div style="font-weight:700;font-size:.88rem;color:var(--dark);display:flex;align-items:center;gap:.4rem;">
                      <i class="bi bi-eye-fill" style="color:#43A047;"></i> Pacote Ativo
                    </div>
                    <div style="font-size:.74rem;color:var(--muted);">Visível para clientes no site</div>
                  </div>
                </label>
              </div>

              <!-- Botão salvar -->
              <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:.5rem;border-radius:12px;font-size:1rem;">
                <i class="bi bi-check2-circle"></i> Salvar Pacote
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Estilos locais para a seção de pacotes ── -->
    <style>
      .pacote-inativo {
        opacity: .55;
        filter: grayscale(.3);
      }

      .pacote-popular {
        box-shadow: 0 4px 18px rgba(0, 0, 0, .08);
      }
    </style>

    <!-- ── Scripts da seção de pacotes ── -->
    <script>
      /* Alterna entre cards e tabela */
      function togglePacoteView(btn) {
        const cards = document.getElementById('viewCards');
        const tabela = document.getElementById('viewTabela');
        if (cards.style.display === 'none') {
          cards.style.display = '';
          tabela.style.display = 'none';
          btn.innerHTML = '<i class="bi bi-grid-3x3-gap"></i> Cards';
        } else {
          cards.style.display = 'none';
          tabela.style.display = '';
          btn.innerHTML = '<i class="bi bi-list-ul"></i> Tabela';
        }
      }

      /* Destaca o label quando o checkbox é marcado */
      function toggleLabel(labelId, checkId, cor) {
        const lbl = document.getElementById(labelId);
        const chk = document.getElementById(checkId);
        lbl.style.borderColor = chk.checked ? cor : 'var(--cream)';
        lbl.style.background = chk.checked ? (cor === '#FB8C00' ? '#fff8e1' : '#e8f5e9') : '#fff';
      }

      /* Atualiza o preview ao vivo */
      function atualizarPreview() {
        const qtd = parseInt(document.getElementById('pacoteQtd').value) || 0;
        const sab = parseInt(document.getElementById('pacoteSabores').value) || 0;
        const preco = parseFloat(document.getElementById('pacotePreco').value) || 0;
        const titulo = document.getElementById('pacoteTitulo').value.trim() || '—';
        const desc = document.getElementById('pacoteDesc').value.trim() || 'Sem descrição';

        document.getElementById('prevQtd').textContent = qtd > 0 ? qtd : '—';
        document.getElementById('prevTitulo').textContent = titulo;
        document.getElementById('prevDesc').textContent = desc;
        document.getElementById('prevSabores').textContent = sab > 0 ? sab : '—';
        document.getElementById('prevPreco').textContent = preco > 0 ? preco.toFixed(2).replace('.', ',') : '—';

        const total = qtd * preco;
        document.getElementById('prevTotal').textContent =
          total > 0 ? 'R$ ' + total.toFixed(2).replace('.', ',') : '—';

        // Contador de caracteres da descrição
        document.getElementById('descContador').textContent =
          document.getElementById('pacoteDesc').value.length + '/100';
      }

      /* Abre o modal de pacote (create ou edit) */
      function abrirModalPacote(pac) {
        const titulo = pac ? 'Editar Pacote' : 'Novo Pacote';
        document.getElementById('modalPacoteTitulo').innerHTML =
          `<i class="bi bi-gift"></i> ${titulo}`;

        document.getElementById('pacoteId').value = pac?.id ?? '';
        document.getElementById('pacoteQtd').value = pac?.quantidade ?? '';
        document.getElementById('pacoteSabores').value = pac?.max_sabores ?? '';
        document.getElementById('pacoteDesc').value = pac?.descricao ?? '';
        document.getElementById('pacotePreco').value = pac?.preco ?? '';
        document.getElementById('pacoteTitulo').value = pac?.titulo ?? '';
        document.getElementById('pacotePopular').checked = pac?.popular == 1;
        document.getElementById('pacoteAtivo').checked = pac ? pac.ativo == 1 : true;

        // Sincroniza estilos dos labels
        toggleLabel('lblPopular', 'pacotePopular', '#FB8C00');
        toggleLabel('lblAtivo', 'lblAtivo', '#43A047');
        // Corrigido:
        document.getElementById('lblAtivo').style.borderColor =
          document.getElementById('pacoteAtivo').checked ? '#43A047' : 'var(--cream)';
        document.getElementById('lblAtivo').style.background =
          document.getElementById('pacoteAtivo').checked ? '#e8f5e9' : '#fff';

        atualizarPreview();
        new bootstrap.Modal(document.getElementById('modalPacote')).show();
      }

      /* Submit do form de pacote */
      document.getElementById('formPacote').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('[type=submit]');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando…';

        const fd = new FormData(this);

        fetch('actions/pacote_salvar.php', {
            method: 'POST',
            body: fd
          })
          .then(r => r.json())
          .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('modalPacote')).hide();
            Swal.fire({
              icon: data.sucesso ? 'success' : 'error',
              title: data.sucesso ? 'Salvo!' : 'Erro',
              text: data.mensagem,
              confirmButtonColor: 'var(--rose)',
              timer: data.sucesso ? 1800 : undefined,
              showConfirmButton: !data.sucesso
            }).then(() => {
              if (data.sucesso) location.reload();
            });
          })
          .catch(() => Swal.fire({
            icon: 'error',
            title: 'Falha de conexão',
            confirmButtonColor: 'var(--rose)'
          }))
          .finally(() => {
            btn.disabled = false;
            btn.innerHTML = orig;
          });
      });

      /* Excluir/desativar pacote */
      function excluirPacote(id, qtd) {
        Swal.fire({
          title: `Desativar pacote de ${qtd} un.?`,
          text: 'O pacote ficará invisível no site, mas não será removido do banco.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#E53935',
          cancelButtonColor: '#9e9e9e',
          confirmButtonText: '<i class="bi bi-eye-slash"></i> Desativar',
          cancelButtonText: 'Cancelar'
        }).then(res => {
          if (!res.isConfirmed) return;
          const fd = new FormData();
          fd.append('id', id);
          fetch('actions/pacote_excluir.php', {
              method: 'POST',
              body: fd
            })
            .then(r => r.json())
            .then(data => {
              Swal.fire({
                icon: data.sucesso ? 'success' : 'error',
                title: data.mensagem,
                confirmButtonColor: 'var(--rose)',
                timer: data.sucesso ? 1600 : undefined,
                showConfirmButton: !data.sucesso
              }).then(() => {
                if (data.sucesso) location.reload();
              });
            });
        });
      }
    </script>

    <!-- ═══════════════ MODAL: PACOTE ═══════════════ -->
    <div class="modal fade" id="modalPacote" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalPacoteTitulo"><i class="bi bi-box-seam"></i> Pacote</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="formPacote">
              <input type="hidden" name="id" id="pacoteId">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Quantidade (un.) <span class="required">*</span></label>
                  <input type="number" class="form-control" name="quantidade" id="pacoteQtd"
                    required min="1" placeholder="Ex: 100">
                </div>
                <div class="form-group">
                  <label class="form-label">Máx. Sabores <span class="required">*</span></label>
                  <input type="number" class="form-control" name="max_sabores" id="pacoteSabores"
                    required min="1" placeholder="Ex: 5">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Descrição</label>
                <input type="text" class="form-control" name="descricao" id="pacoteDesc"
                  maxlength="100" placeholder="Ex: Ideal para festas médias">
              </div>
              <div class="form-row" style="gap:1.5rem;margin-top:.5rem;">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="popular" id="pacotePopular" value="1">
                  <label class="form-check-label" for="pacotePopular">
                    <i class="bi bi-star-fill" style="color:var(--warning);"></i> Marcar como Popular
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="ativo" id="pacoteAtivo" value="1" checked>
                  <label class="form-check-label" for="pacoteAtivo">Pacote ativo</label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:1.5rem;">
                <i class="bi bi-check2-circle"></i> Salvar Pacote
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════ MODAL: NOVO USUÁRIO ═══════════════ -->
    <div class="modal fade" id="modalNovoUsuario" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-person-plus"></i> Novo Usuário</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="formNovoUsuario">
              <div class="form-group">
                <label class="form-label">Nome completo <span class="required">*</span></label>
                <input type="text" class="form-control" name="nome" id="nuNome" required maxlength="100">
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">E-mail <span class="required">*</span></label>
                  <input type="email" class="form-control" name="email" id="nuEmail" required maxlength="150">
                </div>
                <div class="form-group">
                  <label class="form-label">Telefone</label>
                  <input type="tel" class="form-control" name="telefone" id="nuTelefone" maxlength="20" placeholder="(11) 99999-9999">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Senha <span class="required">*</span></label>
                  <input type="password" class="form-control" name="senha" id="nuSenha" required minlength="6" placeholder="Mín. 6 caracteres">
                </div>
                <div class="form-group">
                  <label class="form-label">Tipo <span class="required">*</span></label>
                  <select class="form-control" name="id_tipo" id="nuTipo" required>
                    <option value="2">Cliente</option>
                    <option value="1">Administrador</option>
                  </select>
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:1rem;">
                <i class="bi bi-person-plus"></i> Cadastrar Usuário
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

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
            <form id="formProduto" action="actions/produto_salvar.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="id" id="produtoId">

              <!-- Preview da imagem atual -->
              <div id="produtoImagemWrap" style="display:none;text-align:center;margin-bottom:1rem;">
                <img id="produtoImagemPreview" src="" alt="preview"
                  style="max-height:140px;max-width:100%;border-radius:10px;object-fit:cover;border:1px solid var(--cream);">
                <div style="font-size:.75rem;color:var(--muted);margin-top:.3rem;">Imagem atual</div>
              </div>

              <!-- Upload de imagem -->
              <div class="form-group">
                <label class="form-label"><i class="bi bi-image"></i> Foto do Produto</label>
                <input type="file" class="form-control" name="imagem" id="produtoImagem"
                  accept="image/jpeg,image/png,image/gif,image/webp">
                <div style="font-size:.75rem;color:var(--muted);margin-top:.3rem;">
                  JPG, PNG, GIF ou WEBP · máx. 5 MB · deixe em branco para manter a foto atual
                </div>
              </div>

              <div class="form-row">
                <div class="form-group" style="flex:2;">
                  <label class="form-label">Nome <span class="required">*</span></label>
                  <input type="text" class="form-control" name="nome" id="produtoNome" required maxlength="100">
                </div>
                <div class="form-group">
                  <label class="form-label">Emoji <span style="font-size:.75rem;color:var(--muted);">(fallback)</span></label>
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
                  <!-- Select dinâmico: opções mudam conforme a categoria -->
                  <select class="form-control" name="tag" id="produtoTag">
                    <!-- preenchido por JS -->
                  </select>
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

      /* ── Logout com confirmação ── */
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
          if (r.isConfirmed) this.closest('form').submit();
        });
      });

      /* ── Seção ativa rastreada para reload correto ── */
      let _secaoAtiva = 'secDashboard';

      function recarregarSecao() {
        const nome = _secaoAtiva.replace('sec', '').toLowerCase();
        const params = new URLSearchParams(window.location.search);
        params.set('secao', nome);
        window.location.href = window.location.pathname + '?' + params.toString();
      }

      /* ── Mostrar seção ── */
      function showSection(id, el) {
        _secaoAtiva = id;
        document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        if (el) el.classList.add('active');
        const titles = {
          secDashboard: 'Dashboard',
          secPedidos: 'Pedidos',
          secProdutos: 'Produtos',
          secClientes: 'Clientes',
          secPacotes: 'Pacotes',
          secRelatorios: 'Relatórios'
        };
        document.getElementById('pageTitle').textContent = titles[id] || '';
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
        fetch(`../actions/pedido_detalhe.php?id=${id}`)
          .then(r => r.text())
          .then(html => {
            document.getElementById('modalPedidoBody').innerHTML = html;
          })
          .catch(() => {
            document.getElementById('modalPedidoBody').innerHTML =
              '<p class="text-danger">Erro ao carregar pedido.</p>';
          });
      }

      /* ── Alterar status do pedido ── */
      function abrirAlterarStatus(id, statusAtual) {
        document.getElementById('statusPedidoId').value = id;
        document.getElementById('selectStatus').value = statusAtual;
        new bootstrap.Modal(document.getElementById('modalStatus')).show();
      }

      /* ── Tags disponíveis por categoria ── */
      const _tagsPorCategoria = {
        1: ['Clássico', 'Frito', 'Assado', 'Premium', 'Vegetariano', 'Especial'],
        2: ['Clássico', 'Premium', 'Especial', 'Fruta']
      };

      function _atualizarTagsPorCategoria(catId, tagAtual) {
        const select = document.getElementById('produtoTag');
        const tags = _tagsPorCategoria[parseInt(catId)] || ['Clássico'];
        select.innerHTML = tags.map(t =>
          `<option value="${t}"${t === tagAtual ? ' selected' : ''}>${t}</option>`
        ).join('');
      }

      /* ── Modal de produto ── */
      function abrirModalProduto(prod = null) {
        document.getElementById('modalProdutoTitulo').textContent =
          prod ? '✏️ Editar Produto' : '➕ Novo Produto';
        document.getElementById('produtoId').value = prod?.id ?? '';
        document.getElementById('produtoNome').value = prod?.nome ?? '';
        document.getElementById('produtoEmoji').value = prod?.emoji ?? '';
        document.getElementById('produtoDescricao').value = prod?.descricao ?? '';
        document.getElementById('produtoCat').value = prod?.categoria_id ?? '1';
        document.getElementById('produtoPreco').value = prod?.preco ?? '';
        document.getElementById('produtoAtivo').checked = prod ? prod.ativo == 1 : true;

        // Preenche o select de tags conforme a categoria atual
        _atualizarTagsPorCategoria(prod?.categoria_id ?? 1, prod?.tag ?? 'Clássico');

        // Preview da imagem existente
        const wrap = document.getElementById('produtoImagemWrap');
        const preview = document.getElementById('produtoImagemPreview');
        const fileInput = document.getElementById('produtoImagem');
        fileInput.value = ''; // limpa seleção anterior
        if (prod?.imagem) {
          preview.src = `../uploads/produtos/${prod.imagem}`;
          wrap.style.display = '';
        } else {
          wrap.style.display = 'none';
          preview.src = '';
        }

        new bootstrap.Modal(document.getElementById('modalProduto')).show();
      }

      /* Atualiza tags ao mudar categoria no modal */
      document.getElementById('produtoCat').addEventListener('change', function() {
        _atualizarTagsPorCategoria(this.value, '');
      });

      /* Preview ao selecionar nova imagem */
      document.getElementById('produtoImagem').addEventListener('change', function() {
        const wrap = document.getElementById('produtoImagemWrap');
        const preview = document.getElementById('produtoImagemPreview');
        if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = e => {
            preview.src = e.target.result;
            wrap.style.display = '';
          };
          reader.readAsDataURL(this.files[0]);
        }
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
          fetch('../actions/produto_excluir.php', {
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
                setTimeout(recarregarSecao, 1500);
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
          fetch('../actions/cliente_bloquear.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: `id=${id}&bloqueado=${bloquear}`
            })
            .then(r => {
              if (!r.ok) throw new Error('HTTP ' + r.status);
              return r.json();
            })
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
                setTimeout(recarregarSecao, 1500);
              } else {
                Swal.fire({
                  icon: 'error',
                  title: data.mensagem ?? 'Erro na operação.',
                  confirmButtonColor: 'var(--rose)'
                });
              }
            })
            .catch(err => {
              Swal.fire({
                icon: 'error',
                title: 'Falha na requisição: ' + err.message,
                confirmButtonColor: 'var(--rose)'
              });
            });
        });
      }

      /* ── Modal Pacote ── */
      function abrirModalPacote(pac = null) {
        document.getElementById('modalPacoteTitulo').innerHTML =
          pac ? '<i class="bi bi-pencil"></i> Editar Pacote' :
          '<i class="bi bi-plus-lg"></i> Novo Pacote';
        document.getElementById('pacoteId').value = pac?.id ?? '';
        document.getElementById('pacoteQtd').value = pac?.quantidade ?? '';
        document.getElementById('pacoteSabores').value = pac?.max_sabores ?? '';
        document.getElementById('pacoteDesc').value = pac?.descricao ?? '';
        document.getElementById('pacotePopular').checked = pac ? pac.popular == 1 : false;
        document.getElementById('pacoteAtivo').checked = pac ? pac.ativo == 1 : true;
        new bootstrap.Modal(document.getElementById('modalPacote')).show();
      }

      function excluirPacote(id, qtd) {
        Swal.fire({
          title: `Desativar pacote de ${qtd} un.?`,
          text: 'Ele não aparecerá mais no site.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#e53935',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Sim, desativar',
          cancelButtonText: 'Cancelar'
        }).then(r => {
          if (!r.isConfirmed) return;
          fetch('../actions/pacote_excluir.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: `id=${id}`
            })
            .then(r => r.json())
            .then(data => {
              Swal.fire({
                icon: data.sucesso ? 'success' : 'error',
                title: data.mensagem,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
              });
              if (data.sucesso) setTimeout(recarregarSecao, 1500);
            });
        });
      }

      /* ── Modal Novo Usuário ── */
      function abrirModalNovoUsuario() {
        document.getElementById('formNovoUsuario').reset();
        new bootstrap.Modal(document.getElementById('modalNovoUsuario')).show();
      }

      /* ══════════════════════════════════════════════════════════
         Tudo abaixo precisa do DOM pronto: event listeners e charts
      ══════════════════════════════════════════════════════════ */
      document.addEventListener('DOMContentLoaded', () => {

        /* ── Reabre a seção correta após reload pelo seletor de período ── */
        (function() {
          const params = new URLSearchParams(window.location.search);
          const secao = params.get('secao');
          if (!secao) return;
          const mapa = {
            dashboard: 'secDashboard',
            pedidos: 'secPedidos',
            produtos: 'secProdutos',
            clientes: 'secClientes',
            pacotes: 'secPacotes',
            relatorios: 'secRelatorios'
          };
          const secId = mapa[secao];
          if (!secId) return;
          const linkEl = document.querySelector(`[href="#${secId}"]`);
          showSection(secId, linkEl);
          params.delete('secao');
          const novaUrl = window.location.pathname +
            (params.toString() ? '?' + params.toString() : '');
          window.history.replaceState({}, '', novaUrl);
        })();

        /* ── Flash message ── */
        <?php if ($flash): ?>
          Swal.fire({
            icon: '<?= $flash['tipo'] === 'sucesso' ? 'success' : 'error' ?>',
            title: '<?= addslashes(htmlspecialchars($flash['mensagem'])) ?>',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
          });
        <?php endif; ?>

        /* ── Submit: alterar status do pedido ── */
        document.getElementById('formStatus').addEventListener('submit', function(e) {
          e.preventDefault();
          const pedidoId = document.getElementById('statusPedidoId').value;
          const novoStatus = document.getElementById('selectStatus').value;
          fetch('../actions/alterar_status.php', {
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
                setTimeout(recarregarSecao, 1500);
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Erro ao atualizar status.',
                  confirmButtonColor: 'var(--rose)'
                });
              }
            });
        });

        /* ── Submit: salvar produto ── */
        document.getElementById('formProduto').addEventListener('submit', function(e) {
          e.preventDefault();
          fetch('../actions/produto_salvar.php', {
              method: 'POST',
              body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
              bootstrap.Modal.getInstance(document.getElementById('modalProduto')).hide();
              Swal.fire({
                icon: data.sucesso ? 'success' : 'error',
                title: data.mensagem ?? (data.sucesso ? 'Produto salvo!' : 'Erro ao salvar produto.'),
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
              });
              if (data.sucesso) setTimeout(recarregarSecao, 1500);
            });
        });

        /* ── Submit: salvar pacote ── */
        document.getElementById('formPacote').addEventListener('submit', function(e) {
          e.preventDefault();
          fetch('../actions/pacote_salvar.php', {
              method: 'POST',
              body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
              bootstrap.Modal.getInstance(document.getElementById('modalPacote')).hide();
              Swal.fire({
                icon: data.sucesso ? 'success' : 'error',
                title: data.mensagem,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
              });
              if (data.sucesso) setTimeout(recarregarSecao, 1500);
            });
        });

        /* ── Submit: novo usuário ── */
        document.getElementById('formNovoUsuario').addEventListener('submit', function(e) {
          e.preventDefault();
          const btnSubmit = this.querySelector('button[type="submit"]');
          btnSubmit.disabled = true;
          btnSubmit.textContent = 'Salvando...';
          fetch('../actions/usuario_criar_admin.php', {
              method: 'POST',
              body: new FormData(this)
            })
            .then(r => {
              if (!r.ok) throw new Error('Servidor retornou HTTP ' + r.status);
              return r.text();
            })
            .then(text => {
              try {
                return JSON.parse(text);
              } catch (e) {
                throw new Error('Resposta inválida do servidor. Verifique se a sessão está ativa.\n\nResposta recebida: ' + text.substring(0, 200));
              }
            })
            .then(data => {
              if (data.sucesso) {
                bootstrap.Modal.getInstance(document.getElementById('modalNovoUsuario')).hide();
              }
              Swal.fire({
                icon: data.sucesso ? 'success' : 'error',
                title: data.mensagem ?? (data.sucesso ? 'Usuário cadastrado!' : 'Erro ao cadastrar.'),
                timer: data.sucesso ? 2500 : undefined,
                showConfirmButton: !data.sucesso,
                confirmButtonColor: 'var(--rose)',
                toast: data.sucesso,
                position: data.sucesso ? 'top-end' : 'center'
              });
              if (data.sucesso) setTimeout(recarregarSecao, 1500);
            })
            .catch(err => {
              Swal.fire({
                icon: 'error',
                title: 'Erro ao cadastrar usuário',
                text: err.message,
                confirmButtonColor: 'var(--rose)'
              });
            })
            .finally(() => {
              btnSubmit.disabled = false;
              btnSubmit.innerHTML = '<i class="bi bi-person-plus"></i> Cadastrar Usuário';
            });
        });

        /* ── Máscara de telefone ── */
        document.getElementById('nuTelefone').addEventListener('input', function() {
          let v = this.value.replace(/\D/g, '');
          if (v.length > 11) v = v.slice(0, 11);
          if (v.length > 6) v = `(${v.slice(0,2)}) ${v.slice(2,7)}-${v.slice(7)}`;
          else if (v.length > 2) v = `(${v.slice(0,2)}) ${v.slice(2)}`;
          else if (v.length > 0) v = `(${v}`;
          this.value = v;
        });

        /* ══ CHARTS ══════════════════════════════════════════════ */
        /* chartFaturamento é inicializado abaixo via inicializarChartMensal() */

        /* Top 5 produtos (doughnut) */
        const topNomes = <?= json_encode(array_column($topProdutos, 'nome_produto')) ?>;
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
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: {
                    padding: 12,
                    font: {
                      size: 11
                    },
                    boxWidth: 12,
                    boxHeight: 12
                  }
                }
              },
              cutout: '65%'
            }
          });
        }

        /* Status dos pedidos — secDashboard (doughnut) */
        const ctxStatus = document.getElementById('chartStatus');
        if (ctxStatus) {
          const statusLabels = <?= json_encode(array_values($statusLabels)) ?>;
          const statusData = <?= json_encode(array_values($contStatus)) ?>;
          new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
              labels: statusLabels,
              datasets: [{
                data: statusData,
                backgroundColor: ['#FB8C00', '#1E88E5', '#C2185B', '#43A047', '#E53935'],
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 6
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} pedidos` } }
              },
              cutout: '65%'
            }
          });
        }

        /* Faturamento diário (line) — lê dados do data-* do canvas */
        const ctxDiario = document.getElementById('chartFaturamentoDiario');
        if (ctxDiario) {
          inicializarChartDiario(ctxDiario);
        }

        /* Faturamento mensal (bar) — lê dados do data-* do canvas */
        const ctxFatRel = document.getElementById('chartFaturamento');
        if (ctxFatRel) {
          inicializarChartMensal(ctxFatRel);
        }

        /* Status dos pedidos — secRelatorios (doughnut) — lê dados do data-* */
        const ctxStatusRel = document.getElementById('chartStatusRel');
        if (ctxStatusRel) {
          inicializarChartStatusRel(ctxStatusRel);
        }

      }); // fim DOMContentLoaded
    </script>

    <script>
      /* ── Funções de inicialização de charts (reutilizáveis) ─────── */
      function inicializarChartDiario(canvas) {
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        new Chart(canvas, {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Faturamento Diário (R$)', data: values,
              borderColor: '#C2185B', backgroundColor: 'rgba(194,24,91,.08)',
              tension: 0.4, fill: true, pointRadius: 4,
              pointBackgroundColor: '#C2185B', pointBorderColor: '#fff', pointBorderWidth: 2
            }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } },
              x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 10 } }
            }
          }
        });
      }

      function inicializarChartMensal(canvas) {
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        new Chart(canvas, {
          type: 'bar',
          data: {
            labels: labels.slice(-6),
            datasets: [{
              label: 'Faturamento (R$)', data: values.slice(-6),
              backgroundColor: [
                'rgba(194,24,91,.85)', 'rgba(194,24,91,.75)', 'rgba(194,24,91,.65)',
                'rgba(194,24,91,.55)', 'rgba(194,24,91,.45)', 'rgba(194,24,91,.85)'
              ],
              borderColor: '#C2185B', borderWidth: 0, borderRadius: 8, borderSkipped: false
            }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } },
              x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
          }
        });
      }

      function inicializarChartStatusRel(canvas) {
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        new Chart(canvas, {
          type: 'doughnut',
          data: {
            labels,
            datasets: [{
              data: values,
              backgroundColor: ['#FB8C00', '#1E88E5', '#C2185B', '#43A047', '#E53935'],
              borderWidth: 3, borderColor: '#fff', hoverOffset: 6
            }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} pedidos` } }
            },
            cutout: '65%'
          }
        });
      }

      /* ── Troca de período dos relatórios sem recarregar a página ── */
      function mudarPeriodo(periodo) {
        const secao = document.getElementById('secRelatorios');
        secao.style.opacity = '0.5';
        secao.style.pointerEvents = 'none';

        fetch(window.location.pathname + '?periodo=' + periodo)
          .then(res => res.text())
          .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const novaSec = doc.getElementById('secRelatorios');
            if (!novaSec) return;

            // Destrói os charts existentes ANTES de trocar o innerHTML
            ['chartFaturamentoDiario', 'chartFaturamento', 'chartStatusRel'].forEach(id => {
              const canvas = secao.querySelector('#' + id);
              if (canvas) {
                const inst = Chart.getChart(canvas);
                if (inst) inst.destroy();
              }
            });

            // Substitui o conteúdo
            secao.innerHTML = novaSec.innerHTML;

            // Reinicializa os charts com os dados do data-* dos novos canvas
            const ctxDiario = secao.querySelector('#chartFaturamentoDiario');
            if (ctxDiario) inicializarChartDiario(ctxDiario);

            const ctxMensal = secao.querySelector('#chartFaturamento');
            if (ctxMensal) inicializarChartMensal(ctxMensal);

            const ctxStatusRel = secao.querySelector('#chartStatusRel');
            if (ctxStatusRel) inicializarChartStatusRel(ctxStatusRel);

            // Atualiza estado ativo no seletor de período
            document.querySelectorAll('#seletorPeriodo button').forEach(btn => {
              const ativo = btn.dataset.periodo === periodo;
              btn.style.background = ativo ? 'var(--rose)' : 'transparent';
              btn.style.color      = ativo ? '#fff'        : 'var(--muted)';
            });
          })
          .catch(() => {
            window.location.search = '?periodo=' + periodo;
          })
          .finally(() => {
            secao.style.opacity = '1';
            secao.style.pointerEvents = '';
          });
      }
    </script>
</body>

</html>
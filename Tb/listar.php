<?php
// (Opcional) Exibir erros em desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
Arquivos esperados:
- Pessoas: dados.txt (linhas: "nome;cpf" ou "nome")
- Votos  : votos.txt (linhas: "cpf;nome;opcao;timestamp")
- Turmas : turmas.txt (uma turma por linha)
  (fallback: dados_turma.txt, se turmas.txt não existir)
*/
$ARQ_PESSOAS = __DIR__ . '/dados.txt';
$ARQ_VOTOS   = __DIR__ . '/votos.txt';
$ARQ_TURMAS  = file_exists(__DIR__ . '/turmas.txt')
  ? __DIR__ . '/turmas.txt'
  : (file_exists(__DIR__ . '/dados_turma.txt') ? __DIR__ . '/dados_turma.txt' : null);

// ---------- Funções auxiliares ----------
function soDigitos($s) { return preg_replace('/\D/', '', $s); }
function cpfMask($cpf) {
    $cpf = soDigitos($cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2);
}
function normTurma($s) {
    // normaliza turma para comparação case-insensitive e sem espaços excedentes
    return trim(mb_strtolower($s, 'UTF-8'));
}

// ---------- Carregar Pessoas ----------
$pessoas = []; // cada item: ['nome' => ..., 'cpf' => ''] (cpf vazio se não houver)
if (file_exists($ARQ_PESSOAS)) {
    $linhas = file($ARQ_PESSOAS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '') continue;

        // Tenta "nome;cpf". Se não tiver ";", assume só nome.
        $partes = explode(';', $linha);
        $nome = trim($partes[0]);
        $cpf  = isset($partes[1]) ? soDigitos($partes[1]) : '';
        if ($nome !== '') {
            $pessoas[] = ['nome' => $nome, 'cpf' => $cpf];
        }
    }
}
// Ordenar por nome
usort($pessoas, function($a, $b){ return strcasecmp($a['nome'], $b['nome']); });

// ---------- CPFs e Turmas que já votaram ----------
$cpfsQueVotaram    = [];
$turmasQueVotaram  = []; // chave normalizada com normTurma()

if (file_exists($ARQ_VOTOS)) {
    $linhasVotos = file($ARQ_VOTOS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhasVotos as $linha) {
        $linha = trim($linha);
        if ($linha === '') continue;

        $partes    = array_map('trim', explode(';', $linha));
        $cpfVoto   = isset($partes[0]) ? soDigitos($partes[0]) : '';
        $nomeVoto  = isset($partes[1]) ? $partes[1] : '';

        if ($cpfVoto !== '') {
            // voto de pessoa
            $cpfsQueVotaram[$cpfVoto] = true;
        } else {
            // possível voto de turma no padrão "TURMA: <nome>"
            if (preg_match('/^TURMA:\s*(.+)$/i', $nomeVoto, $m)) {
                $turma = normTurma($m[1]);
                if ($turma !== '') $turmasQueVotaram[$turma] = true;
            }
        }
    }
}

// ---------- Filtro (opcional) por nome/CPF/turma ----------
$filtro  = isset($_GET['q']) ? trim($_GET['q']) : '';
$needleN = mb_strtolower($filtro, 'UTF-8');
$needleC = soDigitos($filtro);

if ($filtro !== '') {
    $pessoas = array_filter($pessoas, function($p) use ($needleN, $needleC) {
        $okNome = (strpos(mb_strtolower($p['nome'], 'UTF-8'), $needleN) !== false);
        $okCpf  = $needleC !== '' ? (strpos($p['cpf'], $needleC) !== false) : false;
        return $okNome || $okCpf; // <-- corrigido
    });
}

// ---------- Carregar Turmas ----------
$turmas = [];
if ($ARQ_TURMAS && file_exists($ARQ_TURMAS)) {
    $turmas = file($ARQ_TURMAS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $turmas = array_map('trim', $turmas);

    // Filtro também nas turmas
    if ($filtro !== '') {
        $turmas = array_filter($turmas, function($t) use ($needleN){
            return (strpos(mb_strtolower($t, 'UTF-8'), $needleN) !== false);
        });
    }
    sort($turmas, SORT_NATURAL | SORT_FLAG_CASE);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Listagem - Pessoas e Turmas</title>
<style>
  body { 
    font-family: Arial, sans-serif; 
    max-width: 1000px; 
    margin: 32px auto; 
    background: url('https://media4.giphy.com/media/v1.Y2lkPTc5MGI3NjExaHV5eDR6Y3BjMHJzaG5jaXlwNnhnZ3d0bHZwNnoyZDRwOWdicDJ0dSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/dXLnSpMDt7CvzRwMa9/giphy.gif') fixed;
    background-size: 50vw 50vh;
    background-repeat: no-repeat;
    background-position: center;
    font-weight: bold;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  h2 { margin: 24px 0 8px; }
  .small { color: #555; font-size: 12px; margin: 4px 0 16px; }
  form.filtro { margin: 12px 0 20px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
  input[type="text"] { padding: 8px; min-width: 280px; }
  button { padding: 8px 12px; }
  .links { margin-left: auto; display: flex; gap: 8px; align-items: center; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 12px; }
  th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
  th { background: #f6f6f6; }
  .ok { color: #0a7; font-weight: bold; }
  .pend { color: #c00; font-weight: bold; }
  .muted { color: #777; }
</style>
</head>
<body>
<h1>Listagem</h1>

<form class="filtro" method="get" action="">
  <input type="text" name="q" placeholder="Buscar por nome, CPF ou turma" value="<?= htmlspecialchars($filtro, ENT_QUOTES, 'UTF-8') ?>">
  <button type="submit">Buscar</button>
  <div class="links">
    <a href="index.html">Voltar</a>
    <a href="votacao.php">Ir para votação</a>
    <a href="resultados_completos.php">Ver resultados completos</a>
  </div>
</form>

<!-- Pessoas -->
<h2>Pessoas cadastradas</h2>
<p class="small">
  Fonte: <code>dados.txt</code> (linhas no formato <em>nome;cpf</em> ou apenas <em>nome</em>).
  <?php if (file_exists($ARQ_VOTOS)): ?>
    Status de voto calculado a partir de <code>votos.txt</code>.
  <?php endif; ?>
</p>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Nome</th>
      <th>CPF</th>
      <th>Status de Voto</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($pessoas)): ?>
    <tr><td colspan="4">Nenhuma pessoa encontrada.</td></tr>
  <?php else: ?>
    <?php $i=1; foreach ($pessoas as $p):
      $cpf = $p['cpf']; $ja = ($cpf !== '' && isset($cpfsQueVotaram[$cpf]));
    ?>
    <tr>
      <td><?= $i++ ?></td>
      <td><?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= $cpf !== '' ? htmlspecialchars(cpfMask($cpf), ENT_QUOTES, 'UTF-8') : '<span class="muted">—</span>' ?></td>
      <td>
        <?php if ($cpf === ''): ?>
          <span class="muted">Sem CPF</span>
        <?php elseif ($ja): ?>
          <span class="ok">Já votou</span>
        <?php else: ?>
          <span class="pend">Não votou</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>

<!-- Turmas -->
<h2>Turmas cadastradas</h2>
<p class="small">
  Fonte: <code><?= $ARQ_TURMAS ? htmlspecialchars(basename($ARQ_TURMAS), ENT_QUOTES, 'UTF-8') : '—' ?></code>,
  uma turma por linha. O status de voto é calculado a partir de <code>votos.txt</code>.
</p>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Turma</th>
      <th>Status de Voto</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($turmas)): ?>
    <tr><td colspan="3">Nenhuma turma encontrada.</td></tr>
  <?php else: ?>
    <?php foreach ($turmas as $j => $turma):
      $ja = isset($turmasQueVotaram[normTurma($turma)]);
    ?>
    <tr>
      <td><?= $j + 1 ?></td>
      <td><?= htmlspecialchars($turma, ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= $ja ? '<span class="ok">Já votou</span>' : '<span class="pend">Não votou</span>' ?></td>
    </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>
</body>
</html>
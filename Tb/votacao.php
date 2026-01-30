<?php
// (Opcional, útil no dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ARQ_PESSOAS = __DIR__ . '/dados.txt'; // linhas: nome;cpf
$ARQ_VOTOS   = __DIR__ . '/votos.txt'; // linhas: cpf;nome;opcao;timestamp

// Lê pessoas (nome;cpf)
$pessoas = [];
if (file_exists($ARQ_PESSOAS)) {
    $linhas = file($ARQ_PESSOAS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $partes = array_map('trim', explode(';', $linha));
        $nome = $partes[0] ?? '';
        $cpf  = preg_replace('/\D/', '', ($partes[1] ?? '')); // normaliza

        if ($nome !== '' && $cpf !== '') {
            $pessoas[] = ['nome' => $nome, 'cpf' => $cpf];
        }
    }
}

// Monta um conjunto (hash) de CPFs que já votaram
$cpfsQueVotaram = [];
if (file_exists($ARQ_VOTOS)) {
    $linhasVotos = file($ARQ_VOTOS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhasVotos as $linha) {
        $partes = array_map('trim', explode(';', $linha));
        $cpfVoto = preg_replace('/\D/', '', ($partes[0] ?? ''));
        if ($cpfVoto !== '') {
            $cpfsQueVotaram[$cpfVoto] = true;
        }
    }
}

// Filtro por nome ou CPF (opcional)
$filtro = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($filtro !== '') {
    $needle = mb_strtolower(preg_replace('/\D/', '', $filtro)) ?: mb_strtolower($filtro);
    $pessoas = array_filter($pessoas, function($p) use ($needle) {
        $nome = mb_strtolower($p['nome']);
        $cpf  = $p['cpf'];
        return str_contains($nome, $needle) || str_contains($cpf, preg_replace('/\D/', '', $needle));
    });
}

// Função pra formatar CPF (###.###.###-##)
function cpfMask($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2);
}

// Ordena por nome (opcional)
usort($pessoas, fn($a,$b) => strcasecmp($a['nome'], $b['nome']));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Listar Nomes</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 900px; margin: 32px auto; }
  h2 { margin-bottom: 8px; }
  form.filtro { margin: 12px 0 20px; display: flex; gap: 8px; }
  input[type="text"] { padding: 8px; flex: 1; }
  button { padding: 8px 12px; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
  th { background: #f6f6f6; }
  .ok { color: #0a7; font-weight: bold; }
  .pend { color: #c00; font-weight: bold; }
  .small { color: #555; font-size: 12px; }
</style>
</head>
<body>

<h2>Lista de Pessoas (Nome + CPF + Status de Voto)</h2>
p class="small">Fonte: <code>dados.txt</code> e <code>votos.txt</code>. Use a busca para filtrar por nome ou CPF.</p>

<form class="filtro" method="get" action="">
  <input type="text" name="q" placeholder="Buscar por nome ou CPF" value="<?= htmlspecialchars($filtro, ENT_QUOTES, 'UTF-8') ?>">
  <button type="submit">Buscar</button>
  index.phpVoltar</a>
</form>

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
      <?php $i = 1; foreach ($pessoas as $p): 
        $cpf = $p['cpf'];
        $jaVotou = isset($cpfsQueVotaram[$cpf]);
      ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars(cpfMask($cpf), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if ($jaVotou): ?>
              span class="ok">Já votou</span>
            <?php else: ?>
              span class="pend">Não votou</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>
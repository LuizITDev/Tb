<?php
// Página para exibir todos os votos completos
$ARQ_VOTOS = __DIR__ . '/votos.txt';
$votos = [];

if (file_exists($ARQ_VOTOS)) {
    $linhas = file($ARQ_VOTOS, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '') continue;

        $partes = array_map('trim', explode(';', $linha));
        $cpf = isset($partes[0]) ? $partes[0] : '';
        $nome = isset($partes[1]) ? $partes[1] : '';
        $opcao = isset($partes[2]) ? $partes[2] : '';
        $timestamp = isset($partes[3]) ? $partes[3] : '';

        $tipo = ($cpf === '') ? 'Turma' : 'Pessoa';
        $votos[] = [
            'tipo' => $tipo,
            'cpf' => $cpf,
            'nome' => $nome,
            'opcao' => $opcao,
            'timestamp' => $timestamp
        ];
    }
}

// Contar votos por opção
$contagem = [];
foreach ($votos as $voto) {
    $op = $voto['opcao'];
    if (!isset($contagem[$op])) $contagem[$op] = 0;
    $contagem[$op]++;
}

// Encontrar a opção mais votada
$maxVotos = 0;
$maisVotado = '';
foreach ($contagem as $op => $count) {
    if ($count > $maxVotos) {
        $maxVotos = $count;
        $maisVotado = $op;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Resultados Completos</title>
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
  h1 { margin-bottom: 16px; }
  table { border-collapse: collapse; width: 100%; margin-bottom: 16px; }
  th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
  th { background: #f6f6f6; }
  .links { margin-top: 16px; }
  .links a { margin: 0 8px; }
</style>
</head>
<body>
<h1>Resultados Completos</h1>

<?php if (empty($votos)): ?>
  <p>Nenhum voto encontrado.</p>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Tipo</th>
        <th>CPF</th>
        <th>Nome</th>
        <th>Opção</th>
        <th>Data/Hora</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($votos as $i => $voto): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($voto['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= $voto['cpf'] !== '' ? htmlspecialchars($voto['cpf'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
        <td><?= htmlspecialchars($voto['nome'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($voto['opcao'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($voto['timestamp'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h2>Contagem de Votos</h2>
  <ul>
  <?php foreach ($contagem as $op => $count): ?>
    <li><strong><?= htmlspecialchars($op, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= $count ?> voto<?= $count != 1 ? 's' : '' ?></li>
  <?php endforeach; ?>
  </ul>

  <?php if ($maisVotado !== ''): ?>
    <h2>Vencedor</h2>
    <p>A opção mais votada é <strong><?= htmlspecialchars($maisVotado, ENT_QUOTES, 'UTF-8') ?></strong> com <?= $maxVotos ?> voto<?= $maxVotos != 1 ? 's' : '' ?>.</p>
  <?php endif; ?>

<?php endif; ?>

<div class="links">
  <a href="votacao.php">Voltar à votação</a>
  <a href="listar.php">Ir para a lista</a>
  <a href="resultado.php">Resultados simples</a>
  <a href="index.html">Início</a>
</div>
</body>
</html>
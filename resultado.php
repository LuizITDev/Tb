<?php
// Só para validar o link e a leitura do arquivo
$ARQ = __DIR__ . '/votos.txt';
$tem = file_exists($ARQ);
$linhas = $tem ? count(file($ARQ, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Resultados - Teste</title>
<style>
  body { 
    font-family: Arial, sans-serif; 
    max-width: 720px; 
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
  .muted { color: #777; }
</style>
</head>
<body>
  <h1>Resultados – Teste</h1>
  <p>Arquivo de votos: <code><?= htmlspecialchars(basename($ARQ), ENT_QUOTES, 'UTF-8') ?></code></p>
  <?php if ($tem): ?>
    <p>Linhas encontradas: <strong><?= $linhas ?></strong></p>
  <?php else: ?>
    <p class="muted">Arquivo <code>votos.txt</code> não encontrado nesta pasta.</p>
  <?php endif; ?>
  <p>
    <a href="votacao.php">Voltar à votação</a> |
    <a href="listar.php">Ir para a lista</a>
  </p>
</body>
</html>
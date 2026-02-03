<?php
// (Opcional) mostrar erros em desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
Este script cadastra turmas em "turmas.txt", uma por linha.
Evita duplicadas, ignorando diferenças de maiúsculas/minúsculas e espaços.
*/

$ARQ_TURMAS = __DIR__ . '/turmas.txt';

$msg  = '';
$erro = '';

/** Normaliza a string da turma (trim e colapsa espaços múltiplos) */
function normalizarTurma($s) {
    // Remove espaços das extremidades
    $s = trim($s);
    // Colapsa espaços internos múltiplos para um único espaço
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

/** Lê turmas existentes (array de strings) */
function lerTurmas($arquivo) {
    if (!file_exists($arquivo)) return [];
    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // Normaliza e remove linhas vazias
    $res = [];
    foreach ($linhas as $linha) {
        $t = normalizarTurma($linha);
        if ($t !== '') $res[] = $t;
    }
    return $res;
}

/** Verifica se já existe a turma (case-insensitive) */
function turmaExiste(array $turmas, $novaTurma) {
    $nova = mb_strtolower($novaTurma);
    foreach ($turmas as $t) {
        if (mb_strtolower($t) === $nova) return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turma = isset($_POST['turma']) ? normalizarTurma($_POST['turma']) : '';

    if ($turma === '') {
        $erro = 'Informe o nome da turma.';
    } else {
        $existentes = lerTurmas($ARQ_TURMAS);

        if (turmaExiste($existentes, $turma)) {
            $erro = 'Esta turma já está cadastrada.';
        } else {
            // Grava a nova turma com lock para evitar condição de corrida
            $linha = $turma . PHP_EOL;
            $ok = @file_put_contents($ARQ_TURMAS, $linha, FILE_APPEND | LOCK_EX);
            if ($ok === false) {
                $erro = 'Não foi possível gravar no arquivo. Verifique permissões da pasta/arquivo.';
            } else {
                $msg = 'Turma cadastrada com sucesso!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cadastrar Turma</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 720px; margin: 32px auto; }
  h2 { margin-bottom: 8px; }
  .msg { color: #0a7; margin: 8px 0; }
  .erro { color: #c00; margin: 8px 0; }
  form { display: flex; gap: 8px; margin: 16px 0; }
  input[type="text"] { flex: 1; padding: 8px; }
  button { padding: 8px 12px; }
  .link { margin-top: 12px; display: inline-block; }
  ul { margin-top: 8px; }
  .muted { color: #666; }
</style>
</head>
<body>

<h2>Cadastro de Turma</h2>

<?php if ($msg): ?>
  <div class="msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($erro): ?>
  <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="">
  <input type="text" name="turma" placeholder="Ex.: ADS1, Tec. 2025 Noite" required>
  <button type="submit">Cadastrar</button>
</form>

<p class="link"><a href="index.php">Voltar</a></p>

<hr>

<h3>Turmas já cadastradas</h3>
<?php
$lista = lerTurmas($ARQ_TURMAS);
if (empty($lista)):
?>
  <p class="muted">Nenhuma turma cadastrada até o momento.</p>
<?php else: ?>
  <ul>
    <?php foreach ($lista as $t): ?>
      <li><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

</body>
</html>
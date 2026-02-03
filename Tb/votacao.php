<?php
// (Opcional em dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ARQ_VOTOS  = __DIR__ . '/votos.txt';
$ARQ_PESSOA = __DIR__ . '/dados.txt';

// ------------------ Funções auxiliares ------------------
function normalizarCPF($cpf) {
    return preg_replace('/\D/', '', $cpf);
}

/**
 * Opção B: aceita qualquer CPF com 11 dígitos (sem DV, sem bloqueio de sequências repetidas).
 */
function validarCPF($cpf) {
    $cpf = normalizarCPF($cpf);
    return strlen($cpf) === 11;
}

function cpfJaVotou($arquivo, $cpf) {
    $cpf = normalizarCPF($cpf);
    if (!file_exists($arquivo)) return false;

    $fh = fopen($arquivo, 'r');
    if (!$fh) return false;

    $ja = false;
    while (($linha = fgets($fh)) !== false) {
        $linha = trim($linha);
        if ($linha === '') continue;

        $partes = explode(';', $linha);
        $cpfLinha = isset($partes[0]) ? preg_replace('/\D/', '', $partes[0]) : '';
        if ($cpfLinha === $cpf) {
            $ja = true;
            break;
        }
    }
    fclose($fh);
    return $ja;
}

function registrarVoto($arquivo, $cpf, $nome, $opcao) {
    $cpf = normalizarCPF($cpf);
    $nome = trim($nome);
    $opcao = trim($opcao);
    $timestamp = date('Y-m-d H:i:s');

    $linha = $cpf . ';' . $nome . ';' . $opcao . ';' . $timestamp . PHP_EOL;
    $ok = @file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);
    return $ok !== false;
}

/**
 * Garante que a pessoa esteja presente no arquivo de pessoas (dados.txt)
 * no formato "nome;cpf" com CPF normalizado (11 dígitos).
 * Não duplica entradas com o mesmo CPF.
 */
function registrarPessoaSeNaoExiste($arquivoPessoas, $nome, $cpf) {
    $nome = trim($nome);
    $cpf  = normalizarCPF($cpf);

    if ($nome === '') return false;
    if (!file_exists($arquivoPessoas)) {
        // cria o arquivo vazio
        @file_put_contents($arquivoPessoas, '');
    }

    // Percorre o arquivo para ver se já existe o CPF cadastrado
    $existe = false;
    $fh = @fopen($arquivoPessoas, 'r');
    if ($fh) {
        while (($linha = fgets($fh)) !== false) {
            $linha = trim($linha);
            if ($linha === '') continue;

            $partes = explode(';', $linha);
            $cpfLinha = isset($partes[1]) ? preg_replace('/\D/', '', $partes[1]) : '';
            if ($cpf !== '' && $cpfLinha === $cpf) {
                $existe = true;
                break;
            }
        }
        fclose($fh);
    }

    if (!$existe) {
        // Grava como "nome;cpf" (se não houver CPF, grava só o nome)
        $linha = $cpf !== '' ? ($nome . ';' . $cpf . PHP_EOL) : ($nome . PHP_EOL);
        $ok = @file_put_contents($arquivoPessoas, $linha, FILE_APPEND | LOCK_EX);
        return $ok !== false;
    }
    return true;
}

// ------------------ Controle da página ------------------
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = isset($_POST['nome'])  ? trim($_POST['nome'])  : '';
    $cpf   = isset($_POST['cpf'])   ? $_POST['cpf']         : '';
    $opcao = isset($_POST['opcao']) ? trim($_POST['opcao']) : '';

    $cpfNum = normalizarCPF($cpf);

    if ($nome === '') {
        $erro = 'Informe o nome.';
    } elseif ($cpfNum === '') {
        $erro = 'Informe o CPF.';
    } elseif (!validarCPF($cpfNum)) {
        $erro = 'CPF inválido.';
    } elseif ($opcao === '') {
        $erro = 'Selecione uma opção de voto.';
    } elseif (cpfJaVotou($ARQ_VOTOS, $cpfNum)) {
        $erro = 'Este CPF já votou. Voto duplicado não é permitido.';
    } else {
        // 1) registra o voto
        if (registrarVoto($ARQ_VOTOS, $cpfNum, $nome, $opcao)) {
            // 2) garante a presença na lista (dados.txt)
            registrarPessoaSeNaoExiste($ARQ_PESSOA, $nome, $cpfNum);

            $msg = 'Voto registrado com sucesso!';
        } else {
            $erro = 'Não foi possível registrar o voto. Verifique permissões da pasta/arquivo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Votação</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 720px; margin: 32px auto; }
  .msg { color: #0a7; margin: 8px 0; }
  .erro { color: #c00; margin: 8px 0; }
  form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
  label { display: grid; gap: 6px; }
  input[type="text"], select { padding: 8px; }
  .full { grid-column: 1 / -1; }
  button { padding: 10px 14px; }
  .link { margin-top: 16px; display: inline-block; }
</style>
</head>
<body>
<h2>Votação</h2>

<?php if ($msg): ?>
  <div class="msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($erro): ?>
  <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="">
  <label>
    Nome
    <input type="text" name="nome" placeholder="Digite o nome completo" required>
  </label>

  <label>
    CPF
    <input
      type="text"
      name="cpf"
      placeholder="000.000.000-00"
      inputmode="numeric"
      pattern="^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$"
      title="Digite um CPF no formato 000.000.000-00"
      required
    >
  </label>

  <label class="full">
    Sua opção de voto
    <select name="opcao" required>
      <option value="">-- selecione --</option>
      <option value="OPCAO_A">Opção A</option>
      <option value="OPCAO_B">Opção B</option>
      <option value="OPCAO_C">Opção C</option>
    </select>
  </label>

  <div class="full">
    <button type="submit">Votar</button>
  </div>
</form>

<p><a href="listar.php">Ir para a lista</a> | <a href="index.php">Voltar</a></p>
</body>
</html>

<?php
// (Opcional em dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ARQ_VOTOS  = __DIR__ . '/votos.txt';
$ARQ_PESSOA = __DIR__ . '/dados.txt';
$ARQ_TURMAS = __DIR__ . '/turmas.txt';

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
        if ($cpfLinha === $cpf && $cpf !== '') {
            $ja = true;
            break;
        }
    }
    fclose($fh);
    return $ja;
}

/**
 * Verifica se uma turma já votou (linhas com CPF vazio e nome "TURMA: <turma>").
 */
function turmaJaVotou($arquivo, $turma) {
    $turma = trim($turma);
    if ($turma === '') return false;
    if (!file_exists($arquivo)) return false;

    $fh = fopen($arquivo, 'r');
    if (!$fh) return false;

    $alvo = 'TURMA: ' . $turma;
    $ja = false;

    while (($linha = fgets($fh)) !== false) {
        $linha = trim($linha);
        if ($linha === '') continue;

        $partes = explode(';', $linha);
        $cpfLinha  = isset($partes[0]) ? preg_replace('/\D/', '', $partes[0]) : '';
        $nomeLinha = isset($partes[1]) ? trim($partes[1]) : '';

        if ($cpfLinha === '' && strcasecmp($nomeLinha, $alvo) === 0) {
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
 * no formato "nome;cpf" com CPF normalizado (11 dígitos). Não duplica por CPF.
 */
function registrarPessoaSeNaoExiste($arquivoPessoas, $nome, $cpf) {
    $nome = trim($nome);
    $cpf  = normalizarCPF($cpf);

    if ($nome === '' || $cpf === '') return false;
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
            if ($cpfLinha === $cpf) {
                $existe = true;
                break;
            }
        }
        fclose($fh);
    }

    if (!$existe) {
        // Grava como "nome;cpf"
        $linha = $nome . ';' . $cpf . PHP_EOL;
        $ok = @file_put_contents($arquivoPessoas, $linha, FILE_APPEND | LOCK_EX);
        return $ok !== false;
    }
    return true;
}

/**
 * Garante que a turma esteja presente no arquivo de turmas (turmas.txt)
 * no formato uma turma por linha. Não duplica por turma (case-insensitive).
 */
function registrarTurmaSeNaoExiste($arquivoTurmas, $turma) {
    $turma = trim($turma);
    if ($turma === '') return false;

    if (!file_exists($arquivoTurmas)) {
        // cria o arquivo vazio
        @file_put_contents($arquivoTurmas, '');
    }

    // Verifica se já existe (case-insensitive)
    $existe = false;
    $fh = @fopen($arquivoTurmas, 'r');
    if ($fh) {
        while (($linha = fgets($fh)) !== false) {
            $linha = trim($linha);
            if ($linha === '') continue;
            if (strcasecmp($linha, $turma) === 0) {
                $existe = true;
                break;
            }
        }
        fclose($fh);
    }

    if (!$existe) {
        // Grava como "turma"
        $linha = $turma . PHP_EOL;
        $ok = @file_put_contents($arquivoTurmas, $linha, FILE_APPEND | LOCK_EX);
        return $ok !== false;
    }
    return true;
}

// ------------------ Controle da página ------------------
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo  = isset($_POST['tipo'])  ? $_POST['tipo']          : 'pessoa'; // 'pessoa' | 'turma'
    $nome  = isset($_POST['nome'])  ? trim($_POST['nome'])    : '';
    $turma = isset($_POST['turma']) ? trim($_POST['turma'])   : '';
    $cpf   = isset($_POST['cpf'])   ? $_POST['cpf']           : '';
    $opcao = isset($_POST['opcao']) ? trim($_POST['opcao'])   : '';

    $cpfNum = normalizarCPF($cpf);

    if ($opcao === '') {
        $erro = 'Selecione uma opção de voto.';
    } elseif ($tipo === 'pessoa') {
        if ($nome === '') {
            $erro = 'Informe o nome.';
        } elseif ($cpfNum === '') {
            $erro = 'Informe o CPF.';
        } elseif (!validarCPF($cpfNum)) {
            $erro = 'CPF inválido.';
        } elseif (cpfJaVotou($ARQ_VOTOS, $cpfNum)) {
            $erro = 'Este CPF já votou. Voto duplicado não é permitido.';
        } else {
            // Nome exibido com a turma (se houver)
            $nomeExibicao = $turma !== '' ? ($nome . ' [Turma: ' . $turma . ']') : $nome;

            if (registrarVoto($ARQ_VOTOS, $cpfNum, $nomeExibicao, $opcao)) {
                // cadastra pessoa (nome;cpf) na lista, mantendo compatibilidade com listar.php
                registrarPessoaSeNaoExiste($ARQ_PESSOA, $nome, $cpfNum);
                $msg = 'Voto registrado com sucesso!';
            } else {
                $erro = 'Não foi possível registrar o voto. Verifique permissões da pasta/arquivo.';
            }
        }
    } elseif ($tipo === 'turma') {
        if ($turma === '') {
            $erro = 'Informe a turma.';
        } elseif (turmaJaVotou($ARQ_VOTOS, $turma)) {
            $erro = 'Esta turma já votou. Voto duplicado não é permitido.';
        } else {
            // Para turma: CPF vazio e "nome" no padrão "TURMA: <nome>"
            $nomeExibicao = 'TURMA: ' . $turma;

            if (registrarVoto($ARQ_VOTOS, '', $nomeExibicao, $opcao)) {
                // cadastra turma na lista, mantendo compatibilidade com listar.php
                registrarTurmaSeNaoExiste($ARQ_TURMAS, $turma);
                $msg = 'Voto da turma registrado com sucesso!';
            } else {
                $erro = 'Não foi possível registrar o voto da turma. Verifique permissões da pasta/arquivo.';
            }
        }
    } else {
        $erro = 'Tipo de voto inválido.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Votação</title>
<style>
  body { 
    font-family: Arial, sans-serif; 
    max-width: 760px; 
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
  .msg { color: #0a7; margin: 8px 0; }
  .erro { color: #c00; margin: 8px 0; }
  form { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
  label { display: grid; gap: 6px; }
  input[type="text"], select { padding: 8px; }
  .full { grid-column: 1 / -1; }
  .row { grid-column: 1 / -1; display: flex; gap: 16px; align-items: center; }
  fieldset { border: 1px solid #ddd; padding: 10px; border-radius: 6px; grid-column: 1 / -1; }
  legend { padding: 0 6px; }
  button { padding: 10px 14px; }
  .links { margin-top: 16px; display: flex; gap: 10px; }
  small.hint { color: #555; }
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
  <fieldset>
    <legend>Tipo de voto</legend>
    <div class="row">
      <label><input type="radio" name="tipo" value="pessoa" checked> Pessoa</label>
      <label><input type="radio" name="tipo" value="turma"> Turma (sem CPF)</label>
    </div>
  </fieldset>

  <label>
    Nome (pessoa)
    <input type="text" name="nome" placeholder="Digite o nome completo">
  </label>

  <label>
    Turma
    <input type="text" name="turma" placeholder="Ex.: 1º DS - Manhã">
  </label>

  <label>
    CPF (pessoa)
    <input
      type="text"
      name="cpf"
      placeholder="000.000.000-00"
      inputmode="numeric"
      pattern="^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$"
      title="Digite um CPF no formato 000.000.000-00"
    >
    <small class="hint">Para voto por turma, deixe em branco.</small>
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

  <div class="links">
    <a href="listar.php">Ir para a lista</a>
    <a href="resultados_completos.php">Ver resultados completos</a>
    <a href="index.html">Voltar</a>
  </div>
</form>

<script>
// Alterna obrigatoriedade dos campos de acordo com o tipo de voto
(function(){
  const radios = document.querySelectorAll('input[name="tipo"]');
  const nome = document.querySelector('input[name="nome"]');
  const turma = document.querySelector('input[name="turma"]');
  const cpf = document.querySelector('input[name="cpf"]');

  function toggleCampos() {
    const tipo = document.querySelector('input[name="tipo"]:checked').value;
    if (tipo === 'pessoa') {
      nome.required = true;
      cpf.required = true;
      turma.required = false; // opcional para pessoa
    } else {
      nome.required = false;
      cpf.required = false;
      turma.required = true;  // obrigatório para turma
    }
  }
  radios.forEach(r => r.addEventListener('change', toggleCampos));
  toggleCampos();
})();
</script>

</body>
</html>
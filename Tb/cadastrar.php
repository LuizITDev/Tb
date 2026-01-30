<?php
if ($_POST) {
    $nome = $_POST['nome'];
    $cpf  = $_POST['cpf'];

    // Remove pontos e traços do CPF (opcional, mas recomendado)
    $cpf = preg_replace('/\D/', '', $cpf);

    // Salva no formato: nome;cpf
    $linha = $nome . ';' . $cpf . "\n";

    file_put_contents('dados.txt', $linha, FILE_APPEND);
    $msg = "Nome cadastrado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cadastrar</title>
</head>
<body>

<h2>Cadastro</h2>

<?= isset($msg) ? $msg : '' ?>

<form method="post">
<input type="text" name="nome" placeholder="Digite um nome" required>
<input type="text" name ="cpf" placeholder="Digite seu CPF" required>

<button>Cadastrar</button>
</form>

<a href="index.php">Voltar</a>

</body>
</html>

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
</style>
</head>
<body>

<h2>Cadastro</h2>

<?= isset($msg) ? $msg : '' ?>

<form method="post">
<input type="text" name="nome" placeholder="Digite um nome" required>
<input type="text" name ="cpf" placeholder="Digite seu CPF" required>

<button>Cadastrar</button>
</form>

<a href="index.html">Voltar</a>

</body>
</html>

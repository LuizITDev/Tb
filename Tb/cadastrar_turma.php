<?php
if($_POST){
    file_put_contents('dados.txt', $_POST['turma']."\n", FILE_APPEND);
    $msg = "Turma  cadastrada com sucesso!";
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
<input type="text" name="turma" placeholder="Digite sua turma" required>

<button>Cadastrar</button>
</form>

<a href="index.php">Voltar</a>

</body>
</html>
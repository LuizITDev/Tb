<?php
$nomes = file_exists('dados.txt') ? file('dados.txt', FILE_IGNORE_NEW_LINES) : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Lista</title>
</head>
<body>

<h2>Nomes cadastrados</h2>

<?php
if(count($nomes) == 0){
    echo "Nenhum cadastro ainda.";
} else {
    foreach($nomes as $n){
        echo $n . "<br>";
    }
}
?>

<a href="index.php">Voltar</a>

</body>
</html>

<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// Configurações de conexão com o banco de dados
$host = "localhost"; // Servidor do banco de dados
$usuario = "simuladorAdmin";   // Usuário do banco de dados
$senha = "sql1459";         // Senha do banco de dados (vazia por padrão no XAMPP)
$banco = "simulador"; // Nome do banco de dados

// Cria uma nova conexão MySQLi
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se a conexão foi estabelecida com sucesso
if ($conn->connect_error) {
    // Encerra a execução e exibe mensagem de erro em caso de falha
    die("Falha na conexão: " . $conn->connect_error);
}
?>
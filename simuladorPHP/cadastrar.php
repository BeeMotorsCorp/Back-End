<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Inclui o arquivo de conexão com o banco de dados
include "conexao.php";

// Lê os dados JSON recebidos na requisição
$data = json_decode(file_get_contents("php://input"), true);

// Extrai os dados do usuário do array recebido
$nome = $data["nome"];
$sobrenome = $data["sobrenome"];
$cpf = $data["cpf"];
$usuario = $data["usuario"];
// Cria um hash seguro da senha usando bcrypt
$senha = password_hash($data["senha"], PASSWORD_DEFAULT);

// Prepara a query SQL para inserir um novo usuário
$stmt = $conn->prepare("INSERT INTO usuarios (nome, sobrenome, cpf, usuario, senha) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nome, $sobrenome, $cpf, $usuario, $senha);

// Define o cabeçalho da resposta como JSON
header("Content-Type: application/json");
// Executa a query e retorna o resultado como JSON
if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
}
exit;
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Inclui o arquivo de conexão com o banco de dados
include "conexao.php";

// Lê os dados JSON recebidos na requisição
$data = json_decode(file_get_contents("php://input"), true);

// Extrai os dados da simulação do array recebido
$usuario_id = $data["usuario_id"];
$data_simulacao = $data["data"];
$valor = $data["valor"];
$entrada = $data["entrada"];
$juros = $data["juros"];
$meses = $data["meses"];
$valor_final = $data["valorFinal"];
$parcela = $data["parcela"];
$total_pago = $data["totalPago"];

// Prepara a query SQL para inserir uma nova simulação
$stmt = $conn->prepare("INSERT INTO simulacoes (usuario_id, data, valor, entrada, juros, meses, valor_final, parcela, total_pago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issdddddd", $usuario_id, $data_simulacao, $valor, $entrada, $juros, $meses, $valor_final, $parcela, $total_pago);

// Executa a query e retorna o resultado como JSON
if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "erro", "mensagem" => $stmt->error]);
}
?>

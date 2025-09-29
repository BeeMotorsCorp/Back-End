<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include "conexao.php";
// Define o cabeçalho da resposta como JSON
header("Content-Type: application/json");

// Lê os dados JSON recebidos na requisição
$data = json_decode(file_get_contents("php://input"), true);

// Validação de segurança
if (!isset($data['id']) || !isset($data['usuario_id'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Dados incompletos para atualização"
    ]);
    exit;
}

try {
    // Prepara a query com verificação de usuário
    $stmt = $conn->prepare("UPDATE simulacoes SET 
        valor = ?, 
        entrada = ?, 
        juros = ?, 
        meses = ?, 
        valor_final = ?, 
        parcela = ?, 
        total_pago = ? 
        WHERE id = ? AND usuario_id = ?");

    // Associa os parâmetros da query com os valores recebidos
    $stmt->bind_param(
        "dddddddii",
        $data['valor'],
        $data['entrada'],
        $data['juros'],
        $data['meses'],
        $data['valor_final'],
        $data['parcela'],
        $data['total_pago'],
        $data['id'],
        $data['usuario_id']
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["status" => "ok"]);
        } else {
            http_response_code(404);
            echo json_encode([
                "status" => "erro",
                "mensagem" => "Simulação não encontrada ou não pertence ao usuário"
            ]);
        }
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro no servidor: " . $e->getMessage()
    ]);
}
?>
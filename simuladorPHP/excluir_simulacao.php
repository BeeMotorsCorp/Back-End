<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Inclui o arquivo de conexão
include "conexao.php";

// Define o tipo de conteúdo como JSON
header("Content-Type: application/json");

// Verifica se os dados foram recebidos via POST
$data = json_decode(file_get_contents("php://input"), true);

/**
 * Validação de segurança:
 * - Verifica se todos os campos necessários estão presentes
 * - Garante que o usuário só pode excluir suas próprias simulações
 */
if (!isset($data['id']) || !isset($data['usuario_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Dados incompletos para exclusão"
    ]);
    exit;
}

// Prepara e executa a query com verificação de usuário
try {
    $stmt = $conn->prepare("DELETE FROM simulacoes WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $data['id'], $data['usuario_id']);
    
    if ($stmt->execute()) {
        // Verifica se alguma linha foi afetada
        if ($stmt->affected_rows > 0) {
            echo json_encode(["status" => "ok"]);
        } else {
            // Nenhuma linha afetada = simulação não existe ou não pertence ao usuário
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
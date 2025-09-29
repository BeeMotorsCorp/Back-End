<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Inclui o arquivo de conexão com o banco de dados
include "conexao.php";

// Define o cabeçalho para retornar JSON
header("Content-Type: application/json");

/**
 * Verifica se o usuário está autenticado
 * - Impede acesso direto sem usuario_id
 * - Retorna erro 403 (Forbidden) se não autorizado
 */
if (!isset($_GET["usuario_id"])) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode([
        "status" => "erro", 
        "mensagem" => "Acesso não autorizado. Faça login primeiro."
    ]);
    exit;
}

// Converte para inteiro para prevenir SQL Injection
$usuario_id = (int)$_GET["usuario_id"];

/**
 * Consulta no banco de dados:
 * - Filtra apenas simulações do usuário atual
 * - Usa prepared statement para segurança
 */
$stmt = $conn->prepare("SELECT * FROM simulacoes WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

// Formata os resultados como array associativo
$simulacoes = [];
while ($row = $result->fetch_assoc()) {
    $simulacoes[] = [
        'id' => $row['id'],
        'data' => $row['data'],
        'valor' => $row['valor'],
        'entrada' => $row['entrada'],
        'juros' => $row['juros'],
        'meses' => $row['meses'],
        'valor_final' => $row['valor_final'],
        'parcela' => $row['parcela'],
        'total_pago' => $row['total_pago']
    ];
}

// Retorna os dados em formato JSON
echo json_encode($simulacoes);
?>

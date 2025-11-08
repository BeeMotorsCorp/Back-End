<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Define o cabeçalho da resposta como JSON
header("Content-Type: application/json");
// Configura o relatório de erros para mostrar todos
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se o arquivo de conexão existe
if (!file_exists("conexao.php")) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Arquivo de conexão não encontrado"
    ]);
    exit;
}

// Inclui o arquivo de conexão com o banco de dados
include "conexao.php";

// Verifica se a conexão foi estabelecida com sucesso
if (!isset($conn) || !is_object($conn) || $conn->connect_error) {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Falha na conexão com o banco: " . (isset($conn) && is_object($conn) ? $conn->connect_error : 'Sem conexão')
    ]);
    exit;
}

// Verifica se os dados foram recebidos na requisição
$json = file_get_contents("php://input");
if (empty($json)) {
    echo json_encode([
        "status" => "erro", 
        "mensagem" => "Nenhum dado recebido"
    ]);
    exit;
}

// Decodifica os dados JSON recebidos
$data = json_decode($json, true);

// Validação dos campos obrigatórios
if (empty($data['usuario']) || empty($data['senha'])) {
    echo json_encode([
        "status" => "erro", 
        "mensagem" => "Usuário e senha são obrigatórios"
    ]);
    exit;
}

try {
    // Obtém e limpa os dados do usuário
    $usuario = trim($data['usuario']);
    $senha = $data['senha'];

    // Prepara a query SQL para buscar o usuário
    $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE usuario = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    $stmt->bind_param("s", $usuario);

    // Executa a query e verifica erros
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar consulta: " . $stmt->error);
    }

    // Obtém o resultado da query
    $result = $stmt->get_result();

    // Verifica se o usuário foi encontrado
    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Usuário não encontrado. <a href='cadastro.html'>Cadastre-se aqui</a>"
        ]);
        exit;
    }

    // Obtém os dados do usuário
    $row = $result->fetch_assoc();

    // Verifica se a senha está correta
    if (password_verify($senha, $row["senha"])) {
        // Login bem-sucedido - retorna o ID do usuário
        echo json_encode([
            "status" => "ok",
            "usuario_id" => $row["id"]
        ]);
    } else {
        // Senha incorreta
        echo json_encode([
            "status" => "erro",
            "mensagem" => "Senha incorreta"
        ]);
    }
} catch (Exception $e) {
    // Captura e trata exceções
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro no servidor: " . $e->getMessage()
    ]);
}
?>
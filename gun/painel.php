<?php
// api/produtos.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Tratar OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
} 
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

// ==================== CLASSE PRODUTO ====================
class Produto {
    private $conn;
    private $table = 'produtos';

    public function __construct($db) {
        $this->conn = $db;
    }

    // Listar todos os produtos
    public function listar() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Buscar produto por ID
    public function buscarPorId($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Criar produto
    public function criar($dados) {
        $query = "INSERT INTO {$this->table} 
                  (nome, descricao, preco, estoque, calibre, capacidade, peso, marca, categoria, badge, imagem, disponivel) 
                  VALUES 
                  (:nome, :descricao, :preco, :estoque, :calibre, :capacidade, :peso, :marca, :categoria, :badge, :imagem, :disponivel)";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind dos parâmetros
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':preco', $dados['preco']);
        $stmt->bindParam(':estoque', $dados['estoque']);
        $stmt->bindParam(':calibre', $dados['calibre']);
        $stmt->bindParam(':capacidade', $dados['capacidade']);
        $stmt->bindParam(':peso', $dados['peso']);
        $stmt->bindParam(':marca', $dados['marca']);
        $stmt->bindParam(':categoria', $dados['categoria']);
        $stmt->bindParam(':badge', $dados['badge']);
        $stmt->bindParam(':imagem', $dados['imagem']);
        $stmt->bindParam(':disponivel', $dados['disponivel']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }

    // Atualizar produto
    public function atualizar($id, $dados) {
        $query = "UPDATE {$this->table} SET 
                  nome = :nome,
                  descricao = :descricao,
                  preco = :preco,
                  estoque = :estoque,
                  calibre = :calibre,
                  capacidade = :capacidade,
                  peso = :peso,
                  marca = :marca,
                  categoria = :categoria,
                  badge = :badge,
                  imagem = :imagem,
                  disponivel = :disponivel,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':descricao', $dados['descricao']);
        $stmt->bindParam(':preco', $dados['preco']);
        $stmt->bindParam(':estoque', $dados['estoque']);
        $stmt->bindParam(':calibre', $dados['calibre']);
        $stmt->bindParam(':capacidade', $dados['capacidade']);
        $stmt->bindParam(':peso', $dados['peso']);
        $stmt->bindParam(':marca', $dados['marca']);
        $stmt->bindParam(':categoria', $dados['categoria']);
        $stmt->bindParam(':badge', $dados['badge']);
        $stmt->bindParam(':imagem', $dados['imagem']);
        $stmt->bindParam(':disponivel', $dados['disponivel']);
        
        return $stmt->execute();
    }

    // Deletar produto
    public function deletar($id) {
        // Buscar imagem antes de deletar
        $produto = $this->buscarPorId($id);
        
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // Deletar imagem do servidor se existir
            if ($produto && $produto['imagem']) {
                $caminhoImagem = '../' . $produto['imagem'];
                if (file_exists($caminhoImagem)) {
                    unlink($caminhoImagem);
                }
            }
            return true;
        }
        
        return false;
    }
}

// ==================== FUNÇÃO UPLOAD DE IMAGEM ====================
function uploadImagem($arquivo) {
    $uploadDir = '../uploads/';
    
    // Criar diretório se não existir
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Validar arquivo
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $tamanhoMaximo = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($arquivo['type'], $tiposPermitidos)) {
        return ['error' => 'Tipo de arquivo não permitido'];
    }
    
    if ($arquivo['size'] > $tamanhoMaximo) {
        return ['error' => 'Arquivo muito grande. Máximo 5MB'];
    }
    
    // Gerar nome único
    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid('produto_') . '.' . $extensao;
    $caminhoCompleto = $uploadDir . $nomeArquivo;
    
    // Mover arquivo
    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        return ['success' => true, 'path' => 'uploads/' . $nomeArquivo];
    }
    
    return ['error' => 'Erro ao fazer upload'];
}

// ==================== ROTEAMENTO ====================
$db = $database->connect();
$produto = new Produto($db);

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['id']) ? $_GET['id'] : null;

try {
    switch($method) {
        case 'GET':
            if ($path) {
                // Buscar produto específico
                $resultado = $produto->buscarPorId($path);
                if ($resultado) {
                    echo json_encode($resultado);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Produto não encontrado']);
                }
            } else {
                // Listar todos os produtos
                $produtos = $produto->listar();
                echo json_encode($produtos);
            }
            break;
        
        case 'POST':
            // Criar novo produto
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'descricao' => $_POST['descricao'] ?? '',
                'preco' => $_POST['preco'] ?? 0,
                'estoque' => $_POST['estoque'] ?? 0,
                'calibre' => $_POST['calibre'] ?? '',
                'capacidade' => $_POST['capacidade'] ?? '',
                'peso' => $_POST['peso'] ?? '',
                'marca' => $_POST['marca'] ?? '',
                'categoria' => $_POST['categoria'] ?? '',
                'badge' => $_POST['badge'] ?? '',
                'disponivel' => isset($_POST['disponivel']) && $_POST['disponivel'] === 'true' ? 1 : 0,
                'imagem' => ''
            ];
            
            // Upload de imagem
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImagem($_FILES['imagem']);
                if (isset($uploadResult['success'])) {
                    $dados['imagem'] = $uploadResult['path'];
                }
            }
            
            // Validação básica
            if (empty($dados['nome']) || empty($dados['preco'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome e preço são obrigatórios']);
                break;
            }
            
            $id = $produto->criar($dados);
            if ($id) {
                http_response_code(201);
                $novoProduto = $produto->buscarPorId($id);
                echo json_encode($novoProduto);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar produto']);
            }
            break;
        
        case 'PUT':
            if (!$path) {
                http_response_code(400);
                echo json_encode(['error' => 'ID não fornecido']);
                break;
            }
            
            // Ler dados do corpo da requisição
            parse_str(file_get_contents('php://input'), $_PUT);
            
            $dados = [
                'nome' => $_PUT['nome'] ?? '',
                'descricao' => $_PUT['descricao'] ?? '',
                'preco' => $_PUT['preco'] ?? 0,
                'estoque' => $_PUT['estoque'] ?? 0,
                'calibre' => $_PUT['calibre'] ?? '',
                'capacidade' => $_PUT['capacidade'] ?? '',
                'peso' => $_PUT['peso'] ?? '',
                'marca' => $_PUT['marca'] ?? '',
                'categoria' => $_PUT['categoria'] ?? '',
                'badge' => $_PUT['badge'] ?? '',
                'disponivel' => isset($_PUT['disponivel']) && $_PUT['disponivel'] === 'true' ? 1 : 0,
                'imagem' => $_PUT['imagem'] ?? ''
            ];
            
            if ($produto->atualizar($path, $dados)) {
                $produtoAtualizado = $produto->buscarPorId($path);
                echo json_encode($produtoAtualizado);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar produto']);
            }
            break;
        
        case 'DELETE':
            if (!$path) {
                http_response_code(400);
                echo json_encode(['error' => 'ID não fornecido']);
                break;
            }
            
            if ($produto->deletar($path)) {
                echo json_encode(['message' => 'Produto deletado com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao deletar produto']);
            }
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>
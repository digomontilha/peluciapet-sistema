<?php
/**
 * Classe Database - Sistema PelúciaPet
 * Gerenciamento de conexão e operações com MySQL
 * Versão otimizada para hospedagem compartilhada
 */

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = [];
    private $connectionAttempts = 0;
    private $maxAttempts = 3;
    private $isConnected = false;
    
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    /**
     * Singleton pattern para garantir uma única instância
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Carregar configurações do banco
     */
    private function loadConfig() {
        $configFile = __DIR__ . '/../config/config.php';
        
        if (file_exists($configFile)) {
            require_once $configFile;
            
            $this->config = [
                'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
                'port' => defined('DB_PORT') ? DB_PORT : '3306',
                'dbname' => defined('DB_NAME') ? DB_NAME : '',
                'username' => defined('DB_USER') ? DB_USER : '',
                'password' => defined('DB_PASS') ? DB_PASS : '',
                'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false, // Evitar problemas em hospedagem compartilhada
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            ];
        } else {
            throw new Exception("Arquivo de configuração não encontrado: $configFile");
        }
        
        // Validar configurações obrigatórias
        if (empty($this->config['dbname']) || empty($this->config['username'])) {
            throw new Exception("Configurações de banco incompletas");
        }
    }
    
    /**
     * Estabelecer conexão com MySQL
     */
    private function connect() {
        $this->connectionAttempts++;
        
        if ($this->connectionAttempts > $this->maxAttempts) {
            throw new Exception("Máximo de tentativas de conexão excedido");
        }
        
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname'],
                $this->config['charset']
            );
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            // Configurações específicas do MySQL
            $this->connection->exec("SET time_zone = '-03:00'"); // Brasília
            $this->connection->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            $this->connection->exec("SET SESSION wait_timeout = 300"); // 5 minutos
            $this->connection->exec("SET SESSION interactive_timeout = 300");
            
            $this->isConnected = true;
            $this->connectionAttempts = 0;
            
            $this->logEvent('connection_success', 'Conexão estabelecida com sucesso');
            
        } catch (PDOException $e) {
            $this->isConnected = false;
            $this->logEvent('connection_error', $e->getMessage());
            
            // Tentar reconectar se não excedeu tentativas
            if ($this->connectionAttempts < $this->maxAttempts) {
                sleep(1); // Aguardar 1 segundo antes de tentar novamente
                return $this->connect();
            }
            
            throw new Exception("Erro de conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar se está conectado
     */
    public function isConnected() {
        if (!$this->connection) {
            return false;
        }
        
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            $this->isConnected = false;
            return false;
        }
    }
    
    /**
     * Garantir conexão ativa
     */
    private function ensureConnection() {
        if (!$this->isConnected()) {
            $this->connect();
        }
    }
    
    /**
     * Executar query e retornar resultado
     */
    public function query($sql, $params = []) {
        $this->ensureConnection();
        
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Tentar reconectar se conexão foi perdida
            if (strpos($e->getMessage(), 'server has gone away') !== false || 
                strpos($e->getMessage(), 'Lost connection') !== false) {
                $this->connect();
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }
            
            $this->logEvent('query_error', $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw new Exception("Erro ao executar consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar um registro
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Buscar todos os registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Executar comando (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Obter último ID inserido
     */
    public function lastInsertId() {
        $this->ensureConnection();
        return $this->connection->lastInsertId();
    }
    
    /**
     * Iniciar transação
     */
    public function beginTransaction() {
        $this->ensureConnection();
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirmar transação
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Reverter transação
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Verificar se tabela existe
     */
    public function tableExists($tableName) {
        try {
            $sql = "SELECT 1 FROM information_schema.tables 
                    WHERE table_schema = ? AND table_name = ? LIMIT 1";
            $result = $this->fetch($sql, [$this->config['dbname'], $tableName]);
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Listar todas as tabelas
     */
    public function listTables() {
        try {
            $sql = "SELECT table_name FROM information_schema.tables 
                    WHERE table_schema = ? ORDER BY table_name";
            return $this->fetchAll($sql, [$this->config['dbname']]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Contar registros em uma tabela
     */
    public function countRecords($tableName, $where = '', $params = []) {
        try {
            if (!$this->tableExists($tableName)) {
                return 0;
            }
            
            $sql = "SELECT COUNT(*) as total FROM `$tableName`";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            
            $result = $this->fetch($sql, $params);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            $this->logEvent('count_error', $e->getMessage(), ['table' => $tableName]);
            return 0;
        }
    }
    
    /**
     * Obter estatísticas do banco
     */
    public function getStats() {
        $stats = [
            'connection_status' => $this->isConnected(),
            'database_name' => $this->config['dbname'],
            'charset' => $this->config['charset'],
            'tables' => [],
            'total_tables' => 0,
            'total_records' => 0
        ];
        
        try {
            $tables = $this->listTables();
            $stats['total_tables'] = count($tables);
            
            foreach ($tables as $table) {
                $tableName = $table['table_name'];
                $count = $this->countRecords($tableName);
                $stats['tables'][$tableName] = $count;
                $stats['total_records'] += $count;
            }
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Testar conexão e configuração
     */
    public function testConnection() {
        $test = [
            'connection' => false,
            'database_access' => false,
            'tables_exist' => false,
            'can_query' => false,
            'charset_correct' => false,
            'timezone_correct' => false,
            'errors' => []
        ];
        
        try {
            // Testar conexão básica
            if ($this->isConnected()) {
                $test['connection'] = true;
            } else {
                $test['errors'][] = 'Falha na conexão com o banco';
                return $test;
            }
            
            // Testar acesso ao banco
            $result = $this->fetch("SELECT DATABASE() as db_name");
            if ($result && $result['db_name'] === $this->config['dbname']) {
                $test['database_access'] = true;
            } else {
                $test['errors'][] = 'Não foi possível acessar o banco especificado';
            }
            
            // Verificar charset
            $result = $this->fetch("SELECT @@character_set_database as charset");
            if ($result && strpos($result['charset'], 'utf8') !== false) {
                $test['charset_correct'] = true;
            }
            
            // Verificar timezone
            $result = $this->fetch("SELECT @@session.time_zone as timezone");
            if ($result) {
                $test['timezone_correct'] = true;
            }
            
            // Verificar se pode executar queries
            $this->fetch("SELECT 1 as test");
            $test['can_query'] = true;
            
            // Verificar se tabelas principais existem
            $requiredTables = ['categorias', 'produtos', 'tamanhos', 'cores'];
            $existingTables = 0;
            
            foreach ($requiredTables as $table) {
                if ($this->tableExists($table)) {
                    $existingTables++;
                }
            }
            
            $test['tables_exist'] = $existingTables > 0;
            $test['tables_found'] = $existingTables;
            $test['tables_required'] = count($requiredTables);
            
        } catch (Exception $e) {
            $test['errors'][] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Log de eventos
     */
    private function logEvent($type, $message, $context = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'database' => $this->config['dbname'] ?? 'unknown'
        ];
        
        error_log('PelúciaPet Database: ' . json_encode($logData));
    }
    
    /**
     * Limpar recursos ao destruir objeto
     */
    public function __destruct() {
        $this->connection = null;
    }
    
    /**
     * Prevenir clonagem
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialização
     */
    public function __wakeup() {
        throw new Exception("Não é possível deserializar singleton");
    }
}


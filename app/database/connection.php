<?php

class TursoConnection {
    private $dbUrl;
    private $authToken;

    public function __construct() {
        $this->dbUrl = TURSO_DB_URL;
        $this->authToken = TURSO_AUTH_TOKEN;
        
        if (empty($this->dbUrl) || empty($this->authToken)) {
            throw new Exception("Turso credentials not configured. Please set TURSO_DATABASE_URL and TURSO_AUTH_TOKEN in .env");
        }
    }

    private function getHttpUrl() {
        $url = $this->dbUrl;
        if (strpos($url, 'libsql://') === 0) {
            $url = 'https://' . substr($url, 9);
        }
        return $url . '/v2/pipeline';
    }

    public function prepare($sql) {
        return new TursoStatement($sql, $this->dbUrl, $this->authToken);
    }

    public function query($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function exec($sql) {
        $stmt = $this->prepare($sql);
        return $stmt->execute();
    }

    public function lastInsertId() {
        return 0;
    }
}

class TursoStatement {
    private $sql;
    private $params = [];
    private $results = [];
    private $columns = [];
    private $types = [];
    private $dbUrl;
    private $authToken;
    private $currentRow = 0;
    private $executed = false;
    private $rawResponse = null;

    public function __construct($sql, $dbUrl = null, $authToken = null) {
        $this->sql = $sql;
        $this->dbUrl = $dbUrl;
        $this->authToken = $authToken;
    }

    private function getHttpUrl() {
        $url = $this->dbUrl;
        if (strpos($url, 'libsql://') === 0) {
            $url = 'https://' . substr($url, 9);
        }
        return $url . '/v2/pipeline';
    }

    private function convertValue($value) {
        if (is_array($value) && isset($value['type']) && isset($value['value'])) {
            return match($value['type']) {
                'integer' => (int)$value['value'],
                'float' => (float)$value['value'],
                'null' => null,
                default => $value['value'],
            };
        }
        return $value;
    }

    private function convertRow($row) {
        $converted = [];
        foreach ($row as $cell) {
            $converted[] = $this->convertValue($cell);
        }
        return $converted;
    }

    private function executeRequest() {
        $url = $this->getHttpUrl();

        $params = $this->params;
        
        // Check if we have named params
        $firstKey = !empty($params) ? array_keys($params)[0] : null;
        $hasNamedParams = is_string($firstKey) && strpos($firstKey, ':') === 0;
        
        $paramValues = [];
        
        if ($hasNamedParams) {
            // Get param names BEFORE replacing
            preg_match_all('/:([a-zA-Z0-9_]+)/', $this->sql, $matches);
            $paramNames = $matches[1] ?? [];
            
            // Replace named params with positional ?
            $this->sql = preg_replace('/:([a-zA-Z0-9_]+)/', '?', $this->sql);
            
            // Reorder params to match the order they appear in SQL
            foreach ($paramNames as $name) {
                $key = ':' . $name;
                if (isset($params[$key])) {
                    $param = $params[$key];
                    if (is_int($param)) {
                        $paramValues[] = ['type' => 'integer', 'value' => (string)$param];
                    } elseif (is_float($param)) {
                        $paramValues[] = ['type' => 'float', 'value' => $param];
                    } elseif (is_null($param)) {
                        $paramValues[] = ['type' => 'null', 'value' => null];
                    } else {
                        $paramValues[] = ['type' => 'text', 'value' => $param];
                    }
                }
            }
        } else {
            // Handle positional params
            foreach ($params as $param) {
                if (is_int($param)) {
                    $paramValues[] = ['type' => 'integer', 'value' => (string)$param];
                } elseif (is_float($param)) {
                    $paramValues[] = ['type' => 'float', 'value' => $param];
                } elseif (is_null($param)) {
                    $paramValues[] = ['type' => 'null', 'value' => null];
                } else {
                    $paramValues[] = ['type' => 'text', 'value' => $param];
                }
            }
        }

        $requests = [
            ['type' => 'execute', 'stmt' => ['sql' => $this->sql, 'args' => $paramValues]]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['requests' => $requests]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->authToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Turso HTTP error: " . $httpCode . " - " . $response);
        }

        $data = json_decode($response, true);
        $this->rawResponse = $data;
        return $data;
    }

    public function bindValue($param, $value, $type = null) {
        $this->params[$param] = $value;
        return true;
    }

    public function bindParam($param, &$value, $type = null) {
        $this->params[$param] = $value;
        return true;
    }

    public function execute($params = []) {
        if (!empty($params)) {
            $this->params = $params;
        }
        
        $result = $this->executeRequest();
        
        // Debug: print the result structure
        // print_r($result);
        
        if (isset($result['results'][0]['response'])) {
            $resp = $result['results'][0]['response'];
            
            if (isset($resp['result']['rows'])) {
                foreach ($resp['result']['rows'] as $row) {
                    $this->results[] = $this->convertRow($row);
                }
            }
            
            if (isset($resp['result']['cols'])) {
                foreach ($resp['result']['cols'] as $col) {
                    $this->columns[] = $col['name'];
                }
            }
        }
        
        $this->executed = true;
        $this->currentRow = 0;
        return true;
    }

    public function fetch($mode = PDO::FETCH_ASSOC) {
        if (!$this->executed) {
            $this->execute();
        }
        
        if ($this->currentRow >= count($this->results)) {
            $this->currentRow = 0;
            return false;
        }
        
        $row = $this->results[$this->currentRow];
        $this->currentRow++;
        
        if ($mode === PDO::FETCH_ASSOC && !empty($this->columns)) {
            $assoc = [];
            foreach ($this->columns as $i => $col) {
                $assoc[$col] = $row[$i] ?? null;
            }
            return $assoc;
        }
        
        if ($mode === PDO::FETCH_OBJ) {
            $obj = new stdClass();
            foreach ($this->columns as $i => $col) {
                $obj->$col = $row[$i] ?? null;
            }
            return $obj;
        }
        
        return $row;
    }

    public function fetchAll($mode = PDO::FETCH_ASSOC) {
        if (!$this->executed) {
            $this->execute();
        }
        
        if (empty($this->columns)) {
            return [];
        }
        
        $rows = [];
        foreach ($this->results as $row) {
            if ($mode === PDO::FETCH_ASSOC) {
                $assoc = [];
                foreach ($this->columns as $i => $col) {
                    $assoc[$col] = $row[$i] ?? null;
                }
                $rows[] = $assoc;
            } elseif ($mode === PDO::FETCH_OBJ) {
                $obj = new stdClass();
                foreach ($this->columns as $i => $col) {
                    $obj->$col = $row[$i] ?? null;
                }
                $rows[] = $obj;
            } else {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function rowCount() {
        if (!$this->executed) {
            $this->execute();
        }
        return count($this->results);
    }

    public function debug() {
        return [
            'sql' => $this->sql,
            'params' => $this->params,
            'results' => $this->results,
            'columns' => $this->columns,
            'rawResponse' => $this->rawResponse
        ];
    }
}

try {
    $turso = new TursoConnection();
    $pdo = $turso;
} catch (Exception $e) {
    echo 'Connection error! ' . $e->getMessage();
    exit;
}
?>

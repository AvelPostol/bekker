<?php
namespace BazisCM\Workspace\Tools;


class MatchStadyWitdthDB {

    private $db;
    private $user;
    private $pass;
    private $mysqli;
    
    public function __construct() {

        $this->db = 'call_manager';
        $this->user = 'python';
        $this->pass = 'Deep1993';
        $this->port = '30305';
        $this->mysqli = new \mysqli("10.178.200.13", $this->user, $this->pass, $this->db, $this->port);
        
        if ($this->mysqli->connect_error) {
            die("Ошибка подключения: " . $this->mysqli->connect_error);
        }
    }
    public function __destruct() {
        // Закрываем соединение при уничтожении объекта
        $this->mysqli->close();
    }
    public function GetHistoryStady($numer,$idcrmdeal) {

     $Result = $this->Get(['request' => "SELECT * FROM match_stady WHERE numer='$numer'"]);

     if($Result){
        return 'yep';
     } else {

        $data = [
            'numer' => $numer,
            'id_crm_deal' => $idcrmdeal
        ];

        $this->syncDataWithDatabase(['data' => [$data], 'table_name' => 'match_stady']);
        return NULL;
     }

    }

    public function Get($p) {
        $query = $p['request'];

        $result = $this->mysqli->query($query);

        if (!$result) {
            echo "Ошибка выполнения запроса: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
            return false;
        }

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $result->free();

        return $data;
    }

    public function syncDataWithDatabase($p) {
      

        $mysqli = $this->mysqli;
        $table_name = $p['table_name'];
    
        $values = [];
    
        foreach ($p['data'] as $item) {
            $escapedValues = array_map(function ($value) use ($mysqli) {
               
                return "'" . $mysqli->real_escape_string($value) . "'";
            }, $item);
    
            $values[] = '(' . implode(',', $escapedValues) . ')';
        }
    
        $fields = array_keys($p['data'][0]);
        $query = "INSERT INTO $table_name (" . implode(',', $fields) . ") VALUES " . implode(',', $values);
        $stmt = $mysqli->prepare($query);
    
        if (!$stmt) {
            echo "Ошибка подготовки запроса: (" . $mysqli->errno . ") " . $mysqli->error;
            exit();
        }
    
        $stmt->execute();
    }


}

/*
CREATE TABLE IF NOT EXISTS match_stady (
    numer VARCHAR(255) PRIMARY KEY,
    id_crm_deal VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);*/


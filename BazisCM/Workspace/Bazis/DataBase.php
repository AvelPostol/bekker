<?php
namespace BazisCM\Workspace\Bazis;

/*
*
* EXAMPLE 
* ------------
* $database = new DataBase();
* $database->GetMaxContractNumber(['UserUID' => 'N69']);
*
*/


class DataBase {

    private $db;
    private $user;
    private $pass;
    private $mysqli;
    
    public function __construct() {
        $this->Base = new \BazisCM\Workspace\Tools\Base();

        $this->db = 'bazis';
        $this->user = 'python';
        $this->pass = 'Deep1993';
        $this->port = '30305';
        $this->mysqli = new \mysqli("10.178.200.13", $this->user, $this->pass, $this->db, $this->port);
        
        if ($this->mysqli->connect_error) {
            die("Ошибка подключения: " . $this->mysqli->connect_error);
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
    public function __destruct() {
        // Закрываем соединение при уничтожении объекта
        $this->mysqli->close();
    }
    public function GetMaxContractNumber($p) {

      try{

        $prefix = $p['ManagerUserField'];

        //   RU => ANGL
        $alfa = [
            'T'=>'T',
            'K'=>'K',
            'E'=>'E',
            'А'=>'A',
            'O'=>'O',
            'Р'=>'P',
            'Х'=>'X',
            'М'=>'M',
            'Н'=>'H',
            'C'=>'C',
            'В'=>'B'
        ];

        $modifiedText = NULL;

        foreach($alfa as $key_i => $val_i){
            $text = $prefix;
            $modifiedText = str_replace($key_i, $val_i, $text);
        }

        if($modifiedText !== NULL){
            $prefix = $modifiedText;
        }
        
        // Запрос для выбора менеджера
        $managerQuery = "SELECT name FROM bazis_managers WHERE article='$prefix'";
        $managerResult = $this->Get(['request' => $managerQuery]);
  
        if (!$managerResult) {
            $this->Base->writeLog(['body' => 'Ошибка при выборе менеджера', 'meta' => 'DB_BAZIS']);
            return null;
        }
  
        // Получаем имя менеджера
        $managerName = $managerResult[0]['name'];
  
        // Запрос для нахождения максимального числа
        $maxNumberQuery = "
        SELECT MAX(CAST(SUBSTRING(number, LENGTH('$prefix') + 1) AS UNSIGNED)) AS max_number
        FROM bazis_orders
        WHERE number LIKE '$prefix%'
          AND manager = '$managerName'
        ";
        $maxNumberResult = $this->Get(['request' => $maxNumberQuery]);
  
        if (!$maxNumberResult) {
            //$this->Base->writeLog(['body' => 'Ошибка при нахождении максимального числа', 'meta' => 'DB_BAZIS']);
            return [
                'maxNumber' => 99,
              ];
        }
  
        $maxNumber = $maxNumberResult[0]['max_number'];

        return [
            'maxNumber' => $maxNumber,
          ];

      }  catch (\Exception $e) { 
        // Обработка ошибок
        $this->Base->writeLog(['body' => 'Ошибка: ' . $e->getMessage(), 'meta' => 'DB_BAZIS']);
        return null; // Вернуть null или другое значение, чтобы обработать ошибку в вызывающем коде
    }
      

    }
}

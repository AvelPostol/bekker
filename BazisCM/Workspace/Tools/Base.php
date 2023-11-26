<?php
namespace BazisCM\Workspace\Tools;

class Base {

    public function writeLog($data) {
        $logFile = '/mnt/data/bitrix/local/php_interface/classes/BazisCM/log/log_'.$data['meta'].time().'.txt';
        //$logFile = __DIR__.'/log_'.$data['meta'].time().'.txt';
        $formattedData = var_export($data['body'], true);
        file_put_contents($logFile, '<?php $array = ' . $formattedData . ';', FILE_APPEND);
    }
    
}
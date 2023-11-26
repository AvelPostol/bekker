<?php
namespace BazisCM\Workspace\Bazis;

class Controller {

    public function __construct() {
        $this->DataBaseBazis = new DataBase();
    }

    public function Check($p){
        
        $MaxBazisContract = $this->DataBaseBazis->GetMaxContractNumber($p); // maxNumber

        return $MaxBazisContract;

    }

}

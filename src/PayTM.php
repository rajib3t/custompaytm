<?php 

namespace PayTM;
class PayTM{
    public function pay($data){
        return json_encode($data);
    }
}
<?

class Loader extends DataLoader{
    
    function Loader($structName){
        global $IMP;
        $this = & $IMP->getLoader($structName);
    }
    
    
}

?>
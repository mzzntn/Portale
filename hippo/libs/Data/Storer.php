<?

class Storer extends DataStorer{
    
    function Storer($structName){
        global $IMP;
        $this = & $IMP->getStorer($structName);
    }
    
    
}

?>
<?
include_once(LIBS.'/Data/DataLoader.php');
include_once(LIBS.'/Data/Inline/ConditionBuilder_inline.php');


/**
* The class that gets data from a database, using the descriptions given in DataStruct
* and in Binding_db
*/    
class DataLoader_inline extends DataLoader{

	function execute(){

	}

	function numResults(){
		return $this->data->listSize();
	}

	function fetch($start=1, $end=0){
		global $IMP;
		$this->struct->data->reset();
		$params = $this->params;
		$qp = new QueryParams();
		$qp->makePelican($params);
		$params->reset();
		if (!$this->data) $this->data = new PHPelican();
		$this->data->becomeList();
		$this->data->uniqueIndexBy('id');
		//just VERY basic condition checking
		while ($this->struct->data->moveNext()){
			$add = true;
			$params->reset();
			while ($params->moveNext()){
				$element = $params->getName();
				if (!$this->struct->hasElement($element)) continue;
				if ($element == $this->structName) $element = 'id';
				$param = $params->get();
				$comparison = $params->getComparison($element);
				$value = $this->struct->data->get($element);
				if (!$comparison) $comparison = '=';
				if ($comparison == '='){
					if (is_array($param)){
						if (!in_array($value, $param)) $add = false;
					}
					elseif ($value != $param) $add = false;
				}
				if ($add == false) break;
			}
			if ($add) $this->data->addRow($this->struct->data->getRow());
		}
		$cnt = 1;
		while ($this->data->moveNext()){
			$id = $this->data->get('id');
			if ($id) break; //be lazy, data should have all ids or no id
			if (!$id) $this->data->set('id', $cnt);
			$cnt++;
		}
		$this->data->reset();
		//KLUDGE TO GET N2N LOAD WORKING (STORE IS OK)
		$params->reset();
		while ($params->moveNext()){
			$paramElement = $params->getName();
			$param = $params->get();
			if (!$this->struct->hasElement($element)){
				$foreignStruct = $this->typeSpace->getStruct($paramElement);
				$foreignBinding = & $this->bindingManager->getBinding($paramElement);
				$foreignElements = $foreignStruct->getElementsByType($this->structName);
				$foreignElement = $foreignElements[0]; //must be only one
				if ($foreignBinding->type == 'db'){
					$db = $foreignBinding->getDbObject();
					$table = $foreignBinding->n2nTable($foreignElement);
					$thisRef = $foreignBinding->n2nForeignId($foreignElement);
					$foreignRef = $foreignBinding->n2nOwnId($foreignElement);
					$sql = "SELECT {$table}.{$thisRef}, {$table}.{$foreignRef} FROM {$table} WHERE ";
					$cond = '';
					$ids = $param->id;
					foreach ($ids as $id){
						if ($cond) $cond .= ' OR ';
						$cond .= $foreignRef.'='.$id;
					}
					$sql .= $cond;
					$db->execute($sql);
					while ($db->fetchrow()){
						$idThis = $db->result($thisRef);
						if (!is_array($assoc[$idThis])) $assoc[$idThis] = array();
						$assoc[$idThis][] = $db->result($foreignRef);
					}
					$this->data->indexBy('_id_'.$paramElement);
					while ($this->data->moveNext()){
						$id = $this->data->get('id');
						if (is_array($assoc[$id])) foreach ($assoc[$id] as $foreignId){
							$indexing = & $this->data->_indexing['_id_'.$paramElement][$foreignId];
							if (!is_array($indexing)) $indexing = array();
							$indexing[] = & $this->data->_l[$this->data->_indexes['_l']];
						}
					}
					$this->data->reset();
				}
			}
		}
	}
	

}


?>

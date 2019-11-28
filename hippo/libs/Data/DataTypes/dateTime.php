<?
include_once(LIBS.'/Data/DataType.php');

function & dt_DateTime($value=0){
  $obj = new DataT_dateTime();
  if ($value) $obj->set($value);
  //else $obj->now();
  return $obj;
}

class DataT_dateTime extends DataT{
  var $h;
  var $m;
  var $s;
  var $day;
  var $month;
  var $year;
  
  function check($data=0){
    if ( isset($data) && !$this->set($data) ) return DT_ERROR_FORMAT;
    if (!$this->checkValid()) return DT_ERROR_FORMAT;
    return 0;
  }
  
  function get($for='', $otherInfo=''){
    if (!$for || $for == 'db' || $for == 'iso'){
#      if ($otherInfo == 'mssql') return "'".$this->toMssql()."'";  //FIXME: non under 'iso'
      $value = $this->toISO();
      if ($for == 'db') $value = "'$value'";
      return $value;
    }
    elseif ($for == 'user') return $this->toUser();
  }
  
  function set($data, $from='', $otherInfo=''){
    if (!$from || $from == 'db' || $from == 'iso'){
 #     if ($otherInfo == 'mssql') $set = $this->fromMssql($data);
      if (!$set) $set = $this->fromISO($data);
      if ($set) $valid = $this->checkValid();
    }
    if ( (!$from && !$set) || $from == 'user'){
      $set = $this->fromUser($data);
      if ($set) $valid = $this->checkValid();
    }
    if (!$set || !$valid){
      $this->clear();
      return false;
    }
    $this->data = $this->toISO();
    return true;
  }
    
  function fromISO($data){
    if ( preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)(?:[T ](\d{1,2}):(\d{1,2}):(\d{1,2}))?/', $data, $matches) ){
      $this->data = $data;
      $this->year = $matches[1];
      $this->month = $matches[2];
      $this->day = $matches[3];
      $this->h = $matches[4];
      $this->m = $matches[5];
      $this->s = $matches[6];
      $this->data = $data;
      return true;
    }
	elseif ( preg_match('/(\d\d\d\d)(\d\d)(\d\d)(?:(\d{1,2}):(\d{1,2}):(\d{1,2}))?/', $data, $matches) ){
      $this->data = $data;
      $this->year = $matches[1];
      $this->month = $matches[2];
      $this->day = $matches[3];
      $this->h = $matches[4];
      $this->m = $matches[5];
      $this->s = $matches[6];
      $this->data = $data;
      return true;
    }
    return false;
  }
  
  function fromUser($data){
    if (preg_match('/(\d+)\/(\d+)\/(\d+)\s*,?(?:\s+(\d+):(\d+)(?:\:(\d+))?)?/', $data, $matches)){
      $this->year = $matches[3];
      $this->month = $matches[2];
      $this->day = $matches[1];
      $this->h = $matches[4];
      $this->m = $matches[5];
      $this->s = $matches[6];
      return true;
    }
    elseif (preg_match('/(\d\d\d\d)(\d\d)(\d\d)/', $data, $matches)){
      $this->year = $matches[1];
      $this->month = $matches[2];
      $this->day = $matches[3];
      return true;
    }
    return false;
  }
  
  function fromMssql($data){
    $months['Jan'] = 1;
    $months['Feb'] = 2;
    $months['Mar'] = 3;
    $months['Apr'] = 4;
    $months['May'] = 5;
    $months['Jun'] = 6;
    $months['Jul'] = 7;
    $months['Aug'] = 8;
    $months['Sep'] = 9;
    $months['Oct'] = 10;
    $months['Nov'] = 11;
    $months['Dec'] = 12;
    if (preg_match('/(\w\w\w)-(\d+)-(\d+)/', $data, $matches)){
      $this->year = $matches[3];
      $this->month = $months[$matches[1]];
      $this->day = $matches[2];
      return true;
    }
    return false;

  }
  
  function setTime($time){
    if (preg_match('/(\d\d?):(\d\d?)/', $time, $matches)){
      $this->h = $matches[1];
      $this->m = $matches[2];
    }
  }
  
  function clearTime(){
    $this->h = $this->m = $this->s = 0;
  }
  
  function fromTimestamp($time){
    $this->day = date('d', $time);
    $this->month = date('m', $time);
    $this->year = date('Y', $time);
    $this->h = date('H', $time);
    $this->m = date('i', $time);
    $this->s = date('s', $time);        
  }
  
  function toISO(){
    global $IMP;
    if ($this->year && $this->month && $this->day){
      $this->fixVals();
	  if($IMP->config['dataTypes']['dateTime']['convertiMessi'])
		$res = "{$this->year}{$this->month}{$this->day}";
	  else 
	    $res = "{$this->year}-{$this->month}-{$this->day}";
      if (!$IMP->config['dataTypes']['dateTime']['noTimeISO']){
	    if($IMP->config['dataTypes']['dateTime']['convertiMessi'])
          $res .= "{$this->h}:{$this->m}:{$this->s}";
	    else
		  $res .= "T{$this->h}:{$this->m}:{$this->s}";
      }
      return $res;
    }
    return '';
  }
  
  function toUser(){
    $string = '';
    if (intval($this->month) && intval($this->day)) $string = "{$this->day}/{$this->month}";
    if ($this->year){
      if ($string) $string .= '/';
      $string .= $this->year;
    }
    $hms = '';
    if (intval($this->h) || intval($this->m)){
      $hms = $this->h.':'.$this->m;
    }
    if (intval($this->s)) $hms .= ':'.$this->s;
    if ($hms) $string .= ', '.$hms;
    return $string;
  }
  
  function toMssql(){
    return $this->month.'-'.$this->day.'-'.$this->year;
  }
  
  function now(){
    $this->fromTimestamp(time());
  }
  
  function moveToDayEnd(){
    $this->s = 59;
    $this->m = 59;
    $this->h = 23;
  }
  
  function moveToDayStart(){
    $this->s = 0;
    $this->m = 0;
    $this->h = 0;
  }

  function moveSecs($secs){
    $time = mktime ($this->h, $this->m, $this->s + $secs, $this->month, $this->day, $this->year);
    $this->fromTimestamp($time);      
  }
  
  function moveDays($days){
    $time = mktime ($this->h, $this->m, $this->s, $this->month, $this->day + $days, $this->year);
    $this->fromTimestamp($time);      
  }

  function moveMonths($months){
    $time = mktime($this->h, $this->m, $this->s, $this->month + 1, $this->day, $this->year);
    $this->fromTimeStamp($time);
  }
  
  function timeStamp(){
    return mktime($this->h, $this->m, $this->s, $this->month, $this->day, $this->year);
  }
  
  function fixVals(){
    $this->year = intval($this->year);
    $this->month = intval($this->month);
    $this->day = intval($this->day);
    $this->h = intval($this->h);
    $this->m = intval($this->m);
    $this->s = intval($this->s);
    if ($this->year < 100){
      if ($this->year > 60) $this->year += 1900;
      else $this->year += 2000;
    }
    if ($this->month < 10) $this->month = '0'.$this->month;
    if ($this->day < 10) $this->day = '0'. $this->day;
    if ($this->h < 10) $this->h = '0'.$this->h;
    if ($this->m < 10) $this->m = '0'.$this->m;
    if ($this->s < 10) $this->s = '0'.$this->s;
    $this->year = strval($this->year);
    $this->month = strval($this->month);
    $this->day = strval($this->day);
    $this->h = strval($this->h);
    $this->m = strval($this->m);
    $this->s = strval($this->s);
  }
  
  function checkValid(){
    if ($this->h > 24 || $this->h < 0) return 0;
    if ($this->m > 60 || $this->m < 0) return 0;
    if ($this->s > 60 || $this->s < 0) return 0;
    return checkdate($this->month, $this->day, $this->year);
  }
  
  function compare($date){
    $t1 = $this->timeStamp();
    $t2 = $date->timeStamp();
    if ($t1 < $t2) return -1;
    elseif ($t2 < $t1) return 1;
    return 0;
  }
  
  function format($format){
    return date($format, $this->timeStamp());
  }
  
  function clear(){
    parent::clear();
    unset($this->year);
    unset($this->month);
    unset($this->day);
    unset($this->h);
    unset($this->m);
    unset($this->s);
  }

  function makeClone(){
    $d = & dt_DateTime();
    $d->fromISO($this->toISO());
    return $d;
  }
  
  
}

function dateToUser($date){
  $d = & dt_DateTime($date);
  $d->clearTime();
  return $d->toUser();
}

function timeToUser($date){
    $d = & dt_DateTime($date);
    return $d->format('H:i');
}



?>

<?
/**
* Class representing a moment in time
* 
*/
 
class DateTime{
  var $year;
  var $month;
  var $day;
  var $hour;
  var $min;
  var $sec;
  var $msec;
  var $usec;

  /**
  * Creates an empty dateTime object
  */
  function DateTime(){
    $this->year = 0;
    $this->month = 0;
    $this->day = 0;
    $this->hour = 0;
    $this->min = 0;
    $this->sec = 0;
    $this->msec = 0;
    $this->usec = 0;
  }
  
  /**
  * int timeStamp()
  * Get Unix timestamp
  * @return the Unix timestamp of the currently set date (@see PHPdoc#mktime)
  */
  function timeStamp(){
    return mktime($this->hour, $this->min, $this->sec, $this->month, $this->day, $this->year);
  }
  
  /**
  * Interpret a date-time string
  * 
  * @param $string String expressing the date-time
  * @param $format (Optional) (string) Format to apply
  * @param 
  *
  * The parse method will interpret a string to get its date/time meaning. If no
  * format is supplied, it will try to interpret the string using the basic date/time formats.
  * An optional format argument can be given, which should be in the format 
  *
  */
  
  function parse($string, $format=''){
  }
  
  /**
  * void now()
  * Set the dateTime to the current time (up to the second)
  *
  **/
  function now(){
    $this->loadFromTimeStamp( time() );
  }
  
  /**
  * void move(int, int, int, int, int, int)
  * Move the clock of the object (up to the second)
  *
  **/
  function move($years, $months, $days, $hours, $mins, $secs){
    $time = mktime($this->hour+$hours, $this->min+$mins, $this->sec+$secs, $this->month+$months, $this->day+$days, $this->year+$years);
    $this->loadFromTimeStamp($time);
  }
  
  /**
  * void moveDays(int)
  * Move the time by the specified number of days
  *
  * This is just a shortcut to move(0 ,0 ,$num, 0, 0, 0) (@see DateTime.dateTime#move)
  **/
  function moveDays($num){
    $time = mktime ($this->hour, $this->min, $this->sec, $this->month, $this->day + $num, $this->year);
    $this->loadFromTimeStamp($time);
  }
  
  /**
  * void moveMonths(int)
  * Move the time by the specified number of months
  *
  * This is just a shortcut to move(0 ,$num, 0, 0, 0, 0) (@see DateTime.dateTime#move)  
  **/
  function moveMonths($num){
    $time = mktime($this->hour, $this->min, $this->sec, $this->month+$num, $this->day, $this->year);
  }
  
  /**
  * void moveHMS(int, int=0, int=0)
  * Move the time by the specified number of hours, minutes and seconds
  *
  * This is just a shortcut to move(0 ,0, 0, $hours, $mins, $secs) (@see DateTime.DateTime#move)  
  **/
  function moveHMS($hours, $mins=0, $secs=0){
    $time = mktime($this->hour+$hours, $this->min+$mins, $this->sec+$secs, $this->month, $this->day, $this->year);
    $this->loadFromTimeStamp($time);
  }
  
  /**
  * array distance(DateTime)
  * Get the temporal distance from another DateTime object
  *
  * @return an array where keys 'y', 'M', 'd', 'h', 'm', 's', 'ms', 'us' correspond to the distance in each component of the date.
  *
  **/
  function distance(& $dateTime){
    $res['y'] = $dateTime->year - $this->year;
    $res['M'] = $dateTime->month - $this->month;
    $res['d'] = $dateTime->day - $this->day;
    $res['h'] = $dateTime->hour - $this->hour;
    $res['m'] = $dateTime->min - $this->min;
    $res['s'] = $dateTime->sec - $this->sec;
    $res['ms'] = $dateTime->msec - $this->msec;
    $res['us'] = $dateTime->usec - $this->usec;
    return $res;
  }
  
}

?>
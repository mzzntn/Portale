<?
include_once(dirname(__FILE__).'/iniRW.php'); 
if(!defined("STDIN")) {
  define("STDIN", fopen('php://stdin','r'));
}
print "Ciao, benvenuto nell'utility di installazione.\n";
print "Prima di tutto, un po'di controlli.\n";
print "Estensioni:\n";
print "DOMXML: ";
if (!extension_loaded('domxml')){
  print "NO\n";
  print "L'estensione DOMXML è necessaria. Attivala nel php.ini.\n";
  return;
}
else print "OK\n";+
print "GD: ";
if (!extension_loaded('gd')){
  print "NO (le funzioni di manipolazione immagini non funzioneranno)\n";
}
else print "OK\n";
print "Tidy: ";
if (!extension_loaded('tidy')){
  print "NO (le funzioni di verifica W3C non funzioneranno)\n";
}
else print "OK\n"; 
print "Ok, possiamo continuare. Ora devi darmi un po'di informazioni sull'ambiente.\n";
$configGuess = dirname(__FILE__).'/config.ini';
while (!$configDir){
  print "Percorso del file di configurazione [$configGuess]:\n";
  $ans = trim(fgets(STDIN));
  if (!$ans) $configFile = $configGuess;
  else $configFile = $ans;
  $configFile = controllaPercorso($configFile, 'file');
}
$ini = new IniRw($configDir.'/config.ini');
$ans = gets("Percorso http della cartella web (p.es. /applicazioni/portal):");
$ini->config['Percorsi']['HOME'] = $ans;
getConfig($ini->config['Percorsi']['HIPPO'], "Percorso su filesystem della cartella hippo", create_function('$val', '
	if (!controllaPercorso($val, \'dir\') return false;
'));
getConfig($ini->config['Percorsi']['HOMEPATH'], "Percorso su filesystem della cartella web", create_function('$val', '
	if (!controllaPercorso($val, \'dir\') return false;
'));
getConfig($ini->config['Percorsi']['SITE'], "Percorso su filesystem della cartella applicazione", create_function('$val', '
	if (!controllaPercorso($val, \'dir\') return false;
'));
getConfig($ini->config['Percorsi']['SERVER'], "Hostname del server");
$ini->write();

function getConfig(& $config, $message, $check=0){
	if ($config) $message .= '['.$config.']';
	$message .= ":\n";
	$config = gets($message);
	if ($check) while(!$check($config)) $config = gets($message);
	return $config;
}

function gets($message){
	while(!$ans){
		print $message."\n";
		$ans = trim(fgets(STDIN));
	}
	return $ans;	
}



function controllaPercorso($percorso, $tipo){
  if (!file_exists($percorso)){
    print "Il percorso \"$percorso\" non esiste!\n";
    return;
  }
  if ($tipo == 'dir' && !is_dir($percorso)){
    print "\"$percorso\" non è una cartella!\n";
    return;
  }
  if ($tipo == 'file' && !is_file($percorso)){
    print "\"$percorso\" non è una file!\n";
    return;
  }
  return $percorso;
}
?>

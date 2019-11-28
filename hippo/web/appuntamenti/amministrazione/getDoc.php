<?
include_once('../init.php');
if ($_REQUEST['f']){
	$mime = 'application/pdf';
	if (preg_match('/\.(\w+)$/', trim($_REQUEST['f']), $matches)){
		$ext = $matches[1];
		if ($ext == 'rtf') $mime = 'application/rtf';
		if ($ext == 'doc') $mime = 'application/msword';
	}
	$path = PATH_WEBDATA.'/'.$_REQUEST['f'];
	$path = str_replace('..', '', $path);
	if (file_exists($path)){
		$basename = basename($path);
		header('Content-Disposition: attachment; filename="'.$basename.'"');
		readfile($path, 'r');
	}
	else{
		userError('Siamo spiacenti, il file richiesto non &egrave; stato trovato.');
	}
}
?>

<?
#$IMP->debugLevel = 3;
function caricaUtente(){
    global $IMP;
    global $C;
    global $loginFailed;
    
    $strutturaUtente = $C['portal']['struttura_utenti'];
    $strutturaDitte = $C['portal']['struttura_ditte'];
    if ($_REQUEST['login'] && $_REQUEST['password']){
        if ($IMP->security->login($_REQUEST['login'], $_REQUEST['password'])){
            if ($_REQUEST['go']) redirect($_REQUEST['go']);
            $_SESSION['loginFailed'] = false;
        } 
        else $_SESSION['loginFailed'] = true;
    }
    if ($_REQUEST['action'] == 'logout'){
        $IMP->security->logout();
    }
    if ($IMP->security->checkDomain($strutturaUtente) || $IMP->security->checkDomain($strutturaDitte)){
        $loader = & $IMP->getLoader($strutturaUtente);
        $loader->addParam('login', $IMP->security->login);
        $loader->requestAll();
        $loader->request('siti', 3);
        $loader->request('siti.id');
        $loader->request('siti.autenticazione');
        $loader->request('siti.nome');
        $loader->request('siti.descrizione');
        $loader->request('siti.login');
        $loader->request('siti.password');
        $loader->request('siti.url');
        $loader->request('siti.campoLogin');
        $loader->request('siti.campoPassword');
        $loader->request('siti.codEstr');
        $utente = $loader->load();
        while ($utente->moveNext('siti')){
            $id = $utente->get('siti.id');
            $nome = $utente->get('siti.nome');
            $descrizione = $utente->get('siti.descrizione');
            $auth = $utente->get('siti.autenticazione');
            $codEstrSito = $utente->get('siti.codEstr');
            $login = $utente->get('siti.login');
            $password = $utente->get('siti.password');
            $campoLogin = $utente->get('siti.campoLogin');
            $campoPassword = $utente->get('siti.campoPassword');
            $url = $utente->get('siti.url');
            $link = $url;
            $loginFunc = '';
            $loginForm = '';
            if ($auth == 2){
                $urlParts = parse_url($url);
                $query = $urlParts['query'];
                $queryParts = explode('&', $query);

                foreach ($queryParts as $part){
                    list($key, $value) = explode('=', $part);
                    $postParams[$key] = $value;
                }
                $link = "javascript: login_{$id}()";

                $loginFunc = <<<_END_
function login_$id(){
    getObj('user_'+$id).value = '$login';
    getObj('pass_'+$id).value = '$password';
    //document.forms['auth_$id'].$campoLogin.value = '$login';
    //document.forms['auth_$id'].$campoPassword.value = '$password';
    document.forms['auth_$id'].submit();
}
_END_;
$loginForm = <<<_END_
    <form action='$url' method='POST' name='auth_$id'>
    <input type='hidden' name='$campoLogin' id='user_$id'>
    <input type='hidden' name='$campoPassword' id='pass_$id'>
_END_;
                if (is_array($postParams)) foreach ($postParams as $key => $value){
                    if ($key)
                        $loginForm .= "<input type='hidden' name='$key' value='$value'>";

                }
                $loginForm .= "</form>";

            }
            else{
                if (!strstr($link, 'http://')) $link = HOME.'/'.$link;
                if ($codEstrSito) $link .= '?codEstr='.$codEstrSito;
            }
            $siti[$id]['nome'] = $nome;
            $siti[$id]['descrizione'] = $descrizione;

            $siti[$id]['link'] = $link;
            $siti[$id]['loginFunc'] = $loginFunc;
            $siti[$id]['loginForm'] = $loginForm;
        }
    }
    return array($siti, $utente);

}

list($siti, $utente) = caricaUtente();
?>

<?
#i flag possibili sono:
#-'i': inserimento
#-'u': aggiornamento (update)
#-'w': scrittura (i+u)
#-'iw': update dei propri inserimenti
#-'r': lettura
#-'ir': lettura dei propri inserimenti
#
#è consigliabile mantenere i permessi sottostanti:
$IMP->security->policies['_security_user'] = array('r'=>1,'i'=>1,'u'=>0);
$IMP->security->policies['_security_group'] = array('r'=>1,'i'=>0);
$IMP->security->policies['_security_perm'] = array('r'=>1, 'i'=>0);
$IMP->security->policies['_struct'] = array('r'=>1, 'i' => 0);

?>

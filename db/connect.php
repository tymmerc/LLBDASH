<?php
function connect(){ 
    $PARAM_hote='localhost';
    $PARAM_port='3306';
    $PARAM_nom_bd='dbsitellb';
    $PARAM_utilisateur='userdbsite';
    $PARAM_mot_passe='pwd$LLB0';
    $connexion = new PDO('mysql:host='.$PARAM_hote.';port='.$PARAM_port.';dbname='.$PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mot_passe);  
    return $connexion;
    
}

?>


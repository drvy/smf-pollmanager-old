<?php
/*
 ____  ___  __  __    ___  __  __  ____    ____  _____  __    __
(  _ \/ __)(  \/  )  / __)(  \/  )( ___)  (  _ \(  _  )(  )  (  )
 ) _ <\__ \ )    (   \__ \ )    (  )__)    )___/ )(_)(  )(__  )(__
(____/(___/(_/\/\_)  (___/(_/\/\_)(__)    (__)  (_____)(____)(____)
 _  _  ____  ____  _    _
( \/ )(_  _)( ___)( \/\/ ) UNDER (CC) - Attribution-Non-Commercial-Share Alike 3.0 Spain
 \  /  _)(_  )__)  )    (                 by BadStupidMonkey
 \/  (____)(____)(__/\__)          http://www.badstupidmonkey.info
 */
 
# CONFIGURACION
$bsm_settings_file = "Settings.php"; // Archivo de configuracion SMF (Settings.php)
$bsm_login = 'badstupidmonkey'; // usuario para login
$bsm_password = 'root'; // password para login
$this_file = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']; // direcion de este archivo (dejar como esta)
 
##############################>>>> DONT TUCH FROM HERE | NO TOCAR DESDE AQUI <<<<###################################
# HEAD
print '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>BadStupidMonkey - SMF Poll View</title>
<style type="text/css">
<!--
body,td,th {font-family:Arial, Helvetica, sans-serif; font-size: 12px;color: #000;}
body {background-color: #FFF;}
a {color: #000;}
a:link {text-decoration: none;}
a:visited {text-decoration: none;color: #000;}
a:hover {text-decoration: underline;color: #000;}
a:active {text-decoration: none;color: #000;}
#main {background-color:#CCC; border:#000 3px solid; width:600px; padding:5px;}
#logo {background-color:#000; font:29px bold; color:#FFF;}
#footer {background-color:#000; font-size:12px; color:#FFF;}
#info {background-color:#999; width:300px;}
#volver {font-size:18px;}
#noscript {background-color:#F00; border:#000 1px dashed; color:#000; width:150px; padding:3px;}
input {background-color:#CCC; font-size:12px; border:#000 1px solid;}
td,th {border:#666 1px dotted;}
tr:hover{background-color:#B4B4B4;}
#table {width:500px; text-align:center;}
-->
</style>
</head>
<body>
<center>
<span id="logo">| BadStupidMonkey |&gt; SMF Poll View |</span>
<br/>
<span id="footer">| www.badstupidmonkey.info | (CC) Reconocimiento-No comercial-Compartir bajo la misma licencia 3.0 | </span>
<div id="main">
';
# HTML CODE
// html login
$login_html = '<h2>Por favor loguese</h2>';
$login_html .= '<noscript><div id="noscript">Este script usa javascript!</div></noscript>';
$login_html .= '<form action="" method="post"><input type="text" name="bsm_login" /><br /><input type="password" name="bsm_password" /><br />';
$login_html .= '<input type="submit" value="Entrar" /><input type="button" value="Recargar" onClick="window.location.reload()"></form>';
# LOGIN
// comprobar post
if(!empty($_POST['bsm_login']) && !empty($_POST['bsm_password']))
{
 $login_cookie_content = md5($_POST['bsm_login'].$_POST['bsm_password']);
 setcookie("smfpollvoteview_bsm_login", $login_cookie_content, time()+3600);
 print '<script>window.location="'.$this_file.'";</script>';
}
// comprobar cookie
if(isset($_COOKIE['smfpollvoteview_bsm_login'])) {
 if($_COOKIE['smfpollvoteview_bsm_login'] != md5($bsm_login.$bsm_password)) {exit($login_html);}
} else {exit($login_html);}
# CARGAR FICHERO DE CONFIGURACION
if(file_exists($bsm_settings_file)) {$db = include_once($bsm_settings_file);}
else {die("<b>[ERROR FATAL] El archivo de configuracion smf no existe. Comprueba la ruta</b>");}
# CONEXION MYSQL
 $mysql_conn = new mysqli($db_server,$db_user,$db_passwd,$db_name) or die("<b>[ERROR FATAL]</b> ".$mysql_conn->error);
# FUNCIONES
// consultas mysql
function run_q($conn,$sql)
{
 $rs = $conn->query($sql) or die("<b>[ERROR FATAL]</b> ".$conn->error);
 return $rs;
}
// sacar encuestas
function sacar_encuestas($prefix,$this,$conn)
{
 $pollsnum = 0;
 $html_g_table = '<table id="table"><thead><tr><th>ID</th><th>Titulo</th><th>Admiistrar</th></tr></thead><tbody>';
 $polls = run_q($conn,'SELECT ID_POLL,question FROM '.$prefix.'polls ORDER BY ID_POLL DESC');
 while ($row = $polls->fetch_assoc())
 {
 $html_g_table .= '<tr><td>'.$row['ID_POLL'].'</td><td>'.$row['question'].'</td>';
 $html_g_table .= '<td><a href="'.$this.'?action=viewpoll&ID='.$row['ID_POLL'].'">Administrar</a></td></tr>';
 $pollsnum ++;
 }
 $html_g_table .= '</tbody></table><br /><div id="info"><b>Total Encuestas:</b> '.$pollsnum.'</td>';
 $polls->close();
 return $html_g_table;
}
// verencuesta
function ver_encuesta($id,$this,$prefix,$conn,$boardurl)
{
 $ss_query = 'SELECT question,posterName,votingLocked,COUNT(slp.ID_CHOICE) as cnt FROM '.$prefix.'polls as ';
 $ss_query .= 'sp,'.$prefix.'log_polls as slp WHERE sp.ID_POLL = '.$id.' AND slp.ID_POLL = '.$id;
 $poll = run_q($conn,$ss_query);
 $row = $poll->fetch_assoc();
 if($row['votingLocked'] > 0) {$poll_cls = 'Si';} else {$poll_cls = 'No';}
 $html_v_poll = '<div id="info"><b>Nombre:</b>'.$row['question'].'<br /><b>Creada por:</b>'.$row['posterName'].'<br />';
 $html_v_poll .= '<b>Total de votos:</b>'.$row['cnt'].'<br /><b>Encuesta cerrada:</b>'.$poll_cls.'</div>';
 $html_v_poll .= '<span id="volver"><a href="'.$this.'">Volver</a></span>';
 $poll->close();
 $ss_query = 'SELECT sm.memberName AS username, sm.ID_MEMBER AS userid,slp.ID_CHOICE as choice,sm.memberIP AS userip, spc.label AS opcion ';
 $ss_query .= 'FROM '.$prefix.'members AS sm, '.$prefix.'log_polls AS slp, '.$prefix.'poll_choices AS spc';
 $ss_query .= ' WHERE slp.ID_MEMBER = sm.ID_MEMBER AND slp.ID_POLL ='.$id.' AND spc.ID_POLL ='.$id.' AND slp.ID_CHOICE =  spc.ID_CHOICE';
 $poll = run_q($conn,$ss_query);
 $html_v_poll .= '<table id="table"><thead><tr><th>usuario</th><th>Ip</th><th>Voto</th><th>Ver Perfil</th><th>Borrar</th></tr>';
 $html_v_poll .= '</thead><tbody>';
 while ($row = $poll->fetch_assoc())
 {
 $html_v_poll .= '<tr><td>'.$row['username'].'</td><td>'.$row['userip'].'</td><td>'.$row['opcion'].'</td>';
 $html_v_poll .= '<td><a href="'.$boardurl.'/index.php?action=profile;u='.$row['userid'].'" target="_blank">Ver Perfil</a></td>';
 $html_v_poll .= '<td><a href="'.$this.'?action=delete&ID='.$id.'&ID2='.$row['userid'].'&ID3='.$row['choice'].'">Borrar</a></td></tr>';
 }
 $html_v_poll .= '</tbody></table>';
 $poll->close();
 return $html_v_poll;
}
// borrar voto
function borrar_voto($id,$id2,$id3,$this,$prefix,$conn)
{
 $ss_query = 'DELETE FROM '.$prefix.'log_polls WHERE ID_POLL = '.$id.' AND ID_MEMBER = '.$id2.' AND ID_CHOICE = '.$id3;
 $delete_vote = run_q($conn,$ss_query);
 $ss_query = 'UPDATE '.$prefix.'poll_choices SET votes = votes - 1 WHERE ID_POLL = '.$id.' AND ID_CHOICE = '.$id3;
 $delete_vote = run_q($conn,$ss_query);
 $html_dv = '<h1>BORRADO</h2><script>window.location="'.$this_file.'?action=viewpoll&ID='.$id.'";</script>';
 return $html_dv;
}
 
# MAIN
// menu
if (!empty($_GET['action']))
{
 // ver encuesta
 if($_GET['action']=='viewpoll')
 {
 if(empty($_GET['ID'])) {die('<b>[ERROR FATAL]</b> Ningun ID determinado.');}
 $poll_id = $_GET['ID'];
 print ver_encuesta($poll_id,$this_file,$db_prefix,$mysql_conn,$boardurl);
 }
 // borrar voto
 if($_GET['action']=='delete')
 {
 if(empty($_GET['ID']) or empty($_GET['ID2'])) {die('<b>[ERROR FATAL]</b>IDs no determinados.');}
 $id = $_GET['ID']; $id2 = $_GET['ID2']; $id3 = $_GET['ID3'];
 print borrar_voto($id,$id2,$id3,$this_file,$db_prefix,$mysql_conn);
 
 }
} else {print sacar_encuestas($db_prefix,$this_file,$mysql_conn);}
#FOOT
print '<div></center></body></html>';
?>

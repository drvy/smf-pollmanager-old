<?php
/* 
 * @nombre: dM SMF Poll Manager 2.0.
 * @versión: 2.0.
 * @descripción: Una simple herramienta que te permite ver quien,cuando y como
 *				 ha votado en una encuesta, en el popular sistema (cms) de foros
 *				 SMF (Simple Machines Forum).
 * @licencia: Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.
 * @uso: Esta herramienta no tiene nada que ver con SMF. No es oficial, y tampoco cumple
 *		 la función de un mod (plugin). Lo único que hace es obtener datos de la base
 *		 de datos donde esta instalado SMF.
 *		 Esta herramienta puede suponer una falta a la privacidad y libertad de los usuarios
 *		 en el foro. Si eres el administrador y/o un usuario capaz de ejecutar esta herramienta
 *		 en tu servidor, deberías informar a tus usuarios sobre esto.
 *		 En ningun momento, el autor de esta herramienta se hace responsable, por los daños,
 *		 malfunciones, o perdidas que puede generar el uso de esta herramienta.
 * @web: Para mas información, actualizaciones y/o dudas visita http://www.drvy.net/
 * @twitter: @drvymonkey
 * @email: bad.stupid.monkey@gmail.com
 *
 * @requisitos: Esta herramienta requiere de base de datos MySql y PHP superior/igual a la versión 5.
 * 				Debido al uso de POO (Programación orientada a objetos), esta aplicación no
 *				podrá correr en un servidor con PHP 4.
 *				ESTA VERSIÓN SOLO ACEPTA SMF 2.x.x. La versión de SMF 1.x YA ESTA FUERA de SOPORTE.
*/

/* Configuración de la aplicación. */

// Ruta del archivo Settings.php de SMF. Dejar en blanco si esta en el mismo directorio.
// Los directorios deben acabar con / (ejemplo: $dm_smf_settings = '../smf/';).
$dm_smf_settings = '';

// Permite reddireccionar los bots (google,yahoo) al index. true / false.
$dm_redirect_bots = true; 

// Si experimentas problemas con el recnocimiento de la version desactiva esta opcion (false).
$dm_check_smf_version = true;

// Si no deseas usar dmLVD (Detecion de votos fraudolentos) desactiva esta opcion (false).
$active_lvd = true;

/* Usuarios. */
// name = nombre, password = contraseña, prev = previlegios (all = lectura y borrar / read = solo lectura).
// Para añadir mas usuarios, copia la siguente linea y cambia sus variables.


$dm_users[] = array('name'=>'admin','password'=>'hahaha','prev'=>'all');
$dm_users[] = array('name'=>'demo','password'=>'demo','prev'=>'read');

/* --------------------------- No modificar desde aquí. ----------------------------- */
function dm_check_php_version(){
	$version = phpversion(); 
	if($version < 5){die('<b>dM Error (1):</b> La version de <b>PHP</b> es menor a 5.
	<br />-!Esta herramienta se puede ejecutar solo en versiones de PHP igual o superior a 5.');}
}
dm_check_php_version();
function dm_check_settings($dm_smf_settings=NULL){
	if(!file_exists($dm_smf_settings.'Settings.php')){
		die('<b>dM Error (2):</b> NO se ha encontrado el archivo <b>Settings.php</b> de SMF.
		<br />-!Asegurate de que has puesto este archivo bien y/o has definido bien la ruta.');
	}
}
dm_check_settings($dm_smf_settings);
if(!@include_once($dm_smf_settings.'Settings.php')){
	die('<b>dM Error (3):</b> NO se ha podido incluir el archivo <b>Settings.php</b> de SMF.
	<br />-!Puede que se deba a falta de permisos.');
}
function dm_check_bot($dm_redirect_bots=true,$boardurl){
	if($dm_redirect_bots==true){
		if(stristr($_SERVER['HTTP_USER_AGENT'],'bot')){die(header('Location: '.$boardurl));}
	}
}
dm_check_bot($dm_redirect_bots,$boardurl);
function dm_check_smf_version($dm_check_smf_version,$dm_smf_settings){
	if($dm_check_smf_version==true){
		$handle = fopen($dm_smf_settings.'Settings.php','r');
		$version = fread($handle, 300);
		fclose($handle);
		if(!stristr($version,'@version 2')){
			die('<b>dM Error (4):</b> La version de SMF es mayor o menor de 2.x.x.
			<br />-!Esta herramienta solo puede correr en la version 2.x.x de SMF.');
		}
		$version = NULL;
	}
}
dm_check_smf_version($dm_check_smf_version=true,$dm_smf_settings);
if($db_type != 'mysql'){die('<b>M Error (5):</b> Esta herramienta solo soporta MySql.
			<br />-!Settings.php tiene definido otro tipo de base de datos.');}

class dmSMFPollManager {
	private $dm_users = NULL;
	private $dm_db = NULL;
	private $dm_reg = NULL;
	private $dm_smf_reg = NULL;
	private $dm_lvd_users = NULL;
	public function dm_get_reg($var){return $this->dm_reg[$var];}
	public function dm_set_reg($var,$val){$this->dm_reg[$var]=$val;}
	private function dm_clean_reg(){$this->dm_reg=NULL;}
	public function __construct($db_server,$db_name,$db_user,$db_passwd,$db_prefix,$dm_users,$boardurl,$activate_lvd){
		$this->dm_users = $dm_users;
		define('dmdbperf',$db_prefix);
		$this->dm_set_reg('db_name',$db_name);
		$this->dm_set_reg('db_user',$db_user);
		$this->dm_set_reg('db_server',$db_server);
		$this->dm_set_reg('db_passwd',$db_passwd);
		$this->dm_set_reg('active_lvd',$activate_lvd);
		$this->dm_smf_reg = $boardurl;
	}
	public function dm_db_connect(){
		$db_server = $this->dm_get_reg('db_server'); $db_user = $this->dm_get_reg('db_user');
		$db_passwd = $this->dm_get_reg('db_passwd'); $db_name = $this->dm_get_reg('db_name');
		$handle = mysql_connect($db_server,$db_user,$db_passwd);
		if(!$handle){die('<b>dM Error (6):</b> No se ha podido establecer conexion con
		la base de datos. <br />-!'.mysql_error());}
		if(!mysql_select_db($db_name,$handle)){
			die('<b>dM Error (7):</b> No se ha podido selecionar
		la base de datos. <br />-!'.mysql_error());
		}
		$this->dm_db = $handle;
		return true;
	}
	public function dm_runq($query,$int=NULL){
		if($this->dm_db==NULL){die('<b>dM Error (8):</b> No hay conexion establecida.
		<br />-!Puede que haya algun fallo en la conexion.');}
		$link = $this->dm_db;
		if($int == NULL) {$handle = mysql_query(mysql_real_escape_string($query,$link),$link);}
		else {$handle = mysql_query($query,$link);}
		if(!$handle){
			die('<b>dM Error (9):</b> Error en la consulta mysql.
			<br />-! '.mysql_error());
		} else { return $handle; $link = NULL; }
	}
	public function dm_db_close(){
		mysql_close($this->dm_db);
		$this->dm_db = NULL;
		return true;
	}
	private function dm_check_login($what=1,$username=NULL,$password=NULL){
		foreach ($this->dm_users as $dm_user){
			if($what==1){
				if($username == $dm_user['name'] && $password == $dm_user['password']){
					@session_start();
					$_SESSION['dm_smf_poll_manager_login'] = md5($username.$password);
					return true;
				}
			} else {
				@session_start();
				if(md5($dm_user['name'].$dm_user['password']) == @$_SESSION['dm_smf_poll_manager_login']){
					$this->dm_set_reg('user_prev',$dm_user['prev']);
					return true;
				}
			}
		}
	}
	public function dm_do_login($what=1){if($what==1){if(!$this->dm_check_login(1,@$_POST['username'],@$_POST['password'])){return false;} else {return true;}} else {if(!$this->dm_check_login(2)){return false;}  else {return true;}}}
	public function dm_error($num){
		switch($num){
			case 1:
				return '<br />Fallo en la identificación.<br /><a href="?dma=search">Volver</a>';
				break;
			case 2:
				return '<br />No has escrito ningun termino para buscar.<br /> <a href="?dma=search">Volver</a>';
				break;
			case 3:
				return '<br />No has selecionado ninguna categoria para buscar.<br /> <a href="?dma=search">Volver</a>';
				break;
			case 4:
				return '<br />La categoria de busqueda no existe.<br /> <a href="?dma=search">Volver</a>';
				break;
			case 5:
				return '<br />No hay resultados.<br /><a href="?dma=search">Volver</a>';
				break;
			case 6:
				return '<br />No has selecionado ninguna encuesta.<br /><a href="?dma=search">Volver</a>';
				break;
			case 7:
				return '<br />No se ha definido una tabla.<br /><a href="?dma=search">Volver</a>';
				break;
			case 8:
				return '<br />No se ha definido una opcion(voto).<br /><a href="?dma=search">Volver</a>';
				break;
			case 9:
				return '<br />No se ha definido un ID de miembro.<br /><a href="?dma=search">Volver</a>';
				break;
			case 10:
				return '<br />No hay token. Por seguridad se define un token.<br /><a href="?dma=search">Volver</a>';
				break;
			case 11:
				return '<br />El token no es valido.<br /><a href="?dma=search">Volver</a>';
				break;
			case 12:
				return '<br />No tienes permisos para borrar votos.<br /><a href="?dma=search">Volver</a>';
				break;}}
	public function dm_template($what,$input=NULL,$token=NULL){
		switch($what){
			case 'login':
				return '<div class="dm_login_box"><h4>Login</h4><form action="" method="POST"><input type="text" name="username" class="dm_input" value="usuario" onclick="this.value=\'\'" /><input type="password" name="password" class="dm_input" value="password" onclick="this.value=\'\'" /><input type="submit" name="dm_login" class="dm_input button" value="Entrar" /></form></div>';
			break;
			case 'menu':
				return '<div class="dm_menu"><ul><li><a href="?dma=close" title="Cerrar Session">Cerrar Session</a></li><li><a href="?dma=all" title="Ver todas las encuestas">Ver todas</a></li><li><a href="?dma=search" title="Buscar especificas">Buscar</a></li><div class="dm_clear"></div></ul></div>';
			break;
			case 'search':
				return '<h4>Buscar encuestas</h4><form action="" method="POST"><input type="text" name="busqueda" value="buscar..." class="dm_input" onclick="this.value=\'\'" /><select class="dm_input" name="categoria"><option disabled="disabled" selected="selected">Selecionar categoria...</option><option value="name">Buscar por nombre.</option><option value="id">Buscar por ID.</option><option value="expire">Buscar por expirada (finalizada) (nombre).</option><option value="closed">Buscar por cerrada (nombre).</option></select><input type="submit" value="Buscar" name="dm_search" class="dm_input button" /></form>';
			break;
			case 'table_p':
				return '<table class="dm_table"><tr clss="dm_trtitle"><td class="dmdbnote"><span>nombre:</span></td><td>'.$input['pollname'].'</td></tr><tr><td class="dmdbnote"><span>expira:</span></td><td>'.$input['pollexpire'].'</td></tr><tr><td class="dmdbnote"><span>estado:</span></td><td>'.$input['pollstatus'].'</td></tr><tr><td class="dmdbnote"></td><td class="dm_manage"><a href="?dma=manage&id='.$input['pollid'].'">Manejar</a></td></tr></table>';
			break;
			case 'dm_info':
				return '<h4>Encuesta: <u>'.utf8_encode($input['question']).'</u></h4><div class="dminfo"><b>Cerrada</b>: '.$input['voting_locked'].'</div><div class="dminfo"><b>Expira</b>: '.$input['expire'].'</div><div class="dminfo"><b>Total Votos</b>: '.$input['totalvotes'].'</div><div class="dminfo"><b>Votos por usuario</b>: '.$input['max_votes'].'</div><div class="clear"></div>';
				break;
			case 'dm_manage_table':
				$return = '<table class="dm_table">
				<tr><td class="dmdbnote"><span>usuario</span></td><td>'.utf8_encode(htmlentities($input['username'],ENT_QUOTES)).'</td></tr><tr><td class="dmdbnote"><span>voto</span></td><td>'.utf8_encode(htmlentities($input['opcion'],ENT_QUOTES)).'</td></tr><tr><td class="dmdbnote"><span>dmLVD</span></td><td class="'.$input['lvdclass'].'">'.$input['lvdmsg'].'</td></tr>';
				if($this->dm_get_reg('user_prev')=='all'){$return .= '<tr><td class="dmdbnote"><span>borrar</span></td><td class="dm_manage"><a href="?dma=delete&pollid='.$input['pollid'].'&userid='.$input['userid'].'&choiceid='.$input['choice'].'&token='.$token.'">Borrar</a></td></tr>';}
				$return .= '</table>';
				return $return; break;}}
	public function dm_main(){
		$return = '';
		if(isset($_POST['dm_login'])){$this->dm_do_login(1);}
		if(!$this->dm_do_login(2)){return $this->dm_template('login');}
		if(isset($_GET['dma']) && $_GET['dma']=='close'){return $this->dm_close_session();}
		echo $this->dm_template('menu');
		if(!empty($_POST['dm_search'])){return $this->dm_search_poll();}
		if(isset($_GET['dma'])){
			if($_GET['dma']=='all'){return '<h4>Ver todas las encuestas.</h4>'.$this->dm_show_polls();}
			if($_GET['dma']=='search'){return $this->dm_template('search');}
			if($_GET['dma']=='manage'){return $this->dm_manage_poll();}
			if($_GET['dma']=='delete'){return $this->dm_delete();}
		} else {return $this->dm_template('search');}
		
	}
	private function dm_show_polls(){
		$this->dm_db_connect();
		$return = '';
		$result = $this->dm_runq('SELECT id_poll,question,expire_time,voting_locked FROM '.dmdbperf.'polls ORDER by id_poll DESC');
		while($row = mysql_fetch_array($result)){
			if($row['expire_time'] > 0){$input['pollexpire'] = date('d/m/Y h:i:s a',$row['expire_time']);
			if($row['expire_time'] <= time()) {$input['pollexpire'] .= 
			' <font color="#999999" style="text-decoration:blink;">finalizada</font>';}}
			else {$input['pollexpire'] = 'Nunca.';}
			if($row['voting_locked'] > 0) {$input['pollstatus'] = 'Cerrada.';} else {$input['pollstatus'] = 'Abierta.';}
			$input['pollname'] = htmlentities($row['question'],ENT_QUOTES);
			$input['pollid'] = (int)$row['id_poll'];
			$return .= $this->dm_template('table_p',$input);
		}
		$this->dm_db_close(); mysql_free_result($result); $result = NULL; $input = NULL; return $return; $return = NULL;
	}
	private function dm_close_session(){@session_start(); $_SESSION['dm_smf_poll_manager_login'] = NULL; $_GET['dma'] = NULL;  @session_destroy(); return 'Se ha cerrado la session correctamente. <a href="?dma=search">Volver al login</a>';}
	private function dm_search_poll(){
		$this->dm_db_connect();
		if(empty($_POST['busqueda'])){return $this->dm_error(2);}
		if(empty($_POST['categoria'])){return $this->dm_error(3);}
		$type = $_POST['categoria'];
		$searchfor = mysql_real_escape_string($_POST['busqueda']);
		$query = 'SELECT id_poll,question,expire_time,voting_locked,COUNT(id_poll) as cnt,max_votes FROM '.dmdbperf.'polls WHERE';
		if($type == 'name'){$query .= " question LIKE '%".$searchfor."%'";}
		elseif($type == 'id'){$query .= " id_poll = ".(int)$searchfor;}
		elseif($type == 'closed'){$query .= " voting_locked = '1' AND question LIKE '%".$searchfor."%'";}
		elseif($type == 'expire'){$query .= " expire_time < '".time()."' AND question LIKE '%".$searchfor."%'";}
		else {return $this->dm_error(4);}
		$return = '<h4>Resultados de la busqueda</h4>';
		$result = $this->dm_runq($query,2);
		while ($row = mysql_fetch_array($result)){
			if($row['cnt']<1){return $this->dm_error(5); break;}
			if($row['expire_time'] > 0){$input['pollexpire'] = date('d/m/Y h:i:s a',$row['expire_time']);
			if($row['expire_time'] <= time()) {$input['pollexpire'] .= 
			' <font color="#999999" style="text-decoration:blink;">finalizada</font>';}}
			else {$input['pollexpire'] = 'Nunca.';}
			if($row['voting_locked'] > 0) {$input['pollstatus'] = 'Cerrada.';} else {$input['pollstatus'] = 'Abierta.';}
			$input['pollname'] = htmlentities($row['question'],ENT_QUOTES);
			$input['pollid'] = (int)$row['id_poll'];
			mysql_free_result($result);
			$return .= $this->dm_template('table_p',$input);
		}
		$this->dm_db_close();
		return $return;
	}
	private function dm_lvd($idmember,$date_registered,$username,$ip,$ip2){
		$profilelink = '<a href="'.$this->dm_smf_reg.'/index.php?action=profile;u=';
		$return['lvdmsg'] = 'Sin sospechas.'; $return['lvdclass'] = 'dmlegal';
		if($this->dm_get_reg('active_lvd')==false){$return['lvdclass']='lvdd'; $return['lvdmsg']='dmLVD esta desactivado.'; return $return;}
		if(!is_array($this->dm_lvd_users)){
			$query = $this->dm_runq('SELECT id_member,member_name,member_ip,member_ip2 FROM '.dmdbperf.'members');
			while($row = mysql_fetch_array($query)){
				$this->dm_lvd_users[] = $row;
			}
			mysql_free_result($query);
		}
		if($date_registered > (time()-172800)){$return['lvdclass'] = 'dmsuspect';$return['ldvmsg'] = 'Registrado hace menos de 2 dias.';}
		if($date_registered > (time()-86400)){$return['lvdclass'] = 'dmsuspect';$return['lvdmsg'] = 'Registro hace menos de 1 dia.';}
		foreach($this->dm_lvd_users as $user){
			if($idmember != $user['id_member']){
				if($ip == $user['member_ip'] OR $ip2 == $user['member_ip2'] OR $ip == $user['member_ip2'] OR $ip == $user['member_ip']){
					$return['lvdclass']='dmdetect';
					$return['lvdmsg']='Misma IP que otro usuario.<br />'.$profilelink.$user['id_member'].'" target="_blank">'.htmlentities($user['member_name'],ENT_QUOTES).'</a>';
				}
			}
		}
		return $return; 
	}
	private function dm_token(){ $token = md5(time().mt_rand(1,400)); @session_start(); $_SESSION['dm_lvd_token'] = $token; return $token;}
	private function dm_delete(){
		@session_start();
		if(empty($_GET['pollid'])){return $this->dm_error(7);}
		elseif(!isset($_GET['choiceid'])){return $this->dm_error(8);}
		elseif(empty($_GET['userid'])){return $this->dm_error(9);}
		elseif(empty($_GET['token'])){return $this->dm_error(10);}
		elseif(empty($_SESSION['dm_lvd_token'])){return $this->dm_error(10);}
		elseif($_SESSION['dm_lvd_token'] != $_GET['token']){return $this->dm_error(11);}
		elseif($this->dm_get_reg('user_prev')!='all'){return $this->dm_error(12);}
		$pollid = (int)$_GET['pollid'];
		$choiceid = (int)$_GET['choiceid'];
		$userid = (int)$_GET['userid'];
		$this->dm_db_connect();
		$query = $this->dm_runq('DELETE FROM '.dmdbperf.'log_polls WHERE id_poll='.$pollid.' AND id_choice='.$choiceid.' AND id_member='.$userid);
		$query = $this->dm_runq('UPDATE '.dmdbperf.'poll_choices SET votes = votes - 1 WHERE id_choice='.$choiceid.' AND id_poll='.$pollid);
		$this->dm_db_close();
		return '<h4>Borrar voto.</h4> El voto ha sido borrado. <br /><a href="?dma=manage&id='.$pollid.'">Volver</a>';
	}
	private function dm_manage_poll(){
		if(empty($_GET['id'])){return $this->dm_error(6);}
		$this->dm_db_connect();
		$poll_id = (int)$_GET['id'];
		$table = NULL;
		$query = 'SELECT id_poll,question,voting_locked,expire_time,max_votes FROM '.dmdbperf.'polls WHERE id_poll = '.$poll_id;
		$result = $this->dm_runq($query);
		$table = mysql_fetch_array($result);
		mysql_free_result($result);
		$query = 'SELECT COUNT(id_member) as totalvotes FROM '.dmdbperf.'log_polls WHERE id_poll = '.$poll_id;
		$result = $this->dm_runq($query);
		$row = mysql_fetch_array($result);
		$table['totalvotes'] = $row['totalvotes'];
		mysql_free_result($result);
		unset($row);
		$return = '';
		if($table['voting_locked'] > 0){$table['voting_locked']='Si';} else {$table['voting_locked']='No';}
		if($table['expire_time'] < time()){$table['expire'] = 'Finalizada ('.date('d/m/Y h:i:s',$table['expire_time']).')';} else {$table['expire']= 'No';}
		if($table['expire_time']==0){$table['expire']='Nunca';}
		$return .= $this->dm_template('dm_info',$table);
		$query = 'SELECT sm.member_name AS username,sm.member_ip,sm.member_ip2,sm.date_registered, sm.id_member AS userid, slp.id_choice AS choice, sm.member_ip AS userip, spc.label AS opcion FROM '.dmdbperf.'members AS sm, '.dmdbperf.'log_polls AS slp, '.dmdbperf.'poll_choices AS spc WHERE slp.id_member = sm.id_member AND slp.id_poll ='.$poll_id.' AND spc.id_poll ='.$poll_id.' AND slp.id_choice = spc.id_choice';
		$result = $this->dm_runq($query);
		$token = $this->dm_token();
		while ($usertable = mysql_fetch_array($result)){
			$res = $this->dm_lvd($usertable['userid'],$usertable['date_registered'],$usertable['username'],$usertable['member_ip'],$usertable['member_ip2']);
			$usertable['lvdclass'] = $res['lvdclass'];
			$usertable['lvdmsg'] = $res['lvdmsg'];
			$usertable['pollid'] = $poll_id;
			$return .= $this->dm_template('dm_manage_table',$usertable,$token);
		} mysql_free_result($result); $this->dm_lvd_users = NULL; $this->dm_db_close();return $return;}
}
$dm = new dmSMFPollManager($db_server,$db_name,$db_user,$db_passwd,$db_prefix,$dm_users,$boardurl,$active_lvd);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta http-equiv="content-type" content="text/html; charset=UTF-8" /><meta name="author" content="Dragomir Valentinov (aka: DrvyMonkey)" /><meta name="copyright" CONTENT="This work by Dragomir Valentinov Yourdanov is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License." /><meta name="Robots" content="noindex" /><meta http-equiv="Window-target" content="_top" ><title>dM SMF Poll Manager 2.0</title><style type="text/css">html{width:100%;background:#000;font-family:Arial,sans-serif;color:#EFEFEF;font-size:14px;margin:20px 0 0;}body{margin:0;padding:10px;}a,a:hover,a:visited{color:#E5E5E5;}.dm_warp{max-width:500px;_width:500px;min-width:315px;}.dm_title{font-size:24px;color:#8BB918;text-shadow:#FFF 0 0 50px;letter-spacing:-2px;width:90%;word-wrap:break-word;text-align:left;font-weight:700;margin:0 0 10px;padding:5px;}.dm_title span{border-bottom:#EFEFEF 1px dashed;padding-bottom:5px;}.dm_content{border:none;border-top:#9BB918 5px solid;background:#207B8D;text-shadow:#666 0 0 5px;color:#EFEFEF;text-align:left;padding:5px;}.dm_input{background:#333;border:#207B8D 2px solid;color:#EFEFEF;border-radius:3px;width:95%;min-width:10%;display:block;padding:5px;}.button{background:#B5432E;width:99%;min-width:10%;font-weight:700;}.dm_input:hover{background:#222;cursor:pointer;}.dm_input:focus{background:#000;letter-spacing:1px;}.dm_input:active{background:#000;}.button:hover{background:#963C18;}h4{border-bottom:#EFEFEF 1px dashed;margin:2px;padding:0 0 5px 5px;}.dm_footer{font-size:10px;color:#777;font-weight:700;margin-top:10px;}.dm_footer a{color:#777;text-decoration:none;border-bottom:#8BB918 1px dashed;padding:2px;}.dm_menu ul{list-style:none;text-align:center;margin:0;}.dm_menu li{float:right;background:#B5432E;font-size:12px;border:#777 1px dashed;margin:0 0 0 5px;padding:5px;}.dm_menu li:hover{background:#000;cursor:pointer;}.dm_menu li:active{background:#444;}.selected{text-decoration:underline;}option{background:#333;cursor:pointer;margin:0;padding:1px;}.dm_clear{clear:both;}.dm_table{background:#222;width:100%;text-align:center;border:#555 1px dashed;margin:5px 0 0;padding:0;}.dm_table tr{background:#000;margin:0;padding:0;}.dm_table td{color:#CCC;border-bottom:#000 1px dashed;font-size:12px;padding:2px;}.dm_table td:hover{border-color:#777;}.dm_table span{color:#555;}.dm_manage{background:#B5432E;}.dm_manage:hover{background:#963C18;cursor:pointer;border-color:#000!important;}.dm_manage a,.dm_manage a:hover,.dm_manage a:visited{color:#E5E5E5;text-decoration:none;display:block;}.dmdbnote{max-width:70px;min-width:70px;width:70px;}.dmdbnote:hover{border-color:#222!important;}.dmlegal{color:#0C3!important;}.dmsuspect{color:#F63!important;}.dmdetect{color:#C00!important;text-decoration:blink!important;}.dminfo{background:#222;color:#FFF;float:left;font-size:10px;max-width:200px;text-align:center;margin:5px 2px 10px;padding:5px;}.lvdd{color:#555!important;}.dm_footer a:hover,.dm_footer a:visited{text-decoration:none;}.dm_menu a,.dm_menu a:hover,.dm_menu a:visited{color:#FFF;text-decoration:none;}.dmdetect a,.dmdetect a:visited{color:#CC0!important;}</style>
</head><body><center><div class="dm_warp"><div class="dm_title"><span>[dM] SMF Poll Manager 2.0</span></div><div class="dm_content"><?php echo $dm->dm_main(); ?></div></div><div class="dm_footer">[dM] SMF Poll Manager 2.0<br />Creative Commons Attribution-NonCommercial-ShareAlike 3.0.<br /> <a href="http://www.drvy.net/" target="_blank" title="Drvy Monkey – PHP, MySql, CSS, HTML5 y otras cosas…">@ drvymonkey</a></div></center></body></html>d

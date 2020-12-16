<?php
$user = 'drvy'; // your user. Use it to access to this script.
$password = 'toor'; // your password. Use it to access to this script.
$config_file = '../smf/Settings.php'; // Settings.php file. Main config file for SMF.
$allow_se = 'no'; // Allow search engines like "Google" to access this script or redirect them to your forum. (yes/no).
// ----------------Don't edit--------------------------------------------------

$userpwd = $user.'@///@'.md5($password);
$t_queries = 0;
function loadtime(){$time = microtime(); $time = explode(" ", $time); $time = $time[1] + $time[0]; return $time;}
$startime = loadtime();
function run_q($query){
    $result = @mysql_query($query) or die('<b>#01 ERROR:</b> '.mysql_error());
    global $t_queries;
    $t_queries ++;
    return $result;
}
// Function For searching in strings. Thanks to WHK (http://www.webcomparte.com)
// edited by: drvy | Change: Repace function eregi(DEPRECATED) for stristr.
function desde_hasta($desde, $hasta, $contenido){
 if(stristr($contenido ,$desde)){
  $retorno = explode($desde, $contenido);
  $retorno = $retorno[1];
  $retorno = explode($hasta, $retorno);
  $retorno = $retorno[0];
  return $retorno;
 } else {
  return FALSE;
 }
}
function ret_name($what){return htmlentities(substr($what, 0,15)).'..';}
function yes_no($what){if($what==0){return 'No';}else{return 'Yes';}}
function expires($what){if($what==0){return 'No';}elseif($what <= time()){return '<i>Expired</i>';}else{return date('d/m/y',$what);}}
function rjfv($dreg,$ldreg,$ip,$useripnamearr){
    if($usr = array_search($ip, $useripnamearr)) {return '<td class="red"><a href="#" title="Same IP as another user ('.$usr.')">Suspected(2)</a></td>';}
    elseif($dreg+86400 >= $ldreg) {return '<td class="red"><a href="#" title="Registered/Activity in less than 1 day">Suspected(1)</a></td>';}
    elseif($dreg+172800 >= $ldreg) { return '<td class="yellow"><a href="#" title="Registered/Activity in less than 2 days">Maybe</a></td>';}
    else{return '<td class="green">Legal</td>';}
}
if(!@include_once($config_file)){die('<b>ERROR #06:</b> Can\'t include config file.');}
$conx = @mysql_connect($db_server,$db_user,$db_passwd) or die('<b>ERROR #02:</b> '.mysql_error());
@mysql_select_db($db_name,$conx) or die('<b>ERROR #03:</b> '.mysql_error());
if($allow_se == 'no'){
    $bothead = array('bot','googlebot','yahoobot','spider','crawer');
    foreach($bothead as $v){
        if(stristr($v,$_SERVER['HTTP_USER_AGENT'])) {return header('Location:'.$boardurl);}
    }
}
class drvy_main{
    public function isitlogged(){
        if(!empty($_COOKIE['drvySMFpollMNG'])){
            global $userpwd;
            $cookie = explode('@///@',$_COOKIE['drvySMFpollMNG']);
            $user = $cookie[0].'@///@'.$cookie[1]; $ctims = $cookie[2];
            if($user == $userpwd && $ctims+3600 > time()){return true;} else {return false;}
        } else {return false;}
    }
    public function log_in(){
        if(!empty($_POST['drvyusrtxt'])&& !empty($_POST['drvypwdtxt'])){
            $user = str_replace('@///@','',$_POST['drvyusrtxt']); // just to be sure =)
            $password = str_replace('@///@','',$_POST['drvypwdtxt']); // just to be sure =)
            $cookieval = $user.'@///@'.md5($password).'@///@'.time();
            setcookie('drvySMFpollMNG',$cookieval,time()+3600);
            return '<script>location.href=\'?\';</script>';
        }
    }
    public function log_out(){
        setcookie('drvySMFpollMNG','none',time()-76500);
        return '<script>location.href=\'?\';</script>';
    }
   public function show_content($what,$isit){
           if($what == 'login'){
               $return = '<h3>Please login</h3><div class="loginbox">';
               $return .= '<form action="?drlogin=true" method="POST">';
               $return .= '<table border="0" class="logintb" cellpadding="0" cellspacing="2">';
               $return .= '<tr><th>User</th><th>Password</th><th></th></tr>';
               $return .= '<tr><td><input name="drvyusrtxt"type="text" /></td>';
               $return .= '<td><input name="drvypwdtxt" type="password" /></td>';
               $return .= '<td><input type="submit" class="ilogin" value="Login" /></td></tr>';
               $return .= '</table></form></div>';
             }
           elseif($what == 'welcome'){
               $return = '<h3>Welcome</h3>';
               $return .= '<span>DSPM allow\'s you to manage polls from SMF forums. See users who voted, what they voted and delete';
               $return .= 'the vote if necessary. Also, in this version you can search for users who registered just for voting.</span>';
               $return .= '<blockquote>I\'ve detected that your SMF version is <b>'.$this->get_sep_info('version').'</b></blockquote>';
               $return .= '<span>There are currently <b>'.$this->get_sep_info('polls').'</b> polls and ';
               $return .= '<b>'.$this->get_sep_info('usersvoted').'</b> users have voted.</span>';
           }
           elseif($what == 'menu'){
               if($isit == 1){
                  $return = '<div onclick="location.href=\'?draction=showall\'" class="menuit"><span>[] </span>Show All</div>';
                  $return .= '<div onclick="location.href=\'?draction=showspec\'" class="menuit"><span>[] </span>Show Specific</div>';
                  $return .= '<div onclick="location.href=\'?draction=aboutthis\'" class="menuit"><span>[] </span>About this</div>';
                  $return .= '<div onclick="location.href=\'?draction=logout\'" class="menuit"><span>[] </span>Logout</div>';
               }else{
                   $return = '<div onclick="location.href=\'?draction=aboutthis\'" class="menuit"><span>[] </span>About this</div>';
               }
           }
           elseif($what == 'about'){
               $return = '<h3>About this</h3>';
               $return .= '<div class="about"><b>Name:</b><span> Drvy Smf Poll Manager (dspm)</span> <br /><b>Version:</b><span> 1.2</span><br />';
               $return .= '<b>Description:</b><span> Simple PHP script, that makes easier the way of managing polls. ';
               $return .= 'Also gives you a easy way to detect malicius poll voters.</span><br /><b>Language:</b><span> PHP</span><br />';
               $return .= '<b>Author:</b><span> Drvy Monkey (Dragomir Valentinov)</span><br />';
               $return .= '<b>Website:</b><span><a href="http://drvymonkey.blogspot.com" title="Drvy Blog"> http://drvymonkey.blogspot.com</a></span><br />';
               $return .= '<div class="menuit" style="width:90px" onclick="javascript:history.back();">Get Back</div>';
               $return .= '</div>';
           }
           elseif($what == 'showspec'){
               $return = '<h3>Show Specific Poll</h3><div class="specpoll"><form action="?draction=search" method="POST"><div class="sps">String</div>';
               $return .= '<input name="dspmsestring" type="text"/><br /><div class="sps">Type</div><select name="dspmseoption">';
               $return .= '<option value="byid">ID - specific id poll (only numbers)</option>';
               $return .= '<option value="byquestion" selected="selected">Question - all polls with specific name</option>';
               $return .= '<option value="byclosed">Closed - only closed polls with specific name</option>';
               $return .= '<option value="byuser">Username - all polls created by user (realname) </option>';
               $return .= '</select><input type="submit" value="Search for it" /></form></div>';
           }
           return $return;
   }
   public function get_menu(){if ($this->isitlogged()==true) {return $this->show_content('menu',1);} else { return $this->show_content('menu',2);}}
   public function maincntshow(){
       if(!empty($_GET['draction'])&&$_GET['draction']=='aboutthis') {return $this->show_content('about',0);}
       if(!empty($_GET['drlogin'])) {return $this->log_in();}
       if($this->isitlogged()==true){
           if(!empty($_GET['draction']) && $_GET['draction']=='showall') {return $this->get_sep_info('allpolls');}
           elseif(!empty($_GET['draction']) && $_GET['draction']=='showspec') {return $this->show_content('showspec',0);}
           elseif(!empty($_GET['draction']) && $_GET['draction']=='search') {return $this->get_sep_info('specpoll');}
           elseif(!empty($_GET['draction']) && $_GET['draction']=='view') {return $this->get_sep_info('viewpoll');}
           elseif(!empty($_GET['draction']) && $_GET['draction']=='delall') {return $this->get_sep_info('deleteall');}
           elseif(!empty($_GET['draction']) && $_GET['draction']=='delete') {return $this->get_sep_info('delete');}
           elseif(!empty($_GET['draction']) && $_GET['draction']=='logout') {return $this->log_out();}
           else{return $this->show_content('welcome',0);}
       } else {return $this->show_content('login',0);}
   }
   public function queries($what,$sstring){
       global $db_prefix;
       if(stristr($this->get_sep_info('version'),'1.')) {
           if($what=='numbpolls'){return 'SELECT COUNT(ID_POLL) AS cnt FROM '.$db_prefix.'polls';}
           elseif($what=='usersvotedgen') {return 'SELECT COUNT(DISTINCT ID_MEMBER) AS cnt FROM '.$db_prefix.'log_polls';}
           elseif($what=='getallpolls') {return 'SELECT ID_POLL AS id, question AS qu, votinglocked AS vt, expireTime AS tm FROM '.$db_prefix.'polls ORDER BY ID_POLL DESC';}
           elseif($what=='specbyid') {return 'SELECT ID_POLL as id, question AS qu, votinglocked AS vt, expireTime AS tm FROM '.$db_prefix.'polls WHERE ID_POLL='.$sstring;}
           elseif($what=='specbyqu') {return 'SELECT ID_POLL as id, question AS qu, votinglocked AS vt, expireTime AS tm FROM '.$db_prefix.'polls WHERE question LIKE \'%'.$sstring.'%\'';}
           elseif($what=='specbycls') {return 'SELECT ID_POLL as id, question AS qu, votinglocked AS vt, expireTime AS tm FROM '.$db_prefix.'polls WHERE votinglocked = 1 AND question LIKE \'%'.$sstring.'%\'';}
           elseif($what=='specbyusr') {return 'SELECT ID_POLL as id, question AS qu, votinglocked AS vt, expireTime AS tm FROM '.$db_prefix.'polls WHERE posterName=\''.$sstring.'\'';}
           elseif($what=='viewpoll1') {return 'SELECT sp.ID_POLL as POLLID,sp.question as QUEST,sp.votingLocked as LOCKED,sp.maxVotes as MVOTES,sp.expireTime as EXPIRE,COUNT(distinct slp.ID_MEMBER) as UUSERS FROM '.$db_prefix.'polls as sp,'.$db_prefix.'poll_choices as spc,'.$db_prefix.'log_polls as slp WHERE sp.ID_POLL = '.$sstring.' AND slp.ID_POLL = sp.ID_POLL AND spc.ID_POLL = sp.ID_POLL';}
           elseif($what=='viewpoll2') {return 'SELECT sm.memberName AS username,sm.dateRegistered as dreg,sm.lastLogin as ldreg,sm.ID_MEMBER AS userid,slp.ID_CHOICE as choice,sm.memberIP as userip,spc.label AS opcion FROM '.$db_prefix.'members AS sm,'.$db_prefix.'log_polls AS slp, '.$db_prefix.'poll_choices AS spc WHERE slp.ID_MEMBER = sm.ID_MEMBER AND slp.ID_POLL = '.$sstring.' AND spc.ID_POLL = slp.ID_POLL AND slp.ID_CHOICE = spc.ID_CHOICE';}
           elseif($what=='viewpoll3') {return 'SELECT SUM(votes) AS VOTES FROM '.$db_prefix.'poll_choices WHERE ID_POLL = '.$sstring;}
           elseif($what=='viewpoll4') {return 'SELECT ID_CHOICE as choiceid, label as choice  from '.$db_prefix.'poll_choices WHERE ID_POLL = '.$sstring;}
           elseif($what=='delpoll1') {$ko = explode('//',$sstring); return 'DELETE FROM '.$db_prefix.'log_polls WHERE ID_POLL='.$ko[0].' AND ID_MEMBER='.$ko[1].' AND ID_CHOICE='.$ko[2];}
           elseif($what=='delpoll2') {$ko = explode('//',$sstring); return 'UPDATE '.$db_prefix.'poll_choices SET votes = votes - 1 WHERE ID_POLL='.$ko[0].' AND ID_CHOICE='.$ko[2];}
       } else {
           if($what=='numbpolls'){return 'SELECT COUNT(id_poll) AS cnt FROM '.$db_prefix.'polls';}
           elseif($what=='usersvotedgen') {return 'SELECT COUNT(DISTINCT id_member) AS cnt FROM '.$db_prefix.'log_polls';}
           elseif($what=='getallpolls') {return 'SELECT id_poll AS id, question AS qu, voting_locked AS vt, expire_time AS tm FROM '.$db_prefix.'polls ORDER BY id_poll DESC';}
           elseif($what=='specbyid') {return 'SELECT id_poll as id, question AS qu, voting_locked AS vt, expire_time AS tm FROM '.$db_prefix.'polls WHERE id_poll='.$sstring;}
           elseif($what=='specbyqu') {return 'SELECT id_poll as id, question AS qu, voting_locked AS vt, expire_time AS tm FROM '.$db_prefix.'polls WHERE question LIKE \'%'.$sstring.'%\'';}
           elseif($what=='specbycls') {return 'SELECT id_poll as id, question AS qu, voting_locked AS vt, expire_time AS tm FROM '.$db_prefix.'polls WHERE voting_locked = 1 AND question LIKE \'%'.$sstring.'%\'';}
           elseif($what=='specbyusr') {return 'SELECT id_poll as id, question AS qu, voting_locked AS vt, expire_time AS tm FROM '.$db_prefix.'polls WHERE posterName=\''.$sstring.'\'';}
           elseif($what=='viewpoll1') {return 'SELECT sp.id_poll as POLLID,sp.question as QUEST,sp.voting_locked as LOCKED,sp.max_votes as MVOTES,sp.expire_time as EXPIRE,COUNT(distinct slp.id_member) as UUSERS FROM '.$db_prefix.'polls as sp,'.$db_prefix.'poll_choices as spc,'.$db_prefix.'log_polls as slp WHERE sp.id_poll = '.$sstring.' AND slp.id_poll = sp.id_poll AND spc.id_poll = sp.id_poll';}
           elseif($what=='viewpoll2') {return 'SELECT sm.member_name AS username,sm.date_registered as dreg,sm.last_login as ldreg,sm.id_member AS userid,slp.id_choice as choice,sm.member_ip as userip,spc.label AS opcion FROM '.$db_prefix.'members AS sm,'.$db_prefix.'log_polls AS slp, '.$db_prefix.'poll_choices AS spc WHERE slp.id_member = sm.id_member AND slp.id_poll = '.$sstring.' AND spc.id_poll = slp.id_poll AND slp.id_choice = spc.id_choice';}
           elseif($what=='viewpoll3') {return 'SELECT SUM(votes) AS VOTES FROM '.$db_prefix.'poll_choices WHERE id_poll = '.$sstring;}
           elseif($what=='viewpoll4') {return 'SELECT id_choice as choiceid, label as choice  from '.$db_prefix.'poll_choices WHERE id_poll = '.$sstring;}
           elseif($what=='delpoll1') {$ko = explode('//',$sstring); return 'DELETE FROM '.$db_prefix.'log_polls WHERE id_poll='.$ko[0].' AND id_member='.$ko[1].' AND id_choice='.$ko[2];}
           elseif($what=='delpoll2') {$ko = explode('//',$sstring); return 'UPDATE '.$db_prefix.'poll_choices SET votes = votes - 1 WHERE id_poll='.$ko[0].' AND id_choice='.$ko[2];}
           }
   }
   public function get_sep_info($what){
       if($what=='version'){
           global $config_file;
           $file = @fopen($config_file,'r') or die('<b>ERROR #04:</b> Can\'t find/open config file.');
           $contents = fread($file, filesize($config_file)) or die('<b>ERROR #05:</b> Can\t read config file.');
           fclose($file);
           $version = desde_hasta('Software Version:','*',$contents);
           return trim($version);
       }
       elseif($what=='polls'){
           $res = run_q($this->queries('numbpolls',0));
           $polls = @mysql_fetch_array($res);
           return $polls['cnt'];
       }
       elseif($what=='usersvoted'){
           $res = run_q($this->queries('usersvotedgen',0));
           $users = @mysql_fetch_array($res);
           return $users['cnt'];
       }
       elseif($what=='allpolls'){
           $rt = 0;
           $res = run_q($this->queries('getallpolls',0));
           $return = '<h3>Show all polls</h3><table cellpadding="2" cellspacing="2" class="tableshowall" >';
           $return .= '<tr><th>Id</th><th>Question</th><th>Closed?</th><th>Expires</th><th>Manage</th></tr>';
           while($row = @mysql_fetch_array($res)){
               if($rt==0){
               $return .= '<tr><td>'.$row['id'].'</td><td>'.ret_name($row['qu']).'</td><td>'.yes_no($row['vt']).'</td><td>'.expires($row['tm']).'</td>';
               $return .= '<td><a href="?draction=view&id='.$row['id'].'">View</td></tr>'; $rt++;} else{
               $return .= '<tr class="ko"><td>'.$row['id'].'</td><td>'.ret_name($row['qu']).'</td><td>'.yes_no($row['vt']).'</td><td>'.expires($row['tm']).'</td>';
               $return .= '<td><a href="?draction=view&id='.$row['id'].'">View</td></tr>'; $rt=0;}
           }
           $return .= '</table>';
           return $return;
       }
       elseif($what=='specpoll'){
           $rt = 0;
           if(!empty($_POST['dspmsestring']) && isset($_POST['dspmseoption'])){
               $sstring = $_POST['dspmsestring']; $soption = $_POST['dspmseoption'];
               if($soption=='byid'){
                   $return = '<h3>Show Specific Poll</h3><span>search by id (results)</span><table cellpadding="2" cellspacing="2" class="tableshowall" >';
                   $sstring = (int)$sstring; $res = run_q($this->queries('specbyid',$sstring));
                   $return .= '<tr><th>Id</th><th>Question</th><th>Closed?</th><th>Expires</th><th>Manage</th></tr>';
                   while($row = @mysql_fetch_array($res)){
                       if($rt==0){
                       $return .= '<tr><td>'.$row['id'].'</td><td>'.ret_name($row['qu']).'</td><td>'.yes_no($row['vt']).'</td><td>'.expires($row['tm']).'</td>';
                       $return .= '<td><a href="?draction=view&id='.$row['id'].'">View</td></tr>'; $rt++;} else {
                       $return .= '<tr class="ko"><td>'.$row['id'].'</td><td>'.ret_name($row['qu']).'</td><td>'.yes_no($row['vt']).'</td><td>'.expires($row['tm']).'</td>';
                       $return .= '<td><a href="?draction=view&id='.$row['id'].'">View</td></tr>'; $rt=0;}
                   }
                   $return .= '</table>';
                   if(@mysql_num_rows($res) < 1) {$return .= '<br /><span>No results, remember ID is num only.</span>';}
                   return $return;}
               elseif($soption=='byquestion'){
                   $return = '<h3>Show Specific Poll</h3><span>search by question (results)</span><table cellpadding="2" cellspacing="2" class="tableshowall" >';
                   $ssting = htmlentities($sstring, ENT_QUOTES); $res = run_q($this->queries('specbyqu',$sstring));
                   $return .= '<tr><th>Id</th><th>Question</th><th>Closed?</th><th>Expires</th><th>Manage</th></tr>';
                   while($row = @mysql_fetch_array($res)){
                       $return .= '<tr class="tb"><td>'.$row['id'].'</td><td>'.ret_name($row['qu']).'</td><td>'.yes_no($row['vt']).'</td><td>'.expires($row['tm']).'</td>';
                       $return .= '<td><a href="?draction=view&id='.$row['id'].'">View</a></td></tr>';
                   }
                   $return .= '</table>';
                   if(@mysql_num_rows($res) < 1) {$return .= '<br /><span>No results. Did you typed correctly the question ?</span>';}
                   return $return;}
              elseif($soption=='byclosed'){
                   $return = '<h3>Show Specific Poll</h3><span>search by closed voting (results)</span><table cellpadding="2" cellspacing="2" class="tableshowall" >';
                   $ssting = htmlentities($sstring, ENT_QUOTES); $res = run_q($this->queries('specbycls',$sstring));
                   $return .= '<tr><th>Id</th><th>Question</th><th>Closed?</th><th>Expires</th><th>Manage</th></tr>';
                   while($row = @mysql_fetch_array($res)){
                       $return .= '<tr class="tb"><td>'.$row['id'].'</td><td>'.ret_name($row['qu']).'</td><td>'.yes_no($row['vt']).'</td><td>'.expires($row['tm']).'</td>';
                       $return .= '<td><a href="?draction=view&id='.$row['id'].'">View</td></tr>';
                   }
                   $return .= '</table>';
                   if(@mysql_num_rows($res) < 1) {$return .= '<br /><span>No results, remember, only closed polls with specific name.</span>';}
                   return $return;}
             elseif($soption=='byuser'){
                   $return = '<h3>Show Specific Poll</h3><span>search by closed voting (results)</span><table cellpadding="2" cellspacing="2" class="tableshowall" >';
                   $ssting = htmlentities($sstring, ENT_QUOTES);; $res = run_q($this->queries('specbyusr',$sstring));
                   $return .= '<tr><th>Id</th><th>Question</th><th>Closed?</th><th>Expires</th><th>Manage</th></tr>';
                   while($row = @mysql_fetch_array($res)){
                       $return .= '<tr class="tb"><td>'.$row['id'].'</td><td>'.ret_name($row['qu']).'</td><td>'.yes_no($row['vt']).'</td><td>'.expires($row['tm']).'</td>';
                       $return .= '<td><a href="?draction=view&id='.$row['id'].'">View</td></tr>';
                   }
                   $return .= '</table>';
                   if(@mysql_num_rows($res) < 1) {$return .= '<br /><span>No results, remember, realname not nick.</span>';}
                   return $return;}
           } else {return '<h3>Show Specific Poll</h3><br /><center> No search string ?</center>';}}
      elseif($what=='viewpoll'){
          if(!empty($_GET['id'])){
              $id = (int)$_GET['id'];
              $stmp = md5(rand(11111,99999));
              setcookie('dspmSTMPvar',$stmp);
              $res = run_q($this->queries('viewpoll1',$id));
              if(@mysql_num_rows($res) < 1) {$return .= '<span>No such poll. Correct ID?</span>';}
              $rst = @mysql_fetch_array($res);
              $return = '<h3>View Poll - ( '.$rst['QUEST'].' )</h3><div class="left"><ul>';
              $return .= '<li><b>Id: </b>'.$rst['POLLID'].'</li><li><b>Closed: </b>'.yes_no($rst['LOCKED']).'</li>';
              $return .= '<li><b>Expires: </b>'.expires($rst['EXPIRE']).'</li></ul></div><div class="left">';
              $res2 = run_q($this->queries('viewpoll3',$id));
              $rst2 = @mysql_fetch_array($res2);
              $return .= '<ul><li><b>Total Votes: </b>'.$rst2['VOTES'].'</li><li><b>Unique Users: </b>'.$rst['UUSERS'].'</li>';
              $return .= '<li><b>Votes Allowed: </b>'.$rst['MVOTES'].'</li></ul></div><div class="clear"></div>';
              $res = run_q($this->queries('viewpoll2',$id));
              $return .= '<table cellpadding="2" cellspacing="2" class="tableshowall"><tr><th>Sel</th><th>User</th><th>Vote</th><th>RJFV</th><th>Delete</th></tr>';
              $return .= '<form name="selfom" action="?draction=delall&id='.$id.'" method="POST" >';
              $useripnamearr = array();
              while ($rst = @mysql_fetch_array($res)){
                  $return .= '<tr><td><input type="checkbox" id="checkall" name="dspmselusr[]" value="'.$rst['userid'].'/'.$rst['choice'].'"></td>';
                  global $boardurl;
                  $return .= '<td><a href="'.$boardurl.'/index.php?action=profile;u='.$rst['userid'].'" target="_blank" title="view profile">'.$rst['username'].'</a></td><td>'.$rst['opcion'].'</td>';
                  $return .= rjfv($rst['dreg'],$rst['ldreg'],$rst['userip'],$useripnamearr);
                  $return .= '<td><a href="?draction=delete&pollid='.$id.'&choice='.$rst['choice'].'&member='.$rst['userid'].'&stmp='.$stmp.'">';
                  $return .= 'Delete</a></td></tr>';
                  $useripnamearr[$rst['username']] = $rst['userip'];
              }
              $return .= '</table><div align="right"><input type="hidden" name="stmp" value="'.$stmp.'" /><input type="submit" value="Delete Selected"/></form></div>';
              return $return;
          } else { return '<h3>Manage Poll</h3><br /><center>No id poll indicated.</center>'; }}
   elseif($what=='deleteall'){
       if(isset($_GET['id']) && isset($_POST['dspmselusr']) && !empty($_POST['stmp'])){
           if($_COOKIE['dspmSTMPvar']==$_POST['stmp']){
               foreach ($_POST['dspmselusr'] as $v){
                   $kop = explode('/',$v); $uid = (int)$kop[0]; $cid = (int)$kop[1]; $id = (int)$_GET['id'];
                   $sstring = $id.'//'.$uid.'//'.$cid;
                   $run1 = run_q($this->queries('delpoll1',$sstring));
                   $run2 = run_q($this->queries('delpoll2',$sstring));
                   } return '<span>Deleted.</span><script>location.href=\'?draction=view&id='.$id.'\';</script>';
           } else {return '<span>h4x attempt ?</span>';}
       } else {return '<span> Some of the inputs empty?</span><script>location.href=\'?draction=viewall\';</script>';}}
  elseif($what=='delete'){
      if(!empty($_GET['pollid']) && isset($_GET['choice']) && isset($_GET['member']) && !empty($_GET['stmp']))
      {
          if($_COOKIE['dspmSTMPvar']==$_GET['stmp']){
              $sstring = (int)$_GET['pollid'].'//'.(int)$_GET['member'].'//'.(int)$_GET['choice'];
              $run1 = run_q($this->queries('delpoll1',$sstring));
              $run2 = run_q($this->queries('delpoll2',$sstring));
              return '<span>Deleted.</span><script>location.href=\'?draction=view&id='.$_GET['pollid'].'\';</script>';
          }else {return 'h4x attempt ?';}
      } else {return '<span> Some of the inputs empty?</span><script>location.href=\'?draction=viewall\';</script>';}}
  }
}
$drvy = new drvy_main;
$endtime = loadtime(); $total_time = round($endtime - $startime,3);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Drvy Smf Poll Manager [1.2]</title>
<script type="application/javascript" src="http://code.jquery.com/jquery-latest.min.js" language="javascript"></script>
<script type="application/javascript" src="http://plugins.jquery.com/files/jquery.color.js.txt" language="javascript"></script>
<script type="application/javascript">
$(document).ready(function() {
    $('.menuit').mouseover(function() {$(this).stop().animate({opacity: 0.8,backgroundColor:"#F1F1F1"}, 500, function() {});});
    $('.menuit').mouseout(function() {$(this).stop().animate({opacity: 1.0,backgroundColor:"#F9F9F9"}, 500, function() {});});;
});
</script>
<style type="text/css">
<!--
body,td,th {font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;font-size: 12px;color: #000;}
body {background-color: #FFF;margin-top: 100px;}
a {font-size: 12px;color: #000;}
a:link {text-decoration: none;}
a:visited {text-decoration: none;color: #000;}
a:hover {text-decoration: underline;color: #000;}
a:active {text-decoration: none;color: #000;}
.clear {clear:both;}
.main {width:500px; text-align:left;}
.title {width:500px; font-size:30px; text-transform:uppercase; color:#286982; letter-spacing:-2px; text-shadow:#000000 0px 0px 3px;}
.sep {width:500px; background-color:#333; height:1px;}
.body {padding:2px;}
.menu {margin-top:5px; width:100px; float:right;}
.menuit {background-color:#F9F9F9; color:#000; margin:2px; padding:2px; text-shadow:#666 0px 0px 3px; padding-left:5px; width:90px;}
.menuit:hover {color:#333; cursor:pointer; width:100px;}
.menuit span { font-size:10px;}
.tableshowall {width:100%;}
.content {float:left; width:350px; margin-top:5px; }
h3 {margin:0px; text-shadow:#666 0px 0px 3px; font-size:18px;}
.loginbox {text-align:center; width:350px;} 
.logintb {width:350px; margin-top:20px; margin-bottom:5px;} .logintb th {background-color:#FFF;}
form {border:none; padding:0; margin:0;}
.specpoll input,select {width:330px; margin-bottom:2px;} .sps {width:330px; text-align:right;}
table {width:350px; margin-top:10px;} th {background-color:#F1F1F1;} td{ text-align:center;}
.ko {background-color:#F9F9F9;} .tbcolor{border:none; padding:0; margin:0; width:270px;}
.footer {font-size:10px; color:#CCC;} .red {background-color:#FFB7B7;} .green {background-color:#C4FDB5;} 
.yellow { background-color:#F2F8B6;} .left {float:left; margin-top:10px;} hr {height:1px;} h4{padding:0; margin:0;}
.scst{width:auto;} .scstt{width:60px;}
.footer a {font-size:10px;color:#ccc;}

-->
</style></head>
<body>
<center>
	<div class="main">
    	<div class="title"><font color="#333333">Drvy</font> Smf Poll <font color="#333333">Manager</font></div>
        <div class="sep"></div>
        <div class="body">
        	<div class="menu">
                    <?php print $drvy->get_menu(); ?>
           	</div>
            <div class="content">
                    <?php print $drvy->maincntshow(); ?>
                
            </div>
            <div class="clear"></div>
        </div>
        <div class="sep"></div>
        <div class="footer">
       Drvy Smf Poll Manager 1.2. <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/" title="Licence">(CC)-(BY-NC-SA)</a>.
       <?php print 'Script loaded in <b>'.$total_time.'</b> secs with <b>'.$t_queries.'</b> queries.'; ?>
        </div>
    </div>
</center>
</body>
</html> 

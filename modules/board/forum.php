<?php
function LastPostDetails($date) {
  global $db, $config, $line, $dsp, $templ;

  if ($date) {
    $row = $db->query_first("SELECT p.userid, p.pid, p.tid, u.username FROM {$config['tables']['board_posts']} AS p
      LEFT JOIN {$config['tables']['user']} AS u ON p.userid = u.userid
      WHERE p.date = $date AND p.tid = {$line['tid']}");

    $ret = '<a href="index.php?mod=board&action=thread&tid='. $row['tid'] .'&gotopid='. $row['pid'] .'#pid'. $row['pid'] .'" class="menu">'. date('d.m.y H:i', $date);
    if ($row['userid']) $ret .= '<br />'. $row['username'] .'</a> '. $dsp->FetchUserIcon($row['userid']);
    else $ret .= '<br />Gast_';
    return $ret;
     
  } else {
    $templ['ms2']['icon_name'] = 'no';
    $templ['ms2']['icon_title'] = '-';
    return $dsp->FetchModTpl('mastersearch2', 'result_icon');
  }
}

function FormatTitle($title) {
  global $dsp, $templ, $line, $func;
  
  $icon = '';
  if ($line['closed']) {
    $templ['ms2']['icon_name'] = 'locked';
    $templ['ms2']['icon_title'] = 'Not Paid';
    $icon = $dsp->FetchModTpl('mastersearch2', 'result_icon'). ' ';
  }
  if ($line['sticky']) {
    $templ['ms2']['icon_name'] = 'important';
    $templ['ms2']['icon_title'] = 'Wichtig!';
    $icon = $dsp->FetchModTpl('mastersearch2', 'result_icon'). ' ';
  }
  return $icon . $func->AllowHTML($title);
}

function NewPosts($last_read) {
	global $db, $config, $auth, $line;

	// Delete old entries
	$db->qry("DELETE FROM %prefix%board_read_state WHERE last_read < %int%", (time() - 60 * 60 * 24 * 7));

	// Older, than one week
	if ($line['LastPost'] < (time() - 60 * 60 * 24 * 7)) return "<a class=\"menu\" href=\"index.php?mod=board&action=thread&fid={$_GET["fid"]}&tid={$line['tid']}\">Alt</a>";

	// No entry -> Thread completely new
	elseif (!$last_read) return "<a class=\"menu\" href=\"index.php?mod=board&action=thread&fid={$_GET["fid"]}&tid={$line['tid']}\">Neu</a>";

	// Entry exists
	else {

		// The posts date is newer than the mark -> New
		if ($last_read < $line['LastPost']) return "<a class=\"menu\" href=\"index.php?mod=board&action=thread&fid={$_GET["fid"]}&tid={$line['tid']}#pid{$line['last_pid']}\">Neu</a>";

		// The posts date is older than the mark -> Old
		else return "<a class=\"menu\" href=\"index.php?mod=board&action=thread&fid={$_GET["fid"]}&tid={$line['tid']}\">Alt</a>";
	}
}

if ($_GET['fid'] != '') {
  $row = $db->query_first("SELECT name, need_type, need_group FROM {$config["tables"]["board_forums"]} WHERE fid={$_GET["fid"]}");
  if ($row['need_type'] == 1 and $auth['login'] == 0) $new_thread = t('Sie müssen sich zuerst einloggen, um einen Thread in diesem Forum starten zu können');
  elseif ($row['need_group'] and $auth['group_id'] != $row['need_group']) $new_thread = t('Sie gehören nicht der richtigen Gruppe an, um einen Thread in diesem Forum starten zu können');
  else $new_thread = $dsp->FetchIcon("index.php?mod=board&action=thread&fid=". $_GET['fid'], "add");

  // Board Headline
	$hyperlink = '<a href="%s" class="menu">%s</a>';
	$overview_capt = sprintf($hyperlink, "index.php?mod=board", t('Forum'));
	$dsp->NewContent($row['name'], "$overview_capt - {$row['name']}");
  $dsp->AddSingleRow($new_thread ." ". $dsp->FetchIcon("index.php?mod=board", "back"));
}


switch($_GET['step']) {
  // Edit headline
  case 10:
    if ($auth['type'] >= 2) {
      $dsp->AddFieldsetStart(t('Thread bearbeiten'));
      include_once('inc/classes/class_masterform.php');
      $mf = new masterform();
      $mf->AddField(t('Überschrift'), 'caption', 'varchar(255)');
      $pid = $mf->SendForm('index.php?mod=board&action=forum&step=10&fid='. $_GET['fid'] .'&tid='. $_GET['tid'], 'board_threads', 'tid', $_GET['tid']);
      $dsp->AddFieldsetEnd();
    }
  break;

  case 20:
    if ($auth['type'] >= 2) foreach ($_POST['action'] as $key => $val) {
      $db->query_first("UPDATE {$config["tables"]["board_threads"]} SET fid = ". (int)$_GET['to_fid'] ." WHERE tid = ". (int)$key);
    }
  break;
  
  // Delete Bookmark
  case 30:
    $GetFid = $db->qry_first('SELECT fid FROM %prefix%board_threads WHERE tid = %int%', $_GET['tid']);
    $db->qry('DELETE FROM %prefix%board_bookmark WHERE fid = 0 AND tid = %int% AND userid = %int%', $_GET['tid'], $auth['userid']);
    $db->qry('DELETE FROM %prefix%board_bookmark WHERE fid = %int% AND tid = 0 AND userid = %int%', $GetFid['fid'], $auth['userid']);
  break;

  // Lable
  case 40:  // None
  case 41:
  case 42:
  case 43:
  case 44:
  case 45:
    if ($auth['type'] >= 2) foreach ($_POST['action'] as $key => $val) {
      $db->qry('UPDATE %prefix%board_threads SET label = %int% WHERE tid = %int%', $_GET['step'] - 40, $key);
    }
  break;

  // Sticky
  case 50: // Add
    if ($auth['type'] >= 2) foreach ($_POST['action'] as $key => $val) {
      $db->qry('UPDATE %prefix%board_threads SET sticky = 1 WHERE tid = %int%', $key);
    }
  break;
  case 51: // Remove
    if ($auth['type'] >= 2) foreach ($_POST['action'] as $key => $val) {
      $db->qry('UPDATE %prefix%board_threads SET sticky = 0 WHERE tid = %int%', $key);
    }
  break;
}

$colors = array();
$colors[0] = '';
$colors[1] = 'red';
$colors[2] = 'blue';
$colors[3] = 'green';
$colors[4] = 'yellow';
$colors[5] = 'purple';


if ($_POST['search_input'][1] != '' or $_POST['search_input'][2] != '' or $_GET['search_input'][1] != '' or $_GET['search_input'][2] != '')
  $dsp->AddSingleRow('<b>'.t('Achtung: Sie haben als Suche einen Autor, bzw. Text angegeben. Die Ergebnis-Felder Antworten, sowie erster und letzter Beitrag beziehen sich daher nur noch auf Posts, in denen diese Eingaben gefunden wurden, nicht mehr auf den ganzen Thread!').'</b>');

include_once('modules/mastersearch2/class_mastersearch2.php');
$ms2 = new mastersearch2();

$ms2->query['from'] = "{$config['tables']['board_threads']} AS t
    LEFT JOIN {$config['tables']['board_forums']} AS f ON t.fid = f.fid
    LEFT JOIN {$config['tables']['board_posts']} AS p ON t.tid = p.tid
    LEFT JOIN {$config["tables"]["board_read_state"]} AS r ON t.tid = r.tid AND r.userid = ". (int)$auth['userid'] ."
    LEFT JOIN {$config["tables"]["user"]} AS u ON p.userid = u.userid
    LEFT JOIN {$config["tables"]["board_bookmark"]} AS b ON (b.fid = t.fid OR b.tid = t.tid) AND b.userid = ". (int)$auth['userid'] ."
    ";
$ms2->query['where'] = 'f.need_type <= '. (int)($auth['type'] + 1 ." AND (!need_group OR need_group = {$auth['group_id']})");
if ($_GET['fid'] != '') $ms2->query['where'] .= ' AND t.fid = '. (int)$_GET['fid'];
if ($_GET['action'] == 'bookmark') $ms2->query['where'] .= ' AND b.bid IS NOT NULL';
$ms2->query['default_order_by'] = 't.sticky DESC, LastPost DESC';

$ms2->AddBGColor('label', $colors);
#$ms2->AddBGColor('sticky', array('', 'red'));

if ($_GET['fid'] == '') {
  $ms2->AddTextSearchField(t('Titel'), array('t.caption' => 'like'));
  $ms2->AddTextSearchField(t('Text'), array('p.comment' => 'fulltext'));
  $ms2->AddTextSearchField(t('Autor'), array('u.username' => '1337', 'u.name' => 'like', 'u.firstname' => 'like'));

  $list = array();
  $list[''] = t('Alle');
  $res = $db->qry("SELECT fid, name FROM %prefix%board_forums");
  while ($row = $db->fetch_array($res)) $list[$row['fid']] = $row['name'];
  $ms2->AddTextSearchDropDown(t('Forum'), 'f.fid', $list);
  $db->free_result($res);
}

$ms2->AddSelect('t.closed');
$ms2->AddSelect('t.sticky');
if ($_GET['fid'] != '') $ms2->AddResultField(t('Thread'), 't.caption', 'FormatTitle');
else $ms2->AddResultField(t('Thread'), 'CONCAT(\'<b>\', f.name, \'</b><br />\', t.caption) AS ThreadName', 'FormatTitle');
$ms2->AddResultField(t('Neu'), 'r.last_read', 'NewPosts');
$ms2->AddResultField(t('Abrufe'), 't.views');
$ms2->AddResultField(t('Antworten'), '(COUNT(p.pid) - 1) AS posts');
$ms2->AddResultField(t('Erster Beitrag'), 'MIN(p.date) AS FirstPost', 'LastPostDetails');
$ms2->AddResultField(t('Letzter Beitrag'), 'MAX(p.date) AS LastPost', 'LastPostDetails');

if ($_GET['action'] == 'bookmark') {
  $ms2->AddResultField(t('E-Mail'), 'b.email', 'TrueFalse');
  $ms2->AddResultField(t('System-Mail'), 'b.sysemail', 'TrueFalse');
}

$ms2->AddIconField('details', 'index.php?mod=board&action=thread&fid='. $_GET["fid"] .'&tid=', t('Details'));
if ($_GET['action'] != 'bookmark') {
  if ($auth['type'] >= 2) $ms2->AddIconField('edit', 'index.php?mod=board&action=forum&step=10&fid='. $_GET['fid'] .'&tid=', t('Überschrift editieren'));
  if ($auth['type'] >= 3) $ms2->AddIconField('delete', 'index.php?mod=board&action=delete&step=11&tid=', t('Löschen'));

  if ($auth['type'] >= 2) {
    $res = $db->qry("SELECT fid, name FROM %prefix%board_forums");
    while ($row = $db->fetch_array($res))
      $ms2->AddMultiSelectAction(t('Verschieben nach '). $row['name'], 'index.php?mod=board&action=forum&step=20&to_fid='. $row['fid'] .'&fid='. $_GET['fid'], 1, 'in');
    $db->free_result($res);

    $ms2->AddMultiSelectAction(t('Markierung entfernen'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=40', 0, 'selection_none');
    $ms2->AddMultiSelectAction(t('Markieren: Rot'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=41', 0, 'selection_all');
    $ms2->AddMultiSelectAction(t('Markieren: Blau'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=42', 0, 'selection_all');
    $ms2->AddMultiSelectAction(t('Markieren: Grün'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=43', 0, 'selection_all');
    $ms2->AddMultiSelectAction(t('Markieren: Gelb'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=44', 0, 'selection_all');
    $ms2->AddMultiSelectAction(t('Markieren: Lila'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=45', 0, 'selection_all');

    $ms2->AddMultiSelectAction(t('Als Top Thread setzen'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=50', 0, 'important');
    $ms2->AddMultiSelectAction(t('Top Thread Marker entfernen'), 'index.php?mod=board&action=forum&fid='. $_GET['fid'] .'&step=51', 0, 'del_important');
  }
} else {
  $ms2->AddIconField('delete', 'index.php?mod=board&action=bookmark&step=30&tid=', t('Löschen'));
}

$ms2->PrintSearch('index.php?mod=board&action='. $_GET['action'] .'&fid='. $_GET['fid'], 't.tid');

if ($_GET['fid'] != '') $dsp->AddSingleRow($new_thread ." ". $dsp->FetchIcon("index.php?mod=board", "back"));

// Bookmarks and Auto-Mail
if ($_GET['fid'] and $auth['login']) {
	if ($_GET["set_bm"]) {
		$db->query_first("DELETE FROM {$config["tables"]["board_bookmark"]} WHERE fid = '{$_GET['fid']}' AND userid = '{$auth['userid']}'");
		if ($_POST["check_bookmark"]) $db->query_first("INSERT INTO {$config["tables"]["board_bookmark"]} SET fid = '{$_GET['fid']}', userid = '{$auth['userid']}', email = '{$_POST["check_email"]}', sysemail = '{$_POST["check_sysemail"]}'");
	}

	$bookmark = $db->query_first("SELECT 1 AS found, email, sysemail FROM {$config["tables"]["board_bookmark"]} WHERE fid = '". (int)$_GET['fid'] ."' AND userid = '{$auth['userid']}'");
	if ($bookmark["found"]) $_POST["check_bookmark"] = 1;
	if ($bookmark["email"]) $_POST["check_email"] = 1;
	if ($bookmark["sysemail"]) $_POST["check_sysemail"] = 1;

	$dsp->SetForm("index.php?mod=board&action=forum&fid={$_GET['fid']}&set_bm=1");
	$dsp->AddFieldsetStart(t('Monitoring'));
  $additionalHTML = "onclick=\"CheckBoxBoxActivate('email', this.checked)\"";
	$dsp->AddCheckBoxRow("check_bookmark", t('Lesezeichen'), t('Alle Beiträge in diesem Forum in meine Lesezeichen aufnehmen<br><i>(Lesezeichen ist Vorraussetzung, um Benachrichtigung per Mail zu abonnieren)</i>'), "", 1, $_POST["check_bookmark"], '', '', $additionalHTML);
	$dsp->StartHiddenBox('email', $_POST["check_bookmark"]);
	$dsp->AddCheckBoxRow("check_email", t('E-Mail Benachrichtigung'), t('Bei Antworten auf Beiträge in Threads dieses Forums eine Internet-Mail an mich senden'), "", 1, $_POST["check_email"]);
	$dsp->AddCheckBoxRow("check_sysemail", t('System-E-Mail'), t('Bei Antworten auf Beiträge in Threads dieses Forums eine System-Mail an mich senden'), "", 1, $_POST["check_sysemail"]);
	$dsp->StopHiddenBox();
	$dsp->AddFormSubmitRow("edit");
	$dsp->AddFieldsetEnd();
}

// Generate Boardlist-Dropdown
$foren_liste = $db->qry("SELECT fid, name FROM %prefix%board_forums WHERE need_type <= %int% AND (!need_group OR need_group = %int%)", ($auth['type'] + 1), $auth['group_id']);
while ($forum = $db->fetch_array($foren_liste))
  $templ['board']['thread']['case']['control']['goto'] .= "<option value=\"index.php?mod=board&action=forum&fid={$forum["fid"]}\">{$forum["name"]}</option>";
$templ['board']['forum']['case']['info']['forum_choise'] = t('Bitte auswählen');
$dsp->AddDoubleRow(t('Gehe zu Forum'), $dsp->FetchModTpl('board', 'forum_dropdown'));
$dsp->AddContent();

?>

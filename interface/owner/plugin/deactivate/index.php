<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'name' => array('directory', 'default'=> null)
	)
);
require ROOT . '/library/dispatcher.php';
requireModel('common.plugin');
requireStrictRoute();

if(empty($_POST['name'])) respond::ResultPage(1);
$pluginInfo = getPluginInformation(trim($_POST['name']));
$pluginScope = $pluginInfo['scope'];
if(in_array('editor',$pluginScope) && $editorCount == 1)
	respond::ResultPage(2);
if(in_array('formatter',$pluginScope) && $formatterCount == 1)
	respond::ResultPage(2);
if (deactivatePlugin($_POST['name']))
	respond::ResultPage(0);
respond::ResultPage(1);
?>

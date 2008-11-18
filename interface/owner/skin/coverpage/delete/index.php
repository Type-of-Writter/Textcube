<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'GET' => array(
		'coverpageNumber' => array('int'),
		'modulePos' => array('int'),
		'viewMode' => array('string', 'default' => '')
	)
);
require ROOT . '/library/includeForBlogOwner.php';
 
requireModel("blog.sidebar");
requireModel("blog.coverpage");


$skin = new BlogSkin($skinSetting['skin']);
$coverpageCount = count($skin->coverpageBasicModules);
$coverpageOrder = deleteCoverpageModuleOrderData(getCoverpageModuleOrderData($coverpageCount), $_GET['coverpageNumber'], $_GET['modulePos']);
setBlogSetting("coverpageOrder", serialize($coverpageOrder));

//respond::PrintResult(array('error' => 0));
if ($_GET['viewMode'] != '') $_GET['viewMode'] = '?' . $_GET['viewMode'];
header('Location: '. $blogURL . '/owner/skin/coverpage' . $_GET['viewMode']);
?>

<?php

########################################################################
# Extension Manager/Repository config file for ext "rtp_imgquery".
#
# Auto generated 04-01-2012 10:16
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Repsonsive Images',
	'description' => 'Adds responsive image functionality to TYPO3',
	'category' => 'fe',
	'author' => 'Simon Tuck',
	'author_email' => 'stu@rtp.ch',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.6.4',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
            // Requieres smarty version 1.11.0 for the smarty plugin
            'smarty' => '0.0.0-1.10.5',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:8:{s:9:"ChangeLog";s:4:"c44d";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:14:"ext_tables.php";s:4:"6bb2";s:19:"doc/wizard_form.dat";s:4:"976c";s:20:"doc/wizard_form.html";s:4:"5e2e";s:38:"static/responsive_images/constants.txt";s:4:"4f1b";s:34:"static/responsive_images/setup.txt";s:4:"6e0f";}',
);

?>

<?php
/**
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2015. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@cubecart.com
 * License:  GPL-3.0 https://www.gnu.org/licenses/quick-guide-gplv3.html
 */
if (!defined('CC_INI_SET')) die('Access Denied');
Admin::getInstance()->permissions('maintenance', CC_PERM_EDIT, true);

global $lang;

function crc_integrity_check($files, $mode = 'upgrade') {
	
	$errors = array();
	
	$log_path = CC_ROOT_DIR.'/backup/'.$mode.'_error_log';
	if(file_exists($log_path)) unlink($log_path); 

	foreach ($files as $file => $value) {
		if(!file_exists($file)) {
			$errors[] = "$file - Missing but expected after extract";
		} elseif (is_file($file)) {
			## Open the source file
			if (($v_file = fopen($file, "rb")) == 0) {
				$errors[] = "$file - Unable to read in order to validate integrity";
			}

			## Read the file content
			$v_content = fread($v_file, filesize($file));
			fclose($v_file);

			if(crc32($v_content) !== $value) {
				$errors[] = "$file - Content after extract don't match source";
			}
		}
	}
	if(count($errors)>0) {
		$errors[] = '--';
		$errors[] = 'Errors were found which may indicate that the source archive has not been extracted successfully.';
		$errors[] = 'It is recommended that a manual '.$mode.' is performed.';	
			
		$error_data = "### START ".strtoupper($mode)." LOG - (".date("d M Y - H:i:s").") ###\r\n";
		$error_data .= implode("\r\n", $errors);
		$error_data .=  "\r\n### END RESTORE LOG ###";

		$fp = fopen($log_path, 'w');
		fwrite($fp, $error_data);
		fclose($fp);
	} else {
		return false;
	}
}

$version_history = $GLOBALS['db']->select('CubeCart_history', false, false, "`version` DESC");

$GLOBALS['smarty']->assign('VERSIONS', $version_history);


if(isset($_GET['compress']) && !empty($_GET['compress'])) {
	chdir(CC_ROOT_DIR.'/backup');
	$file_path = './'.basename($_GET['compress']);
	$zip = new ZipArchive;
	
	if (file_exists($file_path) && $zip->open($file_path.'.zip', ZipArchive::CREATE)==true) {
		$zip->addFile($file_path);
		$zip->close();
		$GLOBALS['main']->setACPNotify(sprintf($lang['maintain']['file_compressed'], basename($file_path)));
		httpredir('?_g=maintenance&node=index#backup');	
	} else {
		$GLOBALS['main']->setACPWarning("Error reading file ".basename($file_path));
	}	
}

if (isset($_GET['restore']) && !empty($_GET['restore'])) {

	// Prevent user stopping process
	ignore_user_abort(true);
	// Set max execution time to three minutes
	set_time_limit(180);
	// Make sure line endings can be detected
	ini_set("auto_detect_line_endings", true);

	$file_path = CC_ROOT_DIR.'/backup/'.basename($_GET['restore']);

	if (preg_match('/^database_full/', $_GET['restore'])) { // Restore database
		$delete_source = false;	
		if (preg_match('/\.sql.zip$/', $_GET['restore'])) { // unzip first
			
			$zip = new ZipArchive;
			if ($zip->open($file_path) === TRUE) {

				$file_path = rtrim($file_path, '.zip');
				// Only delete if it diesn't exist before
				$delete_source = file_exists($file_path) ? false : true;
				$zip->extractTo(CC_ROOT_DIR.'/backup');
    			$zip->close();
			} else {
				$GLOBALS['main']->setACPWarning("Error reading file ".$_GET['restore']);
				httpredir('?_g=maintenance&node=index#backup');	
			}
		}
		
		$handle = fopen($file_path,"r");
		$import = false;
		$GLOBALS['debug']->status(false); // This prevents memory errors
		if($handle) {
			$sql = '';
		    while(($buffer = fgets($handle)) !== false) {
		        $sql .= $buffer;
		        if(substr(trim($buffer),-4) === '#EOQ'){
					if($GLOBALS['db']->parseSchema($sql)) {
						$import = true;
					}
					$sql = '';
		        }
		    }
			fclose($handle);
		}
		
		if($delete_source) {
			unlink($file_path);	
		}

		if ($import) {
			$GLOBALS['main']->setACPNotify($lang['maintain']['db_restored']);
			$GLOBALS['cache']->clear();
			httpredir('?_g=maintenance&node=index#backup');
		}

	} elseif (preg_match('/^files/', $_GET['restore'])) { // restore archive
		
		$file_path = CC_ROOT_DIR.'/backup/'.$_GET['restore'];
		$zip = new ZipArchive;
		if ($zip->open($file_path) === true) {
			
			$crc_check_list = array();
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$stat = $zip->statIndex($i);
				$crc_check_list[$stat['name']] = $stat['crc'];
			}

			$zip->extractTo(CC_ROOT_DIR);
			$zip->close();

			$errors = crc_integrity_check($crc_check_list, 'restore');
			
			if ($errors!==false) {				
				$GLOBALS['main']->setACPWarning($lang['maintain']['files_restore_fail']);
				httpredir('?_g=maintenance&node=index#backup');
			} else {
				$GLOBALS['main']->setACPNotify($lang['maintain']['files_restore_success']);
				$GLOBALS['cache']->clear();
				httpredir('?_g=maintenance&node=index#backup');
			}
		} else {
			$GLOBALS['main']->setACPWarning($lang['maintain']['files_restore_not_possible']);	
		}
		
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['files_restore_not_possible']);
		httpredir('?_g=maintenance&node=index#backup');
	}
}

if (isset($_GET['upgrade']) && !empty($_GET['upgrade'])) {

	$contents = false;
	## Download the version we want
	$request = new Request('www.cubecart.com', '/download/'.$_GET['upgrade'].'.zip', 80, false, true, 10);
	$request->setMethod('get');
	$request->setSSL();
	$request->setData(array('null'=>0)); // setData needs a value to work
	$request->setUserAgent('CubeCart');
	$request->skiplog(true);

	if (!$contents = $request->send()) {
		$contents = file_get_contents('https://www.cubecart.com/download/'.$_GET['upgrade'].'.zip');
	}

	if (empty($contents)) {
		$GLOBALS['main']->setACPWarning($lang['maintain']['files_upgrade_download_fail']);
		httpredir('?_g=maintenance&node=index#upgrade');
	} else {

		if (stristr($contents, 'DOCTYPE') ) {
			$GLOBALS['main']->setACPWarning("Sorry. CubeCart-".$_GET['upgrade'].".zip was not found. Please try again later.");
			httpredir('?_g=maintenance&node=index#upgrade');
		}

		$destination_path = CC_ROOT_DIR.'/backup/CubeCart-'.$_GET['upgrade'].'.zip';
		$fp = fopen($destination_path, 'w');
		fwrite($fp, $contents);
		fclose($fp);

		if (file_exists($destination_path)) {

			$zip = new ZipArchive;
			if ($zip->open($destination_path) === true) {
				
				$crc_check_list = array();
				for ($i = 0; $i < $zip->numFiles; $i++) {
					$stat = $zip->statIndex($i);
					$crc_check_list[$stat['name']] = $stat['crc'];
				}

				$zip->extractTo(CC_ROOT_DIR);
				$zip->close();

				$errors = crc_integrity_check($crc_check_list, 'upgrade');
				
				if ($errors!==false) {
					$GLOBALS['main']->setACPWarning($lang['maintain']['files_upgrade_fail']);
					httpredir('?_g=maintenance&node=index#upgrade');
				} elseif ($_POST['force']) {
					## Try to delete setup folder
					recursiveDelete(CC_ROOT_DIR.'/setup');
					unlink(CC_ROOT_DIR.'/setup');
					## If that fails we try an obscure rename
					if (file_exists(CC_ROOT_DIR.'/setup')) {
						rename(CC_ROOT_DIR.'/setup', CC_ROOT_DIR.'/setup_'.md5(time().$_GET['upgrade']));
					}
					$GLOBALS['main']->renameAdmin();
					$GLOBALS['main']->setACPNotify($lang['maintain']['current_version_restored']);
					httpredir('?_g=maintenance&node=index#upgrade');
				} else {
					$GLOBALS['main']->renameAdmin();
					httpredir(CC_ROOT_REL.'setup/index.php?autoupdate=1');
				}
			} else {
				$GLOBALS['main']->setACPWarning("Unable to read archive.");
				httpredir('?_g=maintenance&node=index#upgrade');
			}			
		}
	}
}

if (isset($_GET['delete'])) {
	$file = 'backup/'.basename($_GET['delete']);
	if(in_array($_GET['delete'], array('restore_error_log','upgrade_error_log'))) {
		unlink($file);
		httpredir('?_g=maintenance&node=index#backup');
	} else if(file_exists($file) && preg_match('/^.*\.(sql|zip)$/i', $file)) {
		## Generic error message for logs delete specific for backup
		$message = preg_match('/\_error_log$/', $file) ? $lang['filemanager']['notify_file_delete'] : sprintf($lang['maintain']['backup_deleted'], basename($file));
		$GLOBALS['main']->setACPNotify($message);
		unlink($file);
		httpredir('?_g=maintenance&node=index#backup');
	}
}
if (isset($_GET['download'])) {
	$file = 'backup/'.basename($_GET['download']);
	if(file_exists($file)) {
		deliverFile($file);
		httpredir('?_g=maintenance&node=index#backup');
	}
}

########## Rebuild ##########
$clear_post = false;

if (isset($_POST['truncate_seo_custom'])) {
	if ($GLOBALS['db']->delete('CubeCart_seo_urls', array('custom' => 1))) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['seo_urls_emptied']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['seo_urls_not_emptied']);
	}
	$clear_post = true;
}
if (isset($_POST['truncate_seo_auto'])) {
	if ($GLOBALS['db']->delete('CubeCart_seo_urls', array('custom' => 0))) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['seo_urls_emptied']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['seo_urls_not_emptied']);
	}
	$clear_post = true;
}

if (isset($_POST['sitemap'])) {
	if ($GLOBALS['seo']->sitemap()) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_sitemap']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['notify_sitemap_fail']);
	}
	$clear_post = true;
}

if (isset($_POST['emptyTransLogs']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear();
	if ($GLOBALS['db']->truncate('CubeCart_transactions')) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_logs_transaction']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_logs_transaction']);
	}
	$clear_post = true;
}

if (isset($_REQUEST['emptyEmailLogs']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear();
	if ($GLOBALS['db']->truncate(array('CubeCart_email_log'))) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_logs_email']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_logs_email']);
	}
	$clear_post = true;
}

if (isset($_REQUEST['emptyErrorLogs']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear();
	if ($GLOBALS['db']->truncate(array('CubeCart_system_error_log', 'CubeCart_admin_error_log'))) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_logs_error']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_logs_error']);
	}
	$clear_post = true;
}

if (isset($_REQUEST['emptyRequestLogs']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear();
	if ($GLOBALS['db']->truncate('CubeCart_request_log')) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_logs_request']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_logs_request']);
	}
	$clear_post = true;
}

if (isset($_POST['clearSearch']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear();
	if ($GLOBALS['db']->truncate('CubeCart_search')) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_search_clear']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_search_clear']);
	}
	$clear_post = true;
}

if (isset($_POST['clearCache']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear();
	$GLOBALS['cache']->tidy();
	$GLOBALS['main']->setACPNotify($lang['maintain']['notify_cache_cleared']);
	$clear_post = true;
}

if (isset($_POST['clearSQLCache']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear('sql');
	$GLOBALS['main']->setACPNotify($lang['maintain']['notify_cache_cleared']);
	$clear_post = true;
}

if (isset($_POST['clearLangCache']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	$GLOBALS['cache']->clear('lang');
	$GLOBALS['main']->setACPNotify($lang['maintain']['notify_cache_cleared']);
	$clear_post = true;
}

if (isset($_POST['clearImageCache']) && Admin::getInstance()->permissions('maintenance', CC_PERM_DELETE)) {
	function cleanImageCache($path = null) {
		$path = (isset($path) && is_dir($path)) ? $path : CC_ROOT_DIR.'/images/cache'.'/';
		$scan = glob($path.'*', GLOB_MARK);
		if (is_array($scan) && !empty($scan)) {
			foreach ($scan as $result) {
				if (is_dir($result)) {
					cleanImageCache($result);
					rmdir($result);
				} else {
					unlink($result);
				}
			}
		}
	}
	## recursively delete the contents of the images/cache folder
	cleanImagecache();
	$GLOBALS['main']->setACPNotify($lang['maintain']['notify_cache_image']);
	$clear_post = true;
}
if (isset($_POST['prodViews'])) {
	$GLOBALS['cache']->clear();
	if ($GLOBALS['db']->update('CubeCart_inventory', array('popularity' => 0), '', true)) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_reset_product']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_reset_product']);
	}
	$clear_post = true;
}

if (isset($_POST['clearLogs'])) {
	$GLOBALS['cache']->clear();
	if ($GLOBALS['db']->truncate(array('CubeCart_admin_log', 'CubeCart_access_log'))) {
		$GLOBALS['main']->setACPNotify($lang['maintain']['notify_logs_admin']);
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_logs_admin']);
	}
	$clear_post = true;
}

########## Database ##########
if (!empty($_POST['database'])) {
	if (is_array($_POST['tablename'])) {
		foreach ($_POST['tablename'] as $value) {
			$tableList[] = sprintf('`%s`', $value);
		}
		$database_result = $GLOBALS['db']->query(sprintf("%s TABLE %s;", $_POST['action'], implode(',', $tableList)));
		$GLOBALS['main']->setACPNotify(sprintf($lang['maintain']['notify_db_action'], $_POST['action']));
	} else {
		$GLOBALS['main']->setACPWarning($lang['maintain']['db_none_selected']);
	}
}

########## Backup ##########
if (isset($_GET['files_backup'])) {

	// Prevent user stopping process
	ignore_user_abort(true);
	// Set max execution time to three minutes
	set_time_limit(180);

	$GLOBALS['cache']->clear(); // Clear cache to remove unimpoartant data to save space and possible errors
	
	chdir(CC_ROOT_DIR);
	$destination = CC_ROOT_DIR.'/backup/files_'.CC_VERSION.'_'.date("dMy-His").'.zip';
	
	$zip = new ZipArchive();

	if ($zip->open($destination, ZipArchive::CREATE)!==true) {
		$GLOBALS['main']->setACPWarning("Error: Backup failed.");
	} else {
		$skip_folders = 'backup|cache|images/cache|includes/extra/sess_';
		if(isset($_POST['skip_images']) && $_POST['skip_images']=='1') {
			$zip->addEmptyDir('./images/source');
			$skip_folders .= '|images/source';
		}
		if(isset($_POST['skip_downloads']) && $_POST['skip_downloads']=='1') {
			$zip->addEmptyDir('./files');
			if(file_exists('./files/.htaccess')) {
				$zip->addFile('./files/.htaccess');
			}
			$skip_folders .= '|files';
		}

		$files = glob_recursive('*');

		$zip->addEmptyDir('./backup');
		if(file_exists('./backup/.htaccess')) {
			$zip->addFile('./backup/.htaccess');
		}
		
		$zip->addEmptyDir('./cache');
		if(file_exists('./cache/.htaccess')) {
			$zip->addFile('./cache/.htaccess');
		}
		$zip->addEmptyDir('./images/cache');

		foreach ($files as $file) {
			$file_match = preg_replace('#^./#','',$file);
			if($file == 'images' || preg_match('#^('.$skip_folders.')#', $file_match)) continue;
			if(is_dir($file)) {
				$zip->addEmptyDir($file);
			} else {
				$zip->addFile($file);	
			}
		}
		$zip->close();
		$GLOBALS['main']->setACPNotify($lang['maintain']['files_backup_complete']);
	}
	httpredir('?_g=maintenance&node=index#backup');
}

if (isset($_POST['backup'])) {

	// Prevent user stopping process
	ignore_user_abort(true);
	// Set max execution time to three minutes
	set_time_limit(180);

	if (!$_POST['drop'] && !$_POST['structure'] && !$_POST['data']) {
		$GLOBALS['main']->setACPWarning($lang['maintain']['error_db_backup_option']);
	} else {
		if ($_POST['drop'] && !$_POST['structure']) {
			$GLOBALS['main']->setACPWarning($lang['maintain']['error_db_backup_conflict']);
		} else {
			$full = ($_POST['drop'] && $_POST['structure'] && $_POST['data']) ? '_full' : ''; 
			chdir(CC_ROOT_DIR.'/backup');
			$fileName 	= 'database'.$full.'_'.CC_VERSION.'_'.$glob['dbdatabase']."_".date("dMy-His").'.sql';
			if(file_exists($fileName)) { // Keep file pointer at the start
				unlink($fileName);	
			}
			$all_tables = (isset($_POST['db_3rdparty']) && $_POST['db_3rdparty'] == '1') ? true : false;
			$write = $GLOBALS['db']->doSQLBackup($_POST['drop'],$_POST['structure'],$_POST['data'], $fileName, $_POST['compress'], $all_tables);
			if($write) {
				$GLOBALS['main']->setACPNotify($lang['maintain']['db_backup_complete']);
			} else {
				$GLOBALS['main']->setACPWarning($lang['maintain']['db_backup_failed']);
			}
		}
		$clear_post = true;
	}
}

if ($clear_post) httpredir(currentPage(array('clearLogs', 'emptyErrorLogs')));

########## Tabs ##########
$GLOBALS['main']->addTabControl($lang['maintain']['tab_rebuild'], 'rebuild');
$GLOBALS['main']->addTabControl($lang['maintain']['tab_backup'], 'backup');
$GLOBALS['main']->addTabControl($lang['common']['upgrade'], 'upgrade');
$GLOBALS['main']->addTabControl($lang['maintain']['tab_db'], 'database');
$GLOBALS['main']->addTabControl($lang['maintain']['tab_query_sql'], 'general', '?_g=maintenance&node=sql');

##########

## Database
if (isset($database_result) && $database_result) {
	$GLOBALS['smarty']->assign('TABLES_AFTER', $database_result);
} elseif (($tables = $GLOBALS['db']->getRows()) !== false) {
	foreach ($tables as $table) {
		$table['Data_free'] = ($table['Data_free'] > 0) ? formatBytes($table['Data_free'], true) : '-';
		$table_size   = $table['Data_length']+$table['Index_length'];
		$data_length  = formatBytes($table_size);
		$table['Data_length'] = ($table_size>0) ? $data_length['size'].' '.$data_length['suffix'] : '-';
		$table['Name_Display'] = $GLOBALS['config']->get('config', 'dbdatabase').'.'.$table['Name'];
		$smarty_data['tables'][] = $table;
	}
	$GLOBALS['smarty']->assign('TABLES', $smarty_data['tables']);
}

## Existing Backups
$files = glob('{backup/*.sql,backup/*.zip}', GLOB_BRACE);

if (count($files)>0) {
	foreach ($files as $file) {
		$sorted_files[filemtime($file)] = $file;
	}
	unset($files);

	krsort($sorted_files); // Sort to time order

	foreach ($sorted_files as $file) {
		$filename = basename($file);
		$type = preg_match('/^database/', $filename) ? 'database' : 'files';
		$restore = preg_match('/^database_full|files/', $filename) ? '?_g=maintenance&node=index&restore='.$filename.'#backup' : false;
		$compress = (preg_match('/.zip$/', $filename) || file_exists($file.'.zip')) ? false : '?_g=maintenance&node=index&compress='.$filename.'#backup';
		$existing_backups[] = array('filename' => $filename,
			'delete_link' => '?_g=maintenance&node=index&delete='.$filename.'#backup',
			'download_link' => '?_g=maintenance&node=index&download='.$filename.'#backup',
			'restore_link' => $restore,
			'compress' =>  $compress,
			'type' => $type,
			'warning' => ($type=='database') ? $lang['maintain']['restore_db_confirm'] : $lang['maintain']['restore_files_confirm'],
			'size' => formatBytes(filesize($file), true)
		);
	}
}
$GLOBALS['smarty']->assign('EXISTING_BACKUPS', $existing_backups);

## Upgrade
## Check current version
if ($request = new Request('www.cubecart.com', '/version-check/'.CC_VERSION)) {
	$request->skiplog(true);
	$request->setMethod('get');
	$request->cache(true);
	$request->setSSL(true);
	$request->setUserAgent('CubeCart');
	$request->setData(array('version' => CC_VERSION));

	if (($response = $request->send()) !== false) {
		$response_array = json_decode($response, true);
		if (version_compare(trim($response_array['version']), CC_VERSION, '>')) {
			$GLOBALS['smarty']->assign('OUT_OF_DATE', sprintf($lang['dashboard']['error_version_update'], $response_array['version'], CC_VERSION));
			$GLOBALS['smarty']->assign('LATEST_VERSION', $response_array['version']);
			$GLOBALS['smarty']->assign('UPGRADE_NOW', $lang['maintain']['upgrade_now']);
			$GLOBALS['smarty']->assign('FORCE', '0');
		} else {
			$GLOBALS['smarty']->assign('LATEST_VERSION', CC_VERSION);
			$GLOBALS['smarty']->assign('UPGRADE_NOW', $lang['maintain']['force_upgrade']);
			$GLOBALS['smarty']->assign('FORCE', '1');
		}
	} else {
		$GLOBALS['smarty']->assign('LATEST_VERSION', $lang['common']['unknown']);
		$GLOBALS['smarty']->assign('UPGRADE_NOW', $lang['maintain']['force_upgrade']);
		$GLOBALS['smarty']->assign('FORCE', '1');
		$GLOBALS['main']->setACPNotify($lang['maintain']['latest_version_unknown']);
	}
}

if (file_exists(CC_ROOT_DIR.'/backup/restore_error_log')) {
	$contents = file_get_contents(CC_ROOT_DIR.'/backup/restore_error_log');
	if (!empty($contents)) {
		$GLOBALS['smarty']->assign('RESTORE_ERROR_LOG', $contents);
	}
}

if (file_exists(CC_ROOT_DIR.'/backup/upgrade_error_log')) {
	$contents = file_get_contents(CC_ROOT_DIR.'/backup/upgrade_error_log');
	if (!empty($contents)) {
		$GLOBALS['smarty']->assign('UPGRADE_ERROR_LOG', $contents);
	}
}

$page_content = $GLOBALS['smarty']->fetch('templates/maintenance.index.php');
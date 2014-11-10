<?php
/**
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-2.0 http://opensource.org/licenses/GPL-2.0
 */
if (!defined('CC_INI_SET')) die('Access Denied');

global $lang, $glob;

$GLOBALS['main']->addTabControl('Manage Plugins', 'plugins');


if(isset($_POST['plugin_token']) && !empty($_POST['plugin_token'])) {
	$token = str_replace('-','',$_POST['plugin_token']);
	$json = file_get_contents('http://sandbox.cubecart.com/extensions/token/'.$token.'/get');
	if($json && !empty($json)) {
		$data = json_decode($json, true);
		$destination = CC_ROOT_DIR.'/'.$data['path'];
		if(file_exists($destination)) {
			if(is_writable($destination)) {
				$tmp_path = CC_ROOT_DIR.'/cache/'.$data['file_name'];
				$fp = fopen($tmp_path, 'w');
				fwrite($fp, hex2bin($data['file_data']));
				fclose($fp);
				if(!file_exists($tmp_path)) {
					$GLOBALS['main']->setACPWarning('Failed to retrieve file.');
				}
				// Read the zip
				require_once CC_INCLUDES_DIR.'lib/pclzip/pclzip.lib.php';
				$source = new PclZip($tmp_path);
				$files = $source->listContent();
				if(is_array($files)) {
					$extract = true;
					$backup = false;
					foreach($files as $file) {
						$root_path = $destination.'/'.$file['filename'];
						
						if(file_exists($root_path) && basename($file['filename'])=="config.xml") {
							// backup existing
							$backup = str_replace('config.xml','',$file['filename'])."*";
						}

						if(file_exists($root_path) && !is_writable($root_path)) {
							$GLOBALS['main']->setACPWarning('Error: '.$data['path'].'/'.$file.' already exists and is not writable.');
							$extract = false;
						}
					}
	
					if($_POST['backup']=='1' && $backup) {
						$destination_filepath = CC_ROOT_DIR.'/backup/'.$data['file_name'].'_'.date("dMy-His").'.zip';
						$archive = new PclZip($destination_filepath);
						chdir($destination);
						$files = glob($backup);
						foreach ($files as $file) {
							$backup_list[] = $file;
						}
						if ($archive->create($backup_list) == 0) {
							if($_POST['abort']=='1') {
								$extract = false;
								$GLOBALS['main']->setACPWarning('Failed to backup existing plugin. Process aborted.');
							} else {
								$GLOBALS['main']->setACPWarning('Failed to backup existing plugin.');
							}
						} else {
							$GLOBALS['main']->setACPNotify('Backup of existing plugin created.');
						}
					}
					if($extract) {
						if ($source->extract(PCLZIP_OPT_PATH, $destination, PCLZIP_OPT_REPLACE_NEWER) == 0) {
							$GLOBALS['main']->setACPWarning('Failed to install plugin.');	
						} else {
							$GLOBALS['main']->setACPNotify('Plugin installed successfully.');
							file_get_contents('http://sandbox.cubecart.com/extensions/token/'.$token.'/delete');
						}
					}

				} else {
					$GLOBALS['main']->setACPWarning('Error: It\'s not been possible to read the contents of the file '.$data['file_name'].'.');
				}

			} else {
				$GLOBALS['main']->setACPWarning('Error: '.$destination.' is not writable by the web server.');
			}
		} else {
			$GLOBALS['main']->setACPWarning('Error: '.$destination.' does not exist. Please create it and try again.');
		}
	} else {
		$GLOBALS['main']->setACPWarning('Error: Token was not recognised.');
	}
}

if (isset($_POST['status'])) {

	$before = md5(serialize($GLOBALS['db']->select('CubeCart_modules')));

	foreach ($_POST['status'] as $module_name => $status) {
		$module_type = $_POST['type'][$module_name];
		if($GLOBALS['db']->select('CubeCart_modules',false,array('folder' => $module_name))) {
			$GLOBALS['db']->update('CubeCart_modules', array('status' => (int)$status), array('folder' => $module_name), true);
			if ($module_type=='plugins') {
				if ($status) {
					$GLOBALS['hooks']->install($module_name);
				} else {
					$GLOBALS['hooks']->uninstall($module_name);
				}
			}
		} else {
			$GLOBALS['db']->insert('CubeCart_modules', array('status' => (int)$status, 'folder' => $module_name, 'module' => $module_type));
		}
		// Update config
		$GLOBALS['config']->set($module_name, 'status', $status);
	}
	$after = md5(serialize($GLOBALS['db']->select('CubeCart_modules')));
	if ($before !== $after) {
		$GLOBALS['gui']->setNotify($lang['module']['notify_module_status']);
	}
	$GLOBALS['cache']->clear();
	httpredir('?_g=plugins');
}

if(!$modules = $GLOBALS['cache']->read('module_list')) {
	$module_paths = glob("modules/*/*/config.xml");
	$i=0;
	foreach ($module_paths as $module_path) {
	
		$xml   = new SimpleXMLElement(file_get_contents($module_path));
		
		$basename = (string)basename(str_replace('config.xml','', $module_path));
		$key = trim((string)$xml->info->name.$i);
		
		$config = $GLOBALS['db']->select('CubeCart_modules','*',array('folder' => $basename, 'module' => (string)$xml->info->type));

		$modules[$key] = array(
			'uid' 				=> (string)$xml->info->uid,
			'type' 				=> (string)$xml->info->type,
			'mobile_optimized' 	=> (string)$xml->info->mobile_optimized,
			'name' 				=> (string)$xml->info->name,
			'description' 		=> (string)$xml->info->description,
			'version' 			=> (string)$xml->info->version,
			'minVersion' 		=> (string)$xml->info->minVersion,
			'maxVersion' 		=> (string)$xml->info->maxVersion,
			'creator' 			=> (string)$xml->info->creator,
			'homepage' 			=> (string)$xml->info->homepage,
			'block' 			=> (string)$xml->info->block,
			'basename' 			=> $basename,
			'config'			=> $config[0]
		);
		$i++;
	}
	ksort($modules);
	$GLOBALS['cache']->write($modules, 'module_list');
}
$GLOBALS['smarty']->assign('MODULES',$modules);
$page_content = $GLOBALS['smarty']->fetch('templates/plugins.index.php');
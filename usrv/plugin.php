<?php
/*
Plugin Name: U-SRV
Plugin URI: https://github.com/joshp23/YOURLS-U-SRV
Description: A universal file server for YOURLS
Version: 2.3.0
Author: Josh Panter
Author URI: https://unfettered.net
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Add the admin page
yourls_add_action( 'plugins_loaded', 'usrv_add_page' );
function usrv_add_page() {
        yourls_register_plugin_page( 'usrv', 'U-SRV', 'usrv_do_page' );
}
function usrv_do_page() {
	$usrv_fu_msg = '';
	// Check if a form was submitted
	if(isset($_POST['fu_submit']) && $_POST['fu_submit'] !== '') $usrv_fu_msg = usrv_fu();
	usrv_form_0();

	// Get the options and set defaults if needed
	$opt = usrv_get_opts();
	// Make sure cache exists
	usrv_mkdir( $opt[0] );
	// Create nonce
	$nonce = yourls_create_nonce( 'usrv' );
	// some values necessary for display
	$D_chk = $P_chk = null;
	switch ($opt[1]) {
		case 'preserve':	$P_chk = 'checked'; break;
		case 'delete':		$D_chk = 'checked'; break;
		default:  	 		$P_chk = 'checked'; break;
	}
	$base = YOURLS_SITE;
	$now = round(time()/60);
	$key = md5($now . 'usrv_files');
	$fn = md5('userv_files'.'example.mp3');

echo <<<HTML
	<div id="wrap">
		<div id="tabs">
			<div class="wrap_unfloat">
				<ul id="headers" class="toggle_display stat_tab">
					<li class="selected"><a href="#stat_tab_upload"><h2>File Utilities</h2></a></li>
					<li><a href="#stat_tab_options"><h2>Config</h2></a></li>
					<li><a href="#stat_tab_examples"><h2>Examples</h2></a></li>
				</ul>
			</div>

			<div id="stat_tab_upload" class="tab">
				<p>Here you can manually upload a file into the cache for retreival via another script.</p>

				<p><strong>$usrv_fu_msg</strong></p>
				
				<form method="post" enctype="multipart/form-data"> 

				<fieldset> <legend>Select a file</legend>
					<p><input type="file" id="usrv_fu" name="usrv_fu" /></p>
				</fieldset>

				<p><input type="submit" name="fu_submit" value="Submit" /></p>
				</form>
				<hr>
				<table id="main_table" class="tblSorter" cellpadding="0" cellspacing="1">
				<thead>
					<tr>
						<th>Original File Name</th>
						<th>Hashed Name</th>
					</tr>
				</thead>
				<tbody>
HTML;
	// CHECK if delete fu form was submitted
	if( isset( $_GET['action'] ) && $_GET['action'] == 'fuDelete' ) {
		$fn = $_GET['fu'];
		usrv_delete($fn);
	}
	// populate table rows with flag data if there is any
	global $ydb;
	$table = 'usrv';
	$sql = "SELECT * FROM `$table` ORDER BY name DESC";
	$fuList = $ydb->fetchObjects($sql);
	$found_rows = false;
	if($fuList) {
		$found_rows = true;
		foreach( $fuList as $fu ) {
			$name = $fu->name;
			$hashname = $fu->hashname;
			$delete = $_SERVER['PHP_SELF'].'?page=usrv&action=fuDelete&fu='.$hashname;
			// print if there is any data
			echo <<<HTML
				<tr>
					<td>$name</td>
					<td>$hashname</td>
					<td><a href="$delete">Delete <img src="$base/images/delete.png" title="Delete" border=0></a></td>
				</tr>
HTML;
		}
	} else {
		echo <<<HTML
				<tr>
					<td>There's nothing here to see, go upload some files!</td>
					<td></td>
					<td></td>
				</tr>
HTML;
	}
	echo <<<HTML
				</tbody>
				</table>	
			</div>
			<div id="stat_tab_options" class="tab">
					<h3>Checks</h3>
					<p>SRV.php: 
HTML;
	$srvLoc = YOURLS_ABSPATH.'/user/pages/srv.php';
	if ( !file_exists( $srvLoc ) ) {
 		echo '<font color="red">srv.php is not in the "pages" directory!</font>';
	} else { 
		$thisFile = dirname( __FILE__ )."/plugin.php";
		$thisData = yourls_get_plugin_data( $thisFile );
		$thisV = $thisData['Version'];
		$thatData = yourls_get_plugin_data( $srvLoc );
		$thatV = $thatData['Version'];
		$status = version_compare($thisV, $thatV);
		switch ($status) {
			case 1: echo '<font color="red">ERROR</font>: installed version in "pages" directory is outdated.'; break;
			case 0: echo '<font color="green">Success</font>: installed and up to date.</font>'; break;
			case -1: echo '<font color="blue">Dev</font>: installed and newer than plugin.</font>'; break;
			default: echo '<font color="red">ERROR</font>: No info available, please check your installation';
		}
	}
	echo <<<HTML
				<form method="post" enctype="multipart/form-data">
					<h4>Cache Location</h4>
					<div style="padding-left: 10pt;">
						<p><input type="text" size=25 name="usrv_cache_loc" value="$opt[0]" /></p>
						<p>Please set the absolute path to the desired cache location. This should be outside of the webserver's path.</p>
					</div>

					<h4>Cache Afterlife</h4>
					<div style="padding-left: 10pt;">
						<input type="hidden" name="usrv_afterlife" value="preserve">
	  					<input type="radio" name="usrv_afterlife" value="preserve" $P_chk> Preserve<br>
	  					<input type="radio" name="usrv_afterlife" value="delete" $D_chk> Delete<br>
	  					<p>Decide what happens to the cache when this plugin is deactivated</p>
  					</div>

					<hr>
					<input type="hidden" name="nonce" value="$nonce" />
					<p><input type="submit" value="Submit" /></p>
				</form>
			</div>

			<div id="stat_tab_examples" class="tab">
			
				<h3>File Publishing Via U-SRV link</h3>
			
				<p>You can use a simple GET request to retrieve a code from U-SRV at the following address:</p>
			
				<div style="padding-left: 10pt;">
					<p><code>$base/srv/</code></p>
				</div>
			
				<p>With the following parameters:</p>
			
				<ul>
					<li>id => 'usrv_files'</li>
					<li>key => &#36;key</li>
					<li>fn => &#36;fn</li>
				</ul>
			
				<h4>id</h4>
				<div style="padding-left: 10pt;">
					<p>The id of the plugin calling for a file. Any plugin or script can access files via U-SRV in the default storage location with the value <code>usrv_files</code> so long as the other values are valid as well.</p>
				</div>
			
				<h4>key</h4>
				<div style="padding-left: 10pt;">
					<p>A key is valid for, at most, one minute, and is determined by hashing a unique timestamp concatenated with the ID. The following PHP produces a valid key:</p>
				
					<div style="padding-left: 10pt;">
<pre>
&#36;now &#61; round&#40;time&#40;&#41;/60&#41;&#59;
&#36;key &#61; md5&#40;&#36;now &#46; &#39;usrv_files&#39;&#41;&#59;
</pre>
					</div>				
					<p>Which gives the following live value:</p> 
					<div style="padding-left: 10pt;">
						<code>$key</code>
					</div>
				</div>
			
				<h4>fn</h4>
				<div style="padding-left: 10pt;">
					<p>The file name is plugin specific, and <strong>must</strong> match an existing filename. See specific plugin documentation for details. If using the file upload feature of this plugin, concatenate and hash <code>usrv_files</code> and the original file name and add <code>.zip</code>:</p>
					<div style="padding-left: 10pt;">				
<pre>&#36;fn &#61; md5&#40;&#39;usrv_files&#39;&#46;&#39;example&#46mp3&#39;&#41; &#46; &#39;&#46;zip&#39;&#59;</pre>
					</div>
					<p>Resulting in:
					<div style="padding-left: 10pt;">					
						<code>$fn.zip</code></p>
					</div>
				</div>
			
				<br>

				<p>In the context of a PHP file, the following code will utilize the values from above and fetch a file:</p>
			
				<div style="padding-left: 10pt;">
<pre>&#60;img src&#61;&#34;$base&#47;srv&#47;&#63;id&#61;usrv_files&#38;key&#61;<strong>&#36;key</strong>&#38;fn&#61;<strong>&#36;fn</strong>&#34; &#47;&#62;</pre>
				</div>
			</div>
		</div>
	</div>

HTML;

}
// Maybe insert some JS and CSS files to head
yourls_add_action( 'html_head', 'usrv_head' );
function usrv_head($context) {
	if ( $context[0] == 'plugin_page_usrv' ) {
		$home = YOURLS_SITE;
		echo "<link rel=\"stylesheet\" href=\"".$home."/css/infos.css?v=".YOURLS_VERSION."\" type=\"text/css\" media=\"screen\" />\n";
		echo "<script src=\"".$home."/js/infos.js?v=".YOURLS_VERSION."\" type=\"text/javascript\"></script>\n";
	}
}
// form submission
function usrv_form_0() {
	// check for POST: if one is set, they all are
	if(isset($_POST['usrv_cache_loc'])) {

		yourls_verify_nonce( 'usrv' );

		// cache check, set, and update
		$pcloc = $_POST['usrv_cache_loc'];
		$ocloc = yourls_get_option( 'usrv_cache_loc' );

		if ($pcloc !== $ocloc ) {
			if ($ocloc == null ) {
				usrv_mkdir( $pcloc );
				yourls_update_option( 'usrv_cache_loc', $pcloc);
			} else {
				usrv_mvdir( $ocloc , $pcloc );
				yourls_update_option( 'usrv_cache_loc', $pcloc );
			}
		}

		yourls_update_option('usrv_afterlife', $_POST['usrv_afterlife']);
	}
}
// option handler | is this neccessary?
function usrv_get_opts() {

	// Check DB
	$CACHE_DIR 	= yourls_get_option('usrv_cache_loc');
	$afterlife	= yourls_get_option('usrv_afterlife');
	
	// Set defaults
	if ($CACHE_DIR 	== null) $CACHE_DIR	= dirname(YOURLS_ABSPATH)."/YOURLS_CACHE";
	if ($afterlife  == null) $afterlife	= 'preserve';
	
	// Return values
	return array(
		$CACHE_DIR,	// opt[0]
		$afterlife,	// opt[1]
	);
}
// Upload a file manually
function usrv_fu() {

	// did the user select any file?
	if ($_FILES['usrv_fu']['error'] == UPLOAD_ERR_NO_FILE) {
		return ('You need to select a file to upload.');
	}

	$opt = usrv_get_opts();
	$cache  = $opt[0];

	$fuName = pathinfo($_FILES['usrv_fu']['name'], PATHINFO_BASENAME);
    $fuSave = md5('usrv_files'.$fuName).".zip";
	$fuPath = $cache."/fu/".$fuSave;
	$tmpName = $_FILES['usrv_fu']['tmp_name'];

	$zip = new ZipArchive();
	$fuZip = $zip->open($fuPath, ZIPARCHIVE::CREATE);

	if ($fuZip === true) {
		$zip->addFile($tmpName, $fuName);
		$zip->close();

		global $ydb;
		$table = 'usrv';
		$binds = array('name' => $fuName, 'hashname' => $fuSave);
		$sql = "INSERT INTO `$table`  (name, hashname) VALUES (:name, :hashname)";
		$insert = $ydb->fetchAffected($sql, $binds);

		return '<font color="green">Success!</font> Refer to the examples for further instruction. Original filename: '.$fuName.'<br />
				';
	} else 
		return '<font color="red">Fail!</font> The error was: '.$_FILES['usrv_fu']['error'].'. Check server logs for more detailed info.';
}
/*
 *
 *	Helpers
 *
 *
*/
// Delete UL file
function usrv_delete( $fn ) {

    $opt  	 = usrv_get_opts();
	$filepath = $opt[0].'/fu/'.$fn;

	if ( file_exists( $filepath ))
		unlink( $filepath );

	global $ydb;
	$table = 'usrv';
	$binds = array('hashname' => $fn);
	$sql = "DELETE FROM `$table`  WHERE hashname=:hashname";
	$delete = $ydb->fetchAffected($sql, $binds);

}

// Make dirs if null
function usrv_mkdir( $dir ) {
	$fu = $dir."/fu";
	if ( !file_exists( $fu ) ) {
		if ( !file_exists( $dir ) ) {
			mkdir( $dir );
			chmod( $dir, 0777 );
		}
		mkdir( $fu );
		chmod( $fu, 0777 );
	} else
		return;
}

// Move directory if option is updated
function usrv_mvdir( $old , $new ) {
	if ( !file_exists( $old ) || $old == null ) {
		usrv_mkdir( $new );
	} else { 
		if ( !file_exists( $new ) ) {
			rename( $old , $new );
			chmod( $new, 0777 );
		} else
			return;
	}
}

// Compatability with forward slashes in keyword
yourls_add_action( 'pre_load_template', 'usrv_exclude' );
function usrv_exclude( $request ) {
	$cs = yourls_get_shorturl_charset();
	if (strpos($cs, '/') !== false) {
		$pg = explode('/',$request[0]);
		if ( file_exists( YOURLS_PAGEDIR ."/".$pg[0].".php" ) )
			yourls_page( $pg[0] );
	}
}
/*
 *
 *	Up/Down
 *
 *
*/
// temporary update DB script
yourls_add_action( 'plugins_loaded', 'usrv_update_DB' );
function usrv_update_DB () {
	global $ydb;
	$table = 'usrv';
    	$sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    		WHERE TABLE_NAME = '".$table."'
    		AND ENGINE = 'MyISAM' LIMIT 1";
	$results = $ydb->fetchObjects($sql);
	if($results) {
		foreach( $results as $result ) {
			$fix = "ALTER TABLE `".$table."` ENGINE = INNODB;";
			$ydb->fetchAffected($fix);
		}
	}
}
// Create tables for this plugin when activated
yourls_add_action( 'activated_usrv/plugin.php', 'usrv_activated' );
function usrv_activated() {
	
	// Managae Databases
	global $ydb;

	$init = yourls_get_option('usrv_init');
	if ($init === false) {
		// Create the init value
		yourls_add_option('usrv_init', time());
		// Create the U-SRV table
		$table_usrv  = "CREATE TABLE IF NOT EXISTS usrv (";
		$table_usrv .= "name varchar(200) NOT NULL, ";
		$table_usrv .= "hashname varchar(200), ";
		$table_usrv .= "PRIMARY KEY (name) ";
		$table_usrv .= ") ENGINE=InnoDB DEFAULT CHARSET=latin1;";

		$tables = $ydb->fetchAffected($table_usrv);

		yourls_update_option('usrv_init', time());
		$init = yourls_get_option('usrv_init');
		if ($init === false) {
			die("Unable to properly enable U-SRV due an apparent problem with the database.");
		}
	}
	
	// put SRV in place
	$srvLoc = YOURLS_ABSPATH.'/user/pages/srv.php';
	if ( !file_exists( $srvLoc ) ) {
		copy( 'assets/srv.php', $srvLoc );
	} else { 
		$thisFile = dirname( __FILE__ )."/plugin.php";
		$thisData = yourls_get_plugin_data( $thisFile );
		$thisV = $thisData['Version'];
		$thatData = yourls_get_plugin_data( $srvLoc );
		$thatV = $thatData['Version'];
		$status = version_compare($thisV, $thatV);
		if($status === 1 ) copy( 'assets/srv.php', $srvLoc );
	}

	$opt = usrv_get_opts();
	usrv_mkdir( $opt[0] );
}

// Clean up when plugin is deactivated
yourls_add_action('deactivated_usrv/plugin.php', 'usrv_deactivate');
function usrv_deactivate() {
	$opt = usrv_get_opts();
	$dir = $opt[0];
	
	if($opt[1] == 'delete') {
		// Delete table
		global $ydb;
		$init = yourls_get_option('usrv_init');
		if ($init !== false) {
			yourls_delete_option('usrv_init');
			$table = "usrv";
			$sql = "DROP TABLE IF EXISTS $table";
			$ydb->fetchAffected($sql);
		}
		// purge cache
		if (file_exists($dir)) {
			foreach (new DirectoryIterator($dir) as $fileInfo) {
				if ($fileInfo->isDot()) {
					continue;
				}
				unlink($fileInfo->getRealPath());
			}
		}
	}
	// remove srv.php
	$srvLoc = YOURLS_ABSPATH.'/user/pages/srv.php';
	if ( file_exists( $srvLoc ) ) {
		unlink( $srvLoc );
	}
}

<?php
class SNAPSHOT
{
	var $snapshot_file;
	var $root_path;
	var $target_dir;
	var $excludes;
	var $password;
	var $site_url;
	var $use_cron;
	var $admin_email;
	
	function SNAPSHOT()
	{
		include_once('config.inc.php');
		$pathinfo = explode('/',$_SERVER['SCRIPT_NAME']);
		array_pop($pathinfo);
		$this->site_url = 'http://' . $_SERVER['HTTP_HOST'] . join('/', $pathinfo) . '/';
		session_start();
		header("Content-type: text/html; charset=utf-8");
		if(function_exists('date_default_timezone_set')) date_default_timezone_set($timezone);
		mb_detect_order('SJIS-win,EUCJP-win,UTF-8,JIS,ASCII');
		$this->root_path     = rtrim(str_replace('\\','/', dirname(__FILE__)), '/');
		$this->snapshot_file = $this->set_snapshot_txt($snapshot_txt);
		$this->target_dir    = $this->set_target_dir($target_dir);
		$this->excludes      = $excludes;
		$this->password      = $password;
		$this->use_cron      = $use_cron;
		$this->admin_email   = $admin_email;
	}
	
	function run()
	{
		$this->login();
		if(isset($_REQUEST['action'])) $action = $_REQUEST['action'];
		else                           $action = '';
		
		switch($action)
		{
			case 'check':
				if(isset($_SESSION['status']) && $_SESSION['status']=='online')
				{
					$_SESSION['msg'] = $this->check_snapshot();
				}
				else $_SESSION['msg'] = 'ログインしていません。';
				header('Location: ' . $this->site_url);
				break;
			case 'snapshot':
				if(isset($_SESSION['status']) && $_SESSION['status']=='online')
				{
					$_SESSION['msg'] = $this->make_snapshot('snapshot');
				}
				else $_SESSION['msg'] = 'ログインしていません。';
				header('Location: ' . $this->site_url);
				break;
			case 'download':
				if(isset($_SESSION['status']) && $_SESSION['status']=='online')
				{
					$_SESSION['msg'] = $this->make_snapshot('download');
				}
				else $_SESSION['msg'] = 'ログインしていません。';
				header('Location: ' . $this->site_url);
				break;
			case 'logout':
				unset($_SESSION['status']);
				$_SESSION['msg'] = 'ログアウトしました。';
				header('Location: ' . $this->site_url);
				break;
			case 'cron':
				if($this->use_cron!=='yes')
				{
					$_SESSION['msg'] = 'cron利用は無効に設定されています。';
					header('Location: ' . $this->site_url);
				}
				elseif(empty($this->admin_email))
				{
					$_SESSION['msg'] = '送信先メールアドレスが設定されていません。';
					header('Location: ' . $this->site_url);
				}
				else
				{
					$report = $this->check_snapshot();
					$report = $this->convert_text($report);
					$date = date('Y-m-d H:i:s');
					$ua = (isset($_SERVER['HTTP_USER_AGENT'])) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT']) : '-';
					$rs = $this->send_report($report,$date,$ua);
					echo $rs;
					exit;
				}
				break;
			default:
				if(isset($_SESSION['status']) && $_SESSION['status']=='online')
				{
					if(!is_writable($this->snapshot_file))
					{
						$msg = $this->snapshot_file . ' に書き込み権限を与えてください。';
					}
					elseif(filesize($this->snapshot_file) < 5)
					{
						$msg = '<p class="ng">最初に現時点のスナップショットを記録してください。</p>';
					}
					else
					{
						$msg = '操作を選んでください。環境によっては数十秒かかることがありますが、表示が変わるまでお待ちください。';
						$timestamp = date('Y-m-d H:i:s ',filemtime($this->snapshot_file));
						$msg .= '<br />スナップショットの日付：' . $timestamp;
					}
				}
				else
					$msg = 'ログインしてください。';
				
				if(isset($_SESSION['msg']) && $_SESSION['msg']!=='')
				{
					$msg = $_SESSION['msg'];
					unset($_SESSION['msg']);
				}
		}
		
		$src = $this->get_template('default.tpl');
		$ph  = $this->get_chunk();
		$ph['msg'] = $msg;
		foreach($ph as $k=>$v)
		{
			$k = '[+' . $k . '+]';
			$src = str_replace($k, $v, $src);
		}
		echo $src;
	}
	
	function send_report($report,$date,$ua)
	{
		mb_language('Japanese');
		mb_internal_encoding('UTF-8');
		
		$subject = '改竄チェックレポート';
		$body    = $date . "\n" . $ua . "\n\n" . $report;
		$rs = mb_send_mail($this->admin_email, $subject, $body);
		return ($rs!==false) ? '改竄チェックレポートを送信しました。' : '送信に失敗しました。';
	}
	
	function convert_text($src)
	{
		return strip_tags($src);
	}
	
	function login()
	{
		if(isset($_POST['password']) && $_POST['password'] == $this->password)
		{
			$_SESSION['status'] = 'online';
		}
		elseif(isset($_POST['password']) && $_POST['password'] !== $this->password)
		{
			$_SESSION['msg'] = '<p class="ng">パスワードが違います。</p>';
		}
	}
	
	function get_chunk()
	{
		$ph = array();
		
		if(isset($_SESSION['status']) && $_SESSION['status']=='online')
		{
			if(filesize($this->snapshot_file) < 5)
			{
				$do_check = '';
			}
			else
			{
				$do_check = <<< EOT
<td>
<div class="icon">
<a href="index.php?action=check"><img src="assets/images/check.png" /></a>
<a href="index.php?action=check">改竄チェック</a>
</div>
</td>
EOT;
			}
			$ph['content'] = <<< EOT
<table>
<tr>
$do_check
<td>
<div class="icon">
<a href="index.php?action=snapshot"><img src="assets/images/snapshot.png" /></a>
<a href="index.php?action=snapshot">スナップショット更新</a>
</div>
</td>
<td>
<div class="icon">
<a href="index.php?action=logout"><img src="assets/images/logout.png" /></a>
<a href="index.php?action=logout">ログアウト</a>
</div>
</td>
</tr>
</table>
EOT;
		}
		else
		{
			$ph['content'] = <<< EOT
<table>
<tr>
<td>
<form action="$this->site_url" method="post">
パスワード
<input name="password" type="password" style="width:120px;" />
<input type="submit" value="ログイン" />
</form>
</td>
</tr>
</table>
EOT;
		}
		return $ph;
	}
	
	function set_snapshot_txt($filename)
	{
		$path = $this->root_path . '/' . $filename;
		$this->snapshot_file = $path;
		return $path;
	}
	
	function set_target_dir($target_dir='')
	{
		if($target_dir == '')       $path = dirname(__FILE__);
		elseif($target_dir[0]=='/') $path = getenv('DOCUMENT_ROOT') . $target_dir;
		else $path = realpath($target_dir);
		$path = str_replace('\\','/',$path);
		$rs = file_exists($path);
		if($rs!==false) $this->target_dir = $path;
		else exit;
		return $path;
	}
	
	function check_snapshot()
	{
		$report = '';
		$filenotfound = array();
		$notreadable  = 0;
		$md5failed    = 0;
		$filesfailed  = array();
		
		$root_path = $this->root_path;
		$fh = fopen($this->snapshot_file,'r');
		
		while(!feof($fh))
		{
			$line = fgets($fh,4096);
			if(empty($line)) continue;
			
			$md5sum = '';
			$path   = '';
			
			if(strstr($line,'-:-') !== FALSE) list($md5sum,$path) = explode('-:-',$line,2);
			else                                continue;
			
			$md5sum = trim($md5sum);
			$path   = trim($path);
			
			$real_path = $this->target_dir . $path;
			
			if(!file_exists($real_path)) {$filenotfound[] = mb_convert_encoding($path,'UTF-8',mb_detect_order()); continue;}
			if(is_dir($real_path))       {                         continue;}
			if(!is_readable($real_path)) {$notreadable++;          continue;}
			$md5 = md5_file($real_path);
			if(!$md5)                    {$md5failed++;            continue;}
			if($md5sum != $md5)          {$filesfailed[] = date('Y-m-d H:i:s ', filemtime($real_path)) . mb_convert_encoding($path,'UTF-8',mb_detect_order());}
		}
		fclose($fh);
		
		if(count($filenotfound) || $notreadable || $md5failed || count($filesfailed))
		{
/*
			$tmp2 = array();
			if(count($filenotfound)) $tmp2[] = count($filenotfound) . '件のファイルが見つかりません。';
			if($notreadable)         $tmp2[] = $notreadable . '件のファイルが読み込めません。';
			if($md5failed)           $tmp2[] = $md5failed . '件のファイルのチェックサムが不正です。';
*/
			
			if(count($filenotfound))
			{
				$tmp[] = "<h2>見つからないファイル</h2>\n";
				$tmp[] = '<ul class="ng">' . "\n<li>" . join("</li>\n<li>", $filenotfound) . "</li>\n</ul>";
			}
			if(count($filesfailed))
			{
				$tmp[] = "<h2>改竄の可能性があるファイル</h2>\n";
				$tmp[] = '<ul class="ng">' . "\n<li>" . join("</li>\n<li>", $filesfailed) . "</li>\n</ul>";
			}
			$report = join("\n", $tmp);
		}
		if(empty($report)) $report = '<h2>検査結果</h2>' . "\n" . '<p class="ok">問題ありません。</p>';
		return $report;
	}
	
	function make_snapshot($mode)
	{
		$output = '';
		$msg    = '';
		
		$excludes = $this->excludes;
		
		$tmp = $this->get_recursive_file_list($this->target_dir, $excludes);
		if(count($tmp) <= 1 )
		{
			echo 'ありません';
			return false;
		}
		
		foreach($tmp as $file)
		{
			if(is_dir($file)) continue;
			$md5sum = md5_file($file);
			$file = str_replace($this->target_dir,'',$file);
			$line[] = $md5sum . '-:-' . $file;
		}
		$output = join("\n",$line);
		if($mode=='download')
		{
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private',false);
			header('Content-Description: File Transfer');
			header('Content-Type: text/plain');
			header("Content-Disposition: attachment; filename=\"checksum.dat\"" );
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . strlen($output));
			echo $output;
			exit();
		}
		elseif($mode=='snapshot')
		{
			$rs = file_put_contents($this->snapshot_file, $output);
			if($rs!==false) $msg = '<p class="ok">スナップショットを更新しました。</p>';
			else            $msg = '<p class="ng">スナップショットを更新できませんでした。</p>';
		}
		return $msg;
	}
	
	function get_recursive_file_list($path , $excludes, $maxdepth = -1 , $mode = "FULL" , $d = 0 )
	{
		if(substr($path , strlen($path ) - 1) != '/' ) { $path .= '/'; }
			$dirlist = array ();
		if($mode != "FILES" ) { $dirlist[] = $path; }
		if($handle = opendir($path ))
		{
			while(false !==($file = readdir($handle )))
			{
				if($file == '.' || $file == '..')                 continue;
				if($this->filespec_is_excluded($file, $excludes)) continue;
				
				$file = $path . $file;
				if(! @is_dir($file ))
				{
					if($mode != 'DIRS') $dirlist[] = $file;
				}
				elseif($d >=0 && ($d < $maxdepth || $maxdepth < 0))
				{
					$result  = $this->get_recursive_file_list($file . '/' , $excludes, $maxdepth , $mode , $d + 1 );
					$dirlist = array_merge($dirlist , $result);
				}
			}
			closedir($handle );
		}
		if($d == 0 ) natcasesort($dirlist);
		return($dirlist );
	}
	
	function filespec_is_excluded($file, $excludes)
	{
		// strip the path from the file
		if( empty($excludes)) return false;
		foreach($excludes as $excl)
		{
			if(@preg_match('/' . $excl . '/i', end(explode('/',$file))))
			{
				return true;
			}
		}
		return false;
	}
	
	function get_template($tpl_name)
	{
		$src = file_get_contents($this->root_path . '/assets/snippets/' . $tpl_name);
		return $src;
	}
}

<?php
$version      = '0.1';
$snapshot_txt = 'assets/snapshot.txt';
$target_dir   = '../';
$password     = 'password';
$timezone     = 'Asia/Tokyo';
$use_cron     = 'no';
$admin_email  = '';

$excludes[] = '\.png$';
$excludes[] = '\.jpg$';
$excludes[] = '\.gif$';
$excludes[] = '\.ini$';
$excludes[] = '\.bak$';
$excludes[] = '\.zip$';
$excludes[] = '^pdf_fonts$';
$excludes[] = '^snapshot\.txt';
$excludes[] = '^cache$';
$excludes[] = '^pma$';
$excludes[] = '^\.svn';
$excludes[] = '^CVS$';
$excludes[] = '^\#.*\#$';
$excludes[] = '~$';
$excludes[] = '^uploads$';
$excludes[] = '^tmp$';
$excludes[] = '^captchas$';

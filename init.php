<?php

define( '_PATH', dirname(__FILE__).'/' );
require _PATH.'core/mime.php';
require _PATH.'core/http.php';
require _PATH.'core/output.php';
require _PATH.'core/errors.php';
require _PATH.'core/libs.php';
require _PATH.'core/files.php';
require _PATH.'core/logs.php';
require _PATH.'core/req_url.php';
require _PATH.'core/settings.php';
require _PATH.'core/top.php';
require _PATH.'core/uploads.php';
require _PATH.'core/user.php';
require _PATH.'core/vars.php';
require _PATH.'core/pages.php';
require _PATH.'core/actions.php';

function hmain( $appdir = 'appfiles', $base = '/' ) {
	h2::main( $appdir, $base );
}
?>

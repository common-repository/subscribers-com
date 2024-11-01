<?php
require("config.php");
header("Content-Type: application/javascript");
$site_lang = swpush_sanitize(get_option('subscribers_lang'));
?>
var siteLanguage = "<?php echo $site_lang; ?>";
var version = '1.5';
importScripts("https://<?php echo $subscribers_cdn_host; ?>/assets/subscribers-sw.js");

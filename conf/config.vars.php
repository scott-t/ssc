<?php
$sscConfig_sqlHost = '127.0.0.1';
$sscConfig_sqlUser = '';
$sscConfig_sqlDB = '';
$sscConfig_sqlDBPrefix = 'ssc_';
$sscConfig_sqlPass = '';

$sscConfig_siteName = 'SSC - The Smooth Sailing CMS';
$sscConfig_siteStatus = '1';	/*0 (offline/maintenance) or 1 (up n working) */

$sscConfig_offlineMessage = 'We are currently undergoing some maintenance and were unfortunately required to pull the site offline<br /><br />We apologise for the inconvenience';
$sscConfig_errorMessage = '?!?<br /><br />Something bad happened!'; //will be used?!?

$sscConfig_absPath = ''; //eg /var/www/ssc
$sscConfig_webPath = ''; //eg http://example.com/ssc
$sscConfig_webPathShort = ''; //eg example.com/ssc
$sscConfig_webFolder = ''; //eg /ssc';

$sscConfig_imgWatermark = false;

$sscConfig_theme = '061225';
$sscConfig_themeRel = $sscConfig_webFolder . '/themes/'.$sscConfig_theme;
$sscConfig_themeAbs = $sscConfig_absPath . '/themes/' . $sscConfig_theme;
$sscConfig_themeWeb = $sscConfig_webPath . '/themes/' . $sscConfig_theme;

$sscConfig_metaDesc = 'SSC - The Smooth Sailing CMS!';
$sscConfig_metaKeywords = 'SSC, Smooth Sailing CMS, CMS';

$sscConfig_mail = 'mail'; //??
$sscConfig_mailFrom = ''; //admin email
$sscConfig_sendMail = '/usr/share/sendmail'; //sendmail path
$sscConfig_smtpAuth = '';
$sscConfig_smtpUser = '';
$sscConfig_smtpPass = '';
$sscConfig_smtpHost = '';

$sscConfig_gzipCompress = '0'; //  1 (yes) || 0 (no)


//$ftpHost = $sqlHost;
//$ftpUser = 'anonymous';
//$ftpPass = 'user@example.com';

?>

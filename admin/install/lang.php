<?php

$lang = array(

	'installer'				=> "Invision Community Installer - %s",
    'healthcheck'			=> "System Check",
	'install_step_1'		=> "Setting up database...",
	'install_step_2'		=> "Installing applications and modules...",
	'install_step_3'		=> "Installing settings...",
	'install_step_4'		=> "Creating admin user...",
	'install_step_5'		=> "Installing tasks...",
	'install_step_6'		=> "Installing language pack...",
	'install_step_7'		=> "Installing languages...",
	'install_step_8'		=> "Installing email templates...",
	'install_step_9'		=> "Installing themes...",
	'install_step_10'		=> "Installing javascript...",
	'install_step_11'		=> "Installing search keywords...",
	'install_step_12'		=> "Installing widgets....",
	'generic_error'			=> "An unknown error occurred",
	'installation_error'	=> "Installation Error",
	'installer_locked'		=> "Installer Locked.",
	'session_no_good'		=> "We were unable to start a PHP session. You will need to contact your host to adjust your PHP configuration before you can continue. The error reported was: %s",
	'error'					=> "Error",
	'error_title'			=> "Error",
	'page_doesnt_exist'		=> "That page doesn't exist.",
	'err_installer_locked'	=> "The installer is locked.",
	'err_conf_noexist'		=> "In order to install Invision Community, rename the <em>conf_global.php.dist</em> file to <em>conf_global.php</em>. If you are not sure how to do this, contact your hosting provider.",
	'err_conf_nowrite'		=> "In order to install Invision Community, you must make the <em>conf_global.php</em> writeable. If you are not sure how to do this, contact your hosting provider.",
	'form_required'			=> "This field is required.",
	'form_email_bad'		=> "That is not a valid email address.",
	'form_bad_value'		=> "That value is not allowed.",
	'form_password_confirm'	=> "The passwords do not match.",
	'step'					=> "Step %d",
	'continue'				=> "Continue",
	'install_guide'			=> "Need help? Consult the Installation Guide &rarr;",
	'err_password_length'	=> "Passwords must be at least 3 characters",
	
	/* !Step Titles */
	'install_step_1_app'	=> "Setting up database (%s - done so far %s)...",
	'install_step_2_app'	=> "Installing applications and modules (%s)...",
	'install_step_3_app'	=> "Installing settings (%s)...",
	// step 4 intentionally skipped - not app loop
	'install_step_5_app'	=> "Installing tasks (%s)...",
	// step 6 intentionally skipped - not app loop
	'install_step_7_app'	=> "Installing languages (%s - done so far %s)...",
	'install_step_8_app'	=> "Installing email templates (%s)...",
	'install_step_9_app'	=> "Installing themes (%s - done so far %s)...",
	'install_step_10_app'	=> "Installing JavaScript (%s - done so far %s)...",
	'install_step_11_app'	=> "Installing search keywords (%s)...",
	'install_step_12_app'	=> "Installing widgets (%s)...",

	'requirements_php_version_success'	=> "PHP version %s.",
	'requirements_php_version_fail'		=> "You are running PHP version %s. You need PHP %s or above (%s or above recommended). You should contact your hosting provider or system administrator to ask for an upgrade.",
	'requirements_php_version_fail_no_recommended'	=> "You are running PHP version %s. You need PHP %s or above. You should contact your hosting provider or system administrator to ask for an upgrade.",
	'requirements_php_version_advice'	=> "You are running PHP version %s. While this version is compatible, we recommend version %s or above.",
	'requirements_mysql_version_fail'	=> "You are running MySQL version %s. You need MySQL %s or above (%s or above recommended). You should contact your hosting provider or system administrator to ask for an upgrade.",
	'requirements_curl_success'			=> "cURL extension loaded.",
	'requirements_file_uploads'			=> "File uploads are currently disabled in your PHP configuration, which will prevent many features from working correctly. You should contact your host to have the option `file_uploads` enabled.",
	'requirements_curl_advice'			=> "You do not have the cURL PHP extension loaded or it is running a version less than 7.36. While this is not required, it is recommended.",
	'requirements_curl_fail'			=> "You do not have the cURL PHP extension loaded (or it is running a version less than 7.36). You should contact your hosting provider or system administrator to ask for cURL version 7.36 or greater to be installed.",
	'requirements_mb_success'			=> "Multibyte String extension loaded",
	'requirements_mb_regex'				=> "The Multibyte String extension has been configured with the --disable-mbregex option. You should contact your hosting provider or system administrator to ask for it to be reconfigured without that option.",
	'requirements_mb_overload'			=> "The PHP configuration has mbstring.func_overload set with a value higher than 0. You should contact your hosting provider or system administrator to disable Multibyte function overloading.",
	'requirements_mb_fail'				=> "You do not have the Multibyte String PHP extension loaded which is required. You should contact your hosting provider or system administrator to ask for it to be enabled. It must be configured <em>without</em> the --disable-mbregex option.",
	'requirements_dns_success'			=> "The dns_get_record function is available",
	'requirements_dns_fail'				=> "The dns_get_record function is not available",
	'requirements_extension_success'	=> "%s extension loaded",
	'requirements_extension_fail'		=> "You do not have the %s PHP extension loaded which is required. You should contact your hosting provider or system administrator to ask for it to be enabled.",
	'requirements_extension_advice'		=> "You do not have the %s PHP extension loaded. While this is not required, it is recommended.",
	'requirements_extension_dom'		=> "DOM",
	'requirements_extension_gd'			=> "GD",
	'requirements_extension_mysqli'		=> "MySQLi",
	'requirements_extension_exif'		=> "Exif",
	'requirements_extension_openssl'	=> "OpenSSL",
	'requirements_extension_session'	=> "Session",
	'requirements_extension_simplexml'	=> "SimpleXML",
	'requirements_extension_xml'		=> "XML",
	'requirements_extension_xmlreader'	=> "XMLReader",
	'requirements_extension_xmlwriter'	=> "XMLWriter",
	'requirements_extension_zip'		=> "Zip",
	'requirements_extension_phar'		=> "Phar",
	'requirements_missing_imagettfbbox'	=> "GD was not compiled with freetype support. You should contact your hosting provider or system administrator to request that PHP be recompiled with freetype support with the --with-freetype-dir=DIR option.",
	'requirements_memory_limit_success'	=> "%s memory limit.",
	'requirements_memory_limit_fail'	=> "Your PHP memory limit is set to %s but should be set to 128M or more. You should contact your hosting provider or system administrator to ask for this to be changed.",
	'requirements_suhosin_limit'		=> "PHP setting %s is set to %s. This can cause problems in some areas. We recommended a value of %s or above.",
	'requirements_suhosin_cookie_encrypt' => "PHP setting suhosin.cookie.encrypt is set to 1. This can cause problems in some areas such as the editor's auto save functionality. We recommended a value of 0 to disable it.",
	'requirements_file_system'			=> "File System",
	'requirements_file_writable'		=> "%s is writable",
	'requirements_mysql_timeout'		=> "Your MySQL %s system variable is currently set to an extremely low value (%s). The default MySQL value is 28800. It is recommended to increase this MySQL system variable to at least 20 in order to prevent potential problems with queries timing out.",
    'dir_does_not_exist'				=> "%s does not exist.",
	'dir_is_not_writable'				=> "%s cannot be written to. Please adjust the permissions on it or contact your hosting provider for assistance.",
	'file_storage_test_error_amazon'	=> "There appears to be a problem with your Amazon (%s) file storage settings which can cause problems with uploads.<br> After attempting to upload a file to the directory, the URL to the file is returning a HTTP %s error. Update your settings and then check and see if the problem has been resolved",
	'ftp_err_no_ext'					=> "Your server does not support using FTP storage. Please contact your hosting provider to ask for PHP FTP extension to be enabled.",
	'ftp_err_no_ssl'					=> "Your server does not support using SSL-FTP storage. Please contact your hosting provider to ask for PHP OpenSSL extension to be enabled or use a different protocol.",
	'ftp_err_no_sftp'					=> "Your server does not support using SFTP storage. Please contact your hosting provider to ask for PHP SSH2 extension to be enabled or use a different protocol.",
	'ftp_err-COULD_NOT_CONNECT'			=> "A connection to the host could not be established. Check the host and port provided are correct.",
	'ftp_err-COULD_NOT_LOGIN'			=> "Authentication failed. Check the username and password provided are correct.",
	'ftp_err-COULD_NOT_CHDIR'			=> "Could not move into the directory specified. Check the directory is correct and the user provided has permission to access it.",
	'ftp_err-COULD_NOT_UPLOAD'			=> "Could not upload to the FTP server. Check the user has permission to write files.",
	'ftp_err-COULD_NOT_DELETE'			=> "Could not delete from the FTP server. Check the user has permission to delete files.",
	'file_storage_test_error_ftp'		=> "There appears to be a problem with your FTP (%s) storage settings which can cause problems with uploads.<br> After attempting to upload a file to the directory, the URL to the file is returning a HTTP %s error. Update your settings and then check and see if the problem has been resolved",
	'file_storage_test_ftp_unexpected_response' => "There appears to be a problem with your FTP (%s) storage settings. Please contact technical support.",
	'license'				=> "License",
	'lkey'					=> "License Key",
	'lkey_help'				=> "Get License Key",
	'license_generic_error'	=> "There was an error communicating with the IPS License Server. Please try again later or contact IPS technical support for assistance.",
	'license_server_error'	=> "There was an error communicating with the IPS License Server: %s. Please try again later or contact IPS technical support for assistance.",
	'license_key_not_found'	=> "The license key supplied is not valid. Please check the provided key and try again or contact IPS technical support for more assistance.",
	'license_key_legacy'	=> "The license key supplied is not compatible with this version of Invision Community. Please contact IPS technical support for assistance.",
	'license_key_active'	=> "An installation has already been activated for this license key. Your license key entitles you to one installation only. If you need to change the URL associated with your license, contact IPS technical support.",
	'license_key_test_active' => "A test installation has already been activated for this license key. Your license key entitles you to one test installation only.",
	'eula'					=> "License Agreement",
	'eula_suffix'			=> "I have read and agree to the license agreement",
	'eula_err'				=> "You must agree to the license agreement.",
	
	'applications'			=> "Applications",
	'apps'					=> "Applications to install",
	'default_app'			=> "Default application",
	'default_app_desc'		=> "The application selected here will be used as the default landing page when visitors first come to your site.",
	'default_app_invalid'	=> "Invalid Default",
	'err_min_php'			=> "Your server needs to be running PHP %s or higher to install this application (your server is running %s). Contact your hosting provider to have your server upgraded.",
	'err_min_php_setting'	=> "Your server needs to have the PHP <em>%s</em> setting set to %s or higher to install this application (your server is set to %s). Contact your hosting provider to have this changed.",
	'err_php_extension'		=> "Your server needs to have the PHP <em>%s</em> extension enabled to install this application. Contact your hosting provider to have this enabled.",
	'err_not_writable'		=> "The directory %s needs to be writable. Please change the directory's CHMOD to 0777",
	'err_tmp_dir_create'	=> "Your server does not allow temporary files to be created in the configured temporary directory. To work around this issue please create a file named constants.php at %s with the following code in it:<br><br><span>&lt;?php<br><br>define( 'TEMP_DIRECTORY', dirname( __FILE__ ) . '/uploads' );</span>",
	'err_tmp_dir_adjust'	=> "Your server does not allow temporary files to be created in the configured temporary directory. To work around this issue please edit the file named constants.php at %s and add following code to it:<br><br><span>define( 'TEMP_DIRECTORY', dirname( __FILE__ ) . '/uploads' );</span>",
	'serverdetails'			=> "Server Details",
	'mysql_server'			=> "MySQL Server Details",
	'sql_host'				=> "Host",
	'sql_host_desc'			=> "If you are not sure, leave at the default value.",
	'sql_user'				=> "Username",
	'sql_user_desc'			=> "If you are not sure what your MySQL Server username and password is, contact your hosting provider for assistance.",
	'sql_pass'				=> "Password",
	'sql_database'			=> "Database Name",
	'sql_database_desc'		=> "If the database does not exist we will try to create it. If your MySQL user does not have permission and you're not sure how to create a database, contact your hosting provider for assistance.",
	'sql_tbl_prefix'		=> "Table Prefix",
	'sql_tbl_prefix_desc'	=> "If provided, all database tables created will be prefixed with the value provided. It is recommended this is left blank. Allowed characters: 0-9 a-z - _",
	'sql_port'				=> "Port",
	'sql_port_desc'			=> "If you are not sure, leave at the default value.",
	'sql_socket'			=> "Socket",
	'sql_socket_desc'		=> "If not provided, the server's default setting will be used. If you're not sure, leave at the default value.",
	'http_server'			=> "Web Server Details",
	'base_url'				=> "Site URL",
	'err_db_exists'			=> "There is already an Invision Community installation in that database. Choose a different database or use a table prefix.",
	'err_db_cant_create'	=> "The Database does not exist and cannot be created automatically. Please create the database manually or contact your hosting provider if you are unsure how to do this.",
	'diagnostics_reporting'				=> "Send usage and diagnostics data to IPS?",
	'diagnostics_reporting_desc'		=> "Help Invision Community improve by automatically sending usage and diagnostic information including a list of features in use and basic statistics. The data sent does not contain any private information about your users or your community.",

	'conf_global_error'		 => "",
	
	'admin'					=> "Administrator Account",
	'admin_user'			=> "Display Name",
	'admin_pass1'			=> "Password",
	'admin_pass2'			=> "Confirm Password",
	'admin_email'			=> "Email Address",
	
	'install'				=> "Install",
	'start'					=> "Start",
	'redirecting'			=> "Redirecting...",
	'redirecting_wait'		=> "Please wait while we transfer you...",
	'installing'			=> "Installing...",
	
	'done'					=> "Installation Complete!",

	'default_menu_item_1'			=> "Browse",
	'default_menu_item_2'			=> "Activity",
	
	/* filesizes */
	'filesize_Y'						=> '%s YB',
	'filesize_Z'						=> '%s ZB',
	'filesize_E'						=> '%s EB',
	'filesize_P'						=> '%s PB',
	'filesize_T'						=> '%s TB',
	'filesize_G'						=> '%s GB',
	'filesize_M'						=> '%s MB',
	'filesize_k'						=> '%s kB',
	'filesize_b'						=> '%s B',
	'unlimited'							=> "Unlimited",

	'ready_to_start'					=> "Ready to begin installation",
	'start_installation'				=> "Start Installation",
	'requirements'						=> "%s Requirements",
	'recommendations'					=> "Recommendations",
	'recom_info'						=> "None of these items are required in order to continue with the installation right now. However, they will be required in a future version of Invision Community. You should make a note of them and contact your hosting provider or system administrator after the installation to address them. You can re-run these checks later from the <em>Support</em> section of the Administrator Control Panel.",

'proceed_anyways'			=> "You must correct any issues listed above before you can proceed",
'confglobal_777'			=> "Please CHMOD to conf_global.php 0777.",
'confglobal_write'			=> "Copy and paste the contents of the box above into this new file and CHMOD to 0777.",
'confglobal_make'			=> "Cannot write the '%s/conf_global.php' file",
'confglobal_note'			=> "Please create a file in %s called conf_global.php.",
'banner'					=> "Install Invision Community 5",
'step_s'					=> "Step: %s",
'done_banner'				=> "Your Invision Community 5 is ready",
'done_note'					=> "The installation process is now complete and your Invision Community is now ready!",
'gotosuite'					=> "Go to the suite",
'gotoacp'					=> "Go to the AdminCP",
'docs_link'					=> "Suite Documentation",
'welcome_banner'			=> "Welcome to Invision Community 5",
'admin'						=> "Admin",
'beforestart'				=> "Before we can begin, please rename conf_global.dist.php to conf_global.php in %s and ensure it is writable (usually CHMOD 0777).",
'beforestarto'				=> "This process will install your software for you. Be sure you have your license key and MySQL database details to hand.",
'server_ip'                 => "IP: ",

'install_guide'				=> "Install Guide",

'session_check_fail'		=> "The installer uses PHP sessions to store data, however PHP sessions are currently not working correctly on your server. This is an issue you will need to contact your host about.",

'files-1'	=> "File could not be opened: %s",
'files-2'	=> "File does not exist: %s",
'files-4'	=> "File could not be copied: %s",
'files-5'	=> "File could not be moved: %s",
'files-3'	=> "File could not be saved: %s",
'files-6'	=> "Folder could not be created: %s",
'files-7'	=> "Response returned a 400 response code but no Amazon region was returned: %s",

);
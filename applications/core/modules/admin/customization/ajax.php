<?php
/**
 * @brief		Customization AJAX actions
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 May 2013
 */

namespace IPS\core\modules\admin\customization;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use function defined;

if (!defined("\IPS\SUITE_UNIQUE_KEY")) {
  header(
    ( $_SERVER["SERVER_PROTOCOL"] ?? "HTTP/1.0" ) . " 403 Forbidden"
  );
  exit();
}

/**
 * Members AJAX actions
 */
class ajax extends Controller
{
  /**
   * @brief	Has been CSRF-protected
   */

  public static bool $csrfProtected = true;
}

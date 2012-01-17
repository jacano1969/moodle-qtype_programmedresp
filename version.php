<?PHP

$plugin->version  = 2012011700;
$plugin->requires = 2007101509;
$plugin->release = '1.2 (Build: 2012011700)';

// To avoid 1.9 Notice
if (!defined('MATURITY_STABLE')) {
	define('MATURITY_STABLE',   200)
}

// To avoid the M&P warning (yes, sad but true http://www.youtube.com/watch?v=l8BRbM52gpc)
$plugin->maturity = MATURITY_STABLE;

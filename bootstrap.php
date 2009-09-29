<?php
/**
 * Recess PHP Framework is bootstrapped by passing control to Recess::main().
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 */
$_ENV['dir.bootstrap'] = str_replace('\\','/',realpath(dirname(__FILE__))) . '/';
$_ENV['url.base'] = str_replace('bootstrap.php', '', $_SERVER['PHP_SELF']);
if(strpos($_SERVER['REQUEST_URI'],'/bootstrap.php')===0) exit;

$bootstrapped = true;
require_once('./recess-conf.php');
RecessConf::init();

Library::import('recess.diagnostics.Diagnostics');
set_error_handler(array('Diagnostics','handleError'));
set_exception_handler(array('Diagnostics','handleException'));

Library::import('recess.http.Environment');
Library::import('recess.Recess');

// Entry point to Recess
Recess::main(Environment::getRawRequest(), RecessConf::$policy, RecessConf::getRoutes(), RecessConf::$plugins);

// RecessConf follows...

abstract class RecessConf {

	const DEVELOPMENT = 0;
	const PRODUCTION = 1;
	
	public static $mode = self::PRODUCTION;
	
	public static $recessDir = '';
	
	public static $pluginsDir = '';
	
	public static $appsDir = '';
	
	public static $dataDir = '';
	
	public static $useTurboSpeed = false;
	
	public static $cacheProviders = array();
	
	public static $applications = array();
	
	public static $plugins = array();
	
	public static $defaultDatabase = array();
	
	public static $namedDatabases= array();
	
	public static $settings = array();
	
	public static $defaultTimeZone = 'America/New_York';
	
	public static $policy;
	
	static function init() {
		if(self::$mode === self::PRODUCTION) {
			self::$useTurboSpeed = true;
		} else {
			list($major,$minor,$revision) = explode('.',phpversion());
			if(!((int)$major >= 5 && (int)$minor >= 2)) {
				die('Recess requires PHP version 5.2.4 or greater.');
			}
		}
		
		$_ENV['dir.recess'] = self::$recessDir;
		$_ENV['dir.apps'] = self::$appsDir;
		$_ENV['dir.plugins'] = self::$pluginsDir;
		$_ENV['dir.temp'] = self::$dataDir . 'temp/';
		$_ENV['dir.test'] = self::$recessDir . 'test/';
		
		if(!isset($_ENV['url.assetbase'])) {
			$_ENV['url.assetbase'] = $_ENV['url.base'];
		}
		
		date_default_timezone_set(self::$defaultTimeZone);
		
		require_once($_ENV['dir.recess'] . 'recess/lang/Library.class.php');
		
		Library::addClassPath(self::$recessDir);
		Library::addClassPath(self::$pluginsDir);
		Library::addClassPath(self::$appsDir);
		
		if(self::$useTurboSpeed) {
			Library::$useNamedRuns = true;
			$cacheProvidersReversed = array_reverse(self::$cacheProviders);
			foreach($cacheProvidersReversed as $provider) {
				$provider = $provider . 'CacheProvider';
				Cache::reportsTo(new $provider);
			}
		}
		
		Library::init();
		Library::beginNamedRun('recess');
		
		Library::import('recess.framework.Application');
		foreach(self::$applications as $key => $app) {
			if(!Library::classExists($app)) {
				die('Application "' . $app . '" does not exist. Remove it from recess-conf.php, RecessConf::$applications array.');
			} else {
				$app = Library::getClassName($app);
				self::$applications[$key] = new $app;
			}
		}
		
		Library::import('recess.database.Databases');
		Library::import('recess.database.orm.ModelDataSource');
		
		if(empty(RecessConf::$defaultDatabase)) {
			$message = 'Congratulations, Recess is almost setup!<br />';
			$message .= '<strong>Next Step(s):</strong>';
			$message .= '<ul>';
			$pdoMessages = array();
			
			if(!extension_loaded('PDO')) {
				$pdoMessages[] = 'Install PHP\'s PDO Extension';
				$pdoMessages[] = 'Install Sqlite or MySQL PDO Driver';
			} else {
				$drivers = pdo_drivers();
				$hasMySql = in_array('mysql', $drivers);
				$hasSqlite = in_array('sqlite', $drivers);
				if(!$hasMySql && !$hasSqlite) {
					$pdoMessages[] = 'Install Sqlite and/or MySQL PDO Driver';
				} else {
					$databases = '';
					if($hasSqlite) { $databases = 'Sqlite'; };
					if($hasMySql) { if($databases != '') { $databases .= ', '; } $databases .= 'MySql'; }
					$pdoMessages[] = 'You have drivers for the following databases: ' . $databases;
				}
			}	
			$pdoMessages[] = 'Setup <strong>recess-conf.php</strong> to point to your database.';
			$pdoMessages[] = 'Checkout the <strong>README.textile</strong> file for instructions.';
			$pdoMessages = '<li>' . implode('</li><li>', $pdoMessages) . '</li>';
			$message .= $pdoMessages . '</ul>';
			die($message);
		}
		
		try {
			Databases::setDefaultSource(new ModelDataSource(RecessConf::$defaultDatabase));
		} catch(DataSourceCouldNotConnectException $e) {
			$databaseType = parse_url(RecessConf::$defaultDatabase[0], PHP_URL_SCHEME);
			if(!in_array($databaseType, pdo_drivers())) {
				$message = 'It looks like PHP could not load the driver needed to connect to <strong>' . RecessConf::$defaultDatabase[0] . '</strong><br />';
				$message .= 'Please install the <strong>' . ucfirst($databaseType) . '</strong> PDO driver and enable it in php.ini';
			} else {
				$message = 'Error connecting to data source: ' . $e->getMessage();
			}
			die($message);
		}
		if(!empty(RecessConf::$namedDatabases)) {
			foreach(RecessConf::$namedDatabases as $name => $sourceInfo) {
				Databases::addSource($name, new ModelDataSource($sourceInfo));
			}
		}
		
		// Allow custom policies
		if(null === self::$policy) {
			Library::import('recess.framework.DefaultPolicy');
			self::$policy = new DefaultPolicy();
		}
	}
	
	const ROUTES_CACHE_KEY = 'Recess::Routes';
	
	static function getRoutes() {
		Library::import('recess.framework.routing.RtNode');
		Library::import('recess.framework.routing.Route');

		$router = Cache::get(self::ROUTES_CACHE_KEY);
		if($router === false) {
			$router = new RtNode();
			foreach(self::$applications as $app) {
				try {
					$app->addRoutesToRouter($router);
				} catch (DuplicateRouteException $e) {
					throw new RecessErrorException("Conflicting routes found: " . $e->getMessage(), 0, 0, $e->file, $e->line, array());
				}
			}
			Cache::set(self::ROUTES_CACHE_KEY, $router);	
		}

		return $router;
	}
	
}

?>
<?php if(isset($bootstrapped)) unset($bootstrapped); else exit;
/*
 * Welcome to the Recess PHP Framework. Let's have some fun!
 * 
 * For tutorials, documentation, bug reports, feature suggestions:
 * http://www.recessframework.org/
 * 
 * Join the Recess developer community:
 * 		+ IRC: irc.freenode.net #recess
 * 		+ Mailing List: http://groups.google.com/group/recess-framework
 * 		+ Github: http://github.com/recess/recess/
 * 		+ Forum: http://www.recessframework.org/
 * 		+ Twitter: http://twitter.com/RecessFramework
 * 
 * Enjoy! -Kris (http://twitter.com/KrisJordan)
 */

// RecessConf::DEVELOPMENT or RecessConf::PRODUCTION
RecessConf::$mode = RecessConf::DEVELOPMENT; 

RecessConf::$applications 
	= array(	'recess.apps.tools.RecessToolsApplication',
				'welcome.WelcomeApplication',
'pgtest.PgtestApplication',
'woot.WootApplication'
			);

RecessConf::$defaultTimeZone = 'America/New_York';

RecessConf::$defaultDatabase
	= array(	//'sqlite:' . $_ENV['dir.bootstrap'] . 'data/sqlite/default.db'
				//'mysql:host=localhost;dbname=DBNAME', 'USER', 'PASS'
			'pgsql:host=localhost;dbname=recess','postgres',''
			);

RecessConf::$namedDatabases
	= array( 	// 'nameFoo' => array('sqlite:' . $_ENV['dir.bootstrap'] . 'data/sqlite/default.db')
				// 'nameBar' => array('mysql:host=localhost;dbname=recess', 'username', 'password')
			);

// Paths to the recess, plugins, and apps directories
RecessConf::$recessDir = $_ENV['dir.bootstrap'] . 'recess/';
RecessConf::$pluginsDir = $_ENV['dir.bootstrap'] . 'plugins/';
RecessConf::$appsDir = $_ENV['dir.bootstrap'] . 'apps/';
RecessConf::$dataDir = $_ENV['dir.bootstrap'] . 'data/';


// Cache providers are only enabled during DEPLOYMENT mode.
//  Always use at least the Sqlite cache.
RecessConf::$cacheProviders 
	= array(	
				// 'Apc',
				// 'Memcache',
				'Sqlite',
			);

?>

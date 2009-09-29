<?php
Library::import('recess.framework.controllers.Controller');
Library::import('recess.database.pdo.RecessType');

/**
 * !RespondsWith Layouts, Json
 * !Prefix apps/
 */
class RecessToolsAppsController extends Controller {
	
	public function init() {
		if(RecessConf::$mode == RecessConf::PRODUCTION) {
			throw new RecessResponseException('Recess Tools are available only during development. Please disable the application in a production environment.', ResponseCodes::HTTP_NOT_FOUND, array());
		}
	}
	
	/** !Route GET */
	public function home() {
		$this->apps = RecessConf::$applications;
		if(isset($this->request->get->flash)) {
			$this->flash = $this->request->get['flash'];
		}
	}
	
	/** !Route GET, uninstall/$appClass */
	public function uninstall($appClass) {
		//Library::getFullyQualifiedClassName($appClass);
		$this->app = new $appClass;
	}
	
	/** !Route GET, new */
	public function newApp() {
		$writeable = is_writable($_ENV['dir.apps']);
		
		$this->appsDirWriteable = $writeable;
		if($this->appsDirWriteable) {
			$this->form = $this->getNewAppForm();
			return $this->ok('newAppWizard');
		} else {
			return $this->ok('newAppInstructions');
		}
	}
	
	/** !Route POST, new */
	public function newAppPost() {
		$form = $this->getNewAppForm($this->request->post);
		$form->assertNotEmpty('appName');
		$form->assertNotEmpty('programmaticName');
		if($form->hasErrors()) {
			$this->form = $form;
			return $this->conflict('newAppWizard');
		} else {
			Library::import('recess.lang.Inflector');
			$this->form = $this->getNewAppStep2Form($this->request->post);
			$this->form->routingPrefix->setValue(Inflector::toCamelCaps($this->form->programmaticName->getValue()) . '/');
			return $this->ok('newAppWizardStep2');
		}
	}
	
	/** !Route POST, new/step2 */
	function newAppStep2 () {
		$form = $this->getNewAppStep2Form($this->request->post);
		$this->generateApp();
		return $this->ok('newAppWizardComplete');
	}
	
	private function generateApp() {
		Library::import('recess.lang.Inflector');
		
		$appName = $this->request->post['appName'];
		$programmaticName = Inflector::toProperCaps($this->request->post['programmaticName']);
		$camelProgrammaticName = Inflector::toCamelCaps($programmaticName);
		
		$this->applicationClass = $programmaticName . 'Application';
		$this->applicationFullClass = $camelProgrammaticName . '.' . $this->applicationClass;
		$this->appName = $appName;
		
		$routesPrefix = $this->request->post['routingPrefix'];
		if(substr($routesPrefix,-1) != '/') { $routesPrefix .= '/'; }
		if($routesPrefix{0} === '/') { $routesPrefix = substr($routesPrefix,1); }
		$appDir = $_ENV['dir.apps'] . $camelProgrammaticName;
		
		$this->messages = array();
		$this->messages[] = $this->tryCreatingDirectory($appDir, 'application');
		
		$appReplacements = array('appName' => $appName, 'programmaticName' => $programmaticName, 'camelProgrammaticName' => $camelProgrammaticName, 'routesPrefix' => $routesPrefix);
		$this->messages[] = $this->tryGeneratingFile('Application Class', $this->application->codeTemplatesDir . 'Application.template.php', $appDir . '/' . $programmaticName . 'Application.class.php', $appReplacements);
		
		$this->messages[] = $this->tryCreatingDirectory($appDir . '/models', 'models');
		
		$this->messages[] = $this->tryCreatingDirectory($appDir . '/controllers', 'controllers');
		$this->messages[] = $this->tryGeneratingFile('Home Controller', $this->application->codeTemplatesDir . 'scaffolding/controllers/HomeController.template.php', $appDir . '/controllers/' . $programmaticName . 'HomeController.class.php', $appReplacements);
		
		$this->messages[] = $this->tryCreatingDirectory($appDir . '/views', 'views');
		$this->messages[] = $this->tryCreatingDirectory($appDir . '/views/parts', 'common parts');
		$this->messages[] = $this->tryGeneratingFile('Navigation Part', $this->application->codeTemplatesDir . 'scaffolding/views/parts/navigation.part.template.php', $appDir . '/views/parts/navigation.part.php', $appReplacements);
		$this->messages[] = $this->tryGeneratingFile('Style Part', $this->application->codeTemplatesDir . 'scaffolding/views/parts/style.part.template.php', $appDir . '/views/parts/style.part.php', $appReplacements);
		$this->messages[] = $this->tryCreatingDirectory($appDir . '/views/home', 'home views');
		$this->messages[] = $this->tryCreatingDirectory($appDir . '/views/layouts', 'layouts');
		$this->messages[] = $this->tryGeneratingFile('Home Template', $this->application->codeTemplatesDir . 'scaffolding/views/home/index.template.php', $appDir . '/views/home/index.html.php', $appReplacements);
		$this->messages[] = $this->tryGeneratingFile('Master Layout', $this->application->codeTemplatesDir . 'scaffolding/views/master.layout.template.php', $appDir . '/views/layouts/master.layout.php', $appReplacements);
		
		$scaffolding_dir = $this->application->codeTemplatesDir . 'scaffolding';
		$this->messages[] = $this->tryCopyDirectory($scaffolding_dir . '/public', $appDir . '/public');
	}
	
	private function tryCreatingDirectory($path, $name) {
		$message = '';
		try { 
			$message = 'Creating ' . $name . ' dir "' . $path . '" ... ';
			mkdir($path);
			$message .= 'ok.';
		} catch (Exception $e) {
			if(file_exists($path)) $message .= ' already exists.';
			else $message .= 'failed.';
		}
		return $message;
	}
	
	/**
	 * Copy all files and directories (recursive) to another directory
	 */
	private function tryCopyDirectory($src, $dst) {
		$message = '';
		try { 
			$message = 'Copying ' . $src . ' dir to ' . $dst . ' ... ';
			$dir = opendir($src); 
			mkdir($dst); 
			while(false !== ( $file = readdir($dir))) {
				if (( $file != '.' ) && ( $file != '..' )) {
					if ( is_dir($src . '/' . $file) ) {
						self::tryCopyDirectory($src . '/' . $file,$dst . '/' . $file);
					} else {
						copy($src . '/' . $file,$dst . '/' . $file);
					}
				}
			}
			closedir($dir);
		} catch (Exception $e) {
			if(file_exists($dst)) $message .= ' already exists.';
			else if(!is_dir($src)) $message .= ', source directory does not exist.';
			else $message .= 'failed.';
		}
		return $message;
	}
	
	private function tryGeneratingFile($name, $template, $outputFile, $values, $allowSlashes = false) {
		$templateContents = file_get_contents($template);
		$search = array_keys($values);
		foreach($search as $key => $value) {
			$search[$key] = '/\{\{' . $value . '\}\}/';
		}
		$replace = array_values($values);
		foreach($replace as $key => $value) {
			if(!$allowSlashes) { 
				$value = addSlashes($value);
			}
			$replace[$key] = $value;
		}
		$output = preg_replace($search,$replace,$templateContents);
		
		$message = '';
		try {
			$message = 'Generating ' . $name . ' at "' . $outputFile . '" ... ';
			if(file_exists($outputFile)) {
				throw new Exception('file exists');
			}
			file_put_contents($outputFile, $output);
			$message .= 'ok.';
		} catch(Exception $e) {
			if(file_exists($outputFile)) $message .= ' already exists. Not overwriting.';
			else $message .= 'failed.';
		}
		return $message;
	}
	
	private function getNewAppForm($fillValues = array()) {
		Library::import('recess.framework.forms.Form');
		$form = new Form('');
		$form->method = "POST";
		$form->flash = "";
		$form->action = $this->urlTo('newApp');
		$form->inputs['appName'] = new TextInput('appName', '', '','');
		$form->inputs['programmaticName'] = new TextInput('programmaticName', '', '','');
		$form->fill($fillValues);
		return $form;
	}
	
	private function getNewAppStep2Form($fillValues = array()) {
		Library::import('recess.framework.forms.Form');
		$form = new Form('');
		$form->method = "POST";
		$form->flash = "";
		$form->action = $this->urlTo('newAppStep2');
		$form->inputs['appName'] = new HiddenInput('appName', '');
		$form->inputs['programmaticName'] = new HiddenInput('programmaticName', '');
		$form->inputs['routingPrefix'] = new TextInput('routingPrefix', '','','');
		$form->fill($fillValues);
		return $form;
	}

	/** !Route GET, $appClass */
	public function app($appClass) {
		$application = $this->getApplication($appClass);
		if(!$application instanceof Application) {
			return $application; // App not found
		}
		
		$this->app = $application;
	}
	
	/** !Route GET, app/$app/model/gen */
	public function createModel($app) {
		$this->sources = Databases::getSources();
		$this->tables = Databases::getDefaultSource()->getTables();
		$this->app = $app;
	}
	
	/** !Route POST, app/$app/model/gen */
	public function generateModel($app) {
		$values = $this->request->post;
		
		$modelName = $values['modelName'];
		$tableExists = $values['tableExists'] == 'yes' ? true : false;
		if($tableExists) {
			$dataSource = $values['existingDataSource'];
			$createTable = false;
			$tableName = $values['existingTableName'];
		} else {
			$dataSource = $values['dataSource'];
			$createTable = $values['createTable'] == 'Yes' ? true : false;
			$tableName = $values['tableName'];
		}
		$propertyNames = $values['fields'];
		$primaryKey = $values['primaryKey'];
		$types = $values['types'];
		
		Library::import('recess.database.orm.Model', true); 
		// Forcing b/c ModelDescriptor is in Model
			
		$modelDescriptor = new ModelDescriptor($modelName, false);
		$modelDescriptor->setSource($dataSource);
		$modelDescriptor->setTable($tableName, false);
		
		$pkFound = false;
		foreach($propertyNames as $i => $name) {
			if($name == "") continue;
			$property = new ModelProperty();
			$property->name = trim($name);
			if($name == $primaryKey) {
				$property->isPrimaryKey = true;
			}
			if($types[$i] == 'Integer Autoincrement') {
				if($property->isPrimaryKey) {
					$property->type = RecessType::INTEGER;
					$property->isAutoIncrement = true;
				} else {
					$property->type = RecessType::INTEGER;
				}
			} else {
				$property->type = $types[$i];
			}
			$modelDescriptor->properties[] = $property;
		}
		
		Library::import('recess.database.orm.ModelGen');
		$this->modelCode = ModelGen::toCode($modelDescriptor, $_ENV['dir.temp'] . 'Model.class.php');
		
		$app = new $app;
		if(strpos($app->modelsPrefix,'recess.apps.') !== false) {
			$base = $_ENV['dir.recess'];
		} else {
			$base = $_ENV['dir.apps'];
		}
		$path = $base . str_replace(Library::dotSeparator,Library::pathSeparator,$app->modelsPrefix);
		$path .= $modelName . '.class.php';
		$this->path = $path;
		
		$this->modelWasSaved = false;
		$this->codeGenMessage = '';
		try {
			if(file_exists($this->path)) {
				if(file_get_contents($this->path) == $this->modelCode) {
					$this->modelWasSaved = true;
				} else {
					$this->codeGenMessage = 'File already exists!';
				}
			} else {
				file_put_contents($this->path, $this->modelCode);			
				$this->modelWasSaved = true;
			}
		} catch(Exception $e) {	
			$this->codeGenMessage = 'File could not be saved. Is models directory writeable?';
			$this->modelWasSaved = false;
		}
		
		$this->modelName = $modelName;
		$this->appName = get_class($app);
		$this->tableGenAttempted = $createTable;
		$this->tableWasCreated = false;
		$this->tableSql = '';
		if($createTable) {
			$modelSource = Databases::getSource($dataSource);
			$this->tableSql = $modelSource->createTableSql($modelDescriptor);
			try {
				$modelSource->exec($this->tableSql);
				$this->tableWasCreated = true;
			} catch(Exception $e) {
				$this->tableWasCreated = false;
			}
		}
		
		return $this->ok('createModelComplete');
	}
	
	/** !Route GET, $app/model/$model/scaffolding */
	public function generateScaffolding($app, $model) {
		$app = new $app;
		if(strpos($app->controllersPrefix,'recess.apps.') !== false) {
			$base = $_ENV['dir.recess'];
		} else {
			$base = $_ENV['dir.apps'];
		}
		Library::import('recess.lang.Inflector');
		$controllersDir = $base . str_replace(Library::dotSeparator,Library::pathSeparator,$app->controllersPrefix);
		$viewsDir = $app->viewsDir;
		
		Library::import($app->modelsPrefix . $model);
		$replacements = 
			array(	'modelName' => $model, 
					'modelNameLower' => Inflector::toCamelCaps($model),
					'fullyQualifiedModel' => $app->modelsPrefix . $model, 
					'primaryKey' => Model::primaryKeyName($model),
					'viewsPrefix' => Inflector::toCamelCaps($model),
					'routesPrefix' => Inflector::toCamelCaps($model),);
		
		$this->messages[] = $this->tryGeneratingFile('RESTful ' . $model . ' Controller', $this->application->codeTemplatesDir . 'scaffolding/controllers/ResourceController.template.php', $controllersDir . $model . 'Controller.class.php', $replacements);
		
		$indexFieldTemplate = $this->getTemplate($this->application->codeTemplatesDir . 'scaffolding/views/resource/indexField.template.php');
		$indexDateFieldTemplate = $this->getTemplate($this->application->codeTemplatesDir . 'scaffolding/views/resource/indexDateField.template.php');
		$editFormInputTemplate = $this->getTemplate($this->application->codeTemplatesDir . 'scaffolding/views/resource/editFormInput.template.php');
		
		$indexFields = '';
		$formFields = '';
		foreach(Model::getProperties($model) as $property) {
			if($property->isPrimaryKey) continue;
			$values = array(
							'fieldName' => $property->name,
							'primaryKey' => Model::primaryKeyName($model),
							'modelName' => $model,
							'modelNameLower' => Inflector::toCamelCaps($model),
							'fieldNameEnglish' => Inflector::toEnglish($property->name) );
			switch($property->type) {
				case RecessType::DATE:
				case RecessType::DATETIME:
				case RecessType::TIME:
				case RecessType::TIMESTAMP:
					$template = $indexDateFieldTemplate;
					break;
				default:
					$template = $indexFieldTemplate;
					break;
			}
			$formFields .= $this->fillTemplate($editFormInputTemplate, $values);
			$indexFields .= $this->fillTemplate($template, $values);
		}
		
		$replacements['fields'] = $indexFields;
		$replacements['editFields'] = $formFields;
		
		$viewsDir = $app->viewsDir . $replacements['viewsPrefix'] . '/';
		$this->messages[] = $this->tryCreatingDirectory($viewsDir, $model . ' views dir');
		$this->messages[] = $this->tryGeneratingFile('resource layout', $this->application->codeTemplatesDir . 'scaffolding/views/resource/resource.layout.template.php', $viewsDir . '../layouts/' . $replacements['viewsPrefix'] . '.layout.php', $replacements);
		$this->messages[] = $this->tryGeneratingFile('index view', $this->application->codeTemplatesDir . 'scaffolding/views/resource/index.template.php', $viewsDir . 'index.html.php', $replacements);
		$this->messages[] = $this->tryGeneratingFile('editForm view', $this->application->codeTemplatesDir . 'scaffolding/views/resource/editForm.template.php', $viewsDir . 'editForm.html.php', $replacements, true);
		$this->messages[] = $this->tryGeneratingFile('form part', $this->application->codeTemplatesDir . 'scaffolding/views/resource/form.part.template.php', $viewsDir . 'form.part.php', $replacements, true);
		$this->messages[] = $this->tryGeneratingFile('static details', $this->application->codeTemplatesDir . 'scaffolding/views/resource/details.template.php', $viewsDir . 'details.html.php', $replacements);
		$this->messages[] = $this->tryGeneratingFile('details part', $this->application->codeTemplatesDir . 'scaffolding/views/resource/details.part.template.php', $viewsDir . 'details.part.php', $replacements);
		$this->appName = get_class($app);
		$this->modelName = $model;
	}
	
	protected function getTemplate($templateFile) {
		try {
			return file_get_contents($templateFile);
		} catch (Exception $e) {
			return '';
		}
	}
	
	protected function fillTemplate($template, $values) {
		$search = array_keys($values);
		foreach($search as $key => $value) {
			$search[$key] = '/\{\{' . $value . '\}\}/';
		}
		$replace = array_values($values);
		foreach($replace as $key => $value) {
			$replace[$key] = addslashes($value);
		}
		return preg_replace($search,$replace,$template);
	}
	
	/** !Route GET, model/gen/analyzeModelName/$modelName */
	public function analyzeModelName($modelName) {
		Library::import('recess.lang.Inflector');
		$this->tableName = Inflector::toPlural(Inflector::toUnderscores($modelName));
		$this->isValid = preg_match('/^[a-zA-Z][_a-zA-z0-9]*$/', $modelName) == 1;
	}
	
	/** !Route GET, model/gen/getTables/$sourceName */
	public function getTables($sourceName) {
		$this->tables = Databases::getSource($sourceName)->getTables();
	}
	
	/** !Route GET, model/gen/getTableProps/$sourceName/$tableName */
	public function getTableProps($sourceName, $tableName) {
		$source = Databases::getSource($sourceName);
		if($source == null) {
			return $this->redirect($this->urlTo('home'));
		} else {
			$this->source = $source;
		}
		$this->sourceName = $sourceName;
		$this->table = $tableName;
		$this->columns = $this->source->getTableDescriptor($tableName)->getColumns();
	}
	
	/** !Route GET, $app/controller/gen */
	public function createController($app) {
		
		$application = $this->getApplication($app);
		if(!$application instanceof Application) {
			return $application; // App not found
		}
		
		$this->app = $application;
		
		return $this->ok('genController');
	}
	
	private function getApplication($appClass) {
		foreach(RecessConf::$applications as $app) {
			if(get_class($app) == $appClass) {
				return $app;
			}
		}
		return $this->forwardNotFound($this->urlTo('home'), 'Application ' . $appClass . ' does not exist or is not enabled.');
	}
	
}

?>

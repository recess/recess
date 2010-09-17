<?php
Library::import('recess.database.orm.Model');
Library::import('recess.apps.ide.models.RecessReflectorClassProperties');
Library::import('recess.apps.ide.models.RecessReflectorClassMethods');

/**
 * !BelongsTo package, Class: RecessReflectorPackage, Key: packageId
 * !BelongsTo parent, Class: RecessReflectorClass, Key: parentId
 * !HasMany children, Class: RecessReflectorClass, Key: parentId
 * !Table recess_tools_classes
 */
class RecessReflectorClass extends Model {
	
	/** !Column PrimaryKey, Integer, AutoIncrement */
	public $id;
	
	/** !Column String */
	public $name;
	
	/** 
	 * !Column Integer
	 */
	public $parentId;
	
	/** 
	 * !Column Integer
	 */
	public $packageId;
	
	/** !Column Text */
	public $docComment;
	
	/** !Column Text */
	public $file;
	
	public function fromClass($class, $dir = '') {
		
		$classInfo = new RecessReflectionClass($class);
		
		$this->docComment = $classInfo->getDocComment();
		
		$this->file = $classInfo->getFileName();
		
		$package = Library::getPackage($class);

		if($dir != '') {
			if(strpos($dir, '/' . str_replace('.','/',$package)) !== 0) {
				throw new RecessException('The class: ' . $class . ' has been imported incorectly with ' . $package . '.' . $class . '. The real location is: ' . $dir, get_defined_vars());
			}
		}
			
		if($package != '') {
			$packageReflector = new RecessReflectorPackage();
			
			$packageReflector->name = $package;
			
			if($packageReflector->exists()) {
				$this->setPackage($packageReflector);
			} else {
				$packageReflector->insert();
				$this->setPackage($packageReflector);
			}
		}
		
		$this->save();
		
		$parent = $classInfo->getParentClass();
		
		if($parent != null) {
			$parentReflectorClass = new RecessReflectorClass();
			$parentReflectorClass->name = $parent->name;
			$exists = $parentReflectorClass->find()->first();
			if(!$exists) {
				$parentReflectorClass->fromClass($parent->name);
				$this->setParent($parentReflectorClass);
			} else {
				$this->setParent($exists);
			}
		}
		
	}
	
    function getPackage() {
        $package = Library::getPackage($this->name);	
		if($package != '') {
			$packageReflector = new RecessReflectorPackage();
			$packageReflector->name = $package;
			if($packageReflector->exists()) {
				return $packageReflector;
			} 
		}
    }
}
?>
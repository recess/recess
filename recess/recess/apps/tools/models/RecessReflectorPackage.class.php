<?php
Library::import('recess.database.orm.Model');

/**
 * !HasMany classes, Class: RecessReflectorClass, Key: packageId
 * !HasMany children, Class: RecessReflectorPackage, Key: parentId
 * !BelongsTo parent, Class: RecessReflectorPackage, Key: parentId
 * !Table recess_tools_packages
 */
class RecessReflectorPackage extends Model {
	
	/** !Column PrimaryKey, Integer, AutoIncrement */
	public $id;
	
	/** !Column String */
	public $name;
	
	/** !Column Integer */
	public $parentId;
	
	function childrenAlphabetically() {
		return $this->children()->orderBy('name ASC');
	}
	
	/**
	 * !Wrappable insert
	 */
	function wrappedInsert() {		
		parent::wrappedInsert();
		$dotPosition = strrpos($this->name, Library::dotSeparator);
		
		if($dotPosition !== false) { 
			$parentName = substr($this->name, 0, $dotPosition);
			
			$parent = new RecessReflectorPackage();
			$parent->name = $parentName;
			
			if(!$parent->exists()) {
				$parent->wrappedInsert();
			}
			
			$this->setParent($parent);
		}
	}
	
}
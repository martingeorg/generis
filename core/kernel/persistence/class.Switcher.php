<?php

error_reporting(E_ALL);

/**
 * Generis Object Oriented API - core/kernel/persistence/class.Switcher.php
 *
 * $Id$
 *
 * This file is part of Generis Object Oriented API.
 *
 * Automatically generated on 20.04.2011, 13:05:54 with ArgoUML PHP module
 * (last revised $Date: 2010-01-12 20:14:42 +0100 (Tue, 12 Jan 2010) $)
 *
 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
 * @package core
 * @subpackage kernel_persistence
 */

if (0 > version_compare(PHP_VERSION, '5')) {
	die('This file was generated for PHP 5');
}

/* user defined includes */
// section 127-0-1-1--5a63b0fb:12f72879be9:-8000:0000000000001588-includes begin
// section 127-0-1-1--5a63b0fb:12f72879be9:-8000:0000000000001588-includes end

/* user defined constants */
// section 127-0-1-1--5a63b0fb:12f72879be9:-8000:0000000000001588-constants begin
// section 127-0-1-1--5a63b0fb:12f72879be9:-8000:0000000000001588-constants end

/**
 * Short description of class core_kernel_persistence_Switcher
 *
 * @access public
 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
 * @package core
 * @subpackage kernel_persistence
 */
class core_kernel_persistence_Switcher
{
	// --- ASSOCIATIONS ---


	// --- ATTRIBUTES ---

	/**
	 * The list of classes that should never been compiled
	 * @var array
	 */
	private static $blackList = array();
	private $hardenedClasses = array();
	private $decompiledClasses = array();

	// --- OPERATIONS ---

	public function __construct($blackList = array()){

		if(count(self::$blackList) == 0 || count($blackList) > 0){
			self::$blackList = array_merge(
                                array(
                                        CLASS_GENERIS_USER,
                                        CLASS_ROLE,
                                        CLASS_ROLE_TAOMANAGER,
                                        CLASS_ROLE_BACKOFFICE,
                                        CLASS_ROLE_FRONTOFFICE,
                                        RDF_CLASS,
                                        'http://www.tao.lu/middleware/wfEngine.rdf#ClassProcessVariables'
                                ),
                                $blackList
			);
		}
	}

	public function __destruct(){
		core_kernel_persistence_PersistenceProxy::resetMode();
		core_kernel_persistence_ClassProxy::$ressourcesDelegatedTo = array();
		core_kernel_persistence_ResourceProxy::$ressourcesDelegatedTo = array();
		core_kernel_persistence_PropertyProxy::$ressourcesDelegatedTo = array();
	}

	private function countStatements (){
		$query =  "SELECT count(*) FROM statements";
		$result = core_kernel_classes_DbWrapper::singleton()->execSql($query);
		return $result->fields[0];
	}

	/**
	 * Short description of method unhardifier
	 *
	 * @access public
	 * @author Cédric Alfonsi, <cedric.alfonsi@tudor.lu>
	 * @param  Class class
	 * @param  array options
	 * @return boolean
	 */
	public function unhardify (core_kernel_classes_Class $class, $options = array ()) {
                
		$returnValue = (bool) false;

		if (defined ("DEBUG_PERSISTENCE") && DEBUG_PERSISTENCE){
			var_dump('unhardify '.$class->uriResource);
		}

		// Check if the class has been hardened
		if (!core_kernel_persistence_hardapi_ResourceReferencer::singleton()->isClassReferenced($class)){
			throw new core_kernel_persistence_hardapi_Exception("Try to unhardify the class {$class->uriResource} which has not been hardened");
		}

		//if defined, we took all the properties of the class and it's parents till the topclass
		$classLocations = core_kernel_persistence_hardapi_ResourceReferencer::singleton()->classLocations($class);
		$topClass = null;
		if (count($classLocations)>1){
			throw new core_kernel_persistence_hardapi_Exception("Try to unhardify the class {$class->uriResource} which has multiple locations");
		}
		else {
			$topClass = new core_kernel_classes_Class($classLocations[0]['topClass']);
		}

		//recursive will unhardify the class and it's subclasses in the same table!
		(isset($options['recursive'])) ? $recursive = $options['recursive'] : $recursive = false;

		//removeForeigns will unhardify the class that are range of the properties
		(isset($options['removeForeigns'])) ? $removeForeigns = $options['removeForeigns'] : $removeForeigns = false;

		//removeForeigns will unhardify the class that are range of the properties
		(isset($options['rmSources'])) ? $rmSources = $options['rmSources'] : $rmSources = true;

		// Get class' properties
		$propertySwitcher = new core_kernel_persistence_switcher_PropertySwitcher($class, $topClass);
		$properties = $propertySwitcher->getProperties();
		$columns = $propertySwitcher->getTableColumns(array(), self::$blackList);

		// Get all instances of this class
		$startIndex = 0;
		$instancePackSize = 100;
		$instances = $class->getInstances(false, array('offset'=>$startIndex, 'limit'=> $instancePackSize));
		$count = count($instances);
		do{
			//reset timeout:
			set_time_limit(30);

			foreach ($instances as $instance) {

				// Get table name where the resource is located
				$tableName = core_kernel_persistence_hardapi_ResourceReferencer::singleton()->resourceLocation($instance);
				
				// Get Instance type
				$types = $instance->getType();

				// Create instance in the smooth implementation
				core_kernel_persistence_PersistenceProxy::forceMode(PERSISTENCE_SMOOTH);
				$class->createInstance('', '', $instance->uriResource);
				// set types to the newly created instance
				foreach ($types as $type) {

					if ($type->uriResource != $class->uriResource) {
						$instance->setType($type);
					}
				}
				core_kernel_persistence_PersistenceProxy::resetMode();

				// Export properties of the instance
				foreach ($columns as $column) {

					$property = new core_kernel_classes_Property(core_kernel_persistence_hardapi_Utils::getLongName($column['name']));
					// Multiple property
					if (isset($column['multi']) && $column['multi']) {

						$sqlQuery = 'SELECT
								"'.$tableName.'Props"."property_value",
								"'.$tableName.'Props"."property_foreign_uri", 
								"'.$tableName.'Props"."l_language" 
							FROM "'.$tableName.'Props"
							LEFT JOIN "'.$tableName.'" ON "'.$tableName.'"."id" = "'.$tableName.'Props"."instance_id"
							WHERE "'.$tableName.'"."uri" = ? 
								AND "'.$tableName.'Props"."property_uri" = ?';
						$dbWrapper = core_kernel_classes_DbWrapper::singleton();
						$sqlResult = $dbWrapper->execSql($sqlQuery, array(
							$instance->uriResource,
							$property->uriResource
						));
						if ($dbWrapper->dbConnector->errorNo() !== 0) {
							var_dump($sqlQuery);
							throw new core_kernel_persistence_hardapi_Exception("unable to unhardify : " . $dbWrapper->dbConnector->errorMsg());
						}

						// ENTER IN SMOOTH SQL MODE
						core_kernel_persistence_PersistenceProxy::forceMode(PERSISTENCE_SMOOTH);

						while (!$sqlResult->EOF) {
							$value = null;
							if (!empty($sqlResult->fields['property_value'])) {
								$value = $sqlResult->fields['property_value'];
							} else {
								$value = $sqlResult->fields['property_foreign_uri'];
							}

							$lg = $sqlResult->fields['l_language'];
							if (!empty($lg)) {
								$instance->setPropertyValueByLg($property, $value, $lg);
							} else {
								$instance->setPropertyValue($property, $value);
							}

							$sqlResult->MoveNext();
						}
						/// EXIT HARD SQL MODE
						core_kernel_persistence_PersistenceProxy::resetMode();
					}
					// Single property
					else {
						$value = $instance->getOnePropertyValue($property);
						if ($value != null) {
							core_kernel_persistence_PersistenceProxy::forceMode(PERSISTENCE_SMOOTH);
							$instance->setPropertyValue($property, $value);
							core_kernel_persistence_PersistenceProxy::resetMode();
						}
					}
				}
				// delete instance in the hard implementation
				if($rmSources) $instance->delete();
			}


			if(!$rmSources){
				//increment start index only if not removed
				$startIndex += $instancePackSize;
			}

			//record decompiled instances number
			if(isset($this->decompiledClasses[$class->uriResource])){
				$this->decompiledClasses[$class->uriResource] += $count;
			}else{
				$this->decompiledClasses[$class->uriResource] = $count;
			}

			//update instance array and count value
			$instances = $class->getInstances(false, array('offset'=>$startIndex, 'limit'=> $instancePackSize));
			$count = count($instances);

		}while($count > 0);
		
		// Unreference the class
		$returnValue = core_kernel_persistence_hardapi_ResourceReferencer::singleton()->unReferenceClass($class);
                
		// If removeForeigns, treat the foreign classes
		if($removeForeigns){

			foreach($properties as $property){
				$range = $property->getRange();
				if (core_kernel_persistence_hardapi_ResourceReferencer::singleton()->isClassReferenced($range)){
					$this->unhardify($range, $options);
				}
			}
		}

		// If recursive, treat the subclasses
		if($recursive){

			foreach($class->getSubClasses(true) as $subClass){
				if (core_kernel_persistence_hardapi_ResourceReferencer::singleton()->isClassReferenced($subClass)){
					$returnValue = $this->unhardify($subClass, $options);
				}
			}
		}

		return (bool) $returnValue;
	}


	public static $debug_tables = array();
        protected $foreignPropertiesWaitingList = array();
        
	/**
	 * Short description of method hardifier
	 *
	 * @access public
	 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
	 * @param  Class class
	 * @param  array options
	 * @return boolean
	 */
	public function hardify( core_kernel_classes_Class $class, $options = array())
	{
		$returnValue = (bool) false;

		// section 127-0-1-1--5a63b0fb:12f72879be9:-8000:0000000000001589 begin

		if (defined ("DEBUG_PERSISTENCE") && DEBUG_PERSISTENCE){
			if (in_array($class->uriResource, self::$debug_tables)){
				return;
			}
			var_dump('hardify '.$class->uriResource);
			self::$debug_tables[] = $class->uriResource;
			$countStatement = $this->countStatements();
		}

		if(in_array($class->uriResource, self::$blackList)){
			return $returnValue;
		}

		// ENTER IN SMOOTH SQL MODE
		core_kernel_persistence_PersistenceProxy::forceMode(PERSISTENCE_SMOOTH);

		//recursive will hardify the class and it's subclasses in the same table!
		(isset($options['recursive'])) ? $recursive = $options['recursive'] : $recursive = false;

		//createForeigns will hardify the class that are range of the properties
		(isset($options['createForeigns'])) ? $createForeigns = $options['createForeigns'] : $createForeigns = false;

		//check if we append the data in case the hard table exists or truncate the table and add the new rows
		(isset($options['append'])) ? $append = $options['append'] : $append = false;

		//If the option is true, we check if the table has alreayd been created, if yes, it's finished. If no, we can continue.
		(isset($options['allOrNothing'])) ? $allOrNothing = $options['allOrNothing'] : $allOrNothing = false;

		//if true, the instances of the class will  be removed!
		(isset($options['rmSources'])) ? $rmSources = (bool) $options['rmSources'] : $rmSources = false;

		//if defined, we took all the properties of the class and it's parents till the topclass
		(isset($options['topClass'])) ? $topClass = $options['topClass'] : $topClass = new core_kernel_classes_Class(CLASS_GENERIS_RESOURCE);

		//if defined, compile the additional properties
		(isset($options['additionalProperties'])) ? $additionalProperties = $options['additionalProperties'] : $additionalProperties = array();

		//if defined, reference the additional class to the table
		(isset($options['referencesAllTypes'])) ? $referencesAllTypes = $options['referencesAllTypes'] : $referencesAllTypes = false;

		$tableName = '_'.core_kernel_persistence_hardapi_Utils::getShortName($class);
		$myTableMgr = new core_kernel_persistence_hardapi_TableManager($tableName);

		if($allOrNothing && $myTableMgr->exists()){
			return $returnValue;
		}

		$referencer = core_kernel_persistence_hardapi_ResourceReferencer::singleton();

		//get the table columns from the class properties
		$columns = array();
		$ps = new core_kernel_persistence_switcher_PropertySwitcher($class, $topClass);
		$properties = $ps->getProperties($additionalProperties);
		$columns = $ps->getTableColumns($additionalProperties, self::$blackList);
                
                //init the count value in hardened classes:
                if(isset($this->hardenedClasses[$class->uriResource])){
                        return true;//already being compiled
                }else{
                       $this->hardenedClasses[$class->uriResource] = 0;
                }
                
                // Treat foreign classes of the current class
		foreach($columns as $i => $column){
			//create the foreign tables recursively
			if(isset($column['foreign']) && !empty($column['foreign'])){
				if($createForeigns){
					$foreignClassUri = core_kernel_persistence_hardapi_Utils::getLongName($column['foreign']);
					$foreignTableMgr = new core_kernel_persistence_hardapi_TableManager($column['foreign']);
					if(!$foreignTableMgr->exists()){
                                                if(!in_array($foreignClassUri, array_keys($this->hardenedClasses))){
                                                        $range = new core_kernel_classes_Class($foreignClassUri);
                                                        $this->hardify($range, array_merge($options, array(
                                                                'topClass'      => new core_kernel_classes_Class(CLASS_GENERIS_RESOURCE),
                                                                'recursive' 	=> false,
                                                                'append' 	=> true,
                                                                'allOrNothing'	=> true
                                                        )));
                                                }else{
                                                        //set in waiting list, the property to be set as foreign key on a table to be compiled
                                                        //array(range => array(currentClass => property))
                                                        //array(foreignTable => array(currentTable => column))
                                                        if(!isset($this->foreignPropertiesWaitingList[$column['foreign']])){
                                                                $this->foreignPropertiesWaitingList[$column['foreign']] = array($tableName => $column['name']);
                                                        }else{
                                                                $this->foreignPropertiesWaitingList[$column['foreign']][$tableName] = $column['name'];
                                                        }
                                                        unset($columns[$i]['foreign']);//do not create the foreign key for now
                                                }
					}
				}else{
					unset($columns[$i]['foreign']);//do not create foreign key at all
				}
			}
		}
                
                // important! need to force the mode again to "smooth" after foreign classes (ranges) compilation
		core_kernel_persistence_PersistenceProxy::forceMode(PERSISTENCE_SMOOTH);
                
		if(!$append || ($append && !$myTableMgr->exists())){
                        
			//create the table
			if($myTableMgr->exists()){
				$myTableMgr->remove();
			}
                        $myTableMgr->create($columns);
                        
			//reference the class
			$referencer->referenceClass($class, null, $topClass);

			if($referencesAllTypes){
				$referencer->referenceInstanceTypes($class);
			}
                        
                        //currently disable the foreign key constraint management:
                        /*
                        //when the table is created, check if it is a missing range (i.e. column) of a foreign key:
                        if(isset($this->foreignPropertiesWaitingList[$tableName])){
                                $dbWrapper = core_kernel_classes_DbWrapper::singleton();
                                foreach($this->foreignPropertiesWaitingList[$tableName] as $sourceTable => $sourceColumn){
                                        $alterForeignKeyQuery = "ALTER TABLE {$sourceTable} ADD fk_{$sourceColumn} FOREIGN KEY ({$sourceColumn}) REFERENCES {$tableName};";
                                        $dbWrapper->execSql($alterForeignKeyQuery);
                                }
                        }
                         */
		}

		//insert the resources
		$startIndex = 0;
		$instancePackSize = 100;
		$instances = $class->getInstances(false, array('offset'=>$startIndex, 'limit'=> $instancePackSize));
		$count = count($instances);
		do{
			//reset timeout:
			set_time_limit('30');

			$rows = array();
                        
			foreach($instances as $index =>  $resource){
				if($referencer->isResourceReferenced($resource)){
					unset($instances[$index]);
					continue;
				}
				$row = array('uri' => $resource->uriResource);
				foreach($properties as $property){
					$propValue = $resource->getOnePropertyValue($property);
                                        $propValue = ($propValue instanceof core_kernel_classes_Resource)?$propValue->uriResource:$propValue;
					$row[core_kernel_persistence_hardapi_Utils::getShortName($property)] = $propValue;
				}

				$rows[] = $row;
			}
                        
			$rowMgr = new core_kernel_persistence_hardapi_RowManager($tableName, $columns);
			$rowMgr->insertRows($rows);
			foreach($instances as $resource){
				$referencer->referenceResource($resource, $tableName, null, true);

				if($rmSources){
					//remove exported resources in smooth sql, if required:
					$resource->delete();
				}
			}

			if(!$rmSources){
				//increment start index only if not removed
				$startIndex += $instancePackSize;
			}

			//record hardened instances number
			if(isset($this->hardenedClasses[$class->uriResource])){
				$this->hardenedClasses[$class->uriResource] += $count;
			}else{
				$this->hardenedClasses[$class->uriResource] = $count;
			}

			//update instance array and count value
			$instances = $class->getInstances(false, array('offset'=>$startIndex, 'limit'=> $instancePackSize));
			$count = count($instances);

		} while($count> 0);
                
                $returnValue = true;
                
		// Treat subclasses of the current class
		if($recursive){
			foreach($class->getSubClasses(true) as $subClass){
				$returnValue = $this->hardify($subClass, array_merge($options, array(
					'recursive' 	=> false,
					'append' 	=> true,
					'allOrNothing'	=> true
				)));
			}
		}

		//reset cache:
		$referencer->resetCache($additionalProperties);
		// EXIT SMOOTH SQL MODE
		core_kernel_persistence_PersistenceProxy::resetMode();

		if (defined ("DEBUG_PERSISTENCE") && DEBUG_PERSISTENCE){
			$this->unhardify($class, array_merge($options, array(
				'recursive' 		=> false,
				'removeForeigns' 	=> false
			)));
			var_dump('unhardened result statements '.$this->countStatements(). ' / '.$countStatement);
		}

		// section 127-0-1-1--5a63b0fb:12f72879be9:-8000:0000000000001589 end

		return (bool) $returnValue;
	}
        
	public function getHardenedClasses(){
		return $this->hardenedClasses;
	}

	public function getDecompiledClasses(){
		return $this->decompiledClasses;
	}

	public static function createIndex($indexProperties = array()){

		$referencer = core_kernel_persistence_hardapi_ResourceReferencer::singleton();
		$dbWrapper = core_kernel_classes_DbWrapper::singleton();

		foreach($indexProperties as $indexProperty){
			$property = new core_kernel_classes_Property($indexProperty);
			$propertyAlias = core_kernel_persistence_hardapi_Utils::getShortName($property);
			foreach($referencer->propertyLocation($property) as $table){
				if(!preg_match("/Props$/", $table) && preg_match("/^_[0-9]{2,}/", $table)){
					$dbWrapper->execSql('ALTER TABLE "'.$table.'" ADD INDEX "idx_'.$propertyAlias.'" ("'.$propertyAlias.'"( 255 ))');
				}
			}
		}

		//Need to OPTIMIZE / FLUSH the tables in order to rebuild the indexes
		$tables = $dbWrapper->dbConnector->MetaTables('TABLES');

		$size = count($tables);
		$i = 0;
		while($i < $size){
	   
			$percent = round(($i / $size) * 100);
			if($percent < 10){
				$percent = '0'.$percent;
			}
	   
			$dbWrapper->execSql('OPTIMIZE TABLE "'.$tables[$i].'"');
			$dbWrapper->execSql('FLUSH TABLE "'.$tables[$i].'"');
	   
			$i++;
		}

		return true;

	}
} /* end of class core_kernel_persistence_Switcher */

?>
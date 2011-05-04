<?php

error_reporting(E_ALL);

/**
 * Generis Object Oriented API -
 *
 * $Id$
 *
 * This file is part of Generis Object Oriented API.
 *
 * Automatically generated on 03.05.2011, 15:34:23 with ArgoUML PHP module 
 * (last revised $Date: 2010-01-12 20:14:42 +0100 (Tue, 12 Jan 2010) $)
 *
 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
 * @package core
 * @subpackage kernel_persistence_hardapi
 */

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

/* user defined includes */
// section 127-0-1-1-8da8919:12f7878e80a:-8000:000000000000161B-includes begin
// section 127-0-1-1-8da8919:12f7878e80a:-8000:000000000000161B-includes end

/* user defined constants */
// section 127-0-1-1-8da8919:12f7878e80a:-8000:000000000000161B-constants begin
// section 127-0-1-1-8da8919:12f7878e80a:-8000:000000000000161B-constants end

/**
 * Short description of class core_kernel_persistence_hardapi_RowManager
 *
 * @access public
 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
 * @package core
 * @subpackage kernel_persistence_hardapi
 */
class core_kernel_persistence_hardapi_RowManager
{
    // --- ASSOCIATIONS ---


    // --- ATTRIBUTES ---

    /**
     * Short description of attribute table
     *
     * @access protected
     * @var string
     */
    protected $table = '';

    /**
     * Short description of attribute columns
     *
     * @access protected
     * @var array
     */
    protected $columns = array();

    // --- OPERATIONS ---

    /**
     * Short description of method __construct
     *
     * @access public
     * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
     * @param  string table
     * @param  array columns
     * @return mixed
     */
    public function __construct($table, $columns)
    {
        // section 127-0-1-1-8da8919:12f7878e80a:-8000:0000000000001622 begin
        
    	$this->table = $table;
		$this->columns = $columns;
    	
        // section 127-0-1-1-8da8919:12f7878e80a:-8000:0000000000001622 end
    }

    /**
     * Short description of method insertRows
     *
     * @access public
     * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
     * @param  array rows
     * @return boolean
     */
    public function insertRows($rows)
    {
        $returnValue = (bool) false;

        // section 127-0-1-1-8da8919:12f7878e80a:-8000:0000000000001626 begin

        // The class has  multiple properties 
        $multipleColumns = array();
        
        $size = count($rows);
		if($size > 0){
			$dbWrapper = core_kernel_classes_DbWrapper::singleton();
						
			
			//get the ids of foreigns value 
			$foreignsIds  = $this->getForeignIds($rows);
			
			//building the insert query
			
			//set the column names
			$query = "INSERT INTO {$this->table} (uri";
			foreach($this->columns as $column){
				if(isset($column['multi']) && $column['multi'] === true){
					continue;
				}
				$query .= ", {$column['name']}";
			}
			$query .= ') VALUES ';
			
			$uris = array();
			
			//set the values
			foreach($rows as $i => $row){
				$uris[] = $row['uri'];
				 
				$query.= "('{$row['uri']}'";
				foreach($this->columns as $column){
					
					if(array_key_exists($column['name'], $row)){
						//the property is multiple, postone its treatment
						if(isset($column['multi']) && $column['multi'] === true){
							continue;
						}
						
						else if(isset($column['foreign'])){
							
							//set the id of the foreign resource
							$foreignResource = $row[$column['name']];
							if(isset($foreignsIds[$column['foreign']][$foreignResource->uriResource])){
								$query.= ", {$foreignsIds[$column['foreign']][$foreignResource->uriResource]}";
							}
							else{
								$query.= ", NULL";
							}
						}
						else{
							
							//the value is a literal
							$value = $row[$column['name']];
							if (!common_Utils::isUri($value)){
								$query.= ", '{$value}'";
							}
							//the value is a resource
							else {
								$query.= ", '{$value->uriResource}'";
							}
						}
					}
				}
				$query.= ")";
				if($i < $size-1){
					$query .= ',';
				}
			}

			// Insert rows of the main table
			$dbWrapper->execSql($query);
			if($dbWrapper->dbConnector->errorNo() !== 0){
				throw new core_kernel_persistence_hardapi_Exception("Unable to insert the rows : " .$dbWrapper->dbConnector->errorMsg());
			}
			
			//get the ids of the inserted rows
			$uriList = '';
			foreach($uris as $uri){
				$uriList .= "'$uri',";
			}
			$uriList = substr($uriList, 0, strlen($uriList) -1);
			
			$instanceIds = array();
			
			$query 	= "SELECT id, uri FROM {$this->table} WHERE uri IN ({$uriList})";
			$result = $dbWrapper->execSql($query);
			while (!$result->EOF){
				$instanceIds[$result->fields['uri']] = $result->fields['id'];
				$result->moveNext();
			}
			
			// If the class has multiple properties
			// Insert rows in its associate table <tableName>Props
			foreach ($rows as $row){
				$queryRows = "";
				
				foreach($this->columns as $column){
					
					if (!isset($column['multi']) || $column['multi'] === false){
						continue;
					}
					
					/**
					 * 
					 * @todo
					 * multiple : foreign lgDependent ?
					 * 
					 */
					
					
						$multiplePropertyUri = core_kernel_persistence_hardapi_Utils::getLongName($column['name']);
						$multiQuery = "SELECT object, l_language FROM statements WHERE subject = ? AND predicate = ?";
						$multiResult = $dbWrapper->execSql($multiQuery, array($row['uri'], $multiplePropertyUri));
						
						if($dbWrapper->dbConnector->errorNo() !== 0){
							throw new core_kernel_persistence_hardapi_Exception("Unable to select foreign data for the property {$multiplePropertyUri} : " .$dbWrapper->dbConnector->errorMsg());
						}
	
						while (!$multiResult->EOF){
							if(!(empty($queryRows))){
								$queryRows .= ',';
							}
							if (isset($column['foreign'])){
								if(isset($foreignsIds[$column['foreign']][$multiResult->fields['object']])){
									$foreignsId = $foreignsIds[$column['foreign']][$multiResult->fields['object']];
									if(is_array($foreignsId)){
										foreach($foreignsId as $index => $id){
											if($index > 0){
												$queryRows .= ',';
											}
											$queryRows .= "({$instanceIds[$row['uri']]}, \"{$multiplePropertyUri}\", NULL, {$id}, \"{$multiResult->fields['l_language']}\")";
										}
									}
									else{
										$queryRows .= "({$instanceIds[$row['uri']]}, \"{$multiplePropertyUri}\", NULL, {$foreignsId}, \"{$multiResult->fields['l_language']}\")";
									}
								}
								else{
									$queryRows .= "({$instanceIds[$row['uri']]}, \"{$multiplePropertyUri}\", NULL, NULL, \"{$multiResult->fields['l_language']}\")";
								}
							}
							else{
								$queryRows .= "({$instanceIds[$row['uri']]}, \"{$multiplePropertyUri}\", \"{$multiResult->fields['object']}\", NULL, \"{$multiResult->fields['l_language']}\")";
							}
							$multiResult->moveNext();
						}
					
				}
				
				if (!empty($queryRows)){
					
					$queryMultiple = "INSERT INTO {$this->table}Props
						(instance_id, property_uri, property_value, property_foreign_id, l_language) VALUES " . $queryRows;
					
					$multiplePropertiesResult = $dbWrapper->execSql($queryMultiple);
					if($dbWrapper->dbConnector->errorNo() !== 0){
						throw new core_kernel_persistence_hardapi_Exception("Unable to insert multiple properties for table {$this->table}Props : " .$dbWrapper->dbConnector->errorMsg(). "<br>$queryMultiple");
					}
				}
			}
		}
        
        // section 127-0-1-1-8da8919:12f7878e80a:-8000:0000000000001626 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method getForeignIds
     *
     * @access protected
     * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
     * @param  array rows
     * @return array
     */
    protected function getForeignIds($rows)
    {
        $returnValue = array();

        // section 127-0-1-1--2b59d385:12fb60e84b9:-8000:0000000000001686 begin
        
        $dbWrapper = core_kernel_classes_DbWrapper::singleton();
        
        $foreigns = array();
        foreach($this->columns as $column){
			if(isset($column['foreign'])){
				
				$uriList = '';
				foreach($rows as  $row){
					$foreignResource = $row[$column['name']];
					$uriList .= "'{$foreignResource->uriResource}',";
				}
				$uriList = substr($uriList, 0, strlen($uriList) -1);
				
				$query = "SELECT id, uri FROM {$column['foreign']} WHERE uri IN ({$uriList})";
				$result = $dbWrapper->execSql($query);
				if($dbWrapper->dbConnector->errorNo() !== 0){
					throw new core_kernel_persistence_hardapi_Exception("Unable to select foreign data : " .$dbWrapper->dbConnector->errorMsg());
				}
				$foreign = array();
				while(!$result->EOF){
					if(!isset($column['multi']) || $column['multi'] === false){
						$foreign[$result->fields['uri']]  = $result->fields['id'];
					}
					else{
						$key = $result->fields['uri'];
						if(array_key_exists($key, $foreign)){
							$foreign[$result->fields['uri']][] = $result->fields['id'];
						}
						else{
							$foreign[$result->fields['uri']]  = array($result->fields['id']);
						}
					}
					$result->moveNext();
				}
				$foreigns[$column['foreign']] = $foreign;
			}
		}
		
		$returnValue = $foreigns;
        
        // section 127-0-1-1--2b59d385:12fb60e84b9:-8000:0000000000001686 end

        return (array) $returnValue;
    }

} /* end of class core_kernel_persistence_hardapi_RowManager */

?>
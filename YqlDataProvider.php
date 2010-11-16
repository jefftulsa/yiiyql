<?php
/**
 * This file contains a class to provide a data provider for data returned by a call to the YQL Web service.
 *
 * @author Jefftulsa <jefftulsa@gmail.com>
 * @link http://www.yiihaw.com/
 * @license MIT
 */

/**
 * YqlDataProvider encapsulates a request to YQL.
 */
class YqlDataProvider extends CDataProvider
{
	
	private $_request;
	private $_response;
	private $_yqlQuery;
	
	/**
	 * Constructor.
	 * @param string the expected result item name. This is the name 
	 * of the item returned in the query and is specific to the webservice being targeted
	 * @param array $config configuration (name=>value) to be applied as the initial property values of this class.
	 */
	public function __construct($itemName,$config=array())
	{
		$this->_request = new YqlRequest();
		
		if(is_string($itemName))
			$this->_request->itemName = $itemName;
		
		$this->setId('YqlRequest-'.$this->_request->itemName);
		
		foreach($config as $key=>$value)
			$this->$key=$value;
		
	}
	
	/**
	 * Sets the yql query criteria.
	 * @param array the query criteria. An array
	 * representing the query criteria.
	 */
	public function setCriteria($value)
	{
		//if the full query was already set, ignore the criteria 
		if(isset($this->_request->query))
			return;
			
		foreach($value as $n=>$v)
		{
			$this->_request->$n = $v;
		}
	}
	
	/**
	 * Sets the full yql query.
	 * @param array the query criteria. An array
	 * representing the query criteria.
	 */
	public function setQuery($value)
	{
		$this->_request->query = $value;
	}
	
	
	/**
	 * Fetches the data from the web service
	 * @return array list of data items
	 */
	protected function fetchData()
	{
		$this->_response = $this->_request->execute();
		if($this->_response->hasErrors())
		{
			return array((array)$this->_response->error);
		}
		else
		{
			$items = $this->_response->results;
			$arrayItems = array();
			foreach($items as $itemObject)
			{
				$arrayItems[] = (array)$itemObject;
			}
			return $arrayItems;
		}
	}
	
	/**
	 * Fetches the data item keys from the persistent data storage.
	 * @return array list of data item keys.
	 */
	
	protected function fetchKeys()
	{
		$keys=array();
		foreach($this->getData() as $i=>$data)
		{
			$key = array_keys($data);
			$keys[$i]=is_array($key) ? implode(',',$key) : $key;
		}
		return $keys;
	}
	
	
	/**
	 * Calculates the total number of data items.
	 * @return integer the total number of data items.
	 */
	protected function calculateTotalItemCount()
	{
		return $this->_response->count;
	}
	
}


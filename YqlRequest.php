<?php
/**
 * This file contains a class to encapsulate making a request to YQL Web Service.
 *
 * @author Jefftulsa <jefftulsa@gmail.com>
 * @link http://www.yiihaw.com/
 * @license MIT
 */

/**
 * YqlRequest encapsulates a request to YQL.
 */
class YqlRequest extends CApplicationComponent
{
	
	private $_yqlQuery; 
	private $_select;
	private $_from;
	private $_where;
	private $_curl;
	private $_rawResponse;
	private $_response;
	private $_itemName;
	private $_error = false;
	private $_version = 'v1/public/yql';
	private $_endpoint = 'http://query.yahooapis.com/'; 
	private $_format = 'json';
	
	/**
	 * @var integer number of seconds that the generated YQL result can remain valid in cache. Defaults to 0, meaning no caching.
	 */
	public $responseCacheDuration=0;
	
	/**
	 * @var string the ID of the cache application component that is used to cache the YQL response.
	 * Defaults to 'cache' which refers to the primary cache application component.
	 * Set this property to false if you want to disable caching the YQL response altogether.
	 */
	public $cacheID='cache';
	
	/*
	 * Determines whether or not diagnostic information is returned with the response.
	 */
	public $diagnostics = true;
	
	/*
	 * Enables network-level logging of each network call within a YQL statement or API query.
	 * For more information see {@link http://developer.yahoo.com/yql/guide/yql-network-logging.html Logging Network Calls in Open Data Tables}
	 */
	public $debug = false;
	
	/*
	 * Set this to allow use of multiple Open Data Tables through a YQL environment file.
	 */
	public $env = 'store%3A%2F%2Fdatatables.org%2Falltableswithkeys';
		
	/**
	 * Constructor.
	 * @param string $yql a valid YQL query statement
	 * @param string $itemName the name of the main item returned in the query results. This will depend on the query being made
	 */
	public function __construct($yql='', $itemName='')
	{
		if($yql != '')
			$this->_yqlQuery = $yql;
		if($itemName != '')
			$this->_itemName = $itemName;
	}
	
	/**
	 * @var string a comma separated list of columns being selected. This refers to the SELECT clause in an YQL
	 * statement. The property should be a string (column names separated by commas)
	 * Defaults to '*', meaning all columns.
	 */
	public function setSelect($fieldList='*')
	{
	   $this->_select = "select " . $fieldList;	
	}
	
	/**
	 * @var string query table. This refers to the FROM clause in an YQL statement and is where you set the table.
	 */
	public function setFrom($table)
	{
		$this->_from = "from " . $table;
	}
	
	/**
	 * @var string query condition. This refers to the WHERE clause in an YQL statement.
	 */
	public function setWhere($condition)
	{
		$this->_where = "where " . $condition;
	}
	
	/**
	 * @var string the query item name. This refers to the name of the item that is returned in the query. This 
	 * depends on the table (service) being queried. For example, most queries agains RSS feeds this is <code>item</code>.
	 * For a flickr query, this is <code>photo</code>, etc. It is the repeated field (like a row in an SQL table) 
	 * in the returned response 
	 */
	public function setItemName($itemName)
	{
		$this->_itemName = $itemName;
	}
	
	/**
	 * @var string the desired format for the returned query payload. YQL offers both xml and json, but this only
	 * supports json
	 */
	public function setFormat($value)
	{
		//only json is currently supported
		$this->_format = 'json';
	}
	
	public function setQuery($yql)
	{
		$this->_yqlQuery = $yql;
	}
	
	
	public function getItemName()
	{
		return $this->_itemName;
	}
	
	
	public function buildCommand()
	{
		if('' == $this->_yqlQuery)
		{
			$this->_yqlQuery = $this->_select . " " . $this->_from;
			if($this->_where != '')
				$this->_yqlQuery .= " " . $this->_where;
		}
		return urlencode($this->_yqlQuery);
	}
	

   
   // set up the cURL 
   protected function setupCurl()
   {
       if(!empty($this->_curl)) return;
       $this->_curl = curl_init();  
	   curl_setopt($this->_curl, CURLOPT_URL, $this->buildUrl());  
	   curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);  
	   curl_setopt($this->_curl, CURLOPT_SSL_VERIFYPEER, false);  
	   curl_setopt($this->_curl, CURLOPT_SSL_VERIFYHOST, false);  
	
   } 

   public function execute($useCUrl=true)
   {
        //get from cache if we can
		if($this->responseCacheDuration>0 && $this->cacheID!==false && ($cache=Yii::app()->getComponent($this->cacheID))!==null)
		{
			Yii::trace('Attepmting to get previous YQL response from cache.');
			$key='Yiiyql.YqlRequest.'.$this->buildCommand();
			if(($cachedResponse=$cache->get($key))!==false)
			{
				$this->_rawResponse = $cachedResponse;
				return $this->createResponse();
			}
			
		}
		
		$statusCode = '';
		//use curl if loaded, otherwise use file_get_contents
		if (extension_loaded('curl') && true == $useCUrl)
		{
        	Yii::trace('Using cUrl to execute the YQL request.');
			$this->setupCurl();
        	$this->_rawResponse = curl_exec($this->_curl);

            // Check if any curl error occured
			if(curl_errno($this->_curl))
			{
 				$this->_error = curl_error($this->_curl); 
				Yii::trace('A cUrl execution error occured: ' . $this->_error);
			}
			else
			{
				//check HTTP header status code
				$statusCode = curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
			}
			curl_close($this->_curl);
 		}
		else
		{
			Yii::trace('Using file_get_contents to execute the YQL request.');
			$this->_rawResponse = @file_get_contents($this->buildUrl()); 
			list($version, $statusCode, $msg) = explode(' ', $http_response_header[0], 3);
		}
		
		$this->handleReturnStatusCode($statusCode);
		
		if(isset($key))
		{
			//unless we have an error, store the response in cache
			if(!isset($this->_error))
			{
				$cache->set($key,$this->_rawResponse,$this->responseCacheDuration);
			}
			
		}
		
		return $this->createResponse();
		
   }

   public function getRawResponse()
   {
        return $this->_rawResponse;
   }

   public function getFullResponse()
   {
        return $this->_response;	
   }

  	public function buildUrl()
	{
		$fullYQLUrl = $this->_endpoint . $this->_version . '?format=' . $this->_format . '&q=' . $this->buildCommand();
		$fullYQLUrl .= '&env=' . $this->env;
		if($this->diagnostics)
		{
			$fullYQLUrl .= '&diagnostics=true';
		}
		Yii::trace('Built the following request URL: ' . $fullYQLUrl);
		return $fullYQLUrl;
	}
	
	protected function createResponse()
	{
		return new YqlResponse($this->_rawResponse, $this->_itemName, $this->_format, $this->_error);
	}
	
	protected function handleReturnStatusCode($statusCode)
	{
		switch( $statusCode ) 
		{
			case 200:
		       	// Success do nothing
		       	break;
		    case 503:
		    	$this->_error = 'HTTP 503: Service Unavailable';
		       	break;
		    case 403:
				$this->_error = 'HTTP 403: Forbidden. You do not have permission to access this resource';
		       	break;
			case 404:
				$this->_error = 'HTTP 404: Not Found. The requested resource was not found';
			    break;
		    case 400:
				$this->_error = 'HTTP 400: Bad Request. The request sent could not be processed.';
				break;
		    default:
		        $this->_error = 'Your call to YQL Web service returned an unexpected HTTP status of:' . $statusCode;
		}
		
	}
	
	   
}
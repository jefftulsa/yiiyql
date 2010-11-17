<?php
/**
 * This file contains a class to encapsulate the response returned from a call to YQL Web Service.
 *
 * @author Jefftulsa <jefftulsa@gmail.com>
 * @link http://www.yiihaw.com/
 * @license MIT
 */

/**
 * YqlResponse encapsulates a returned YQL response.
 */
class YqlResponse extends CComponent
{
	private $_rawData;
	private $_data;
	private $_results;
	private $_count;
	private $_created;
	private $_language;
	private $_error;
	private $_itemName;
	            
	/**
	 * Constructor.
	 * @param mixed $response the raw response returned from the query call
	 * @param string $itemName the name of the main item returned in the query results. This will depend on the query being made
	 * @param string $format the format of the returned response (can be either xml or json)
	 * @param boolean $requestedError whether or not there was an error with the request
	 */
	public function __construct($response, $itemName, $format='json', $requestError=false)
	{
		if($requestError)
		{
			$this->_error = new stdClass;
			$this->_error->description = $requestError; 
		    return;
		}
		$this->_rawData = $response;
		$this->_itemName = $itemName;
		if('json' == $format)
		{
			$this->_data = json_decode($this->_rawData);
		}
		
		if(is_object($this->_data))
		{
			//check for errors
			if(isset($this->_data->error))
			{
				if(is_object($this->_data->error))
				{
					$this->_error = $this->_data->error;
				}
				else
				{
					$this->_error = new stdClass;
					$this->_error->description = 'Unknown error returned from YQL call.';
				}
				
			}
			else
			{
				if(is_object($this->_data->query))
				{
					$this->_count = $this->_data->query->count;
					$this->_created = $this->_data->query->created;
					$this->_language = $this->_data->query->lang;
					$this->_results = $this->_data->query->results;
				}
				else
					throw new CException('response not an object');	
			}
			
		}
	}
	
	/**
	 * Returns the number of returned results.
	 * @return integer count of items
	 */
	public function getCount()
	{
		return $this->_count;
	}
	
	/**
	 * Returns the date of the returned response.
	 * @return string
	 */
	public function getDate()
	{
		return $this->_created;
	}
	
	/**
	 * Returns the language in which the returned results are written.
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->_language;
	}
	
	/**
	 * Returns the results (rows) of the response.
	 * @return array of json objects representing the returned rows of data
	 */
	public function getResults()
	{
		$name = $this->_itemName;
		return $this->_results->$name;
	}
	
	/*
	 * Returns the raw data from the response (i.e. in the raw json or xml format).
	 */
	public function getRawData()
    {
        return $this->_rawData;
    }

	/*
	 * Returns the full response (i.e. not just the data rows).
	 */
    public function getFullData()
    {
        return $this->_data;	
    }

    public function hasErrors()
    {
	   return isset($this->_error);
    }	

    public function getHasErrors()
	{
		return $this->hasErrors();
	}

    public function getError()
    {
		return $this->_error;
	}   
}
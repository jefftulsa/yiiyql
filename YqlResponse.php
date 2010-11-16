<?php

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
	
	public function getCount()
	{
		return $this->_count;
	}
	
	public function getDate()
	{
		return $this->_created;
	}
	
	public function getLanguage()
	{
		return $this->_language;
	}
	
	public function getResults()
	{
		$name = $this->_itemName;
		return $this->_results->$name;
	}
	
	public function getRawData()
    {
        return $this->_rawData;
    }

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
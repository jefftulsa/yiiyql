<?php
class YqlTest extends CTestCase
{  
     public function testConstruction()
     {
	      //create new Request object
		  $query = 'select * from rss where url="http://rss.news.yahoo.com/rss/topstories"';
		  $itemName = 'item';
		  $request = new YqlRequest($query, $itemName);
			
	      $this->assertEquals($request->itemName,$itemName);
		  $this->assertEquals($request->buildCommand(), urlencode($query));
	 } 
}
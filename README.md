Introduction
============

YiiYQL is a very simple wrapper to the [Yahoo! Query Language(YQL)](http://developer.yahoo.com/yql/) Web service API to make it even easier to
use in a Yii application. 


Initial Installation
====================
You can either download the source files or fork on github. Then just add them to the extensions folder under your Yii application.

Example:
========

	<?PHP
	$yql = new YqlRequest();
	$yql->select = '*';
	$yql->from = 'rss';
	$yql->where = 'url="http://rss.news.yahoo.com/rss/topstories"';
	$yql->itemName = 'item';
	$response = $yql->execute();

	//check for errors
	if($response->hasErrors)
	{
		//do some error handling, the error object is returned via $response->error
		echo $response->error->description;
	}
	else
	{
		//process the returned data
		print_r($response->results);
	}

You can also just set the query directly on the request object. This should be used for more complex queries:

	<?PHP
	$yql = new YqlRequest();
	$yql->query = 'select * from search.web where query in (select title from rss where url="http://rss.news.yahoo.com/rss/topstories" | truncate(count=1)) limit 1';
	$yql->itemName = 'result';
	$response = $yql->execute();
	...
	
The `itemName` property of the request object is important to understand. This completely depends on the request you are making. It is the repeated field (like a row in an SQL table) in the returned response. In the above examples, it is `item` when we queried the rss feed, but it is `result` for a query against the `web.search` table. For Youtube is it `video`, for Flickr it is `photo`, etc. 

You should use the [YQL Console](http://developer.yahoo.com/yql/console/) to see the available tables and to test our your YQL query statements and syntax. You can view the formatted results using this tool. A snipped of a response from a flickr request looks like
 	
	"query": {
	  "count": "20",
	  "created": "2010-11-16T04:42:51Z",
	  "lang": "en-US",
	  "results": {
	   "photo": [
	    {
		...

The first property under the `results` object is what needs to be specified as the request `itemName` property. In this case, you can see it is `photo`.


Components
==========

This extension as two main classes, and a custom [Data Provider](http://www.yiiframework.com/doc/api/1.1/CDataProvider/) class for ease of
use with Zii data display widgets.

YqlRequest class
----------------

Encapsulates the YQL request. When making the actual call to the Yahoo Web service, it will use cUrl if available, otherwise it will use `file_get_contents`. Only JSON requests are supported.

You can also set its `responseCacheDuration` property to cache the response if you have your application configured for caching.

YqlResponse class
------------------

Encapsulates the YQL response. Provides easy access to the successful results via the `->results` call, or in the event there was an error, to the error object `->error`.

YqlDataProvider
---------------

A basic data provider that will manage its data via the request and response objects. You can use as follows:

	$dataProvider=new YqlDataProvider('item', array(
		'criteria'=>array(
			'select'=>'title, link, description',
			'from'=>'rss',
			'where'=>'url="http://rss.news.yahoo.com/rss/topstories"',
		),
	));
	
OR

	$dataProvider=new YqlDataProvider('video', array(
		'query'=>"select title from youtube.searchuu where query='louis ck'",
		),
	));
	
The first item is the `itemName` of the expected results, and the second specifies the initial configuration params. 


Quick-start guide
=================

1. copy the yiiyql folder, which includes YqlRequest.php, YqlResponse.php and YqlDataProvider.php under the protected/extensions/ directory within your Yii application
   
2. Either Include (`require_once`) these whenever you need to access, or add to the import block in your Yii config file :
        
        ...
        // autoloading model and component classes
		'import'=>array(
			...
			'ext.yiiyql.*',
		),


3. Use it! Either create the request object and use directly, or use the dataProvider approach. Both are shown in the above examples.

        
Resources
=========

[YQL Guide](http://developer.yahoo.com/yql/guide/)


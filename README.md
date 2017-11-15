# pdowrapper
PHP wrapper class for PDO

PDOWrapper is a PHP class for using pdo queries easier. The PDO handler is easily switchable. Let me show you a few examples:

    // initialisation happens when including
    require_once("class.pdowrapper.php");
    
    // handler pdo object is accessible through DB::$handler
    
    // simple get query
    $rows = DB::get("SELECT * FROM mytable");
    
    // simple get query with where filter, returns an array
    $rows = DB::get("SELECT column1, column2 FROM mytable WHERE column1=?", [value1, value2]);
    
    // simple insert query
    $lastinsertid = DB::insert("mytable", ["column1" => "value1", "column2" => "value2"]);
    
    // simple update query
    DB::update("mytable", ["column1" => "newvalue1"], "id=? AND column3>?", ["where1", "where_col3"]);
    
    // simple remove/delete query, delete all table content
    DB::remove("mytable");
    
    // simple selective delte query
    DB::remove("mytable", "column1!=?", ["where_col1"]);

These were simple queries without transactions. Let me show you how to use complex queries with transactions and another PDO object:

    try {
	    // primary PDO initialised with class include
    	DB::beginTrans();
    	$id = DB::insert("table1", ["date" => date("Y-m-d")]);
    	DB::update("table2", ["idQuery1" => $id], "col3value>100");
    	$id_insert2 = DB::insert("table3", ["stamp" => time()]);
	    DB::commit();
	    // use class with another PDO connection
	    DB::setHandler($my_new_pdo);
	    $result_new_pdo = DB::get("SELECT * FROM new_pdo_table");
	    // revert to initial PDO connection
	    DB::reverseHandler();
    }
    catch (Exception $e) {
    	DB::rollBack();
    }

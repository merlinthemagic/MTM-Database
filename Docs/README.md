### What is this?

A wrapper for working with mysql PDO

#### Get an adaptor for MySql
```
$dbObj		= \MTM\Database\Factories::getMySQL()->getConnection("IpAddress", "username", "password");
```

#### Get a connection to a specific database:
```
//The conncetion can access any database when selecting, 
//you are simply specifying the default when doing insert, update
$connObj		= $dbObj->getNewClient("MyDatabase");
```

#### Select multiple rows with constraints:
```
$sql	= "SELECT mt.* FROM `myTable` mt
			WHERE mt.first LIKE :fname
			AND mt.last=:last
			";
$qPs	= array(":fname" => "%somename%", ":last" => "jackson");
$rows	= $connObj->getAll($sql, $qPs);
if ($rows !== null) {
	foreach ($rows as $row) {
		//something
	}
}

```
#### Select multiple rows with no constraints:
```
$sql	= "SELECT mt.* FROM `myTable` mt
			";
$rows	= $connObj->getAll($sql);
if ($rows !== null) {
	foreach ($rows as $row) {
		//something
	}
}

```

#### Select single row with constraints:
```
$sql	= "SELECT mt.* FROM `myTable` mt
			WHERE mt.first=:fname
			AND mt.last=:last
			";
$qPs	= array(":fname" => "jack", ":last" => "jackson");
$row	= $connObj->getRow($sql, $qPs);
if ($row !== null) {
	//something
}

```

#### Select single cell with constraints:
```
$sql	= "SELECT mt.age FROM `myTable` mt
			WHERE mt.first=:fname
			AND mt.last=:last
			";
$qPs	= array(":fname" => "jack", ":last" => "jackson");
$cell	= $connObj->getCell($sql, $qPs);
if ($cell !== null) {
	//something
}

```

#### Insert single record
```
$data					= array();
$data["fname"]		= "Lois";
$data["lname"]		= "Clarke";
$id						= $connObj->insert("myTable", $data); //returns primary key for the new record
```
#### Insert multiple record
```
$datas					= array();

//record one
$data					= array();
$data["fname"]		= "Troy";
$data["lname"]		= "Plamo";
$datas[]				= $data;

//record two
$data					= array();
$data["fname"]		= "Peter";
$data["lname"]		= "Lada";
$datas[]				= $data;

$connObj->insert("myTable", $datas);
```

#### Update with constraints:
```
$data					= array();
$data["fname"]		= "Lois";
$data["lname"]		= "Clarke";

$where					= "id=:myId";
$qPs					= array(":myId" => 5);
$connObj->update("myTable", $data, $where, $qPs);
```
#### Update with no constraints:
```
$data					= array();
$data["fname"]		= "Lois";
$data["lname"]		= "Clarke";

$connObj->update("myTable", $data);

```

# Query Builder Methods

The query builder contains command methods (for building the query), termnation methods (for executing the query, getting the output, and resetting the builder), and utility and debugging methods.

## Command Methods

### select, update, insert, delete 

| Parameters |
| --- |
| None |

Sets the current query type to SELECT, UPDATE, INSERT, or DELETE

---

### table 
 
| Parameters |
| --- |
| string $tablename |  

Sets the current primary table name.

**WARNING: NEVER PASS UNVALIDATED USER INPUT TO THIS FUNCTION.** Doing so may leave your code vulnerable to SQL injection.

---

### columns 
 
| Parameters | 
| --- | 
| mixed | 

Sets the columns to attach to the current query. This can accept five different formats depending on what's required:

    $column
    array($columnone, $columntwo, ...)
    array($alias => $column, ...)
    array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
    array($tableone => array($aliasone => $columnone, $aliastwo => $columntwo,  ...), $tabletwo => array(...) ...)
    
If used with update() or insert(), the column names in add() or set() will be validated against this configuration.

`*` may be substituted in the form `array($tablename => array('*')`, but this will trigger a validation error when used with update() or insert(). It's recommended that you only use `*` on the primary table.

**WARNING: NEVER PASS UNVALIDATED USER INPUT TO THIS FUNCTION.** Doing so may leave your code vulnerable to SQL injection.

---

### where 
 
| Parameters   |
| --- | 
| Array $where    |
| $innercondition = 'AND' |
| $outercondition = 'AND' |

Adds a where sequence to the query. The $where array has two possible formats:

    array($column => $param, ...)
    array($tableone => array($column => $param, ...), $tabletwo => array($column => $param, ...), ...)
    
* Column names can use the format 'columnname operand' to use operands other than '=', e.g. 'id >'
* Valid operands: `>|<|>=|<=|like|in`
* If the tablename is not specified in the $where array parameter, the primary table will be used instead
* Using the IN operand will force the param to be treated as an array. 
* Setting the param to NULL will force the operand to IS.

* Each key/value pair in the where sequence is placed inside brackets `WHERE (...)`, each separated by the $innercondition.
* Each where sequence added after the first is appended to the query using the previous where sequence's $outercondition.
* The $innercondition and $outercondition may only be either AND or OR.

Example:

    $this->select()
        ->table('users')
        ->where(['id IN' => [1,5,7,9,11], 'active' => 1, 'AND', 'OR')
        ->where(['id' => 15])
        ->many();

This set of commands produces the following:

    SELECT * FROM `users` WHERE(`users`.`id` IN (1,5,7,9,11) AND `users`.`active` = 1) OR WHERE (`users`.`id` = 15)
    
**WARNING: NEVER PASS UNVALIDATED USER INPUT TO THIS FUNCTION.** Doing so may leave your code vulnerable to SQL injection.

---

### add
 
| Parameters |
| --- | 
| Array $record  |  

Used with insert() to add records. It may be used in a loop to add a batch of records.

$record should be in the following form: `array('column' => $variable, ... )`

---

### set
 
| Parameters | 
| --- |
| Array $set  |  

Used with update() to specify column changes.

$set should be in the following form: `array('column' => $variable, ... )`

---

### limit
 
| Parameters  | 
| --- |
| $limit  |
| $offset = false  |

Sets the limit and optionally the offset in the query.

$limit and $offset are both cast to an integer before being added.

---

### orderBy
 
| Parameters   | 
| --- | 
| $columnname_or_array  |
| $order = 'ASC'  |

Sets the order in the query, and the first parameter may take three possible types:

    $column
    array($columnone, $columntwo, ...)
    array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
    
$order may be 'ASC' or 'DESC' only.

---

### join
 
| Parameters   | 
| --- |
| string $firsttable  |
| string $secondtable  |
| Array $on  |
| string $type  |

Sets a join for the current query.

$on must be in the following format, and multiple join column relations are allowed: 

    array('firsttablecolumn' => 'secondtablecolumn, ...)
    
Join relations only use the '=' operand.
    
$type may be one of INNER, FULL, LEFT or RIGHT.

---

## Termination Methods

These methods trigger a response from the database by compiling the query and sending it to the PDO connection. The query is deleted after the execution is complete. (You can pass in SQL and binds manually for the purposes of debugging, but DON'T DO IT IN PRODUCTION.)

### one
 
| Parameters   | 
| --- |
| $sql = false        |
| $binds = false      |  

Returns the first matching result from the database as a plain PHP object, or false if nothing is found. 

---

### many 
 
| Parameters   |
| --- | 
| $sql = false        |
| $binds = false      | 

As one(), but instead returns an array of all matching PHP objects, or false if nothing is found.

---

### count 
 
| Parameters   | 
| --- |
| none    | 

Returns an integer COUNT(*) of the current query.

---

### execute 
 
| Parameters   | 
| --- |
| $sql = false        |
| $binds = false      | 

Executes a query on the database and returns the result. Normally used only with UPDATE/INSERT/DELETE, and so only returns true or false. It can be used with SELECT, but it's not recommended because it may produce strange behaviour.

---

### raw
 
| Parameters   | 
| --- | 
| string $sql |
| Array $values | 
| string $fetch | 
| $returntype | 

**WARNING: Don't use this unless you know what you're doing.**

Executes a raw prepared SQL statement, with an array of bindings if required, on the current database connection without using the compiler. Returns an error, a boolean for success/fail, or an array of results.

$fetch may be set to 'fetch' or 'fetchAll'.
$returntype must be a PDO return type. (defaults to \PDO::FETCH_OBJ)

---

## Utility and Debugging methods

These methods are used for debugging, testing, and other utility tasks within Blueprint. Some require accessing the Source or Query objects.

### getCurrentSql
 
| Parameters   |
| --- | 
| none |

Returns the current raw SQL output of the Query as a string.

---

### getCurrentBindings
 
| Parameters   |
| --- | 
| none |

Returns an array of the current bindings which will be added to the prepared statement to be executed by the Source

---

### whitelistColumn
 
| Parameters   |
| --- | 
| $column (string or array) |

Adds a column or array of columns to the current whitelist. This whitelist is used on top of the Pattern whitelisting that happens with Patterns applied to INSERT or UPDATE queries.

---

### whitelistTable
 
| Parameters   |
| --- | 
| $table |

Adds a table or array of tables to the current whitelist. This whitelist is used on top of the Pattern whitelisting that happens with Patterns applied to INSERT or UPDATE queries.

---

### record
 
| Parameters   |
| --- | 
| none |

Starts the query recorder.

---

### stop
 
| Parameters   |
| --- | 
| none |

Stops the query recorder.

---

### output
 
| Parameters   |
| --- | 
| none |

Returns an array of information containing the generated SQL, bindings, and error output for each recorded query.

**Note**: `record`, `stop` and `output` are shorthand methods that pass through to the underlying Source object. Query recording is not enabled by default.

---

## Additional Utility Methods

The methods below belong to the source or query objects within Blueprint, and are included here for further debugging purposes if needed.

### $this->source->reset
 
| Parameters   |
| --- | 
| none |

Clears the current Query object, if one has been set in the Source.

---

### $this->source->getDatabaseName (MySQL only)
 
| Parameters   |
| --- | 
| none |

Returns the name of the current schema as a string.

---

### $this->source->getTableColumns (MySQL only)
 
| Parameters   |
| --- | 
| $tablename |

Returns an array of columns in the table, if it exists.

---

### $this->source->lastInsertId
 
| Parameters   |
| --- | 
| string $name = null |

Alias for \PDO::lastInsertId.

---

### $this->source->beginTransaction
 
| Parameters   |
| --- | 
| none |

Alias for \PDO::beginTransaction, with some additional tracking

---

### $this->source->commit
 
| Parameters   |
| --- | 
| none |

Alias for \PDO::commit, with some additional tracking

---

### $this->source->rollBack
 
| Parameters   |
| --- | 
| none |

Alias for \PDO::rollBack, with some additional tracking

---

### $this->source->setQuery
 
| Parameters   |
| --- | 
| QueryInterface $query |

Sets the current query to a $query

---

### $this->query->getSection
 
| Parameters   |
| --- | 
| $sectionName |

This method is a getter for any internal property of the Query. It should only be used for deeper analysis of the Query object during debugging, and never in live production code.

Ref: [https://github.com/sypherlev/blueprint/blob/master/src/QueryBuilders/MySql/MySqlQuery.php](Github) for the property list and this function's code.

---
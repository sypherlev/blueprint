---
layout: page
title: "Query Command Methods"
category: qb
date: 2016-10-18 13:30:00
---

These methods are available within Blueprint, and used to set up the SQL commands in the builder. All return $this.

**Important Note:** Blueprint was created for developers with a strong knowledge of SQL and it will not stop you from writing vulnerable code. Please note the methods below which should never accept unvalidated user input. If you need to pass user input into these functions, please use the `whitelistTable` and `whitelistColumn` functions (see Utility and Debugging Methods).

### select, update, insert, delete 
 
| Parameters   | none    |  

Sets the current query type to SELECT, UPDATE, INSERT, or DELETE

---

### table 
 
| Parameters   | string $tablename    |  

Sets the current primary table name.

**WARNING: NEVER PASS UNVALIDATED USER INPUT TO THIS FUNCTION.** Doing so may leave your code vulnerable to SQL injection.

---

### columns 
 
| Parameters   | mixed    | 

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
 
| Parameters   | Array $where    |
|              | $innercondition = 'AND' |
|              | $outercondition = 'AND' |

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
 
| Parameters   | Array $record  |  

Used with insert() to add records. It may be used in a loop to add a batch of records.

$record should be in the following form: `array('column' => $variable, ... )`

---

### set
 
| Parameters   | Array $set  |  

Used with update() to specify column changes.

$set should be in the following form: `array('column' => $variable, ... )`

---

### limit
 
| Parameters   | $limit  |
|               | $offset = false  |

Sets the limit and optionally the offset in the query.

$limit and $offset are both cast to an integer before being added.

---

### orderBy
 
| Parameters   | $columnname_or_array  |
|              | $order = 'ASC'  |

Sets the order in the query, and the first parameter may take three possible types:

    $column
    array($columnone, $columntwo, ...)
    array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
    
$order may be 'ASC' or 'DESC' only.

---

### join
 
| Parameters   | string $firsttable  |
|              | string $secondtable  |
|              | Array $on  |
|              | string $type  |

Sets a join for the current query.

$on must be in the following format, and multiple join column relations are allowed: 

    array('firsttablecolumn' => 'secondtablecolumn, ...)
    
Join relations only use the '=' operand.
    
$type may be one of INNER, FULL, LEFT or RIGHT.

---
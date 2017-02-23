---
layout: page
title: "Termination Methods"
category: qb
date: 2016-10-18 13:29:45
---

These methods trigger a response from the database by compiling the query and sending it to the PDO connection. The query is deleted after the execution is complete.

### one
 
| Parameters   | $sql = false        |
|              | $binds = false      | 
| Returns      | Object, or false    | 

Returns the first matching result from the database as a plain PHP object. 

---

### many 
 
| Parameters   | $sql = false        |
|              | $binds = false      | 
| Returns      | Array, or false     | 

As one(), but instead returns an array of all matching PHP objects. 

---

### count 
 
| Parameters   | none    | 
| Returns      | integer | 

Returns a COUNT(*) of the current query.

---

### execute 
 
| Parameters   | $sql = false        |
|              | $binds = false      | 
| Returns      | boolean    | 

Executes a query on the database and returns the result. Normally used only with UPDATE/INSERT/DELETE, and so only returns true or false. It can be used with SELECT, but it's not recommended because it may produce strange behaviour.

---

### raw
 
| Parameters   | string $sql                      |
|              | Array $values                   | 
|              | string $fetch                    | 
|              | $returntype               | 
| Returns      | boolean\|Array\|Exception | 

**WARNING: Don't use this unless you know what you're doing.**

Executes a raw prepared SQL statement on the current database connection without using the compiler. Returns an error, a boolean for success/fail, or an array of results.

$fetch may be set to 'fetch' or 'fetchAll'.
$returntype must be a PDO return type. (defaults to \PDO::FETCH_OBJ)

---
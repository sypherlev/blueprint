---
layout: page
title: "Utility and Debugging Methods"
category: qb
date: 2016-10-18 12:49:25
---

These methods are used for debugging, testing, and other utility tasks.

### startRecording
 
| Parameters   | none                     |
| Returns      | none | 

Starts the query recorder. SQL, bindings, and error output is saved for each query.
 
Queries may be compiled normally without the recorder being active as long as it is started before the termination method call.

---

### stopRecording
 
| Parameters   | none                     |
| Returns      | none | 

Stops the query recorder.

---

### getRecordedOutput
 
| Parameters   | none                     |
| Returns      | Array | 

Returns an array of information containing the generated SQL, bindings, and error output for each recorded query.

---

### reset
 
| Parameters   | none                     |
| Returns      | none | 

Resets the current query parameters, allowing a new query to start compiling.

---

### getSchemaName
 
| Parameters   | none                     |
| Returns      | string $schemaname | 

Returns the name of the current schema.

---

### getTableColumns
 
| Parameters   | string $tablename      |
| Returns      | Array      | 

Returns a list of columns in the table, if it exists.

---

### retrieveQuery
 
| Parameters   | none                     |
| Returns      | \stdClass | 

Returns a plain PHP object which contains the current query string and bindings.

---

### lastIdFrom
 
| Parameters   | $tablename                 |
|              | $primaryKeyname = 'id'     |
| Returns      | string\|boolean | 

Returns the last primary key from a table, or false if the table is empty.

---

### lastInsertId
 
| Parameters   | string $name = null          |
| Returns      | string | 

Alias for \PDO::lastInsertId.

---

### startTransaction
 
| Parameters   | none                     |
| Returns      | none | 

Starts a \PDO transaction

---

### startTransaction
 
| Parameters   | none                     |
| Returns      | none | 

Starts a \PDO transaction

---

### commitTransaction
 
| Parameters   | none                     |
| Returns      | none | 

Commits a \PDO transaction

---

### rollbackTransaction
 
| Parameters   | none                     |
| Returns      | none | 

Rolls back a \PDO transaction

---

### cloneQuery
 
| Parameters   | none                     |
| Returns      | QueryInterface | 

Returns a clone of the current Query object inside the compiler. (Useful for storing/rerunning/debugging failed queries)

---

### setQuery
 
| Parameters   | QueryInterface $query    |
| Returns      | none | 

Sets the current query to a $query

---

### getCurrentSql
 
| Parameters   | none |
| Returns      | string | 

Returns the current raw SQL output of the query builder.

---
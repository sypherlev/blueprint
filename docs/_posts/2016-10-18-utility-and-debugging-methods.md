---
layout: page
title: "Utility and Debugging Methods"
category: qb
date: 2016-10-18 12:49:25
---

These methods are used for debugging, testing, and other utility tasks within Blueprint. Some require accessing the Source or Query objects.

### getCurrentSql
 
| Parameters   | none |
| Returns      | string | 

Returns the current raw SQL output of the Query.

---

### getCurrentBindings
 
| Parameters   | none |
| Returns      | Array | 

Returns the current bindings which will be added to the prepared statement to be executed by the Source

---

### $this->whitelistColumn
 
| Parameters   | $column (string or array)                    |
| Returns      | none | 

Adds a column or array of columns to the current whitelist. This whitelist is used on top of the Pattern whitelisting that happens with Patterns applied to INSERT or UPDATE queries.

---

### $this->whitelistTable
 
| Parameters   | $table                     |
| Returns      | none | 

Adds a table or array of tables to the current whitelist. This whitelist is used on top of the Pattern whitelisting that happens with Patterns applied to INSERT or UPDATE queries.

---

### $this->query->getSection
 
| Parameters   | $sectionName                     |
| Returns      | $section\false | 

This method is a getter for any internal property of the Query. It should only be used for deeper analysis of the Query object during debugging, and never in live production code.

Ref: [https://github.com/sypherlev/blueprint/blob/master/src/QueryBuilders/MySql/MySqlQuery.php](Github) for the property list and this function's code.

---

### $this->record
 
| Parameters   | none                     |
| Returns      | none | 

Starts the query recorder.

---

### $this->stop
 
| Parameters   | none                     |
| Returns      | none | 

Stops the query recorder.

---

### $this->output
 
| Parameters   | none                     |
| Returns      | Array | 

Returns an array of information containing the generated SQL, bindings, and error output for each recorded query.

---

### $this->source->reset
 
| Parameters   | none                     |
| Returns      | none | 

Clears the current Query object, if one has been set in the Source.

---

### $this->source->getDatabaseName (MySQL only)
 
| Parameters   | none                     |
| Returns      | string $schemaname | 

Returns the name of the current schema.

---

### $this->source->getTableColumns (MySQL only)
 
| Parameters   | string $tablename      |
| Returns      | Array      | 

Returns a list of columns in the table, if it exists.

---

### $this->source->lastInsertId
 
| Parameters   | string $name = null          |
| Returns      | string | 

Alias for \PDO::lastInsertId.

---

### $this->source->beginTransaction
 
| Parameters   | none                     |
| Returns      | none | 

Alias for \PDO::beginTransaction, with some additional tracking

---

### $this->source->commit
 
| Parameters   | none                     |
| Returns      | none | 

Alias for \PDO::commit, with some additional tracking

---

### $this->source->rollBack
 
| Parameters   | none                     |
| Returns      | none | 

Alias for \PDO::rollBack, with some additional tracking

---

### $this->source->setQuery
 
| Parameters   | QueryInterface $query    |
| Returns      | none | 

Sets the current query to a $query

---


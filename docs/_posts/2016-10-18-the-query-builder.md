---
layout: page
title: "Basic Usage"
category: qb
date: 2016-10-18 13:30:17
---

The Blueprint query builder has three sets of methods: commands, termination methods, and utility methods.

Commands should be broadly familiar to anyone who's used a query builder before and who knows a little SQL. Commands can be chained together for better readability, like so:

    $houses = $this->select()
        ->table('houses')
        ->where(['architect' => 'John Doe'])
        ->orderBy('date_constructed')
        ->many();
        
This produces the SQL string below:

    SELECT * FROM `houses` WHERE `houses`.`architect` = :wh0 ORDER BY `houses`.`date_constructed`;
    
Once a query type and a primary table are set, additional commands may be added in any order, but the query will only be executed and a result returned when a termination method is called.

If running any query:

* one of select(), update(), insert() or delete() is required.
* table() is required.

If running a select():

* No additional parameters are required, but adding a limit() is good practice.

If running an update():

* set() is required
* where() is highly recommended

If running an insert()

* at least one add() is required

If running a delete()

* where() is highly recommended

Utility methods are provided to help with debugging or with more extensive query manipulation and analysis.
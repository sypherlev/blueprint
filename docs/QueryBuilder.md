# The Query Builder

[Function List](https://github.com/sypherlev/blueprint/blob/master/docs/QueryBuilderFunctions.md)

The Blueprint query builder is very simple - chain a bunch of commands together with `$this`, and call a termination method to get a result and reset `$this` for the next query.

    $houses = $this->select()
        ->table('houses')
        ->where(['architect' => 'John Doe'])
        ->orderBy('date_constructed')
        ->many();
        
You can run the commands together, or split them up.

    $this->select();
    $this->table('houses');
    $this->where(['architect' => 'John Doe']);
    $this->orderBy('date_constructed');
    $houses = $this->many();
    
You can add commands conditionally as needed. The builder will not try to execute any part of it until a termination method is called.

Termination methods are:

* `one()` : used for getting back a single record.
* `many()` : used for getting back an array of records.
* `execute` : used for create/update queries, when you just want a true/false response.
* `count()` : used as a quick shorthand for `COUNT(*)`.

The builder won't validate the SQL that's generated. It's up to you to handle SQL errors.
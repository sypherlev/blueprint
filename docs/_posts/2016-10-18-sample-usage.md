---
layout: page
title: "Sample Usage"
category: bp
date: 2016-10-18 15:34:50
---

Classes which interact with the database are normally called models; this is not true of Blueprint, as its core functionality is to define how to access and manipulate data, not define it. For the sake of having some kind of convention, I call them `SomethingData`.

Each Data class's constructor should include whatever Patterns, Filters and Transformations you require (because it's convenient), but you can put them in their own methods and initialize them as needed if you like.

**Patterns, Filters and Transformations cannot be removed once added. They can only be overwritten with a new element of the same name.**

Blueprint object example:
    
    class UserData extends Blueprint
    {
        public function __construct(SourceInterface $source) {
            parent::__construct($source);
     
            // add patterns
            $this->addPattern('summary', function() {
                return (new Pattern())->table('users')
                    ->join('users', 'users_extended', ['id' => 'user_id'], 'LEFT')
                    ->join('users', 'businesses', ['id' => 'user_id'], 'LEFT')
                    ->columns([
                        'users' => ['id', 'username', 'first_name', 'last_name'],
                        'users_extended' => ['profile_pic'],
                        'businesses' => ['city', 'province']
                    ]);
            });
     
            $this->addPattern('whole', function(){
                return (new Pattern())->table('users')
                    ->join('users', 'users_extended', ['id' => 'user_id'], 'LEFT')
                    ->join('users', 'businesses', ['id' => 'user_id'], 'LEFT')
                    ->columns([
                        'users' => ['id', 'username', 'first_name', 'last_name'],
                        'users_extended' => ['*'],
                        'businesses' => ['*']
                    ]);
            });
     
            // add filters
            $this->addFilter('onlyActive', function(){
                return (new Filter())->where(['active' => 1]);
            });
     
            // add transformations
            $this->addTransformation('convertTimes', function($record){
                if(isset($record->created)) {
                    $record->created = date('Y-m-d', $record->created);
                }
                return $record;
            });
        }
    }
    
The constructor accepts a Source - this is the database object through which queries will be run. Then three sections are defined - Patterns, Filters, and Transformations. Each one is defined by the following syntax:

    $this->addPattern('patternname', function() {...});
    
The second argument for addPattern or addFilter should be a closure that produces an object of the relevant type. The second argument for addTransformation should be a closure that returns the same record entity passed into the function.

### Sample Functions

In the User object itself, here are some possible functions for retrieving data:

    public function getUser($id, $pattern = 'whole') {
        return $this
            ->select()
            ->withPattern($pattern)
            ->where(['users' => ['id' => $id]])
            ->withTransformation('convertTimes')
            ->one();
    }

    public function getUserByUsername($username, $pattern = 'whole') {
        return $this
            ->select()
            ->withPattern($pattern)
            ->withTransformation('convertTimes')
            ->where(['users' => ['username' => $username]])
            ->one();
    }
    
    public function getUserList() {
        return $this
            ->select()
            ->withPattern('summary')
            ->withFilter('onlyActive')
            ->withTransformation('convertTimes')
            ->many();
    }
If you've ever used a query builder, the methods above should look familiar. When the termination methods are called (`one()`, `many()`, `execute()`, `count()`), any chosen Pattern and Filter is added to the query.

Transformations are applied to records returned from any SELECT(), and any passed into INSERT() and UPDATE(). You may find it tricky to apply the same Transformation both ways.

Here's some create and update functions:

    public function insertUser(Array $user) {
        return $this
            ->insert()
            ->withPattern('summary')
            ->add($user)
            ->execute();
    }
    
    public function updateUser($id, Array $userUpdate) {
        return $this
            ->update()
            ->withPattern('summary')
            ->withFilter('onlyActive')
            ->set($userUpdate)
            ->where(['id' => $id])
            ->execute();
    }
    
In the insertUser function, invoking the 'summary' pattern means that Blueprint will try to insert a record into the table 'users' using the array $user, where the only allowed fields are those listed for the table 'users' in the Pattern. Therefore the array may only consist of the following:

    [
        'id' => '...',
        'username' => '...',
        'first_name' => '...',
        'last_name' => '...'
    ]
    
If any other array keys are present that don't match the list allowed by the Pattern for the primary table, an exception will be thrown. (This validation is designed to prevent SQL injection in the column names.)

The same applies for the updateUser function. As long as a Pattern is invoked, Blueprint will validate the data first. It will not ensure that all fields are present; it only ensures that the given fields are whitelisted. This means that you may add a single Pattern that defines which fields are editable in a particular table, and use it for any number of queries that edit that table.

You can specify different Patterns for what data may be edited, vs. what data may be inserted.

For more simple tables, you can define a single Pattern and apply it to all queries.
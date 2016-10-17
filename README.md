# Blueprint Query Compiler

Blueprint is an extended query builder that allows you to define, reuse, and override chunks of query code at will.

ORMs such as Doctrine and Propel are based on the concept of mapping a record in a table to an object, with the columns becoming the object's properties. This approach has many benefits - for example, it simplifies the codebase, and it's more accessible to developers who are less familiar with SQL. It's far less suitable for apps with large, complex data relationships, however, and many developers find the loss of flexibility and control to be annoying.

In short: working with an ORM pulls you away from the database, and this isn't a good solution if you find yourself having to fight your way back to the database to get something done.

Blueprint was based on the concept that we should not treat relational data as objects; to do so effectively sacrifices the power of a relational database for the sake of making everything an object in the code. **Blueprint instead defines the interactions between the code and the database** in a reusable way, so that we're not forced to constantly write more queries that are just a little different (which is the case with other query builders) and we're not at risk of having to change a column name in many places when the database schema changes.

It's built using PDO, and currently supports MySQL/MariaDB. (It probably works with other SQL databases but I haven't tested it yet.)

Highly experimental and not recommended for production work right now.

## How it works

The first part of Blueprint is the **Pattern** - a sequence of query builder commands.

    $this->addPattern('summary', function() {
        return (new Pattern())
            ->table('users')
            ->columns(['username', 'first_name', 'last_name', 'email', 'phone']);
    });
    
Apply the Pattern to a SELECT, and it will merge those commands into the query. (Apply it to an UPDATE or INSERT, and it will update/insert into the specified table and only the specified columns.)

    public function getUser($id) {
        return $this
            ->select()
            ->withPattern('summary')
            ->where(['id' => $id])
            ->one();
    }

You can specify more complex Patterns, including joins.

    $this->addPattern('whole', function(){
        return (new Pattern())->table('users')
            ->join('users', 'users_extended', ['id' => 'user_id'], 'LEFT')
            ->join('users', 'businesses', ['id' => 'user_id'], 'LEFT')
            ->columns([
                'users' => ['*'],
                'users_extended' => ['addr1', 'addr2', 'postcode', 'city', 'state', 'country'],
                'businesses' => ['business_name', 'industry', 'website']
            ])
            ->limit(100);
    });
    
And then using the same function, you can call whichever Pattern you need. (Applying a complex Pattern to an UPDATE or INSERT will validate against the primary table only, and it will NOT validate at all against a '*' selection.)

    public function getUser($id, $pattern = 'summary') {
        return $this
            ->select()
            ->withPattern($pattern)
            ->where(['id' => $id])
            ->one();
    }
    
You can add more commands, and override any of the Pattern's commands.

    public function getActiveUsers($limit, $offset) {
        return $this
            ->select()
            ->withPattern('summary')
            ->where(['active' => 1])
            ->orderBy('created', 'DESC')
            ->limit($limit, $offset)
            ->many();
    }

Patterns may only be applied once per query. Calling *withPattern* a second time simply overwrites the first Pattern. Patterns allow you to set a primary table, any number of joins, columns, orderBy, limit, and groupBy.

The second part of Blueprint is the **Filter** - a sequence of commands that add to the query without overwriting each other.

    $this->addFilter('onlyActive', function(){
        return (new Filter())->where(['active' => 1]);
    });
    
Filters are a convenient way to add-on extra where and join clauses.  *TO-DO: more about Filter usage*

The third part of Blueprint is the **Transformation** - a function which is called after the query output, and modifies it in some way. *TO-DO: add code for handling transformations before query execution*

    $this->addTransformation('convertTimes', function($record){
        if(isset($record->created)) {
            $record->created = date('Y-m-d', $record->created);
        }
        return $record;
    });
    
When applying a Transformation, Blueprint will pass the query output into the Transformation function. (If it receives an array, it will loop over the array and pass each element into the function.)

Transformations are best used in the form above - working on a single record, assumed to be a plain PHP object, with a check to make sure that the field it's operating on exists.

## Usage

Blueprint object example:
    
    class User extends Blueprint
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
    
The second argument for addPattern, addFilter, and addTransformation should be a closure that produces an object of the relevant type.

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
If you've ever used a query builder, the methods above should look familiar.

withPattern, withFilter, and withTransformation simply applies the chosen set of rules to the query.

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

The same applies for the updateUser function. As long as a Pattern is invoked, Blueprint will validate the data first. It will not ensure that all fields are present; it only ensures that the given fields are whitelisted.
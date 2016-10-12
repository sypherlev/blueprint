# Blueprint Query Compiler

Blueprint is an extended query builder that allows you to define, reuse, and override chunks of query code at will.

It's built using PDO, and currently supports MySQL/MariaDB. (It probably works with other SQL databases but I haven't tested it yet.)

Highly experimental and not recommended for production work right now.

## Usage

Let's setup a basic Blueprint object and define how it should interact with the database. (Blueprint is not an ORM; your data will always be plain PHP objects or arrays.)
    
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
    
The constructor accepts a Source - this is the database object through which queries will be run. Then three sections are defined - Patterns, Filters, and Transformations.

### Patterns

A Pattern is a reusable piece of query code that may only be applied once per query, meaning that adding more Patterns just overrides any previously set Pattern.

Patterns allow you to set a primary table, any number of joins, and a set of columns.

### Filters

A Filter is another type of reusable code that is additive - instead of additional Filters overriding those previously set, they are all applied to the query in sequence.

Filters allow you to set wheres, orderby, limits and groupby.

### Transformations

A Transformation is a particular piece of code that's applied after a query is run and information is received from the database. When applying a Transformation, Blueprint will pass the query output - a single plain PHP object row, or an array of the same - into the Transformation function. If it receives an array, it will loop over the array and pass each row into the function.

Transformations are best used in the form above - working on a single record, assumed to be a plain PHP object, with a check to make sure that the field it's operating on exists. Always make sure your Transformations can handle the output given to them sensibly.

### Sample Functions

In the User object itself, here are some possible functions:

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

TO DO - add more about how all this interacts with update/insert/delete
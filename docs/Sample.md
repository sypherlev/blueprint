# Sample

For the sake of having some kind of convention, I call classes extended from Blueprint `SomethingData`.

Each Data class may include Patterns, Filters, and Transformations. These are additional components used to avoid having to re-write large chunks of queries over and over again. They should be included in the class constructor, but it's possible to add them conditionally and just include them as needed if you want more granular control.

**Patterns, Filters and Transformations cannot be removed once added. They can only be overwritten with a new element of the same name.**

## Example of a constructor    
    
    class UserData extends Blueprint
    {
        public function __construct(SourceInterface $source, QueryInterface $query) {
        parent::__construct($source, $query);
        
        // add patterns
        $this->addPattern('summary', function() {
            return (new Pattern())->table('users')
                ->join('users', 'users_extended', ['id' => 'user_id'], 'LEFT')
                ->join('users', 'businesses', ['id' => 'user_id'], 'LEFT')
                ->columns([
                    'users' => ['id', 'username', 'first_name', 'last_name'],
                    'users_extended' => ['profile_pic'],
                    'businesses' => ['name', 'state']
            ]);
        });
        
        $this->addPattern('whole', function(){
            return (new Pattern())->table('users')
                ->join('users', 'users_extended', ['id' => 'user_id'], 'LEFT')
                ->join('users', 'businesses', ['id' => 'user_id'], 'LEFT')
                ->columns([
                    'users' => ['*'],
                    'users_extended' => ['profile_pic', 'phone', 'twitter_handle'],
                    'businesses' => ['name', 'address1', 'address2', 'state', 'zipcode']
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
        
        $this->addTransformation('convertTimesExtended', function(Array $records){
                foreach ($records as $idx => $record) {
                    if(isset($record->created)) {
                        $records[$idx]->created = date('Y-m-d H:i:s', $record->created);
                    }
                }
                return $records;
            }, true);
        }
    }
Three sections are defined - Patterns, Filters, and Transformations. Each one represents a different re-usable query part.

* Patterns define tables, joins and columns, groupings, and aggregates.
* Filters define where clauses, orders, and limits.
* Transformations define additional operations that must occur before or after a query.

The second argument for `addPattern` or `addFilter` should be a closure that produces an object of the relevant type.  The second argument for `addTransformation` should be a closure that returns the same record entity passed into the function.

A Transformation’s default behavior is to cycle through an array of records and apply the transformation closure to each one. In the event that you need to apply a Transformation to the whole array, set the closure to expect an array, and set the third argument of addTransformation to `true`.

**Note:** It’s possible to use Transformations to invoke other class methods and therefore trigger additional queries, and append the data returned to a record or records returned from a query. This could easily turn into an N+1 problem and trash your database performance. There are no safeguards to stop you from doing it, so consider this a warning: *be very careful when making further database queries inside a Transformation closure, especially when the Transformation is applied to a single record at a time*.

## Method Examples

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
    
When the termination methods are called (one(), many(), execute(), count()), any chosen Pattern and Filter is added to the query.

Transformations are applied to records returned from any SELECT(), and any passed into INSERT() and UPDATE(). You may find it tricky to apply the same Transformation both ways.

Here’s some create and update functions:

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

The Patterns in Blueprint double as a whitelist when used with insert and update queries. Invoking a Pattern on these queries will set the table to be updated/inserted into, and the columns parameter of the Pattern will be used to validate the update/insert arrays.

For example: In the `insertUser function`, invoking the ‘summary’ pattern means that Blueprint will try to insert a record into the table ‘users’ using the array $user, where the only allowed fields are those listed for the table ‘users’ in the Pattern. Therefore the array may only consist of the following:

    [
        'id' => '...',
        'username' => '...',
        'first_name' => '...',
        'last_name' => '...'
    ]
    
If any other array keys are present that don’t match the list allowed by the Pattern for the primary table, an exception will be thrown. (This validation is designed to prevent SQL injection in the column and table names.)

The same applies for the `updateUser` function. As long as a Pattern is invoked, Blueprint will validate the data first. It will not ensure that all fields are present; it only ensures that the given fields are whitelisted. This means that you may add a single Pattern that defines which fields are editable in a particular table, and use it for any query that edits that table.

You can specify different Patterns for what data may be edited, vs. what data may be inserted.

For more simple tables, you can define a single Pattern and apply it to all queries.
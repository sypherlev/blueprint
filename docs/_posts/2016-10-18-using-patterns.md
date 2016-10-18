---
layout: page
title: "Using Patterns"
category: pat
date: 2016-10-18 15:13:55
---

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
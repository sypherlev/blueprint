---
layout: page
title: "Using Filters"
category: fil
date: 2016-10-18 15:14:28
---

The second part of Blueprint is the **Filter** - a sequence of commands that add to the query without overwriting each other.

    $this->addFilter('onlyActive', function(){
        return (new Filter())->where(['active' => 1]);
    });
    
Filters are a convenient way to add-on extra where and join clauses.  *TO-DO: more about Filter usage*
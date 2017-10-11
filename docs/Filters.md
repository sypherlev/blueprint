# Filters

The second part of Blueprint is the **Filter** - a sequence of commands that adds where clauses to the query without overwriting existing ones, and sets the `limit` and `orderBy`.

    $this->addFilter('onlyActive', function(){
        return (new Filter())->where(['active' => 1]);
    });
   
Filters are useful for conditionally adding constraints to the query.

## Filter Methods

Filter methods store the query elements below, and can be applied to a Query when the Filter is used.

All methods below are analogues to those of the same name in the Blueprint query builder.

* where
* orderBy
* limit

### setQueryParams
 
| Parameters   | QueryInterface $query  |
| Returns      | $query |

This function causes the Filter's settings to be applied to the $query.

This is normally only used in the main Blueprint class and can be ignored.

---
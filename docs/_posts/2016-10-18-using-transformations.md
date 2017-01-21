---
layout: page
title: "Using Transformations"
category: tra
date: 2016-10-18 15:14:46
---

The third part of Blueprint is the **Transformation** - a function which is called before or after the query, and modifies its input/output in some way.

    $this->addTransformation('convertTimes', function($record){
        if(isset($record->created)) {
            $record->created = date('Y-m-d', $record->created);
        }
        return $record;
    });
    
When applying a Transformation, Blueprint will pass the input or output into the Transformation function. (If it receives an array, it will loop over the array and pass each element into the function.)

Transformations are best used in the form above - working on a single record, assumed to be a plain PHP object, with a check to make sure that the field it's operating on exists.

When applied to an INSERT or UPDATE, the Transformation is performed on the input before the query is performed. When applied to a SELECT, the Transformation is applied to the query's output.
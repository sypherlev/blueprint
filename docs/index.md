---
layout: default
title: "Blueprint Query Compiler"
---

## The Blueprint Query Compiler

Blueprint is an extended query builder that allows you to define, reuse, and override chunks of query code at will.

### Background

ORMs such as Doctrine and Propel are based on the concept of mapping a record in a table to an object, with the columns becoming the object's properties. This approach has many benefits - for example, it simplifies the codebase, and it's more accessible to developers who are less familiar with SQL. It's far less suitable for apps with large, complex data relationships, however, and many developers find the loss of flexibility and control to be annoying.

In short: working with an ORM pulls you away from the database, and this isn't a good solution if you find yourself having to fight your way back to the database to get something done.

Query builders have their own issues. They're close to the database, but the greater control means a greater number of things to stipulate. They speed up the creation of complete SQL commands, but if you've ever used one, you've probably gotten bored of writing the same CRUD functions over and over, or found you have a model class full of ten slightly different list methods, and changing one column name means an update to the code in fifty places.

Query builders take you too close to the database all the time, and they tend to lead to lots of copy-pasted code - or worse, SQL injection vulnerabilities.

I found that, while building a number of large, data-driven apps, an ORM just got in the way most of the time, while a query builder meant writing lots of difficult-to-maintain, possibly-insecure code. Having found nothing that could provide a happy medium, I came up with Blueprint.

### Philosophy

Blueprint is designed specifically for complex data manipulation. It was based on the concept that we should not treat relational data as objects; to do so effectively sacrifices the power of a relational database for the sake of making everything an object in the code. But we still need to handle relational data sensibly, and that means applying enough object-oriented design to integrate well with our applications.
 
Instead of going the ORM route, and transferring a record from a table into an object, Blueprint instead defines and stores SQL elements that may be used to interact with a table, or a set of tables through joins. When the table is queried, Blueprint can use stored elements to complete the query instead of having to rewrite the same commands over and over again. It can also store filters and transformations to make data manipulation easier.

It's still a query builder at heart, and allows for raw SQL if needed. It's built using PDO, and currently supports MySQL/MariaDB. (It probably works with other SQL databases but I haven't tested it yet.)

Highly experimental and not recommended for production work right now. Also not really recommended for basic CRUD work, unless you're more comfortable with a query builder or you expect things to get more complicated later.

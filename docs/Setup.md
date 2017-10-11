# Setup

## Requirements

* PHP 7.0+

## Usage

Blueprint does not behave like an ORM, and does not match one class to one database table. Its core functionality is to define how to access and manipulate the database, and it makes no value judgments about how that data should look.

(This is a reflection of how I build applications to deal with large amounts of complex data; it becomes difficult to adapt the database to the code, and far less so to adapt the code to the database. YMMV.)

The basic usage is to have a Blueprint-extending class that encapsulates all the database operations of a single logical entity in the business domain, including joins and references as needed. This class requires a Source object and a Query object injected into it that correspond to the type of database it's expected to manipulate. The Source object needs a \PDO object.

(There are classes included for MySQL/MariaDB and PostgreSQL, but the interfaces are there to add more.)

Blueprint does NOT manage the connection to the database. You will need to successfully create the \PDO object and pass it to the Source before using it. I've included a basic ConnectionGenerator class if needed but it's not required.
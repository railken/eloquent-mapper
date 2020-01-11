<h1 align="left">Eloquent Mapper</h1>

[![Actions Status](https://github.com/railken/eloquent-mapper/workflows/Test/badge.svg)](https://github.com/railken/eloquent-mapper/actions)

A laravel package that use the full power of relations to create automatic joins and perform advanced filtering.

Given for e.g. two models `Office` and `Employee`, you can transform a string like this `"employees.name ct 'Mario Rossi' or employees.name ct 'Giacomo'"` into a sql query like this

```sql
select offices.* 
from `offices` 
left join `employees` as `employees` on `employees`.`office_id` = `offices`.`id`
where (`employees`.`name` like ? or `employees`.`name` like ?)
```

Functions: 

- Join automatically your relations
- Filter query with complex logic expression [lara-eye](https://github.com/railken/lara-eye)
- Attach dynamic relationships without touching the code using [eloquent-relativity](https://github.com/imanghafoori1/eloquent-relativity)

## Requirements

PHP 7.2 and laravel 5.8

## Installation

You can install it via [Composer](https://getcomposer.org/) by typing the following command:

```bash
composer require railken/eloquent-mapper
```

## Usage

In order to use this library you need a map.

Create a new class wherever you want like the following example

`app/Map.php`
```php
namespace App;

use Railken\EloquentMapper\Map as BaseMap;

class Map extends BaseMap
{
    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    public function models(): array
    {
        /** return [
            \App\Models\User::class
        ]; **/
    }
}
```

The method `models` must return a list of all models. You can even add models that are in your vendor folder, regardless of the logic you use, you only have to return an array.

`Railken\EloquentMapper\Map` also has the mapping of relations and attributes based on the model, if you wish you can ovveride that functionality and write your own. [Check source](src/Map.php)

These methods are invoked only when you call the command `artisan mapper:generate` (see below) and the result will be cached in a file placed in `bootstrap/cache/map.php`. 

This means you can perform whatever logic you want to retrieve all models (e.g. scanning files), so don't worry about caching.

**Important**: In order to be detected, all relations must return the type `Illuminate\Database\Eloquent\Relations\Relation` like this:

```php
namespace App;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Foo extends Model
{   
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bar(): BelongsTo
    {
        return $this->belongsTo(Bar::class);
    }
}
```

Now it's time to register this class in any provider to override the default one.

`app/Providers/AppServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Map;
use Railken\EloquentMapper\Contracts\Map as MapContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @inherit
     */
    public function register()
    {
        $this->app->bind(MapContract::class, Map::class);
    }
}
```

## Artisan

There is only one command, and it's `artisan mapper:generate`. This command will remap and recache so keep in mind that you have to execute it whanever you change your code models .

If you use models that are in your vendor folder, you could add this in your `composer.json` to reload everytime the libreries are updated.
```json
{
   "scripts": {
        "post-autoload-dump": [
            "@php artisan mapper:generate"
        ]
    }
}
```

## Filtering

Sow how the filtering actually works?


```php
use Railken\EloquentMapper\Scopes\FilterScope;
use Railken\EloquentMapper\With\WithCollection;
use Railken\EloquentMapper\With\WithItem;
use App\Models\Foo;

$foo = new Foo;
$query = $foo->newQuery();
$filter = "created_at >= 2019";

$scope = new FilterScope;
$scope->apply($query, $filter, new WithCollection([
    new WithItem('bar')
]));

```

And that's it! `$query` is now filtered, if `Foo` has any relationships you can use the dot notation and the filter will automatically perform the join. For e.g. if `Foo` has a relationship called `tags` and you want to retrieve all `Foo` with the tag name `myCustomTag` simply use `tag.name = 'myCustomTag'`.

Here's the [full syntax](https://github.com/railken/search-query#nodes)

The third parameter is the eager loading option. You can of course use the dot notation as well and add subquery.
For istance the following example rapresent a list of all authors that contains the name `Mario` and returns all of theirs books that have a `tag.name` called `sci-fi`.

```php
use Railken\EloquentMapper\Scopes\FilterScope;
use Railken\EloquentMapper\With\WithCollection;
use Railken\EloquentMapper\With\WithItem;
use Railken\EloquentMapper\Tests\Models\Author;

$author = new Author;
$query = $author->newQuery();
$filter = "name ct 'Mario'";
$scope = new FilterScope;

$scope->apply($query, $filter, new WithCollection([
    new WithItem('books', 'tag.name eq "sci-fi"')
]));
```

## Joiner

This is an internal class used by the `FilterScope` to join the necessary relations before performing the filtering, but you can use it indipendently. [see tests](tests/JoinerTest.php)

## Example - Setup

Let's continue with a real example, first the setup. We will use two models: `Office` and `Employee`

`app/Models/Office.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{   
    /** 
     * @var array
     */
    public $fillable = [
        'name',
        'description'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
```

`app/Models/Employee.php`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Office;

class Employee extends Model
{   
    /**
     * @var array
     */
    public $fillable = [
        'name',
        'description',
        'office_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
```

`app/Map.php`
```php
namespace App;

use Railken\EloquentMapper\Map as BaseMap;

class Map extends BaseMap
{
    /**
     * Return an array of all models you want to map
     *
     * @return array
     */
    public function models(): array
    {
        return [
            \App\Models\Employee::class,
            \App\Models\Office::class
        ];
    }
}

```

# Example - Usage

Retrieve all offices that have employees with name `Mario Rossi` or `Giacomo`

```php
use App\Models\Office;
use Railken\EloquentMapper\Scopes\FilterScope;

$office = new Office;

$query = $office->newQuery();
$filter = "employees.name ct 'Mario Rossi' or employees.name ct 'Giacomo'"

$scope = new FilterScope();
$scope->apply($query, $filter);

echo $query->toSql();

```

Result:
```sql
select offices.* 
from `offices` 
left join `employees` as `employees` on `employees`.`office_id` = `offices`.`id`
where (`employees`.`name` like ? or `employees`.`name` like ?)
```

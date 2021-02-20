# linqinp

linqinp is for using something like C # LINQ in php.<br/>

## features

- You can operate iterator by your specified callble.
- Ypu can use not only value but also key in your making callable.
- You can modify key for return value.

## sample

```php
use Linqinp\Linqinp;

$target = [1, 2, 3];

// sample01 = [2, 4, 6];
$sample01 = Linqinp::from($target)
  ->select(
    function (int $value) { // x is value
      return $value * 2;
    }
  )->toArray();

// sample02 = [1, 4, 9];
$sample02 = Linqinp::from($target)
  ->select(
    function (int $value, int $key) {
      return $value * $key;
    }
  )->toArray();

// sample03 = [1 => 1, 2 => 2, 3 => 3];
$sample03 = Linqinp::from($target)
  ->select(
    function (int $value, int &$key) {
      $key += 1;
      return $value;
    }
  )->toArray();
```


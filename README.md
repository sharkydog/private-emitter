# private-emitter
Non-public emitter extension to [evenement/evenement](https://packagist.org/packages/evenement/evenement).

### Basic usage
```php
class Emitter1 {
  use PrivateEmitterTrait;

  public function pubEmitter(): callable {
    return $this->_emitter();
  }

  public function event1($p) {
    $this->_emit('event1', [$p]);
  }
}

$emitter = new Emitter1;
$emitter->on('event1', function($p) {
  echo "event1: $p\n";
});

$emitter->event1('works');
$emitter->emit('event1', ['does not work']);

$pubEmitter = $emitter->pubEmitter();
$pubEmitter('event1', ['also works']);
```

### Capture events in methods
Events can be mapped to methods prefixed with `_event_`.
Event `event1` would be handled by `_event_event1()` method.

If the same event is emitted in that method, it will now fire the `EventEmitter` listeners.

```php
class Emitter2 {
  use PrivateEmitterTrait;

  public function pubEmitter(): callable {
    return $this->_emitter();
  }

  private function _event_event1($p) {
    print "_event_event1: $p\n";
    $this->_emit('event2', [$p]);
  }
  private function _event_event2($p) {
    print "_event_event2: $p\n";
    $this->_emit('event2', [$p]);
    $this->_emit('event3', [$p]);
  }
}

$emitter = new Emitter2;

$emitter->on('event1', function($p) {
  echo "event1: $p\n";
});
$emitter->on('event2', function($p) {
  echo "event2: $p\n";
});
$emitter->on('event3', function($p) {
  echo "event3: $p\n";
});

$pubEmitter = $emitter->pubEmitter();

// calls _event_event1 and emits event2
// then calls _event_event2 and emits event2 and event3
// prints:
//  _event_event1: p1
//  _event_event2: p1
//  event2: p1
//  event3: p1
$pubEmitter('event1', ['p1']);
```

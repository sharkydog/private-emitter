# private-emitter
Non-public emitter extension to [evenement/evenement](https://packagist.org/packages/evenement/evenement).

### Basic usage
```php
use SharkyDog\PrivateEmitter\PrivateEmitterTrait;

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
use SharkyDog\PrivateEmitter\PrivateEmitterTrait;

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
### Forward events
Events can be forwarded to other emitters.

The rules above for capturing events apply. Provided emitter callbacks will be called if events were not captured or were captured and re-emitted.

```php
use SharkyDog\PrivateEmitter\PrivateEmitterTrait;
use Evenement\EventEmitter;

// Events can be emitted only
// by whoever $emitter is shared with
class PrivateEmitter {
  use PrivateEmitterTrait;
  public function __construct(&$emitter) {
    $emitter = $this->_emitter();
  }
}

// A simple and boring emitter
class PublicEmitter extends EventEmitter {}

$prvEmitter1Obj = new PrivateEmitter($prvEmitter1Fn);
$prvEmitter2Obj = new PrivateEmitter($prvEmitter2Fn);
$pubEmitter1Obj = new PublicEmitter;

// Let this serve as forwarding callback signature
$pubEmitter1Fn = function(string $event, array $args) use($pubEmitter1Obj) {
  $pubEmitter1Obj->emit($event,$args);
};
// or as EventEmitter::emit() is public
// forwardEvents() bellow can be used with array callable
//$pubEmitter1Fn = [$pubEmitter1Obj,'emit'];

// first listener, first to receive event1
$prvEmitter1Obj->on('event1', function() { echo "event1: prv1\n"; });
// second listener for event1, first for event2
$prvEmitter1Obj->forwardEvents($prvEmitter2Fn, 'event1','event2');
// third listener for event1, second for event2
$prvEmitter1Obj->forwardEvents($pubEmitter1Fn, 'event1','event2');
// third listener for event2
$prvEmitter1Obj->on('event2', function() { echo "event2: prv1\n"; });

// listeners on objects events are forwarded to
$prvEmitter2Obj->on('event1', function() { echo "event1: prv2\n"; });
$prvEmitter2Obj->on('event2', function() { echo "event2: prv2\n"; });
$pubEmitter1Obj->on('event1', function() { echo "event1: pub1\n"; });
$pubEmitter1Obj->on('event2', function() { echo "event2: pub1\n"; });

// prints
//  event1: prv1
//  event1: prv2
//  event1: pub1
//  event2: prv2
//  event2: pub1
//  event2: prv1
$prvEmitter1Fn('event1');
$prvEmitter1Fn('event2');
```

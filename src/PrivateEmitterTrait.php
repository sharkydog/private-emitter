<?php
namespace SharkyDog\PrivateEmitter;
use Evenement\EventEmitterTrait;

trait PrivateEmitterTrait {
  use EventEmitterTrait {
    EventEmitterTrait::emit as private _EventEmitter_emit;
  }

  private $_prem_current_event = null;

  public function forwardEvents(callable $emitter, string ...$events) {
    foreach($events as $event) {
      $this->on($event, function(...$args) use($event,$emitter) {
        $emitter($event, $args);
      });
    }
  }

  public function emit($event, array $args=[]) {
  }

  protected function _emit($event, $args=[]) {
    if($event == $this->_prem_current_event) {
      $this->_EventEmitter_emit($event, $args);
      return;
    }

    $method = '_event_'.preg_replace('/[^a-z0-9_]+/i', '_', $event);

    if(!method_exists($this, $method)) {
      $this->_EventEmitter_emit($event, $args);
      return;
    }

    $prev_event = $this->_prem_current_event;
    $this->_prem_current_event = $event;
    $this->$method(...$args);
    $this->_prem_current_event = $prev_event;
  }

  protected function _emitter() {
    return function(string $event, array $args=[]) {
      $this->_emit($event, $args);
    };
  }
}

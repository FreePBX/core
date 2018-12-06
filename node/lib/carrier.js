/**
 * https://github.com/pgte/carrier/blob/master/LICENSE
 */
var util   = require('util'),
    events = require('events'),
    dgram  = require('dgram');

function Carrier(reader, listener, encoding, separator) {
  var self = this;
  var eventName = (reader.constructor === dgram.Socket) ? 'message' : 'data';
  var buffer = '';

  self.reader = reader;
  encoding = encoding || 'utf-8';
  separator = separator || /\r?\n/;

  if (listener) {
    self.addListener('line', listener);
  }

  if (typeof reader.setEncoding === 'function') {
    reader.setEncoding(encoding);
  }

  var defferredLineEmit = function(line) {
    process.nextTick(function() {
      self.emit('line', line);
    });
  };

  var onData = function carrierOnData(data) {
    if (data instanceof Buffer) {
      data = data.toString(encoding);
    }

    data = buffer + data;

    var lines = data.split(separator);
    var length = lines.length - 1;

    buffer = lines[length] || '';

    for (var i = 0; i < length; i++) {
      defferredLineEmit(lines[i]);
    }
  };

  var onEnd = function onEnd() {
    if (buffer) {
      defferredLineEmit(buffer);
      buffer = '';
    }

    process.nextTick(function() {
      self.emit('end');
    });
  };

  reader.on(eventName, onData);
  reader.on('end', onEnd);
}

util.inherits(Carrier, events.EventEmitter);

exports.carry = function(reader, listener, encoding, separator) {
  return new Carrier(reader, listener, encoding, separator);
}
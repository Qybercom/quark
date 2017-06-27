/**
 * JS part of SaaS PHP framework
 *
 * @type {Quark}
 */
var Quark = Quark || {};

/**
 * Quark media API
 */
Quark.Media = {};

/**
 * @param {object} opt
 * @param {Function} success
 * @param {Function} error
 *
 * @return {*}
 */
Quark.Media.Get = function (opt, success, error) {
	navigator.getUserMedia = navigator.getUserMedia
			 || navigator.webkitGetUserMedia
	  		 || navigator.mozGetUserMedia
	  		 || navigator.msGetUserMedia;
	
	return navigator.getUserMedia(opt, success || function () {}, error || function () {});
};

/**
 * @return {AudioContext}
 *
 * @constructor
 */
Quark.Media.AudioContext = function () {
	window.AudioContext = window.AudioContext || window.webkitAudioContext;

	return new AudioContext();
};

/**
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.Media.Video = function (selector, opt) {
	opt = opt || {};
		opt.ready = opt.ready == undefined ? false : opt.ready;
		opt.play = opt.play == undefined ? false : opt.play;
	
	var that = this;
	
	that.Elem = $(selector);
	
	that.Position = 0.0;
	that.Duration = 0.0;
	that.Stream = null;
	that.Timer = null;
	
	that.Elem.on('loadedmetadata', function () {
		var video = $(this)[0];
		that.Duration = video.duration;
		
		if (opt.ready instanceof Function)
			opt.ready(video, that.Duration);
	});
	
	that.Elem.on('timeupdate', function () {
		var video = $(this)[0];
		that.Position = video.currentTime;
		
		if (opt.play instanceof Function)
			opt.play(video, that.Position);
	});
	
	/**
	 * @param src
	 *
	 * @return {void}
	 */
	that.Src = function (src) {
		that.Elem[0].src = src;
	};
	
	/**
	 * @return {void}
	 */
	that.Play = function () {
		that.Elem[0].play();
	};
	
	/**
	 * @return {void}
	 */
	that.Pause = function () {
		that.Elem[0].pause();
	};
	
	/**
	 * @return {void}
	 */
	that.Stop = function () {
		that.Pause();
		
		if (that.Stream != null)
			that.Stream.stop();
	};
	
	/**
	 * @return {ClientRect}
	 */
	that.Size = function () {
		return that.Elem[0].getBoundingClientRect();
	};
	
	/**
	 * @return {string}
	 */
	that.Played = function () {
		return (that.Duration == 0 ? 0 : ((that.Position / that.Duration) * 100).toFixed(2)) + ' %';
	};
	
	/**
	 * @param {string=} type = 'image/png'
	 * @param {number=} scaleFactor = 1
	 *
	 * @return {string}
	 */
	that.Frame = function (type, scaleFactor) {
		type = type || 'image/png';
		scaleFactor = scaleFactor == undefined ? 1 : scaleFactor;
		
		var frame = document.createElement('canvas'),
			context = frame.getContext('2d'),
			size = {
				w: that.Elem[0].videoWidth * scaleFactor,
				h: that.Elem[0].videoHeight * scaleFactor
			};
		
		frame.width = size.w;
		frame.height = size.h;
		
		context.drawImage(that.Elem[0], 0, 0, size.w, size.h);
		
		return frame.toDataURL(type);
	};
	
	/**
	 * @param {object=} opt
	 */
	that.FromCamera = function (opt) {
		opt = opt || {};
		
		Quark.Media.Get(
			{
				video: opt.video || true,
				audio: opt.audio || false
			},
			function (stream) {
				that.Stream = stream;
				that.Src(Quark.ObjectURL(stream));
				that.Play();
				
				if (opt.success instanceof Function)
					opt.success(stream);
				
				if (opt.capture instanceof Function)
					that.Timer = setInterval(opt.capture, opt.capturePeriod || 100);
			},
			opt.error
		);
	}
};

/**
 * @param selector
 * @param opt
 *
 * @constructor
 */
Quark.Media.Audio = function (selector, opt) {
	opt = opt || {};
	
	var that = this;
	
	that.Track = [];
	that.Recorder = new Quark.Media.Audio.Recorder(null, opt.record || {});
	
	/**
	 * @param opt
	 */
	that.FromMicrophone = function (opt) {
		opt = opt || {};
		
		Quark.Media.Get(
			{
				video: opt.video || false,
				audio: opt.audio || true
			},
			function (stream) {
				var AudioContext = window.AudioContext || window.webkitAudioContext;
				var audioCtx = new AudioContext();
				
				var oscillatorNode = audioCtx.createOscillator();
				var gainNode = audioCtx.createGain();
				var finish = audioCtx.destination;
				
        		that.Recorder.Init(audioCtx.createMediaStreamSource(stream));
			}
		);
	};
};

/**
 * @param {*=} stream
 * @param {object=} opt
 *
 * @constructor
 */
Quark.Media.Audio.Recorder = function (stream, opt) {
	opt = opt || {};
		opt.buffer = opt.buffer || 4096;
		opt.channels = opt.channels || {};
			opt.channels.input = opt.channels.input || 2;
			opt.channels.output = opt.channels.output || 2;
	
	var that = this;
	
	that.Active = false;
	that.Buffer = [];
	that.Length = 0;
	that.Stream = null;
	that.Context = null;
	that.Node = null;
	
	/**
	 * @param stream
	 */
	that.Init = function (stream) {
		that.Stream = stream;
		that.Context = that.Stream.context;
		that.Node = (that.Context.createScriptProcessor || that.Context.createJavaScriptNode)
					.call(that.Context, opt.buffer, opt.channels.input, opt.channels.output);
		
		that.Node.onaudioprocess = function (e) {
			if (!that.Active) return;
	
			var buffer = [], c = 0, lengths = [];
			
			while (c < opt.channels.input) {
				buffer.push(e.inputBuffer.getChannelData(c));
				c++;
			}
			
			c = 0;
			
			while (c < opt.channels.input) {
				that.Buffer[c].push(buffer[c]);
				lengths[c] = buffer[c].length;
				
				c++;
			}
			
			that.Length += lengths.max();
			
			if (opt.capture instanceof Function)
				opt.capture(buffer);
		};
		
		that.Stream.connect(that.Node);
		that.Node.connect(that.Context.destination);
	};
	
	/**
	 * Start recording
	 */
	that.Start = function () {
		that.Buffer = [];
		that.Length = 0;
		that.Active = true;
		
		var c = 0;
		
		while (c < opt.channels.input) {
			that.Buffer[c] = [];
			c++;
		}
		
		if (opt.start instanceof Function)
			opt.start();
	};
	
	/**
	 * Stop recording
	 */
	that.Stop = function () {
		that.Active = false;
		
		if (opt.stop instanceof Function)
			opt.stop(that.ExportWAV());
	};

            function mergeBuffers(recBuffers, recLength) {
                var result = new Float32Array(recLength);
                var offset = 0;
                for (var i = 0; i < recBuffers.length; i++) {
                    result.set(recBuffers[i], offset);
                    offset += recBuffers[i].length;
                }
                return result;
            }

            function interleave(inputL, inputR) {
                var length = inputL.length + inputR.length;
                var result = new Float32Array(length);

                var index = 0,
                    inputIndex = 0;

                while (index < length) {
                    result[index++] = inputL[inputIndex];
                    result[index++] = inputR[inputIndex];
                    inputIndex++;
                }
                return result;
            }

            function floatTo16BitPCM(output, offset, input) {
                for (var i = 0; i < input.length; i++, offset += 2) {
                    var s = Math.max(-1, Math.min(1, input[i]));
                    output.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
                }
            }

            function writeString(view, offset, string) {
                for (var i = 0; i < string.length; i++) {
                    view.setUint8(offset + i, string.charCodeAt(i));
                }
            }

            function encodeWAV(samples) {
                var buffer = new ArrayBuffer(44 + samples.length * 2);
                var view = new DataView(buffer);
                var sampleRate = 192600;

                /* RIFF identifier */
                writeString(view, 0, 'RIFF');
                /* RIFF chunk length */
                view.setUint32(4, 36 + samples.length * 2, true);
                /* RIFF type */
                writeString(view, 8, 'WAVE');
                /* format chunk identifier */
                writeString(view, 12, 'fmt ');
                /* format chunk length */
                view.setUint32(16, 16, true);
                /* sample format (raw) */
                view.setUint16(20, 1, true);
                /* channel count */
                view.setUint16(22, opt.channels.input, true);
                /* sample rate */
                view.setUint32(24, sampleRate, true);
                /* byte rate (sample rate * block align) */
                view.setUint32(28, sampleRate * 4, true);
                /* block align (channel count * bytes per sample) */
                view.setUint16(32, opt.channels.input * 2, true);
                /* bits per sample */
                view.setUint16(34, 16, true);
                /* data chunk identifier */
                writeString(view, 36, 'data');
                /* data chunk length */
                view.setUint32(40, samples.length * 2, true);

                floatTo16BitPCM(view, 44, samples);

                return view;
            }
	
	that.ExportWAV = function () {
		var buffers = [];
                for (var channel = 0; channel < opt.channels.input; channel++) {
                    buffers.push(mergeBuffers(that.Buffer[channel], that.Length));
                }
                var interleaved = undefined;
                if (opt.channels.input === 2) {
                    interleaved = interleave(buffers[0], buffers[1]);
                } else {
                    interleaved = buffers[0];
                }
                var dataview = encodeWAV(interleaved);
		return new Blob([dataview], { type: 'audio/wav' });
   };
	
	/**
	 * @return {Blob}
	 */
	that.ExportWAV1 = function () {
		var buffers = [], c = 0, i = 0, offset = 0, result = null, tmp = new Float32Array(4096);
		
		while (c < opt.channels.input) {
			result = new Float32Array(that.Length);
			offset = 0;
			i = 0;
			
			while (i < that.Buffer[c].length) {
				result.set(that.Buffer[c][i], offset);
				offset += that.Buffer[c][i].length;
				
				i++;
			}
			
			buffers.push(result);
			
			c++;
		}
		
		var output = Quark.Media.Audio.Interleave(buffers);
		
		return Quark.Media.Audio.WAV.Encode(
			output,
			that.Context.sampleRate,
			opt.channels.output
		);
	};
	
	if (stream) that.Init(stream);
};

/**
 * @param input
 *
 * @return {Float32Array}
 */
Quark.Media.Audio.Interleave = function (input) {
	var length = 0, c = 0, i = 0, index = 0;
	
	while (c < input.length) {
		length += input[c].length;
		c++;
	}
	
	var result = new Float32Array(length);
	
	while (i < length) {
		c = 0;
		
		while (c < input.length) {
			result[i++] = input[c][index];
			c++;
		}
		
		index++;
	}
	
	return result;
};

/**
 * @type {{}}
 */
Quark.Media.Audio.WAV = {};



            function floatTo16BitPCM(output, offset, input) {
                for (var i = 0; i < input.length; i++, offset += 2) {
                    var s = Math.max(-1, Math.min(1, input[i]));
                    output.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
                }
            }

            function writeString(view, offset, string) {
                for (var i = 0; i < string.length; i++) {
                    view.setUint8(offset + i, string.charCodeAt(i));
                }
            }

Quark.Media.Audio.WAV.Encode = function (samples, sampleRate, numChannels) {
	var buffer = new ArrayBuffer(44 + samples.length * 2);
                var view = new DataView(buffer);

                /* RIFF identifier */
                writeString(view, 0, 'RIFF');
                /* RIFF chunk length */
                view.setUint32(4, 36 + samples.length * 2, true);
                /* RIFF type */
                writeString(view, 8, 'WAVE');
                /* format chunk identifier */
                writeString(view, 12, 'fmt ');
                /* format chunk length */
                view.setUint32(16, 16, true);
                /* sample format (raw) */
                view.setUint16(20, 1, true);
                /* channel count */
                view.setUint16(22, numChannels, true);
                /* sample rate */
                view.setUint32(24, sampleRate, true);
                /* byte rate (sample rate * block align) */
                view.setUint32(28, sampleRate * 4, true);
                /* block align (channel count * bytes per sample) */
                view.setUint16(32, numChannels * 2, true);
                /* bits per sample */
                view.setUint16(34, 16, true);
                /* data chunk identifier */
                writeString(view, 36, 'data');
                /* data chunk length */
                view.setUint32(40, samples.length * 2, true);

                floatTo16BitPCM(view, 44, samples);

                return new Blob([view], {type: 'audio/wav'});
};

/**
 * https://webaudiodemos.appspot.com/AudioRecorder/js/recorderjs/recorderWorker.js
 *
 * @param samples
 * @param rate
 * @param {number=} channels = 2
 *
 * @return {Blob}
 */
Quark.Media.Audio.WAV.Encode1 = function (samples, rate, channels) {
	channels = channels || 2;
	
	var view = Quark.DataView.WithBuffer(44 + samples.length * 2);
	
	view.WriteString(0, 'RIFF');							 // RIFF identifier
	view.Buffer.setUint32(4, 32 + samples.length * 2, true); // file length
	view.WriteString(8, 'WAVE');						 	 // RIFF type
	view.WriteString(12, 'fmt ');							 // format chunk identifier
	view.Buffer.setUint32(16, 16, true);					 // format chunk length
	view.Buffer.setUint16(20, 1, true);						 // sample format (raw)
	view.Buffer.setUint16(22, channels, true);				 // channel count
	view.Buffer.setUint32(24, rate, true);					 // sample rate
	view.Buffer.setUint32(28, rate * (channels * 2), true);	 // byte rate (sample rate * block align)
	view.Buffer.setUint16(32, channels * 2, true);			 // block align (channel count * bytes per sample)
	view.Buffer.setUint16(34, 16, true);					 // bits per sample
	view.WriteString(36, 'data'); 							 // data chunk identifier
	view.Buffer.setUint32(40, samples.length * 2, true);	 // data chunk length

	view.PCM16Bit(44, samples);
	
	console.log('view', view, samples);

	return new Blob([view.Buffer], {type: 'audio/wav'});
};
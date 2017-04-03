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
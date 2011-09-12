
var Log = function( pVal ){
	if( typeof console != "undefined" )
        console.log( pVal );
}

var HumanTimes = new Class({
	Implements: [Events, Options],
	
	options: {
		delay: 5000
	},
	
	initialize: function(options)
	{
		this.setOptions(options);
		this.watchedElements = [];
		this.numWatchedElements = 0;
		
		this.secsDay = 86400;
		this.secsHour = 3600;
		this.secsMinute = 60;
		
		this._tick.bind(this).delay(1);
	},
	
	_tick: function()
	{
		this.watchedElements.each(function(obj, index) {
			var readableTime = this._humanReadable(obj.time);
			obj.span.set('text', readableTime);
		}.bind(this));
		
		this._tick.bind(this).delay(this.options.delay);
	},
	
	_humanReadable: function(time)
	{
		var currentTime = Math.round((new Date().getTime() - Date.UTC(1970, 0, 1)) / 1000);
		var startOfDay = new Date().clearTime().getTime() / 1000;
		var diffTime = currentTime - time;
		
		var diffDate = new Date();
		diffDate.setTime(diffTime * 1000); // Set timestamp
		diffDate.decrement('hour', parseInt(diffDate.get("gmtoffset").substring(0, 3), 10)); // Set to GMT 0 time
		
		var timeDate = new Date();
		timeDate.setTime(time * 1000);
		
		var timeDateE = timeDate.format("%d");
		if(timeDateE.substr(0, 1) == "0")
			timeDateE = timeDateE.substr(1);
		var timeDateK = timeDate.format("%H");
		if(timeDateK.substr(0, 1) == "0")
			timeDateK = timeDateK.substr(1);
		
		// More than a week ago
		if(time < startOfDay - 6*this.secsDay)
			return timeDate.format("%A ") + timeDateE + timeDate.format(" %B %Y ") + timeDateK + timeDate.format(":%M");
		if(time < startOfDay - this.secsDay)
			return timeDate.format("%A ") + timeDateK + timeDate.format(":%M");
		if(time < startOfDay)
			return "yesterday " + timeDateK + timeDate.format(":%M");
		if(diffTime > 6 * this.secsHour)
			return "today " + timeDateK + timeDate.format(":%M");
		if(diffTime > 2 * this.secsHour)
			return diffDate.get("hours")+" hours ago";
		if(diffTime > this.secsHour)
			return "about an hour ago";
		if(diffTime > 2 * this.secsMinute)
			return diffDate.get("minutes")+" minutes ago";
		if(diffTime > this.secsMinute)
			return "about a minute ago";
		else
			return "a few seconds ago";
	},
	
	addElement: function(id, time)
	{
		var span = $(id).getElement('span.album-modified-time');
		
		this.watchedElements[this.numWatchedElements++] = {
			id: id,
			span: span,
			time: time
		};
	}
	
});

HumanTimes = new HumanTimes();

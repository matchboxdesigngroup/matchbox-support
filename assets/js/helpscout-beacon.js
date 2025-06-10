(function (e, t, n) {
	function a() {
		var e = t.getElementsByTagName('script')[0],
			n = t.createElement('script');
		(n.type = 'text/javascript'),
			(n.async = !0),
			(n.src = 'https://beacon-v2.helpscout.net'),
			e.parentNode.insertBefore(n, e);
	}
	// Initialize Beacon and add methods to the readyQueue
	(e.Beacon = n =
		function (t, n, a) {
			e.Beacon.readyQueue.push({ method: t, options: n, data: a });
		}),
		(n.readyQueue = []);
	// Load the script if the document is complete
	if ('complete' === t.readyState) {
		return a();
	}
	// Attach load event
	e.attachEvent
		? e.attachEvent('onload', a)
		: e.addEventListener('load', a, !1);
})(window, document, window.Beacon || function () {});
// Initialize the Beacon with the beacon_id from localized parameters
if (
	typeof matchbox_helpscout_params !== 'undefined' &&
	matchbox_helpscout_params.beacon_id
) {
	window.Beacon('init', matchbox_helpscout_params.beacon_id);
} else {
	console.warn(
		'HelpScout Beacon ID is not configured. Please set it in WordPress admin or wp-config.php.'
	);
}

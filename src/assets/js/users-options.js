function set_option(key, value) {
	jQuery.ajax({
		url: '/ajax/user-set-option',
		data: {
			key: key,
			value: value,
		},
		method: 'POST'
	}).done(function(data) {
	});
}

function get_option(key, callback, defaultValue) {
	jQuery.ajax({
		url: '/ajax/user-get-option',
		data: {
			key: key
		},
		method: 'POST'
	}).done(function(data) {
		if (null === data.value) data.value = defaultValue;
		callback(data.value);
	});
}
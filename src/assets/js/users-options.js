function set_option(key, value) {
	jQuery.ajax({
		url: '/ajax/user-set-option',
		data: {
			key: key,
			value: value,
		},
		method: 'POST'
	}).done(function (data) {
	});
}
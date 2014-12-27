$('.fpbx-submit').submit(function() {
	var theForm = document.ampuser,
		username = theForm.username;

	if (username.value == "") {
		return warnInvalid(username, "<?php echo _("Username must not be blank")?>");
	} else if (!username.value.match('^[a-zA-Z][a-zA-Z0-9]+$')) {
		return warnInvalid(username, "<?php echo _("Username cannot start with a number, and can only contain letters and numbers")?>");
	}
	return true;
});
var el = document.getElementById('enabled');
var enabled = Sortable.create(el,{
	group:"Enabled",
	sort: true,
	disanled: false,
	store: null,
	animation:150,
});
var el = document.getElementById('disabled');
var disabled = Sortable.create(el);

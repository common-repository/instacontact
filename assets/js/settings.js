jQuery(document).ready(function($) {
  $('form.ajax-form').submit(function(event) {
    event.preventDefault();
    var arr = $( this ).serializeArray();

    var data = {
      'action': 'instacontact-connect',
      // '_wpnonce': arr.find(function(o) { return o.name == '_wpnonce' }).value,
      // '_wp_http_referer': arr.find(function(o) { return o.name == '_wp_http_referer' }).value,
      'email': arr.find(function(o) { return o.name == "instacontact[email]" }).value,
      'api_key': arr.find(function(o) { return o.name == "instacontact[api_key]" }).value,
    }
    $.post(ajaxurl, data, function(response) {
      var json = JSON.parse(response)
      if (json.status == 'success') {
        document.querySelector('#updated').hidden = false
        document.querySelector('#error').hidden = true
      } else {
        document.querySelector('#updated').hidden = true
        var error = document.querySelector('#error')
            error.innerHTML = '<p>' + json.message + '</p>'
            error.hidden = false
      }
      console.log('response', JSON.parse(response))
    })
    return false;
  })
})
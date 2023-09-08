$( document ).ready(function() {
    //Toggle password
    $("#toggle-password").click(function() {

        $(this).toggleClass("fa-eye fa-eye-slash");
        var input = $($("#password-field"));
        if (input.attr("type") == "password") {
          input.attr("type", "text");
        } else {
          input.attr("type", "password");
        }
      });
});
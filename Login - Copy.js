$(document).ready(function() {
  // Show login or signup form based on button clicks
  $("#admin-btn, #famo-btn").click(function() {
      $(".form.log").show();
      $(".form.reg").hide();
  });

  $("#guest-btn").click(function() {
      $(".form.log").hide();
      $(".form.reg").show();
  });

  // Show sign up form
  $("#signup-btn").click(function() {
      $(".form.log").hide();
      $(".form.reg").show();
  });

  // Show login form
  $("#create-btn, #oklogin").click(function() {
      $(".form.log").show();
      $(".form.reg").hide();
  });

  // Show sign up form from login form
  $("#oksignup").click(function() {
      $(".form.log").hide();
      $(".form.reg").show();
  });
});

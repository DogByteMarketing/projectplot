jQuery(document).ready(function ($) {

  const { __, _x, _n, _nx } = wp.i18n;

  initTabs();

  function initTabs() {
    $(".nav-links li a").on("click", function() {
      let selected = $(this).data('section');

      $(".nav-links li a").each(function() {
        let section = $(this).data('section');

        $(this).removeClass("active");

        if (selected == section) {
          $(this).addClass("active");
        }
      });

      $(".tabs .tab").each(function() {
        let tab = $(this);

        $(this).hide();

        if (tab.hasClass(selected)) {
          tab.show();
        }
      });
    });
  }

});
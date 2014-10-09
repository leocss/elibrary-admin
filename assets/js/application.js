(function($) {

  $(function() {
    // Manage users page
    $('.delete-user').on('click', function (e) {
      var $this = $(this);
      var $container = $this.parents('.user-item');
      var id = $this.attr('id').replace('user-', '');

      $this.button('loading');

      $.ajax({
        url: app.base_url + 'ajax/users/' + id,
        type: 'delete',
        dataType: 'json',
        success: function(res) {
          $container.fadeOut('slow').done(function() {
            $container.remove();
          });
        }
      });

      return false;
    });
  });

})(jQuery);
(function ($) {
    var setUserTypeInputPlaceholder = function (type) {
        var $input_unique_id = $('#input-unique-id');

        switch (type) {
            case 'staff':
                $input_unique_id.attr('placeholder', 'Enter staff id here...');
                break;
            case 'student':
                $input_unique_id.attr('placeholder', 'Enter student\'s matric number here...');
                break;
            case 'admin':
                $input_unique_id.attr('placeholder', 'Enter admins\'s staff id');
                break;
            default:
                $input_unique_id.attr('placeholder', 'Select a user type first...');
                break;
        }
    }

    $(function () {

        setUserTypeInputPlaceholder($('#input-user-type').val());
        // Create / Delete user page
        $('#input-user-type').on('change', function (e) {
            var $this = $(this);
            setUserTypeInputPlaceholder($this.val());
        });

        $('#btn-generate-password').on('click', function () {
            // TODO: Generate random value.
            $('#input-user-password').val('123456');
        });

        // Manage users [delete user]
        $('.delete-user').on('click', function (e) {
            var $this = $(this);
            var $container = $this.parents('.user-item');
            var id = $this.attr('id').replace('user-', '');

            if (confirm('Are you sure you want to delete this user?')) {
                $this.button('loading');

                $.ajax({
                    url: app.base_url + 'ajax/users/' + id,
                    type: 'delete',
                    dataType: 'json',
                    success: function (res) {
                        $container.fadeOut('slow').done(function () {
                            $container.remove();
                        });
                    }
                });
            }

            return false;
        });

        // Manage articles
        $('.delete-article').on('click', function (e) {
            var $this = $(this);
            var $container = $(this).parents('tr.article-item');
            var id = $this.attr('id').replace('delete-article-', '');

            if (confirm('Are you sure you want to delete this article?')) {
                $.ajax({
                    url: app.base_url + 'ajax/articles/' + id,
                    type: 'delete',
                    dataType: 'json',
                    success: function (res) {
                        $container.fadeOut('slow').done(function () {
                            $container.remove();
                        });
                    }
                });
            }

            return false;
        });

        // TODO Manage questions... Delete course button clicked
        $('.delete-course').on('click', function (e) {
            return false;
        });

        // TODO Create question form.. show and hide options type


        // pay user bill
        $('.btn-pay-bill').on('click', function () {
            var $modal = $(this).parents('.pay-bill-modal');
            var $input = $modal.find('.pay-bill-input');
            var id = $modal.attr('id').replace('pay-bill-modal-', '');
            var amount = $input.val();
            var self = this;

            $.ajax({
                url: app.base_url + 'ajax/users/' + id + '/debt',
                type: 'delete',
                dataType: 'json',
                data: {
                    'amount': amount
                },
                beforeSend: function () {
                    $(self).button('loading');
                },
                success: function (data) {
                    if (data.success)
                    {
                        window.location = app.base_url + 'users';
                    }
                }
            }).always(function () {
                $(self).button('reset');
            });
        });
    });

    var $tof = $('#question-options-true-or-false').clone();
    var $mc = $('#question-options-multiple-choice').clone();

    $tof.end().remove();
    $mc.end().remove();

    // TODO Create question form.. show and hide options type
    $('#question-type-select').on('change', function () {
      var $qopts = $('#question-options');
      switch ($(this).val()) {
        case 'multiple_choice':
          $qopts.find('> .row').html('').append($mc);
          break;
        case 'true_or_false':
          $qopts.find('> .row').html('').append($tof);
          break;
        default:

          break;
      }
    });
  });

})(jQuery);
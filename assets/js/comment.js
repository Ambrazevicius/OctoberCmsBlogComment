$('.send-comment').each(function () {
    $(this).click(function () {
        $(this).request('onComment', {
            success: function (data) {

                $('#message').html(data.message);

                setTimeout(function () {
                    $('#message').html('');
                }, 5000);

                var commentBlock = $('#all-comments');
                if (data.child) {
                    commentBlock = $('#child-comments-' + data.id);
                    if (!commentBlock.length) {
                        $('#block-' + data.id).append('<ul id="child-comments-' + data.id + '"></ul>');
                        commentBlock = $('#child-comments-' + data.id);
                    }
                }

                $('#tallpro_comments_comment-text').val('');

                $('.comment-field').each(function (e) {
                    $(this).val('');
                })

                $('.name-field').each(function (e) {
                    if ($(this).length !== 0) {
                        $(this).val('');
                    }
                });

                $('.email-field').each(function (e) {
                    if ($(this).length !== 0) {
                        $(this).val('');
                    }
                });

                $('.reply-field').each(function () {
                    $(this).val('');
                });
                $('#reply-to-' + data.id).hide();

                commentBlock.prepend(data.content);

            }
        });
    })
})

$('.reply-button').each(function () {
    $(this).click(function (e) {

        e.preventDefault();
        var id = $(this).attr('data-id');
        var reply_to = $('#reply-to-' + id);

        reply_to.show();

    })
})
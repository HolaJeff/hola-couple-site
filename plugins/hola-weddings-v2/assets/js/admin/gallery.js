jQuery.fn.extend({
    hwgallery: function () {

        var _ = this,
            $imageList = _.find('ul.hw-gallery-list'),
            $addButton = _.find('.hw-gallery-add'),
            gallery_frame;

        function syncIds() {
            var ids = [];
            $imageList.find('li.hw-gallery-image').each(function () {
                ids.push(jQuery(this).data('attachment_id'));
            });
            _.find('input.hw-gallery-field').val(ids.join(',')).trigger('change');
        }

        $addButton.on('click', function (event) {
            event.preventDefault();

            if (gallery_frame) {
                gallery_frame.open();
                return;
            }

            gallery_frame = wp.media({
                title: $addButton.data('choose') || 'Select Images',
                button: {
                    text: $addButton.data('update') || 'Add to gallery'
                },
                states: [
                    new wp.media.controller.Library({
                        title: $addButton.data('choose') || 'Select Images',
                        filterable: 'all',
                        multiple: true
                    })
                ]
            });

            gallery_frame.on('select', function () {
                var selection = gallery_frame.state().get('selection');

                selection.map(function (attachment) {
                    attachment = attachment.toJSON();

                    if (attachment.id) {
                        var thumb = attachment.sizes && attachment.sizes.medium
                            ? attachment.sizes.medium.url
                            : attachment.url;

                        $imageList.append(
                            '<li class="hw-gallery-image position-relative" data-attachment_id="' + attachment.id + '">' +
                                '<img class="rounded" src="' + thumb + '" style="width:150px;height:150px;object-fit:cover;" />' +
                                '<button type="button" class="hw-gallery-remove position-absolute btn btn-danger top-0 end-0 me-2 mt-2">&times;</button>' +
                            '</li>'
                        );
                    }
                });

                syncIds();
            });

            gallery_frame.open();
        });

        $imageList.on('click', '.hw-gallery-remove', function () {
            jQuery(this).closest('li.hw-gallery-image').remove();
            syncIds();
        });
    }
});

jQuery(function ($) {
    $('.hw-gallery').each(function () {
        $(this).hwgallery();
    });
});

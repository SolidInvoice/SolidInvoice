define(['jquery', 'marionette', 'lodash', 'routing', 'translator', 'template', 'jquery.uploadify'], function($, Mn, _, Routing, __, Template) {
    return Mn.Object.extend({
        id: null,
        value: null,
        sessionId: null,
        el: null,
        uploadEl: null,
        queueEl: null,
        absolutePath: null,
        initialize: function (id, sessionId, swfPath, absolutePath, value) {
            this.id = id;
            this.value = value;
            this.sessionId = sessionId;
            this.absolutePath = absolutePath;

            if (!_.isEmpty(value)) {
                this.uploadSuccess(null, {'file': value});
            }

            $('#' + this.id).hide();

            /* The init method needs to be wrapped in a setTimeout to prevent a race condition
             in Chrome (http://stackoverflow.com/a/25135325/833811) */
            setTimeout(_.bind(function() {
                $('#' + id + '-uploader').uploadify({
                    'swf'            : swfPath,
                    'uploader'       : Routing.generate('_image_upload'),
                    'formData'       : {"sessionId": sessionId},
                    'buttonText'     : __('Select File'),
                    'multi'          : false,
                    'onUploadSuccess': _.bind(this.uploadSuccess, this)
                });

            }, this), 0);
        },
        uploadSuccess: function (file, data) {
            if ($.type(data) !== 'object') {
                data = $.parseJSON(data);
            }

            var uploadEl = $('#' + this.id + '-uploader'),
                el = $('#' + this.id),
                queueEl = $('#' + this.id + '-uploader-queue'),
                html = $(Template['upload']({'imagePath' : this.absolutePath + data.file}));

            html.find('.remove-image').on('click', function(evt) {
                evt.preventDefault();
                var a = $(this);

                a.parents('div.image-thumbnail-preview').remove();

                uploadEl.show();
                el.val('');
            });

            $('.image-thumbnail-preview').remove();

            el.val(data.file);
            queueEl.html('');
            uploadEl.after(html);
        }
    });
});
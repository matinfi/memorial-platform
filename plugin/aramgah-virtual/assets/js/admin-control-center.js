jQuery(function ($) {
  $(document).on('click', '.avam-cc-media-pick', function (event) {
    event.preventDefault();
    var button = $(this);
    var target = $('#' + button.data('target'));
    var type = button.data('type') || 'image';
    var frame = wp.media({
      title: 'انتخاب رسانه',
      button: { text: 'استفاده از این فایل' },
      multiple: false,
      library: { type: type }
    });
    frame.on('select', function () {
      var item = frame.state().get('selection').first().toJSON();
      target.val(item.id).trigger('change');
    });
    frame.open();
  });
});

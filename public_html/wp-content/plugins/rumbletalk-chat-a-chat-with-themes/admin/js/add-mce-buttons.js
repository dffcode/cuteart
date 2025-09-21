(function () {
    /* Register the buttons */
    tinymce.create(
        'tinymce.plugins.RumbleTalkButtons',
        {
            init: function (editor, url) {
                /**
                 * Inserts [shortcode] content
                 */
                editor.addButton(
                    'button_rumbletalk_chat',
                    {
                        title: 'Insert a Chat Room \(Rumbletalk\)',
                        image: url + '/../images/tinymce-button.jpg',
                        onclick: function () {
                            editor.selection.setContent('[rumbletalk-chat hash="insert here your chat hash"]');
                        }
                    }
                );
            },
            createControl: function () {
                return null;
            }
        }
    );

    /* Start the buttons */
    tinymce.PluginManager.add('rumbletalk_mce_buttons', tinymce.plugins.RumbleTalkButtons);
})();

<div class="info">
    <div class="shortcode-info">
        <h3>What is a [shortcode]?</h3>
        <p>
            A <b>[shortcode]</b> is a WordPress code that can be used to quickly, and easily, add your chat
            into your pages by simply copying and pasting text (or "short code") directly into your text editor.
            <br>
            Simply copy the line, e.g. "<b style="user-select: all;">[rumbletalk-chat]</b>" into your text editor,
            and RumbleTalk will take care of the rest.
        </p>
        <p>
            * It is recommended to use your specific chat [shortcode]
            (found under each chat edit box, above this paragraph)
            <br>
            The available attributes for the [shortcode] are:
            <b>hash</b>, <b>width</b>, <b>height</b>, <b>members</b>, <b>floating</b>
        </p>

        <h3> Attribute descriptions: </h3>

        <table>
            <tr>
                <td>Chat name:</td>
                <td>The name of the chat - for personal use and SEO</td>
            </tr>

            <tr>
                <td>Hash:</td>
                <td>This is a unique 8 characters string (can be found in the top right gray area of chat square).
                    <br>
                    It is populated automatically once you create an account.
                </td>
            </tr>

            <tr>
                <td>Width:</td>
                <td>The width (in pixels) of your chat room.
                    <br>
                    You can set to a percentage (e.g. 40%) or leave blank to fill the width of the page.
                </td>
            </tr>

            <tr>
                <td>Height:</td>
                <td>The height of your chat room.
                    <br>
                    You may <b>not</b> use percentage in height
                    <br>
                    * If left blank, defaults to 500px
                </td>
            </tr>

            <tr>
                <td>Members:</td>
                <td>
                    Automatically logs the members of your community into your chat room (no need to supply user and
                    password).
                    <br>
                    If you wish to allow registered users, and guests, to log into your chat, you should uncheck the
                    <a href="https://rumbletalk.com/support/API_Auto_Login/" target="_blank">"Force SDK"</a>
                    option in the chat's settings.
                </td>
            </tr>

            <tr>
                <td>Login name:</td>
                <td>
                    The user's attribute used to log into your RumbleTalk chat.
                    <br>
                    If the chosen field is empty, the user's "Display name" is used
                </td>
            </tr>

            <tr>
                <td>Floating:</td>
                <td>
                    A floating toolbar chat.
                    <br>
                    It will appear on the right (or left) bottom corner of the page.
                    <br>
                    Configuring the side can be done in the chat settings under the "Floating" tab.
                </td>
            </tr>

            <tr>
                <td>&nbsp;</td>
                <td class="embed-options">
                    <div>Embedded chat</div>
                    <a href="<?php echo esc_url(plugins_url('../images/embed-option.png', __FILE__)) ?>" target="_blank">
                        <img src="<?php echo esc_url(plugins_url('../images/embed-option.png', __FILE__)) ?>"
                             width="150px" alt="Embed preview"/>
                    </a>
                    <div>Floating chat (toolbar)</div>
                    <a href="<?php echo esc_url(plugins_url('../images/embed-option-floating.png', __FILE__)) ?>"
                       target="_blank">
                        <img src="<?php echo esc_url(plugins_url('../images/embed-option-floating.png', __FILE__)) ?>"
                             width="150px" alt="Floating preview"/>
                    </a>
                </td>
            </tr>

            <tr>
                <td>Force login</td>
                <td>
                    Enabling this feature will log out the current user before trying to log them in.
                    This feature is useful in cases where multiple users are using the same device.
                    <br><br>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    If you have any questions about the plugin, feel free to
                    <a href="https://rumbletalk.com/about_us/contact_us/" target="_blank">contact us</a>
                    <br><br>
                </td>
            </tr>

        </table>

    </div>

    <h3>Other questions and sources</h3>

    <h3 id="question1">
        How to add your chat to a webpage:
        <img src="<?php echo esc_url(plugins_url('../images/expand.png', __FILE__)) ?>" alt="Expand"/>
    </h3>

    <div id="question1-body">
        <p>
            Add the exact text [rumbletalk-chat] to your visual editor where you want your chat to show. Save and
            publish.
        </p>
        <h4>NOTE:</h4>
        <p>
            In case you have more than one chat, you can add a specific chat using the chat's hash
            [rumbletalk-chat hash="<b>replace this with the hash</b>"].
        </p>
    </div>

    <hr/>

    <h3 id="question2">
        How to create a chat moderator:
        <img src="<?php echo esc_url(plugins_url('../images/expand.png', __FILE__)) ?>" alt="Expand"/>
    </h3>

    <div id="question2-body">
        <ol>
            <li>Go to the RumbleTalk chat plugin settings, and then click on the <b><i>Settings</i></b> button.</li>
            <li>Click <b><i>Users</i></b></li>
            <li>Then, <b><i>Add New User/Admin.</i></b></li>
            <li>Fill in the form and choose where your administrator will only be for that specific chat room or all
                your chat rooms.
            </li>
        </ol>
        <h4>NOTE:</h4>
        <p>
            Only add users in your chat room setting if you wish to set them as an administrator of the chat room. This
            will allow other WordPress users from your database to login with the chat automatically and set passwords
            only for the administrators.
        </p>

        <p>
            You can find this article on the knowledge base with image references:
            <a href="https://rumbletalk.com/blog/index.php/knowledge-base/how-to-create-a-chat-moderator-using-your-wordpress-rumbletalk-chat-plugin/"
               target="_blank">https://rumbletalk.com/blog/index.php/knowledge-base/how-to-create-a-chat-moderator-using-your-wordpress-rumbletalk-chat-plugin/</a>
        </p>
    </div>
    <hr/>

    <h3 id="question3">
        WordPress Message: "This chat room is for private users only"
        <img src="<?php echo esc_url(plugins_url('../images/expand.png', __FILE__)) ?>" alt="Expand"/>
    </h3>

    <div id="question3-body">
        <p>
            The message "<b>This chat room is for private users only</b>" means that Members mode is on and you are not
            logged in to the website.
        </p>

        <p>
            However, if you are still logged in to the website and seeing this error message, it seems that the chat
            login option settings were altered after turning on the Members mode.
        </p>

        <p>
            If you wish to integrate the specific room to your WordPress, uncheck Members and save. Refresh your page,
            and then check again Members and click save.
        </p>

        <h4>NOTE:</h4>
        <p>
            When you check Members mode on, it will automatically fix the settings in the login options. If you change
            these settings, issues will occur. Once you have the Members mode refreshed, it should work again properly.
        </p>

        <p>
            If you still have the same issue after resetting the Members mode, please send us the URL where the chat is
            placed so we can check the settings.
        </p>

        <p>
            You can find this article on the knowledge base with image references:
            <a href="https://rumbletalk.com/blog/index.php/knowledge-base/wordpress-message-this-chat-room-is-for-private-users-only/"
               target="_blank">https://rumbletalk.com/blog/index.php/knowledge-base/wordpress-message-this-chat-room-is-for-private-users-only/</a>
        </p>
    </div>
</div>

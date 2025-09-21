<div class="rumbletalk-main">
    <header>
        <img src="<?php echo esc_url(plugins_url('../images/rumbletalk-logo.png', __FILE__)) ?>" alt="RumbleTalk Logo">
        <h1 id="view-title"></h1>
    </header>

    <div id="invalid-token-page" class="rt-page">
        Invalid access token.
        <button type="button" id="solve-invalid-token" class="button-main" title="Try refetching a new token">
            Solve
        </button>
    </div>

    <div id="create-account-page" class="rt-page">
        <form id="create-account-form">
            <div class="form-data">
                <p>Type your email and choose a password</p>
                <input type="text" name="email" placeholder="Email">
                <input type="password" name="password" placeholder="Password">
                <input type="password" name="password_confirmation" placeholder="Confirm password">
            </div>

            <div class="form-note">
                <p>Free account entitles you to one chat room</p>
                <div class="error-note"></div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="button-main">
                    Create your account
                </button>
                <button type="button" class="anchor update-token-button">
                    Already have an account?
                </button>
            </div>
        </form>
    </div>

    <div id="manage-chats-page" class="rt-page">
        <div class="header">
            <div class="plan-info">
                <a href="https://cp.rumbletalk.com/login" title="Account email"
                   class="account-email" target="_blank"></a>
                <div>Your current plan:</div>
                <div>
                    <b class="users">5</b> Seats
                    <br>
                    <b class="rooms">1</b> Rooms
                    <br>
                    <b class="admins">1</b> Admins
                    <br>
                    <b class="keywords">1</b> Keywords
                </div>
                <button type="button" class="button-attention"
                        title="Upgrade your account, create more rooms and get more chat seats">
                    Upgrade 
                </button>
            </div>

            <div class="managing-buttons">
                <button type="button" class="button-main update-token-button">Integration</button>
                <button type="button" class="button-main" id="admin-panel">Admin Panel</button>
                <button type="button" class="button-main" id="manage-admins">Admins</button>
            </div>
        </div>

        <div id="chats">
            You don't have any chats,
            <button type="button" class="anchor">create one!</button>
        </div>

        <button type="button" id="create-new-chat" class="button-main" title="create a new chat">
            Add new chat +
        </button>
        <button type="button" id="refresh-chats" class="button-main" title="Reload chats data">
            Reload chats
            <span class="refresh-icon">&#x21bb;</span>
        </button>

        <?php include 'rumbletalk-admin-info-display.php'; ?>
    </div>

    <script id="update-token" type="text/template">
        <p>
            If you already have an account,
        </p>
        <ol>
            <li>
                Log into your
                <a href="https://cp.rumbletalk.com/login" target="_blank">RumbleTalk administration panel</a>
            </li>
            <li>Click on your name (or email at the top right corner)</li>
            <li>Select <b>Account settings</b></li>
            <li>Click on the <b>Integration</b> tab</li>
            <li>Copy your token <b>Key</b> and <b>Secret</b></li>
        </ol>

        <form id="update-token-form">
            <div>
                <label for="token-manage-key">Key</label>
                <input type="text" name="tokenKey" id="token-manage-key" value="">
            </div>
            <div>
                <label for="token-manage-secret">Secret</label>
                <input type="text" name="tokenSecret" id="token-manage-secret"
                       value="">
            </div>
        </form>
    </script>

    <script id="edit-chat" type="text/template">
        <div class="chat-name">
            <label>Chat name</label>
            <a class="chat-open" target="_blank" title="Open the chat">
                <img src="<?php echo esc_url(plugins_url('../images/open-in-new.svg', __FILE__)) ?>" alt="Open the chat">
            </a>
            <div class="hash-display" title="Hash"></div>
            <input type="text" name="name">
        </div>
        <div class="chat-dimensions">
            <label class="label-width">Width</label>
            <input type="text" name="width" placeholder="auto">
            <label class="label-height">Height</label>
            <input type="text" name="height" placeholder="500px">
        </div>
        <div class="chat-properties">
            <label>
                <input type="checkbox" name="membersOnly">
                Members
                <select name="loginName" class="login-name-options" title="Login name">
                    <option value="display_name">Display name</option>
                    <option value="user_login">Username</option>
                    <option value="nickname">Nickname</option>
                    <option value="first_name">First name</option>
                    <option value="last_name">Last name</option>
                    <option value="first_name last_name">First name + Last name</option>
                    <option value="last_name first_name">Last name + First name</option>
                    <option value="user_description">Display name + | + Bio</option>
                    <option value="username">Username + | + Bio</option>
                    <option value="nicknameBio">Nickname + | + Bio</option>
                    <option value="firstnameBio">First name + | + Bio</option>
                    <option value="lastnameBio">Last name + | + Bio</option>
                </select>
            </label>
            <label>
                <input type="checkbox" name="floating">
                Floating
            </label>
            <label>
                <input type="checkbox" name="forceLogin">
                Force login
            </label>
        </div>
        <div class="chat-buttons">
            <button type="button" class="button-sub chat-delete">Delete</button>
            <button type="button" class="button-sub chat-settings">Settings</button>
            <button type="submit" class="button-main">Save</button>
        </div>
        <div class="shortcode-bar">
            shortcode:
            <strong class="shortcode-handle"></strong><button id="shortcode-copy" type="button" class="anchor no-margin">
                copy
            </button>
        </div>
    </script>

    <script id="invalid-token" type="text/template">
        <p>Your access token is invalid.</p>
        <p>
            <button type="button" class="update-token-button anchor">Update</button>
            your key and secret.
        </p>
        <p>Or try <b>fetching</b> the token again</p>
    </script>
</div>
<?php include 'rumbletalk-admin-sidebar-display.html'; ?>

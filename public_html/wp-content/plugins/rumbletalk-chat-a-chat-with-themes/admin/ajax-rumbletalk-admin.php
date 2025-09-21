<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'includes/rumbletalk-sdk.php';

use RumbleTalk\RumbleTalkSDK;

class RumbleTalk_AJAX
{
    const NONCE_NAME = 'ajax-nonce';

    /**
     * @var string - RumbleTalk API token key
     */
    private $tokenKey;

    /**
     * @var string - RumbleTalk API token secret
     */
    private $tokenSecret;

    /**
     * @var RumbleTalkSDK - the API connection instance
     */
    private $rumbletalk;

    /**
     * RumbleTalkAJAX constructor.
     * @param $tokenKey
     * @param $tokenSecret
     */
    public function __construct($tokenKey, $tokenSecret)
    {
        $this->rumbletalk = new RumbleTalkSDK($tokenKey, $tokenSecret);

        $this->setToken($tokenKey, $tokenSecret);

        $this->updateAccessToken();
    }

    /**
     * Removes any non-alphanumeric characters from a string
     * i.e. characters that are not numbers or English alphabet case-insensitive
     * @param string $value
     * @return string
     */
    private function sanitizeAlphaNumeric($value)
    {
        if (!is_string($value)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9]+/', '', $value);
    }

    private function sanitizeChatData($data)
    {
        return array(
            'name' => sanitize_text_field($data['name']),
            'width' => $data['width']
                ? (int)$data['width']
                : '',
            'height' => $data['height']
                ? (int)$data['height']
                : '',
            'membersOnly' => $data['membersOnly'] == 'true',
            'loginName' => sanitize_text_field($data['loginName']),
            'floating' => $data['floating'] == 'true',
            'forceLogin' => $data['forceLogin'] == 'true'
        );
    }

    public function handleRequest()
    {
        # enable AJAX requests to editors and admins only
        if (!current_user_can('editor') && !current_user_can('administrator')) {
            return;
        }

        if (!wp_verify_nonce(@$_POST['nonce'], self::NONCE_NAME)) {
            return;
        }

        $data = stripslashes_deep(@$_POST['data']['data']);
        switch (@$_POST['data']['request']) {
            case 'GET_TOKEN':
                $this->getToken();
                break;

            case 'UPDATE_TOKEN':
                $this->updateToken($data);
                break;

            case 'CREATE_ACCOUNT':
                $this->createAccount($data);
                break;

            case 'RELOAD_CHATS':
                $this->reloadChats();
                break;

            case 'CREATE_CHAT':
                $this->createChat($data);
                break;

            case 'UPDATE_CHAT':
                $this->updateChat($data);
                break;

            case 'DELETE_CHAT':
                $this->deleteChat($data);
                break;

            default:
                $this->response(array(
                    'status' => false,
                    'message' => 'invalid request type'
                ));
        }
    }

    public function updateAccessToken()
    {
        $accessToken = get_option('rumbletalk_accesstoken');

        # if there's a saved and valid access token, set it
        if ($accessToken && !RumbleTalkSDK::renewalNeeded(RumbleTalkSDK::getTokenExpiration($accessToken))) {
            $this->rumbletalk->setAccessToken($accessToken);

        } else {
            # if the token key and secret are set, try fetching an access token
            if ($this->tokenKey && $this->tokenSecret) {
                try {
                    $accessToken = $this->rumbletalk->fetchAccessToken();
                } catch (Exception $e) {
                    $accessToken = '';
                }

                # no other option, clear the token
            } else {
                $accessToken = '';
            }

            update_option('rumbletalk_accesstoken', $accessToken);
        }

        return $accessToken;
    }

    /**
     * sets the token key and secret for the instance
     * @param string $tokenKey
     * @param string $tokenSecret
     * @param bool $updateWP
     * @return bool true if the token was set and not cleared
     */
    public function setToken($tokenKey, $tokenSecret, $updateWP = false)
    {
        $this->tokenKey = $tokenKey;
        $this->tokenSecret = $tokenSecret;
        $this->rumbletalk->setToken($tokenKey, $tokenSecret);

        if ($updateWP) {
            update_option('rumbletalk_chat_token_key', $this->tokenKey);
            update_option('rumbletalk_chat_token_secret', $this->tokenSecret);
        }

        return $this->tokenKey && $this->tokenSecret;
    }

    private function response($data = array())
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($data['status'])) {
            $data['status'] = true;
        }

        echo json_encode($data);
        exit;
    }

    public function getToken($return = false)
    {
        $response = array(
            'key' => $this->tokenKey,
            'secret' => $this->tokenSecret
        );

        if (!$return) {
            $this->response($response);
        }

        return $response;
    }

    private function updateToken($data)
    {
        $set = $this->setToken(
            $this->sanitizeAlphaNumeric($data['key']),
            $this->sanitizeAlphaNumeric($data['secret']),
            true
        );

        update_option('rumbletalk_accesstoken', '');

        RumbleTalk_Admin::removeChats();

        if ($set) {
            $token = $this->updateAccessToken();

            # if there's a token, update the chats
            if ($token) {
                $this->reloadChats(true);
            }
        }

        $this->response(array('status' => !$set || !!$token));
    }

    private function createAccount($data)
    {
        $sanitizedData = array(
            'referrer' => 'WordPress',
            'email' => sanitize_email($data['email']),
            'password' => $data['password'], # we allow all characters in passwords
        );

        if (!$this->rumbletalk->validateEmail($sanitizedData['email'])) {
            throw new Exception('invalid email address supplied', 400);
        }

        if (!$this->rumbletalk->validatePassword($sanitizedData['password'])) {
            throw new Exception('invalid password supplied', 400);
        }

        $result = $this->rumbletalk->post('accounts', $sanitizedData);

        if (@$result['status']) {
            $this->setToken(
                $result['token']['key'],
                $result['token']['secret'],
                true
            );

            $chats = array(
                $result['hash'] => array(
                    'id' => $result['chatId'],
                    'name' => 'New Chat',
                    'width' => '',
                    'height' => '',
                    'membersOnly' => false,
                    'floating' => false,
                    'forceLogin' => false
                )
            );

            RumbleTalk_Admin::updateChats($chats);

            $result['accessToken'] = $this->updateAccessToken();
        }

        $this->response(
            array(
                'status' => @$result['status'] ?: false,
                'message' => @$result['message'] ?: $result
            )
        );
    }

    private function createChat($data)
    {
        $sanitizedData = $this->sanitizeChatData($data);

        if ($sanitizedData['membersOnly']) {
            $sanitizedData['forceSDKLogin'] = true;
            $sanitizedData['allowListeners'] = false;
            $sanitizedData['autoInvite'] = false;
            $sanitizedData['inviteFriends'] = false;
        }
        $result = $this->rumbletalk->post('chats', $sanitizedData);

        if ($result['status']) {
            $chats = RumbleTalk_Admin::getChats();
            $chats[$result['hash']] = array(
                'id' => $result['chatId'],
                'name' => $sanitizedData['name'],
                'width' => $sanitizedData['width'],
                'height' => $sanitizedData['height'],
                'membersOnly' => $sanitizedData['membersOnly'],
                'loginName' => $sanitizedData['loginName'],
                'floating' => $sanitizedData['floating'],
                'forceLogin' => $sanitizedData['forceLogin']
            );

            RumbleTalk_Admin::updateChats($chats);
        }

        $this->response($result);
    }

    private function updateChat($data)
    {
        $sanitizedData = $this->sanitizeChatData($data);

        $chats = RumbleTalk_Admin::getChats();
        $hash = $data['hash'];
        if (!RumbleTalkSDK::validateHashStructure($hash)) {
            $this->response(array(
                'status' => false,
                'message' => 'Invalid room hash supplied'
            ));
        }

        if (!$chats[$hash]['id']) {
            $chats = $this->reloadChats(true);
        }

        $membersChanged = $sanitizedData['membersOnly'] != $chats[$hash]['membersOnly'];
        $chats[$hash] = array_merge($chats[$hash], $sanitizedData);

        RumbleTalk_Admin::updateChats($chats);

        $postData = array(
            'name' => $chats[$hash]['name'],
            'forceSDKLogin' => !!$chats[$hash]['membersOnly']
        );
        if ($postData['forceSDKLogin']) {
            $postData['allowListeners'] = false;
            $postData['autoInvite'] = false;
            $postData['inviteFriends'] = false;
        } elseif ($membersChanged) {
            $postData['facebookLogin'] = true;
            $postData['twitterLogin'] = true;
            $postData['rumbleTalkLogin'] = true;
            $postData['anonymous'] = true;
        }

        $result = $this->rumbletalk->put("chats/{$chats[$hash]['id']}", $postData);

        $this->response($result);
    }

    private function deleteChat($data)
    {
        $roomId = (int)$data['id'];
        $result = $this->rumbletalk->delete("chats/$roomId");

        if ($result['status']) {
            $chats = RumbleTalk_Admin::getChats();
            foreach ($chats as $hash => $chat) {
                if ($chat['id'] == $roomId) {
                    unset($chats[$hash]);
                    break;
                }
            }

            RumbleTalk_Admin::updateChats($chats);
        }

        $this->response($result);
    }

    public function reloadChats($return = false)
    {
        $chatsOld = RumbleTalk_Admin::getChats();

        $result = $this->rumbletalk->get('chats');

        $chats = array();
        if ($result['status']) {
            foreach ($result['data'] as $chat) {
                $chats[$chat['hash']] = array(
                    'id' => $chat['id'],
                    'name' => $chat['name'],
                    'width' => @$chatsOld[$chat['hash']]['width'],
                    'height' => @$chatsOld[$chat['hash']]['height'],
                    'membersOnly' => @$chatsOld[$chat['hash']]['membersOnly'],
                    'loginName' => @$chatsOld[$chat['hash']]['loginName'],
                    'floating' => @$chatsOld[$chat['hash']]['floating'],
                    'forceLogin' => @$chatsOld[$chat['hash']]['forceLogin']
                );
            }
        }

        RumbleTalk_Admin::updateChats($chats);

        if (!$return) {
            $this->response(
                array(
                    'chats' => $chats,
                    'status' => $result['status']
                )
            );
        }

        return $chats;
    }

    public function getAccountInfo($return = false)
    {
        $result = $this->rumbletalk->get('account');

        if (!$return) {
            $this->response($result);
        }

        return $result;
    }

    public function getOnlineModerators($hash)
    {
        $chats = RumbleTalk_Admin::getChats();
        if (!isset($chats[$hash])) {
            return array('error' => "invalid hash: $hash");
        }

        $result = $this->rumbletalk->get("chats/{$chats[$hash]['id']}/onlineUsers");

        $moderators = array();
        foreach ($result['users'] as $user) {
            if (in_array($user['userLevel'], array(RumbleTalkSDK::UL_MODERATOR, RumbleTalkSDK::UL_MODERATOR_GLOBAL))) {
                $moderators[] = $user;
            }
        }

        return $moderators;
    }

    public function getAccessToken()
    {
        return get_option('rumbletalk_accesstoken');
    }
}

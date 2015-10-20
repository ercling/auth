<?php
namespace base;

use app\models\User;

class UserManager
{
    private $_identity = false;
    public $loginUrl = '/index.php?r=user/login';

    /**
     * @var string the session variable name used to store the value of [[id]].
     */
    public $idParam = '__id';

    /**
     * @var string the session variable name used to store the value of expiration timestamp of the authenticated state.
     * This is used when [[authTimeout]] is set.
     */
    public $authTimeoutParam = '__expire';

    /**
     * @var integer the number of seconds in which the user will be logged out automatically if he
     * remains inactive. If this property is not set, the user will be logged out after
     * the current session expires (c.f. [[Session::timeout]]).
     * Note that this will not work if [[enableAutoLogin]] is true.
     */
    public $authTimeout;

    /**
     * @var integer the number of seconds in which the user will be logged out automatically
     * regardless of activity.
     * Note that this will not work if [[enableAutoLogin]] is true.
     */
    public $absoluteAuthTimeout;

    /**
     * @var string the session variable name used to store the value of absolute expiration timestamp of the authenticated state.
     * This is used when [[absoluteAuthTimeout]] is set.
     */
    public $absoluteAuthTimeoutParam = '__absoluteExpire';

    public function __construct()
    {

    }
    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     * @return boolean whether the current user is a guest.
     */
    public function getIsGuest()
    {
        return $this->getIdentity() === null;
    }

    public function getIdentity($autoRenew = true)
    {
        if ($this->_identity === false) {
            $this->_identity = null;
            $this->renewAuthStatus();
        }

        return $this->_identity;
    }

    protected function renewAuthStatus()
    {
        $session = Application::$app->getSession();
        $id = $session->get($this->idParam);//$session->getHasSessionId() || $session->getIsActive() ? $session->get($this->idParam) : null;

        if ($id === null) {
            $identity = null;
        } else {
            $identity = User::findIdentity($id);
        }

        $this->setIdentity($identity);

        if ($identity !== null && ($this->authTimeout !== null || $this->absoluteAuthTimeout !== null)) {
            $expire = $this->authTimeout !== null ? $session->get($this->authTimeoutParam) : null;
            $expireAbsolute = $this->absoluteAuthTimeout !== null ? $session->get($this->absoluteAuthTimeoutParam) : null;
            if ($expire !== null && $expire < time() || $expireAbsolute !== null && $expireAbsolute < time()) {
                //$this->logout(false);
            } elseif ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
        }

        if ($this->getIsGuest()) {
            $this->loginByCookie();
        } else {
            $this->renewIdentityCookie();
        }
    }

    protected function renewIdentityCookie()
    {
        $value = isset($_COOKIE['_identity'])?$_COOKIE['_identity']:null;
        if ($value !== null) {
            $data = json_decode($value, true);
            if (is_array($data) && isset($data[2])) {
                if ((int) $data[2] > 0){
                    $expire = time() + (int) $data[2];
                } else {
                    $expire = 0;
                }
                setcookie('_identity', $value, $expire, '/', $_SERVER['SERVER_NAME'], false, true);
            }
        }
    }

    protected function loginByCookie()
    {
        $value = isset($_COOKIE['_identity'])?$_COOKIE['_identity']:null;
        if ($value === null) {
            return;
        }
        $data = json_decode($value, true);
        if (count($data) !== 3 || !isset($data[0], $data[1], $data[2])) {
            return;
        }
        list ($id, $authKey, $duration) = $data;
        $identity = User::findIdentity($id);
        if ($identity === null) {
            return;
        } elseif (!$identity instanceof IdentityInterface) {
            throw new \ErrorException("User::findIdentity() must return an object implementing IdentityInterface.");
        }

        if ($identity->validateAuthKey($authKey)) {
            $this->switchIdentity($identity, $duration);
            Application::info("User '$id' logged in via cookie.", __METHOD__);
        } else {
            Application::warning("Invalid auth key attempted for user '$id': $authKey", __METHOD__);
        }
    }

    public function login(IdentityInterface $identity, $duration = 0)
    {
        $this->switchIdentity($identity, $duration);
        $id = $identity->getId();
        Application::info("User '$id' logged in", __METHOD__);
        return !$this->getIsGuest();
    }

    public function logout($destroySession = true)
    {
        $identity = $this->getIdentity();
        if ($identity !== null) {
            $this->switchIdentity(null);
            $id = $identity->getId();
            //$this->unsetIdentityCookie();
            Application::info("User '$id' logged out.", __METHOD__);
            if ($destroySession) {
                Application::$app->getSession()->destroy();
            }

        }
        return $this->getIsGuest();
    }

    public function switchIdentity($identity, $duration = 0)
    {
        $this->setIdentity($identity);

        $session = Application::$app->getSession();
        $session->remove($this->idParam);
        $session->remove($this->authTimeoutParam);

        if ($identity) {
            $session->set($this->idParam, $identity->getId());
            $this->sendIdentityCookie($identity, $duration);
        } else {
            $this->unsetIdentityCookie();
        }
    }

    public function setIdentity($identity)
    {
        if ($identity instanceof IdentityInterface) {
            $this->_identity = $identity;
        } elseif ($identity === null) {
            $this->_identity = null;
        } else {
            throw new \ErrorException('The identity object must implement IdentityInterface.');
        }
    }

    protected function unsetIdentityCookie()
    {
        setcookie('_identity', '', 1, '/', $_SERVER['SERVER_NAME'], false, true);
        unset($_COOKIE['_identity']);
    }

    protected function sendIdentityCookie($identity, $duration)
    {
        $value = json_encode([
            $identity->getId(),
            $identity->getAuthKey(),
            $duration,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($duration > 0){
            $expire = time() + $duration;
        } else {
            $expire = 0;
        }

        setcookie('_identity', $value, $expire, '/', $_SERVER['SERVER_NAME'], false, true);
    }

    public function loginByAccessToken($token, $type = null)
    {
        $identity = User::findIdentityByAccessToken($token, $type);
        if ($identity && $this->login($identity)) {
            return $identity;
        } else {
            return null;
        }
    }
}
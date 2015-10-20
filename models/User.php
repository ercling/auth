<?php
namespace app\models;

use base\Application;
use base\helpers\Security;
use base\IdentityInterface;
use base\Model;

class User extends Model implements IdentityInterface
{

    public $id;
    public $email;
    public $password;
    public $repeat_password;

    public function rules()
    {
        return [
            'email' => [FILTER_VALIDATE_EMAIL, FILTER_SANITIZE_EMAIL],
            'password' => [FILTER_DEFAULT],
            'repeat_password' => [FILTER_DEFAULT]
        ];
    }

    public function insert()
    {
        $insert = "INSERT INTO user (email, password, auth_key)
            VALUES (:email, :password, :auth_key)";
        $stmt = Application::$app->getDb()->prepare($insert);
        $stmt->bindValue(':email', $this->email, SQLITE3_TEXT);
        $stmt->bindValue(':password', password_hash($this->password, PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt->bindValue(':auth_key', Security::generateRandomString(64), SQLITE3_TEXT);

        return $stmt->execute();
    }

    public static function findIdentity($id)
    {
        $query = 'SELECT * FROM user WHERE id=:id';
        $stmt = Application::$app->getDb()->prepare($query);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_LAZY);
        if ($row){
            $model = new User();
            $model->load((array) $row, false);
            return $model;
        }
        return null;
    }

    public static function findIdentityByEmailAndPassword($email, $password)
    {
        $query = 'SELECT * FROM user WHERE email=:email';
        $stmt = Application::$app->getDb()->prepare($query);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_LAZY);
        if ($row){
            $model = new User();
            $model->load((array) $row, false);
            if (password_verify($password, $model->password)){
                return $model;
            }
        }
        return null;
    }

    public static function findIdentityByEmail($email)
    {
        $query = 'SELECT * FROM user WHERE email=:email';
        $stmt = Application::$app->getDb()->prepare($query);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_LAZY);
        if ($row){
            $model = new User();
            $model->load((array) $row, false);
            return $model;
        }
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $query = 'SELECT * FROM user WHERE api_key=:apikey';
        $stmt = Application::$app->getDb()->prepare($query);
        $stmt->bindValue(':apikey', $token, SQLITE3_TEXT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_LAZY);
        if ($row){
            $model = new User();
            $model->load((array) $row, false);
            return $model;
        }
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

}
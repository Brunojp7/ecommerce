<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {

	const SESSION = "User";
	// const SECRET = "HcodePhp7_Secret";
	const TYPECYPHER = "AES-256-CBC";
	const SECRET = "BTK8plRwzSXQvkr1";
	const IV = "Y3B7D3FZywxykNts";

	public static function login ($login, $password)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$login
		));

		if (count($results) === 0)
		{
			throw new \Exception("User or Password non existent...", 1);
			
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true)
		{

			$user = new User();

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;


		} else {

			throw new \Exception("User or Password non existent...", 1);

		}

	}

	public static function verifyLogin($inadmin = true)
	{

		if (
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			||
			(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
		)
			{
				header("Location: /admin/login");
				exit;
			}

	}

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}

	public static function listALL()
	{

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
		//$sql->select("SELECT * FROM tb_persons");

	}

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function get($iduser)
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
			":iduser"=>$iduser
			));

		$this->setData($results[0]);

	}

	public function update()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));

	}

	public static function getForgot($email)
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_persons a 
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
			", array(
				"email"=>$email
			));

		if(count($results) === 0 )
		{
			throw new \Exception("Could not retrieve password request.");
			
		}
		else
		{

			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array (
				":iduser"=>$data["iduser"],
				":desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if(count($results2) === 0)
			{
				throw new \Exception("Could not retrieve password request.");
				
			}
			else
			{

				$dataRecovery = $results2[0];
				//$ciphertext = openssl_encrypt($plaintext, $cipher, $key, $options=0, $iv, $tag);
				
				$code = base64_encode(openssl_encrypt($dataRecovery["idrecovery"], User::TYPECYPHER, User::SECRET,$options = 0, 
					$iv = User::IV));
				$link = "www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
		
				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha do Sistema", "forgot", 
					array(
					"name"=>$data["desperson"],
					"link"=>$link
					));
				$mailer->send();
				return $data;

			}

		}

	}

	public static function validForgotDecrypt($code)
	{
		$idrecovery = openssl_decrypt(base64_decode($code), User::TYPECYPHER, User::SECRET, $options=0, $iv = User::IV);
		
		$sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			inner join tb_users b using(iduser)
			inner join tb_persons c using(idperson)
			where (1=1)
			and a.idrecovery = :idrecovery
			and a.dtrecovery is NULL
			and DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
			", array(
				":idrecovery"=>$idrecovery
			));
		if (count($results) === 0) 
		{
			throw new \Exception("Error retrieving new password.");
			
		}
		else
		{
			return $results[0];
		}
	}

	public static function setForgotUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));

	}

	public function setPassword($password)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$password,
			"iduser"=>$this->getiduser()
		));

	}

}



 ?>
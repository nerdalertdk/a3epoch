<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
// Config
$bank = new BankCli();
$bank->setredisInstance(1);
$bank->setepochInstance('*'); // Use * as wildcard to run it on alle instance
$bank->setPassword("[PASSWORD]");
$bank->connectDb();

// Add interrest to players bank account
// Hours = alive time
//   1 Hour  = 0.1%
//  10 Hours = 1%
// 100 Hours = 10%
$bank->Interest();

// See top 10 rich people
//$bank->forbesTop10();

// Debug
ec///ho $bank->dbError();
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 					DON' T EDIT BELOW THIS LINE				   *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class BankCli{
	
	private $redis 			= null;
	private $epochInstance 	= 0;
	private $redisInstance 	= 0;
	private $dbpasss		= false;
	
	function __construct(){}
	function __destruct(){
		if($this->redis->ping()){
			$this->redis->close();
		}
		$this->redis = null;
	}
	
	public function setredisInstance($int){
		if(is_int($int)) {
			$this->redisInstance = $int;
		} else {
			die("redisInstance is not an integer");
		}
	}
	public function setepochInstance($int){
		if(is_int($int) || $int == '*') {
			$this->epochInstance = $int;
		} else {
			die("epochInstance is not an integer");
		}
	}
	public function setPassword($pass){
			$this->dbpasss = $pass;
	}
	public function connectDb(){
		$this->redis = new Redis();
		$this->redis->connect('127.0.0.1', 6379,2);
		$this->redis->auth($this->dbpasss);
		$this->redis->select($this->redisInstance);
		
		if(!$this->redis->ping()){
			die( "Cannot connect to redis server.\n" );
		}
	}
	public function dbError(){
		return $this->redis->getLastError();
	}
	
	private function getPlayerInfo($steamID){
		// Get players
		$player = $this->redis->GET('Player:'.$steamID);
		// Player exists
		if($player){
			// Players is alive
			$playerData = json_decode($player,true);
			if($playerData != 0){
				return $playerData;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	private function getBankAccounts(){
		// Get bank accounts
		$bank = $this->redis->keys( 'Bank:*' );
		if($bank) {
			return $bank;
		} else {
			return false;
		}
	}
	private function getBankAccount($steamID){
		// Get bank account
		$account = $this->redis->GET( 'Bank:'.$steamID );
		if($account) {
			$accountData = json_decode($account,true);
			if($accountData != 0){
			return $accountData;
			} else{
				return false;
			}
		} else {
			return false;
		}
	}
	/* *
	 *  Bank interest only active if player is alive
	 *  
	 * */
	private function calcInterest($steamID){
		$account 	= $this->getBankAccount($steamID);
		$player 	= $this->getPlayerInfo($steamID);
				
		// Players is alive and has an back account
		if($account && $player){
			// year = hour
			$timeAlive 	= round($player[4][3] / 3600,5); // Time alive ingame
			$balance 	= (int)$account[0]; // ingame bank
			$rate		= round($timeAlive / 1000,5); // interest rate
			$interest	= (int)floor(($balance * $rate)); // Amount given back to player
			$addtobank 	= (int)($balance + $interest);
			
			if($balance != 0 && $timeAlive != 0){
				if($this->deposit($steamID,$addtobank)){
					echo "Alive: ".$timeAlive."\nrate: ".$rate."\nAdd to bank: ".$interest."\n";
					echo "new balance: ".$addtobank."\n\n";
				} else {
					echo "error inserting money\n";
				}
			}
			
		} else {
			echo "No player or bank on ".$steamID."\n";
		}
	}

	/* *
	*  
	* */
	private  function deposit($steamID,$amount){
		if(is_int($amount)){
			$bank = $this->redis->set( 'Bank:'.$steamID, json_encode(array($amount)) );	
		} else {
			$bank = false;
		}
		
		if($bank){
			return true;
		} else {
			return false;
		}
		
	}
	private function withdrawde($steamID,$amount){
		if(is_int($amount)){
			$bank = $this->redis->set( 'Bank:'.$steamID, json_encode(array($amount)) );	
		} else {
			$bank = false;
		}
		
		if($bank){
			return true;
		} else {
			return false;
		}
	}
	public function Interest(){
		// Get bank accounts
		$bank = $this->getBankAccounts();
		
		/* *
		 * Loops through all bank accounts
		 * */
		if($bank) {
			// Total bank accounts
			echo $number = count($bank). " Bank accounts\n\n";
			foreach($bank AS $account){
				// Get steamID
				$accountNr = explode(":", $account);
				// Get bank account
				$deposits = $this->getBankAccount($accountNr[1]);
				$this->calcInterest($accountNr[1]);
			}
		}
	}
	public function forbesTop10(){
		// Get bank accounts
		$bank = $this->getBankAccounts();
		
		/* *
		 * Loops through all bank accounts
		 * */
		if($bank) {
			$accountList = array();
			// Total bank accounts
			echo $number = count($bank). " Bank accounts\n\n";
			foreach($bank AS $account){
				// Get steamID
				$accountNr = explode(":", $account);
				// Get bank account
				$deposits = $this->getBankAccount($accountNr[1]);
				
				if($deposits){
					// Get account deposits
					//$deposits = json_decode($deposits);
					if($deposits[0] > 0) {
						$accountList[$accountNr[1]] = $deposits[0];
					}
				} else {
					echo "Error getting account ".$account."\n";
				}
			}
			// Sorts array high to low
			arsort($accountList);
			// Get top 10
			$topTen = array_slice($accountList, 0, 10, true);
			$count = 0;
			
			
			// Get account info
			foreach($topTen AS $steamid => $balance){
				// Get player info
				
				$owner = $this->getPlayerInfo($steamid);
				if($owner){
					$count++;
					$accountHolder = $owner[3][0];
					$accountAmount = $balance;
					
					echo str_pad($count,2,"0",STR_PAD_LEFT)." ".$accountHolder."(".$steamid.") has ".$accountAmount. " in the bank\n";
				} else {
					echo "Error getting player\n";
				}
			}
		} else {
			echo "Error getting bank accounts\n";
		}
	}
	
}
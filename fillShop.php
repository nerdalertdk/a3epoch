<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);


$traders = new Traders();
$traders->setRedisInstace(1); // Sets in redi.conf
$traders->setEpochInstace(1); // Sets in epochserver.ini
$traders->setPassword(""); // Database password set in redi.conf
$traders->setItemArray(array(
	'CircuitParts','ItemScraps','ItemCorrugated','ItemCorrugatedLg','CinderBlocks','MortarBucket','WoodLog_EPOCH','PartPlankPack',
	'C_Quadbike_01_EPOCH',
	'ItemTunaCooked','CookedGoat_EPOCH','ItemSodaRbull','WhiskeyNoodle'
	));
$traders->connectDb();
echo $traders->updateStock();

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 					DON' T EDIT BELOW THIS LINE				   *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Traders{
	
	private $redis 			= null;
	
	private $epochInstace 	= 0;
	private $redisInstace 	= 0;
	private $stockMin 		= 0;
	private $stockMax 		= 2;
	private $itemarray		= array();
	private $dbpasss		= false;
	
	function __construct(){}
	function __destruct(){
		//$this->redis->close();
		$this->redis = null;
	}
	
	public function setRedisInstace($int){
		if(is_int($int)) {
			$this->redisInstace = $int;
		} else {
			die("redisInstace is not an integer");
		}
	}
	public function setEpochInstace($int){
		if(is_int($int)) {
			$this->epochInstace = $int;
		} else {
			die("epochInstace is not an integer");
		}
	}
	public function setStockMin($int){
		if(is_int($int)) {
			$this->stockMin = $int;
		} else {
			die("stockMin is not an integer");
		}
	}
	public function setStockMax($int){
		if(is_int($int)) {
			$this->stockMax = $int;
		} else {
			die("stockMax is not an integer");
		}
	}
	public function setItemArray($items){
		if(is_array($items))
			$this->itemarray = $items;
		else
			die("setItemArray is not an array");
	}
	public function setPassword($pass){
			$this->dbpasss = $pass;
	}
	
	public function connectDb(){
		$this->redis = new Redis();
		$this->redis->connect('127.0.0.1', 6379);
		$this->redis->auth($this->dbpasss);
		$this->redis->select($this->redisInstace);
		
		if(!$this->redis->ping()){
			die( "Cannot connect to redis server.\n" );
		}
	}
	
	public function updateStock(){
		// Get trader item
		$AI_ITEMS = $this->redis->keys( 'AI_ITEMS:'.$this->epochInstace.':*' );
		
		/* *
		 * Loops through  all traders
		 * */
		if($AI_ITEMS) {
			echo $number = count($AI_ITEMS). " Traders\n";
			foreach($AI_ITEMS AS $trader){
				echo "\nTRADER ID: ".$trader;
				
				// Get items array
				$trader_items = $this->redis->GET($trader);
				if($trader_items){
					$trader_items = json_decode($trader_items,true);
					
					// if trader is empty, create need arrays
					if(count($trader_items) != 2){
							$trader_items = array(array(),array());
					}
					
					/* *
					 *  Item exists update stock
					 *  else craete item with stock
					 * */ 
					foreach($this->itemarray AS $item) {
				
						if(in_array ( $item , $trader_items[0])) {
							// Find item id
							$key = array_search($item, $trader_items[0]);
							if($key !== false) {
								// Update item stock
								$trader_items[1][$key] = rand($this->stockMin,$this->stockMax);
								echo "\n\tUpdatede ".$trader_items[0][$key]."/".rand($this->stockMin,$this->stockMax);
							}
						} else {
							// Create item
							array_push($trader_items[0],$item);
							// Find item ID, stock id must match item id
							$key = array_search($item, $trader_items[0]);
							// Create Stock with item ID
							if($key !== false) {
								$trader_items[1][$key] = rand($this->stockMin,$this->stockMax);
								echo "\n\tCreated ".$trader_items[0][$key]."/".rand($this->stockMin,$this->stockMax);
							}
						}
					}
					// Save to database.
					$this->redis->set($trader, json_encode($trader_items));
				} else {
					echo ("Error gettting item list");
				}
			}
		} else {
			echo ("No traders");
		}
	}
}

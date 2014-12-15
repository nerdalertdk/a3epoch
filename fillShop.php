<?php
$traders = new Traders();
$traders->setEpochInstace(1);
$traders->setPassword("");
$traders->setItemArray(array(
	'CircuitParts','ItemScraps','ItemCorrugated','ItemCorrugatedLg','CinderBlocks','MortarBucket','WoodLog_EPOCH','PartPlankPack',
	'C_Quadbike_01_EPOCH',
	'ItemTunaCooked','CookedGoat_EPOCH','ItemSodaRbull','WhiskeyNoodle'
	));
echo $traders->updateStock();

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 					DON' T EDIT BELOW THIS LINE				   *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
class Traders{
	
	private $redis 			= null;
	
	private $epochInstace 	= 0;
	private $stockMin 		= 0;
	private $stockMax 		= 2;
	private $itemarray		= array();
	private $dbpasss		= "";
	
	function __construct(){
		// Connect to database
		try{
			$this->redis = new Redis();
			$this->redis->connect('127.0.0.1', 6379);
			$this->redis->auth($this->dbpasss);
			$this->redis->select(1);
		} catch( Exception $e ){ 
			echo $e->getMessage(); 
		}
	}
	function __destruct(){
		$this->redis->close();
		$this->redis = null;
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
	
	public function updateStock(){
		// Get trader item
		try{
			$AI_ITEMS = $this->redis->keys( 'AI_ITEMS:'.$this->epochInstace.':*' );
		} catch( Exception $e ){ 
			echo $e->getMessage(); 
		}
		echo $number = count($AI_ITEMS). " Traders\n";
		
		/* *
		 * Loops through  all traders
		 * */
		foreach($AI_ITEMS AS $trader){
			echo "\nTRADER ID: ".$trader;
			
			// Get items array
			try{
				$trader_items = json_decode($this->redis->GET($trader),true);
			} catch( Exception $e ){ 
				echo $e->getMessage(); 
			}
			
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
			try {	
				$this->redis->set($trader, json_encode($trader_items));
			} catch( Exception $e ){ 
				echo $e->getMessage(); 
			}
		}
	}
}

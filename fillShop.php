<?php
/* *
 *  @file fillShop.php
 *  @brief Ctreatede by itsatrap
 * */
ini_set('display_errors', 0); 
error_reporting(E_ALL);

$traders = new Traders();
$traders->setRedisInstace(1); // Sets in epochserver.ini
$traders->setEpochInstace(''); // Sets in epochserver.ini / if empty all instance get updatede
$traders->setChance(1); // % for rare item spawn
$traders->setPassword(""); // Database password set in redi.conf
/* *
 * Building 
 * Vehicles
 * Food
 * Backpack
 * Vests
 * Cloth
 * Items
 * */
$traders->setItemArray(array(
	'CircuitParts','ItemScraps','ItemCorrugated','ItemCorrugatedLg','CinderBlocks','MortarBucket','WoodLog_EPOCH','PartPlankPack',
	'C_Quadbike_01_EPOCH',
	'CookedGoat_EPOCH','ItemSodaRbull','WhiskeyNoodle','sweetcorn_epoch',
	'smallbackpack_pink_epoch',
	'V_4_EPOCH',
	'FAK'
));
	
$traders->setRareArray(array(
	'ItemLockBox','EnergyPackLg'
));

$traders->setRemoveitem(array(
	'DemoCharge_Remote_Mag','SatchelCharge_Remote_Mag','ATMine_Range_Mag','ClaymoreDirectionalMine_Remote_Mag','APERSMine_Range_Mag',
	'APERSBoundingMine_Range_Mag','SLAMDirectionalMine_Wire_Mag','APERSTripMine_Wire_Mag'
));
	
$traders->connectDb();
echo $traders->updateStock();

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * 		DON' T EDIT BELOW THIS LINE			*
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
 
 /* *
 * @class Traders
 * @brief This is a class to demonstrate how to use Doxygen in this project.
 * */
class Traders{
	
	// Database
	private $redis 			= null;
	private $dbpasss		= false;
	private $epochInstace 		= 0;
	private $redisInstace 		= 0;
	
	// Amount
	private $stockMin 		= 0;
	private $stockMax 		= 2;
	private $rareChance		= 0;
	
	//Items
	private $itemarray		= array();
	private $itemRare		= array();
	private $itemRemove		= array();
	
	function __construct(){}
	/* *
	 *  @brief Brief
	 *  
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	function __destruct(){
		if($this->redis->ping()){
			$this->redis->close();
		}
		$this->redis = null;
	}
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $int Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setRedisInstace($int){
		if(is_int($int)) {
			$this->redisInstace = $int;
		} else {
			die("redisInstace is not an integer");
		}
	}
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $int Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setEpochInstace($int){
		if(is_int($int) || $int == '*') {
			$this->epochInstace = $int;
		} elseif(empty($int)){
			$this->epochInstace = '*';
		} else {
			die("epochInstace is not an integer or *");
		}
	}
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $pass Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setPassword($pass){
			$this->dbpasss = $pass;
	}
	
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $int Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setStockMin($int){
		if(is_int($int)) {
			$this->stockMin = $int;
		} else {
			die("stockMin is not an integer");
		}
	}
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $int Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setStockMax($int){
		if(is_int($int)) {
			$this->stockMax = $int;
		} else {
			die("stockMax is not an integer");
		}
	}
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $int Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setChance($int){
		if(is_int($int)) {
			$this->rareChance = $int;
		} else {
			die("setChance is not an integer");
		}
	}
	
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $items Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setItemArray($items){
		if(is_array($items))
			$this->itemarray = $items;
		else
			die("setItemArray is not an array");
	}
	
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $items Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setRareArray($items){
		if(is_array($items))
			$this->itemRare = $items;
		else
			die("setRareArray is not an array");
	}
	
	/* *
	 *  @brief Brief
	 *  
	 *  @param [in] $items Parameter_Description
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function setRemoveitem($items){
		if(is_array($items))
			$this->itemRemove = $items;
		else
			die("setRemoveitem is not an array");
	}
	
	/* *
	 *  @brief Brief
	 *  
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
	public function connectDb(){
		$this->redis = new Redis();
		$this->redis->connect('127.0.0.1', 6379);
		$this->redis->auth($this->dbpasss);
		$this->redis->select($this->redisInstace);
		
		if(!$this->redis->ping()){
			die( "Cannot connect to redis server.\n" );
		}
	}
	
	/* *
	 *  @brief Brief
	 *  
	 *  @return Return_Description
	 *  
	 *  @details Details
	 * */
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
					
					// if trader is empty, create needed arrays
					if(count($trader_items) != 2){
							$trader_items = array(array(),array());
					}
					
					/* *
					 *  Item exists update stock
					 *  else create item with stock
					 * */ 
					foreach($this->itemarray AS $item) {
						if(in_array ( $item , $trader_items[0])) {
							// Find item id
							$key = array_search($item, $trader_items[0]);
							// Item exists and stock is under stockMax
							if($key !== false && $trader_items[1][$key] < $this->stockMax) {
								// Update item stock
								$trader_items[1][$key] = rand($this->stockMin,$this->stockMax);
								echo "\n\tUpdated ".$trader_items[0][$key]."/".rand($this->stockMin,$this->stockMax);
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
					
					/* *
					 *  Rare items
					 * */
					foreach($this->itemRare AS $item) {
						if($this->percentChance($this->rareChance)){
							if(in_array ( $item , $trader_items[0])) {
								// Find item id
								$key = array_search($item, $trader_items[0]);
								// Item exists, stock is 0
								if($key !== false && $trader_items[1][$key] >= 0) {
									// Update item stock
									$trader_items[1][$key] = 1;
									echo "\n\tRare Updated ".$trader_items[0][$key]."/1";
								}
							} else {
								// Create item
								array_push($trader_items[0],$item);
								// Find item ID, stock id must match item id
								$key = array_search($item, $trader_items[0]);
								// Create Stock with item ID
								if($key !== false) {
									$trader_items[1][$key] = 1;
									echo "\n\tRare Created ".$trader_items[0][$key]."/1";
								}
							}
							/*if(!array_key_exists($key,$trader_items[1])){
									echo $key." findes ikke";
									array_push($trader_items[1],$key);
							}*/
							//print_r($trader_items[0]);
							//print_r($trader_items[1]);
						}
					}
					
					/* *
					 *  Remove items
					 * */
					foreach($this->itemRemove AS $item) {
						if(in_array ( $item , $trader_items[0])) {
							// Find item id
							$key = array_search($item, $trader_items[0]);
							// Item exists
							if($key !== false && $trader_items[1][$key] >= 1) {
								// Sets stock to 0 since we what to remove the item from the shop
								$trader_items[1][$key] = 0;
								echo "\n\tRemoved ".$trader_items[0][$key];
							}
						}
					}
					
					if(count($trader_items[0]) != count($trader_items[1])) {
						echo "\nError in array\n".count($trader_items[0])."/".count($trader_items[1])."\n";
					} else {
						// Save to database.
						$this->redis->set($trader, json_encode($trader_items));
					}
					
					
				} else {
					echo ("Error getting item list");
				}
			}
		} else {
			echo ("No traders");
		}
	}
	
	 /* *
	 *  @brief Calc chance / used when updating rare items
	 *  
	 *  @param [in] $chance Parameter_Description
	 *  @return int
	 *  
	 *  @details Notice we go from 0-99 - therefore a 100% $chance is always larger
	 * */
	private function percentChance($chance){
		
		$randPercent = mt_rand(0,99);
		echo "\n". $chance > $randPercent."\n";
		return $chance > $randPercent;
	}
}

<?php

namespace Laith98Dev\LevelSystem;

use mysqli;
use pocketmine\player\Player;

class MysqlDB extends DB {

    /*@var mysqli*/
    private ?mysqli $db;
	  /*@var Main*/
    private ?Main $plugin;
	  /*@var string*/
    public string $dbName;
  
        /**
     * @param string $dbName
     */
    public function __construct(string $dbName){
	    $this->plugin = Main::getInstance();
        $this->dbName = $dbName;
        $config = $this->plugin->getConfig()->getNested("Mysql");
        $this->db = new mysqli(
			$config["Host"] ?? "127.0.0.1",
			$config["User"] ?? "root",
			$config["Password"] ?? "",
			$config["Database"] ?? "LevelSystem",
		);
			
		if($this->db->connect_error){
			$this->plugin->getLogger()->critical("Could not connect to MySQL server: ".$this->db->connect_error);
			return;
		}
		
		if(!$this->db->query("CREATE TABLE if NOT EXISTS levelsystem_user(
			    xuid VARCHAR(50) PRIMARY KEY,
				  username VARCHAR(50),
          xp FLOAT,
          level FLOAT
		    );"
		)){
		    $this->plugin->getLogger()->critical("Error creating table: " . $this->db->error);
		    return;
		}		
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string{ return $this->dbName; }

    /**
     * @return void
     */
    public function close(): void{}

    /**
     * @return void
     */
    public function reset(): void{}

    /**
     * @param string $name
     * @return bool
     */
    public function accountExists(string $name){
		$result = $this->db->query("SELECT * FROM levelsystem_user WHERE xuid='".$this->db->real_escape_string($name)."'");
		return $result->num_rows > 0 ? true:false;
	}

    /**
     * @param Player $player
     * @return bool
     */
    public function createProfile(Player $player) :bool{
		if($player instanceof Player){
			$xuid = $player->getXuid();
		}
		$xuid = strtolower($xuid);
		$namePlayer = $player->getName();
		if(!$this->accountExists($xuid)){
			$this->db->query("INSERT INTO levelsystem_user (
			    xuid,
				username,
				xp,
				level
			)
			VALUES ('".$this->db->real_escape_string($xuid)."', 
			    '$namePlayer',
			    0.0,
			    0.0,
				0.0,
			    0.0,
			);");
			return true;
		}
		return false;
	}

    /**
     * @param Player $player
     * @return bool
     */
    public function removeProfile(Player $player) :bool{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		if($this->db->query("DELETE FROM levelsystem_user WHERE xuid='".$this->db->real_escape_string($player)."'") === true) return true;
		return false;
	}

    /**
     * @param Player $player
     * @return float
     */
    public function getXp(Player $player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT xp FROM levelsystem_user WHERE xuid='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	/**
     * @param Player $player
	 * @param float $amount
     * @return float
     */
	public function addXp(Player $player, float $amount) :float{
		$calculate = $this->getDeaths($player) + $amount;
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		return $this->db->query("UPDATE levelsystem_user SET xp= $calculate WHERE xuid='".$this->db->real_escape_string($player)."'");
	}
  
  public function setXp(){

  }

    /**
     * @param Player $player
     * @return float
     */
    public function getLevel(Player $player) :float{
		if($player instanceof Player){
			$player = $player->getXuid();
		}
		$player = strtolower($player);
		$res = $this->db->query("SELECT FROM levelsystem_user WHERE xuid='".$this->db->real_escape_string($player)."'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}
	
	/**
     * @param Player $player
	 * @param float $amount
     * @return float
     */
	public function addLevel(Player $player, float $amount) :float{
		$calculate = $this->getKills($player) + $amount;
		if($player instanceof Player){
			$player = strtolower($player->getXuid());
		}
		return $this->db->query("UPDATE levelsystem_user SET kills = $calculate WHERE xuid='".$this->db->real_escape_string($player)."'");
	}
  
  public function setLevel(){

  }
	
	/**
	 * @return array
	 */
	public function getAll(){
		$res = $this->db->query("SELECT * FROM levelsystem_user");
		$ret = [];
		foreach($res->fetch_all() as $val){
			$ret[] = [
			    "xuid" => $val[0],
				"name" => $val[1],
				"xp" => $val[2],
				"level" => $val[3],
			];
		}
		$res->free();
		return $ret;
	}
}

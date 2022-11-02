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
                                  level FLOAT,
				  addxp FLOAT,
				  nextlevelxp FLOAT
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
				level,
				addxp,
			        nextlevelxp
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
	
    public function LevelInfo(Player $player){ // transfered function of CheckAccount in DataMGR.php
        		$add = (50 * $this->getLevel($player) / 2);
			$nextLevelXP = ($add * $this->getLevel($player) * 100) / ($this->getLevel($player) * 4);
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
  
       public function setXp(Player $player, float $amount) : float{
           if($player instanceof Player){
		$player = strtolower($player->getXuid());
	   }
	   $cfg = new Config(Main::getInstance()->getDataFolder . "settings.yml", Config::YAML);
	   if($this->getLevel($player) >= intval($cfg->get("MaxLevel")))
	       return false;
	       
	   return $this->db->query("UPDATE levelsystem_user SET xp= $amount WHERE xuid='".$this->db->real_escape_string($player)."'");
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
		$res = $this->db->query("SELECT level FROM levelsystem_user WHERE xuid='".$this->db->real_escape_string($player)."'");
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
		return $this->db->query("UPDATE levelsystem_user SET level= $calculate WHERE xuid='".$this->db->real_escape_string($player)."'");
	}
  
       public function setLevel(Player $player, float $amount) : float{
           if($player instanceof Player){
		$player = strtolower($player->getXuid());
	   }
	   $cfg = new Config(Main::getInstance()->getDataFolder() . "settings.yml", Config::YAML);    
	   if($this->getLevel($player) >= intval($cfg->get("MaxLevel")))
	       return false;
	       
	   return $this->db->query("UPDATE levelsystem_user SET level= $amount WHERE xuid='".$this->db->real_escape_string($player)."'");
       }
	
       public function getNextLevelXp(Player $player) :float{
	   if($player instanceof Player){
		$player = $player->getXuid();
	   }
	   $player = strtolower($player);
	   $res = $this->db->query("SELECT nextlevelxp FROM levelsystem_user WHERE xuid='".$this->db->real_escape_string($player)."'");
	   $ret = $res->fetch_array()[0] ?? false;
	   $res->free();
	   return $ret;
       }
	
       public function setNextLevelXp(Player $player, float $amount) : float{
           if($player instanceof Player){
		$player = strtolower($player->getXuid());
	   }   
	   return $this->db->query("UPDATE levelsystem_user SET nextlevelxp= $amount WHERE xuid='".$this->db->real_escape_string($player)."'");
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
				"addxp" => $val[4],
				"nextlevelxp" => $val[5]
			];
		}
		$res->free();
		return $ret;
	}
}

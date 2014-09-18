<?php
//Luokka
class mapManager {
	//Määritellään yleiset muuttujat
	private $kayttajatunnus="root"; 
	private $salasana=""; 
	private $tietokanta="mindmaps"; 
	private $palvelin="localhost";
	private $sql_lause;
	
	//TODO: muuta mysql yhteys mysqliin tai pdo:hon
	
	public function __construct() {
		$yhteys=mysql_connect($this->palvelin,$this->kayttajatunnus,$this->salasana);
		mysql_select_db($this->tietokanta,$yhteys) or die ("Virhe tietokannan avauksessa");
		mysql_query("SET NAMES 'utf8'", $yhteys);
		//http://stackoverflow.com/questions/5288953/is-mysql-real-escape-string-broken/5289141#5289141
	}
	
	public function logIn($username,$password) {
		$username = mysql_real_escape_string($username);
		$password = mysql_real_escape_string($password); 
		$this->sql_lause = "SELECT * FROM users WHERE name = '".$username."' AND password='".$password."';";
    
        return $this->getUserData();
	}
	
	private function getuserData() {
		$kysely = mysql_query($this->sql_lause);
		$rivi=mysql_fetch_array($kysely);
		$userData = array();
		$userData["userid"]=$rivi["userid"];
		$userData["username"]=$rivi["name"];
		
		return $userData;
	}
	
	public function getMaps($id) { //karttojen nimilistaus
		$this->sql_lause="SELECT * FROM maps WHERE userid=".$id.";";
		return $this->selectMaps();
	}
	
	private function selectMaps() {
		$kysely = mysql_query($this->sql_lause);
		
		$maps = array();
		//Ajatuskartat omiin taulukoihin yhden ison taulukon sisälle
		while($rivi=mysql_fetch_array($kysely)) {
			$array = array();
				$array["mapid"] = $rivi["mapid"];
				$array["name"] = $rivi["name"];
				$array["x"] = $rivi["x"];
				$array["y"] = $rivi["y"];
				$array["linecoord"] = $rivi["linecoord"];
			$maps[] = $array;
		}
		//json_encode($maps);
		return $maps;
	}
	
	public function getMapContent($id) { //haetaan karttojen sisältä
		$this->sql_lause="SELECT * FROM ideas WHERE mapid=".$id.";";
		
		return $this->selectMapContent();
	}
	
	private function selectMapContent() {
		$kysely = mysql_query($this->sql_lause);
		
		//Ajatuskartan sisältö omiin taulukoihin yhden ison taulukon sisälle
		$mapContents = array();
		
		while($rivi=mysql_fetch_array($kysely)) {
				$array = array();
					$array["ideaid"] = $rivi["ideaid"];
					$array["name"] = $rivi["name"];
					$array["x"] = $rivi["x"];
					$array["y"] = $rivi["y"];
					$array["linecoord"] = array();				
					
					//Käydään läpi ajatuksen viivasuhdekoordinaatit, eli slice 'n dice
					// ensin erotellaan koordinaattiparit | merkin perusteella
					// sitten x ja y arvo - merkin perusteella
					if($rivi["linecoord"] != "") {
						$pairSets = explode("|", $rivi["linecoord"]);
						foreach($pairSets as $set) {
							$values = explode("-", $set);
							$array["linecoord"][] = array ( "x" => $values[0], "y" => $values[1]); //tuupataan tauluun
						}
					}
				$mapContents[] = $array; 

		} //while end
		
		//json_encode($mapContents);
		return $mapContents;
	}
	
	public function insertNewMap($mapData) { //tallennetaan uusi kartta kantaan
		foreach ($mapData as $key=>$val){ //jotai vikaa the shit
			if($key === "contents") {
				foreach($val as $key => $pallero) {
					//mysql injektio tsekki
					$name = mysql_real_escape_string($pallero["name"]);
					if($key == 0) { //jos eka arvo. mindmapin otsikko on aina eka. jos poistaa otsikon, koko mindmap poistuu
						if(!isset($pallero["linecoord"])) 						
							$pallero["linecoord"]="";
						
						$this->createNewMap($name,$mapData["userid"],$pallero["x"],$pallero["y"],$pallero["linecoord"]);
							
						$this->sql_lause="SELECT * FROM maps WHERE name='".$name."' AND userid=".$mapData["userid"].";";
						$mapid = $this->getMapByName();
					}
					else {
						if(!isset($pallero["linecoord"])) 						
							$pallero["linecoord"]="";
							
						$this->createNewIdea($name,$mapid,$pallero["x"],$pallero["y"],$pallero["linecoord"]);
						
					}
				}
			}
		}
	}
	
	private function getMapByName() {
		$kysely = mysql_query($this->sql_lause);
		$rivi=mysql_fetch_array($kysely);
		return $rivi["mapid"];
	}
	
	private function createNewMap($mapname,$userid,$x,$y,$linecoord) {
		$this->sql_lause="INSERT INTO maps(name,userid,x,y,linecoord) VALUES('".$mapname."','".$userid."','".$x."','".$y."','".$linecoord."');";
		mysql_query($this->sql_lause);
	}
	private function createNewIdea($ideaname,$mapid,$x,$y,$linecoord) {
		$this->sql_lause="INSERT INTO ideas(name, mapid,x,y,linecoord) VALUES('".$ideaname."','".$mapid."','".$x."','".$y."','".$linecoord."');";
		mysql_query($this->sql_lause);
	}
}

$hallintaolio = new mapManager();
if(isset($_GET["userid"])) {
	$karttalista = $hallintaolio->getMaps($_GET["userid"]);
	print json_encode($karttalista);
}
if(isset($_GET["mapid"])) {
	$ajatukset = $hallintaolio->getMapContent($_GET["mapid"]);
	print json_encode($ajatukset);
}
if(isset($_GET["mapData"])) {
	$hallintaolio->insertNewMap(json_decode($_GET["mapData"],true));
}
if(isset($_GET["user"]) && isset($_GET["pass"])) {
	$userData = $hallintaolio->logIn($_GET["user"],$_GET["pass"]);
	print json_encode($userData);
}
?>
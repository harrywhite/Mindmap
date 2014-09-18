<?php
//Includet
include('include/class.php');

$mapid=$_GET["id"];
$mapname=$_GET["name"];
echo "<h3>".$mapid." ".$mapname."</h3>";

if(isset($_POST["nap"]) && $_POST["ideaname"] != "") {
	$hallintaolio->createNewIdea($_POST["ideaname"], $mapid);
}

$mapContent = $hallintaolio->getMapContent($mapid);

foreach($mapContent as $idea) {
	echo $idea["ideaid"]." ".$idea["name"]."<br/>";
	if(!empty($idea["children"])) {
		foreach($idea["children"] as $child) {
			echo "...".$child["ideaid"]." ".$child["name"]."<br/>";
		}
	}
}
?>
<br/>
<?php
print_r($mapContent);
?>
<form method="post" style="border:1px solid black; padding:5px; width:400px;">
<label for="ideaid">Isäntäidean id</label>
<input type="text" name="ideaid"/><br/>
<label for="ideaname">Idean nimi</label>
<input type="text" name="ideaname"/><br/>
<input type="submit" name="nap" value="Uusi idea"/>
</form>
<br/>
<a href="index.php">Takaisin</a>
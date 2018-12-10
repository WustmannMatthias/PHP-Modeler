<?php
	use GraphAware\Neo4j\Client\ClientBuilder;
?>


<div class="row">
	
	<form class="col-lg-8 col-lg-offset-2 query_database_form" method="post"
			action="index.php?query_database">
		<p class="col-lg-12 center">Enter here a query for neo4J database : </p>
		<div class="row form-group">
			<input class="col-lg-12" type="text" name="query" required="required" />
		</div>
		<div class="row form-group">
			<button class="btn btn-primary center-block" type="submit" name="submit">Submit</button>
		</div>
	</form>
</div>


<div class="row">
	<?php
		if (isset($_POST['submit']) && isset($_POST['query'])) {
			$query = $_POST['query'];

			$databaseURL 	= $_SESSION['DATABASE_URL'];
			$databasePort 	= $_SESSION['DATABASE_PORT'];
			$username		= $_SESSION['USERNAME'];
			$password 		= $_SESSION['PASSWORD'];
			
			require_once "vendor/autoload.php";

			require_once "functions/database_functions.php";



			$fullURL = "bolt://".$username.":".$password."@".$databaseURL.":".$databasePort;
			$client = ClientBuilder::create()
				->addConnection('bolt', $fullURL)
				->build();




			$result = runQuery($client, $query);

			if ($result) {
				//build a html table displaying all records
				/*
				foreach ($result->records() as $record) {
					print_r($record->values());
				}
				*/
			}
		}
	?>
</div>
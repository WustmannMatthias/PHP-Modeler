<?php
	use GraphAware\Neo4j\Client\ClientBuilder;

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




	//Get projects list
	$query = "MATCH (p:Project) RETURN p.name as project";
	$result = runQuery($client, $query);

	$projectOptions = "";

	foreach ($result->records() as $record) {
		$project = $record->value("project");
		$projectOptions.= "<option value='$project'";
		if (isset($_SESSION['project']) && $_SESSION['project'] == $project) {
			$projectOptions.= " selected";
		}
		$projectOptions.= ">$project</option>";
	}
?>

<div class="row">
	<h1 class="center">Features to test</h1>
</div>

<div class="row">
	<form class="col-lg-8 col-lg-offset-2 form-horizontal" 
			method="post" action="index.php?features">
		
		<div class="row form-group">
			<label>Select your project </label>
			<select name="project">
				<?php
					echo $projectOptions;
				?>
			</select>
			<button class="btn btn-primary" type="submit" 
					name="projectSubmit">Validate
			</button>
		</div>

	</form>
</div>

<?php
	if (isset($_POST['projectSubmit']) || isset($_SESSION['project'])) {
		$project;
		if (isset($_POST['project'])) {
			$project = $_POST['project'];
			$_SESSION['project'] = $project;
		}
		else if (isset($_SESSION['project'])) {
			$project = $_SESSION['project'];
		}

		//Get all iterations of the project
		$query = "MATCH (p:Project {name: '$project'})
					<-[:IS_ITERATION_OF]-(i:Iteration) 
					RETURN i.name as iteration";
		$result = runQuery($client, $query);

		$iterationOptions = "";

		foreach ($result->records() as $record) {
			$iteration = $record->value('iteration');
			$iterationOptions.= "<option value='$iteration'";
			if (isset($_POST['iteration'])
				&& $_POST['iteration'] == $iteration) {
				$iterationOptions.= " selected";
			}
			$iterationOptions.= ">$iteration</option>";
		}

		?>

		<div class="row">
			<form class="col-lg-8 col-lg-offset-2 form-horizontal" 
					method="post" action="index.php?features">
				
				<div class="row form-group">
					<label>Select the iteration</label>
					<select name="iteration">
						<?php
							echo $iterationOptions;
						?>
					</select>
					<button class="btn btn-primary" type="submit" 
							name="iterationSubmit">Validate
					</button>
				</div>

			</form>
		</div>

		<?php

		if (isset($_POST['iterationSubmit'])) {
			$project = $_SESSION['project'];
			$iteration = $_POST['iteration'];

			$outputList = "<ul>";

			$query = "MATCH (p:Project {name: '$project'}) 
						MATCH (i:Iteration {name: '$iteration'})-[:IS_ITERATION_OF]->(p)
						MATCH (i)<-[:BELONGS_TO]-(files)-[:IS_INCLUDED_IN|:IS_REQUIRED_IN|:IS_USED_BY|:IMPACTS|:DECLARES*0..]->(feature:Feature)
						WITH feature
						ORDER BY feature.name ASC 
						RETURN DISTINCT feature.name AS feature";

			$result = runQuery($client, $query);

			foreach ($result->records() as $record) {
				$outputList.= "<li>".$record->value('feature')."</li>";
			}
			$outputList.= "</ul>";
			echo $outputList;
		}

	}
?>

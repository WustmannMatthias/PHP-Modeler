<?php
	$databaseURL 	= $_SESSION['DATABASE_URL'];
	$databasePort 	= $_SESSION['DATABASE_PORT'];
	$username		= $_SESSION['USERNAME'];
	$password 		= $_SESSION['PASSWORD'];
	
	require_once "vendor/autoload.php";

	use GraphAware\Neo4j\Client\ClientBuilder;
	require_once "functions/database_functions.php";



	$fullURL = "bolt://".$username.":".$password."@".$databaseURL.":".$databasePort;
	$client = ClientBuilder::create()
	    ->addConnection('bolt', $fullURL)
	    ->build();



	/**
		Get list of all projects in database
	*/
	$query = "MATCH (i:Iteration) RETURN DISTINCT i.project as project";
	$result = runQuery($client, $query);

	$dynamicMenu = "";
	$_SESSION['PROJECTS'] = array();

	foreach ($result->records() as $record) {
		$project = $record->value('project');

		array_push($_SESSION['PROJECTS'], $project);
		
		$isActive = isset($_GET['project']) && $_GET['project'] == $project;

		$classes = "list-group-item";
		if ($isActive) $classes.= " active";

		$dynamicMenu.= "<a href='index.php?project=$project'>
							<li class='$classes'>$project</li>
						</a>";
	}

?>

<aside>
	<ul class="list-group">
		<a href="index.php?new_project">
			<li class="list-group-item <?php if (isset($_GET['new_project'])) echo 'active'; ?>"> New project...</li>
		</a>
		<a>
			<li class="list-group-item <?php if (isset($_GET['request_database'])) echo 'active'; ?>">Request database</li>
		</a>
		<?php echo $dynamicMenu; ?>

	</ul>
</aside>
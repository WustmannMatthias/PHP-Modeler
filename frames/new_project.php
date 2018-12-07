<?php
	

?>

<div class="row">
	<h1>Add a new project in database</h1>

</div>

<div class="row">
	<form class="col-lg-6 col-lg-offset-2 form-horizontal" method="post" action="index.php?new_project">
		<div class="row form-group">
			<label class="col-lg-6 control-label">Name of the project</label>
			<input class="col-lg-6" type="text" name="project" required="required" value="<?php if (isset($_POST['project'])) echo $_POST['project'];?>"/>
		</div>

		<?php
			/**
				Check if a directory corresponding to the given name exists in data/projects.
			*/

			$askForSettings = FALSE;

			if (isset($_POST['project'])) {
				$project = $_POST['project'];

				if (!is_dir("/var/www/html/application_modeling_2.0/data/projects/$project")) {
					echo "<div class='row'>
							<div class='col-lg-8 col-lg-offset-4 alert alert-warning'>No directory with the given name was found in data/projects. Make sure to clone the repository before continuing.</div>
						</div>";
				}
				else if (in_array($project, $_SESSION['PROJECTS'])) {
					echo "<div class='row'>
							<div class='col-lg-8 col-lg-offset-4 alert alert-warning'>Project is already in database.</div>
						</div>";
				}
				else {
					$askForSettings = TRUE;
				}
			}


		?>
		
		<div class="row">
			<button class="btn btn-primary pull-right" type="submit" name="submit">Submit</button>
		</div>

	</form>
</div>






<?php
	if ($askForSettings) {

?>


<div class="row">
	<h1><?php echo $project ?></h1>

	<h2>Settings</h2>

	<form class="col-lg-6 col-lg-offset-2 form-horizontal" method="post" action="programs/init_project.php?project=<?php echo $project; ?>">
		<div class="row form-group">
			<label class="col-lg-6 control-label">Files to analyse (extensions)</label>
			<input class="col-lg-6" type="text" name="extensions" required="required" value="" />
		</div>
		
		<div class="row form-group">
			<label class="col-lg-6 control-label">Analyse files without extensions</label>
			
			<label class=" control-label"><input class="col-lg-1" type="radio" name="withoutExtension" required="required" value="TRUE" />yes</label>
			
			<label class=" control-label"><input class="col-lg-1" type="radio" name="withoutExtension" required="required" value="FALSE" />no</label>
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Feature declaration</label>
			<input class="col-lg-6" type="text" name="feature" required="required" value="" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Sub-directories to ignore</label>
			<input class="col-lg-6" type="text" name="subDirectories" required="required" value="" />
		</div>

		<div class="row form-group">
			<label class="col-lg-6 control-label">Files to ignore</label>
			<input class="col-lg-6" type="text" name="filesToIgnore" value="" />
		</div>

		<div class="row">
			<button class="btn btn-primary pull-right" type="submit" name="changeSettings">Load project</button>
		</div>
	</form>
</div>

<?php
	}
?>
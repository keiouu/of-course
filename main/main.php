<?php

class CoursesFrontEnd {

	/**
	 * A Programmes Plant API Object.
	 */
	public $pp = false;

	public function __construct()
	{
		$this->pp = new ProgrammesPlant\API(XCRI_WEBSERVICE);
		$this->pp->no_ssl_verification();
	}

	/**
	 * View - View a "live" programme from the programmes plant
	 *
	 * @param string Type UG|PG
	 * @param yyyy Year to show
	 * @param int Id of programme
	 * @param string Slug - programme name
	 */
	public function view($type, $year, $id, $slug = '')
	{

		Flight::view()->set('type', $type);
		Flight::view()->set('year', $year);

		// Use webservices to get course data for programme
		try
		{
			$course = $this->pp->get_programme($year, $type, $id);
		}
		catch(ProgrammesPlant\ProgrammesPlantNotFoundException $e)
		{
			// 404? handle has missing/unknown course
			Flight::response()->status(404);
			return Flight::layout('missing_course', array('slug'=> $slug, 'id'=>$id, 'programmes'=> $this->get_programme_index($year, $type)));
		}
		
		// Debug option
		if(isset($_GET['debug_performance'])){ inspect($course); }
		
		// Fix slug paths
		if($course->slug != $slug){
 			return Flight::redirect($type.'/'.$year.'/'.$id.'/'.$course->slug);
 		}

 		// Render programme page
 		Flight::layout('course_page', array('course'=>$course, 'type'=> $type, 'subjects'=> $subjects));
	}

	/**
	 * Display a preview page
	 *
	 * @param string $hash of preview
	 */
	public function preview($hash){

		//Set vars
		Flight::view()->set('type', 'ug');
		Flight::view()->set('preview', true);
		Flight::view()->set('year', 'auto');

		try
		{
			$course = $this->pp->get_preview_programme($hash);	
		}
		catch(ProgrammesPlant\ProgrammesPlantNotFoundException $e)
		{
			// 404? handle has missing/unknown course
			Flight::response()->status(404);
			return Flight::layout('missing_course');
		}

		// Set year
		Flight::view()->set('year', $course->year);

		// Debug option
		if(isset($_GET['debug_performance'])){ inspect($course); }
		
		Flight::layout('course_page', array('course'=> $course));
	}

	/**
	 * Display subjects page
	 *
	 * @param string Type UG|PG
	 * @param yyyy Year to show
	 */
	public function subjects($type, $year)
	{
		// Get feed
		try
		{
			$subjects = $this->pp->get_subject_index($year, $type);	
		}
		catch(ProgrammesPlant\ProgrammesPlantNotFoundException $e)
		{
			$subjects = array();	
		}

		Flight::view()->set('type', $type);
		Flight::view()->set('year', $year);

		Flight::layout('subjects', array('subjects'=> $subjects));
	}
	
	/**
	 * List programmes - Show a list of all programmes availble to the system.
	 *
	 * @param string Type UG|PG
	 * @param yyyy Year to show
	 */
	public function list_programmes($type, $year)
	{
		$listing = $this->pp->get_programmes_index('2014', 'ug');

		$base_url = BASE_URL;

		foreach($listing as $course){
			echo "<a href='{$base_url}{$type}/{$year}/{$course->id}/{$course->slug}'>{$course->name}</a><br/>";
		}
		die();
	}


	/**
	 * Data formatted for searching by quickspot
	 *
	 */
	public function ajax_search_data($type, $year){
		$out = array();
		try{
			$js = $this->pp->get_programmes_index($year, $type);
		}
		catch(ProgrammesPlant\ProgrammesPlantNotFoundException $e)
		{
			die("fatal erorr.");
		}
		foreach($js as $j)$out[] = $j;
		echo json_encode($out);
	}
	/**
	 * Subjects Page
	 */
	public function ajax_subjects_page(){

		try
		{
			$subjects = $this->pp->get_subjectcategories();
		}
		catch(ProgrammesPlant\ProgrammesPlantNotFoundException $e)
		{
			$subjects = array();	
		}

		return Flight::render('menus/subjects', array('subjects'=> $subjects));
	}

	/**
	 * Search page
	 *
	 * @param string Type UG|PG
	 * @param yyyy Year to show
	 * @param int Id of programme
	 * @param string Slug - programme name
	 */
	public function search($type, $year)
	{

		Flight::view()->set('type',$type);
		Flight::view()->set('year',$year);

	    $programmes = $this->pp->get_programmes_index($year, $type);//5 minute cache
		//debug option
		if(isset($_GET['debug_performance'])){ inspect($programmes); }
		
		//Render full page
		Flight::layout('search', array('programmes' => $programmes));	
		
	}


	// Quietly grab index
	private function get_programme_index($year, $type){
		try{
			return $this->pp->get_programmes_index($year, $type);
		}
		catch(Exception $e)
		{
			return array();
		}
	}

}
<?php

class report_generator extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->helper(array('url', 'html'));
        include_once(APPPATH . 'libraries/Renderer.php'); 
    }

    function index()
    {
        echo doctype();
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';  

        $url = site_url('public/report_generator');
        $url1a = $url . '/report1a';
        $url1b = $url . '/report1b';
        $url2a = $url . '/report2a';
        $url2b = $url . '/report2b';
        $url3a = $url . '/report3a';
        $url3b = $url . '/report3c';
        $urlAuthor = $url . '/authors';
        $urlBook = $url . '/books';

        echo <<<html
<h3>Examples showing simple report generations using render_as_table() and view files.</h3>

<p>You can populate the Authors data <a href="$urlAuthor" target="_blank">here</a> and the Books data <a href="$urlBook" target="_blank">here</a>.</p>

<p>Show all books and their authors:</p>
<a href="$url1a">Using render_as_table()</a><br/>
<a href="$url1b">Using view file</a>

<p>Show all books and their authors where books are within a given price range:<br/>
<a href="$url2a">Using render_as_table()</a><br/>
<a href="$url2b">Using view file</a>

<p>Show only books from a given author:<br/></p>
<a href="$url3a">Using render_as_table()</a></br>
<a href="$url3b">Using view file</a>
html;

    }

    // Displays table to manage Authors for this example
    function authors()
    {
        $R = new Renderer();
        $options = array(
            'model' => 'nsf2\crop_status',
            'output' => TRUE
        );
        $result = $R->render_as_table($options);

        echo doctype();
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';  

        if($result['success'] == FALSE)
            echo cbeads_error_message('There was an error rendering the table:' . $result['msg']);
        else
            echo $result['output_html'];
    }
    // Displays table to manage Books for this example
   /* function books()
    {
        $R = new Renderer();
        $options = array(
            'model' => 'temp\Book',
            'output' => TRUE
        );
        $result = $R->render_as_table($options);

        echo doctype();
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';  

        if($result['success'] == FALSE)
            echo cbeads_error_message('There was an error rendering the table:' . $result['msg']);
        else
            echo $result['output_html'];
    }
*/

    // Show all books and their authors.
    /*function report1a()
    {
        // Using render_as_table() to display all books. Don't want to allow any user actions on this table.
        $R = new Renderer();
        $options = array(
           'model' => 'nsf2\crop_status',
				'title' => FALSE,
				'description' => 'Shows all  hat have a price between 30 and 70',
				'output' => TRUE,
				'column_order'=>array('crop_variety_id','planting_stage','expected_yield'),
				'columns' => array(
				'crop_variety_id' => array('label' => 'Crop Variety'),
				'planting_stage' => array('label' => 'Planting Stage'),
				'expected_yield' => array('label' => 'Expected Harvest'),
            'create' => FALSE, 'edit' => FALSE, 'view' => FALSE, 'delete' => FALSE, 'search' => FALSE
        );
        $result = $R->render_as_table($options);

        echo doctype();
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';  

        if($result['success'] == FALSE)
            echo cbeads_error_message('There was an error rendering the table:' . $result['msg']);
        else
            echo $result['output_html'];
    }
	

    function report1b()
    {
        // Get all Books and their associated Authors.
          $q = Doctrine_Query::create()
    ->select('c.crop_variety_id','c.planting_stage','c.expected_yield')        // select the book IDs.
    ->from('nsf2\crop_status c') ;       // Select the book model and joins it with the author model.
        $status = $q->fetchArray();                  // Returns an array of book records with all their attributes.
        // Create an array containing our data. Each key will become a variable in the view file.
        // So you will have a variable $description and $books to use in the view file.
        $data = array('books' => $staus, 'description' => 'Shows all books');
        // Select the view file to use and supply the data.
        $this->load->view('public/report_generator', $data);
    }
	*/

    // Show all books and their authors where books are within a given price range.
    function report2a()
    {
        // Using render_as_table() to display all books that have a price between 30 and 70.
        // This can be done by providing a dql query to the table options. The query must
        // select the book ids to display.
         $q = Doctrine_Query::create()
    ->select('c.crop_variety_id','c.planting_stage','c.expected_yield')        // select the book IDs.
    ->from('nsf2\crop_status c') ; 
        $R = new Renderer();
        $options = array(
            'model' => 'nsf2\crop_status',
				'title' => FALSE,
				'description' => 'Shows all  hat have a price between 30 and 70',
				'output' => TRUE,
				'column_order'=>array('crop_variety_id','planting_stage','expected_yield'),
				'columns' => array(
				'crop_variety_id' => array('label' => 'Crop Variety'),
				'planting_stage' => array('label' => 'Planting Stage'),
				'expected_yield' => array('label' => 'Expected Harvest'),),
            'create' => FALSE, 'edit' => FALSE, 'view' => FALSE, 'delete' => FALSE, 'search' => FALSE,
            // $q->getDql() converts the query object into a dql query string which is used by the Renderer.
            'filter_by_dql' => $q->getDql()
        );
        $result = $R->render_as_table($options);

        echo doctype();
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';  

        if($result['success'] == FALSE)
            echo cbeads_error_message('There was an error rendering the table:' . $result['msg']);
        else
            echo $result['output_html'];
    }

   /* function report2b()
    {
        // Get all Books within a price range and their associated Authors.
        $q = Doctrine_Query::create()
            ->select('b.*, a.*')                    // Want to select everything from the books and authors.
            ->from('temp\Book b, b.Author a')       // Select the book model and joins it with the author model.
            ->where('b.price > 30')                 // set bottom price boundary
            ->andWhere('b.price < 70');             // set top price boundary
        $books = $q->fetchArray();                  // Returns an array of book records with all their attributes.
        $data = array('books' => $books, 'description' => 'Shows all books that have a price between 30 and 70');
        $this->load->view('public/report_generator', $data);
    }


    // Show only books from a given author.
    function report3a()
    {
        // Using render_as_table() to display all books that have a specific author.
        // As in report2, select the book IDs. Specify the model as well as the related
        // model.
        $q = Doctrine_Query::create()
            ->select('b.id')                // select the book IDs.
            // select the book model (alias 'b') and join it with the author model (alias 'a')
            ->from('temp\Book b, b.Author a')
            ->where('a.name = "bob"');

        $R = new Renderer();
        $options = array(
            'model' => 'temp\Book',
            'title' => FALSE,
            'description' => "Shows all books for a specific author.",
            'output' => TRUE,
            'columns' => array('author_id' => array('label' => 'Author')),
            'create' => FALSE, 'edit' => FALSE, 'view' => FALSE, 'delete' => FALSE, 'search' => FALSE,
            'filter_by_dql' => $q->getDql()
        );
        $result = $R->render_as_table($options);

        echo doctype();
        echo '<link href="'.base_url().'cbeads/css/cbeads_style.css" type="text/css" rel="stylesheet">';  

        if($result['success'] == FALSE)
            echo cbeads_error_message('There was an error rendering the table:' . $result['msg']);
        else
            echo $result['output_html'];
    }

    function report3c()
    {
        // Get all Books within a price range and their associated Authors.
        $q = Doctrine_Query::create()
            ->select('b.*, a.*')    
            ->from('temp\Book b, b.Author a')
            ->where('a.name = "bob"');
        $books = $q->fetchArray();
        $data = array('books' => $books, 'description' => 'Shows all books for a specific author.');
        $this->load->view('public/report_generator', $data);
    }

}*/
}

?>
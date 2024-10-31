<?php
/*
Plugin Name: Select table and column and output in CSV.
Plugin URI:  https://wspri.dip.jp/?p=194
Description: 全てのwordpress.*データベースからカラムを選択して、その内容を表示し、CSV出力するプラグイン:\nPlugin that selects columns from all wordpress. * Databases, displays their contents, and outputs them in CSV format.
Author: jack.Amano
Version: 1.0
Author URI:https://wspri.dip.jp
*/

//Create object
new showtables;
//Create CLASS
class showtables{
    //Constrructer
    function __construct(){
        add_action('admin_menu', array($this, 'add_admin'));//Preparing to Add to the Administrator Menu
    }
    //Add menu fanction
    function add_admin(){
        //Are using Japanese or another language?
        if(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)=="ja"){
            $menuname="DB一覧表示";
        }else{
            $menuname="DB list display";
        }
        //level_8-10 is Admin
        add_menu_page($nenuname,$menuname,'level_9', __FILE__, array($this,'show_DB'));
    }
    //View table and column.And selected table and column.Output CSV 
    function show_DB(){
        
        //Post table and column array
        if(isset($_POST['table_name']) && isset($_POST['colum_name'])){
            //Are using Japanese or another language?
            if(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)=="ja"){
                $massage02="テーブルのカラムが送信されないとカラムの内容を読み込めません。ここに表示されているテーブル名のカラムか確認してから[Read Table & Columns]ボタンを押してください。";
                $selecter200="最新の200レコード";
                $selecter500="最新の500レコード";
                $selecter1000="最新の1000レコード";
                $selecterall="全てのレコード";
            }else{
                $massage02="If the table column is not sent, the contents of the column can not be read. Confirm that the table name column is displayed here, and then click the [Read Table & Columns] button.";
                $selecter200="200row from the latest";
                $selecter500="500row from the latest";
                $selecter1000="1000row from the latest";
                $selecterall="All";
            }
            //Get table_name
            $table_mono=sanitize_text_field($_POST['table_name']);
            //Get column array
            $colum_array=$_POST['colum_name'];
            echo <<<FORM
            <h1>Show WordPress Table & Columns</h1>
            {$massage02}
            <hr width="98%">
            <form method="post" action="" >
            <input type="checkbox" name="createCSV" value="DL">DownloadCSV | row count:<select name="CSVrow">
            <option value="200">{$selecter200}</option>
            <option value="500">{$selecter500}</option>
            <option value="1000">{$selecter1000}</option>
            <option value="all">{$selecterall}</option>
            </select>

            <h2>TABLE NAME:<input type="text" name="post_tablename" value="{$table_mono}" style='font-size:20px;font-weight:900;'></h2>
FORM;
            for ($i=0;$i<count($colum_array);$i++){
                //This is column No
                $c=$i+1;
                //sanitaize
                $columns=sanitize_text_field($colum_array[$i]);
                //Echo text form
                echo "<h4>&raquo; COLUMN{$c}:<input type='text' name='post_columname[]' value='{$columns}'></h4>";
            }
            echo <<<FORM
            <input type="submit" value="Read Table & Columns">
            </form>
FORM;
            //$this->readDB();//will be able to reselected
        //If the POSTed data of selected table names and columns.
        }else if(isset($_POST['post_tablename']) && isset($_POST['post_columname'])){
            //Are using Japanese or another language?
            if(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)=="ja"){
                $errormsg1="POSTしたTABLEのカラムは存在していません！";
                $errormsg2="不正なテーブル情報がPOSTされています。";
                $massage03="POSTしたTABLEのカラムの内容を表示しています。";
            }else{
                $errormsg1="The column of TABLE that has been posted does not exist!";
                $errormsg2="Incorrect table information has been POSTed.";
                $massage03="Displays the contents of the posted TABLE column.";
            }
            //Get table name
            $get_table=sanitize_text_field($_POST['post_tablename']);
            //Get column array
            $get_columns=$_POST['post_columname'];
            //When there are multiple arrays
            if(count($get_columns)>1){
                $get_column=implode(",",$get_columns);//Separate the array with "," to make it a string
            }else{//If the array is one
                $get_column=$get_columns[0];
            }
            //sanitaize
            $get_column=sanitize_text_field($get_column);
            //SELECT a column from the table
            global $wpdb;//Call WP instance and login
            $reading=$wpdb->get_results("SELECT $get_column FROM $get_table");
            $matchtitle=$wpdb->get_results("SELECT guid,post_title FROM wp_posts");
            //Failed to SELECT
            if(!$reading){
                echo <<<TABLE
                <h1>SQL COMMAND : SELECT {$get_column} FROM {$get_table}</h1>
                <h2 style="color:red;">{$errormsg1}</h2>
                <hr width='98%'>
                {$errormsg2}
TABLE;
            }else{//Success to SELECT

                /*Create if there is no csv primary storage directory*/
                $directory_path = "./tmp";//directory name is tmp
                if(!file_exists($directory_path)){
                    /*Processing when there is not exist: If there is not, create with 0777*/
                    mkdir($directory_path, 0777);
                /*Processing when there is a tmp directory*/
                }else{
                    /*Search *.csv if there is a tmp directory, it's array: If not, it will be an empty array*/
                    $csv = glob('*.csv');
                    /*Processing when array is not empty*/
                    if(empty($csv)){
                        /*Look inside wp-admin / tmp and read all files with * .csv extension*/
                        foreach(glob('tmp/{*csv}',GLOB_BRACE) as $file){
                            /*Process if $file is a file*/
                            if(is_file($file)){
                                /*Process if Unix timestamp of file is older than the current time*/
                                if(filemtime($file) < time()){
                                /*delete*/
                                unlink($file);
                                }
                            }
                        }
                    }
                }

                echo <<<TABLE
                <h1>SQL COMMAND : SELECT {$get_column} FROM {$get_table}</h1>
                {$massage03}
                <hr width='98%'>
                <table  border='1' cellpadding='5' bordercolor='#ffffff' width='98%'>
                <tr>
TABLE;
                //export CSV 1at session
                $columnamearray=[];
                //Here is the name of the column
                for($r=0;$r<count($get_columns);$r++){
                    //If do not convert variables to characters, can not use them.
                    $name=strval($get_columns[$r]);
                    echo "<td width='10%' bgcolor='#EEcccc'>".$name."</td>";
                    //export CSV 1st session
                    $columnamearray[$r]=$name;//push colum name
                }
                echo "</tr>";

                //export CSV 2nd session
                $rowarray[-1]=$columnamearray;//push row onece. Why is it [-1]? Because the count starts with [0].
                //echo use
                $rowarray2=[];
                $columdataarray=[];
                //Because it is OBUJECT, first, it repeats by the number of array of $reading.
                for($i=0;$i<count($reading);$i++){
                    //echo "<tr>";
                    //Next, repeat the number of columns and extract the columns
                    for($c=0;$c<count($get_columns);$c++){
                        //If do not convert variables to characters, can not use them.
                        $name=strval($get_columns[$c]);//to string
                        $data=$reading[$i]->$name;
                        if($name=='dt'||$name=='dt_out'){
                            $data=date('Y/m/d H:i:s', $data);//convert unixtimestamp
                        }
                        if($name=='resource'){
                            /*Repeat by $matchtitle array*/
                            for($b = 0 ; $b < count($matchtitle) ; $b++){
                                /*if $matchtitle[$b]->guid is $data*/
                                if(strpos($matchtitle[$b]->guid,$data) !== false){
                                    /*"/"is top pagge*/
                                    if($matchtitle[$b]->guid=='/'){
                                        $data = 'TopPage';
                                    }else{
                                        /*$matchtitle[$b]->post_title is $data,$post_title is $data*/
                                        $data = $matchtitle[$b]->post_title;
                                    }
                                /*no $data*/
                                }
                            }
                        }
                        //echo "<td width='10%' bgcolor='#eeeeee'>".$data."</td>";
                        $columdataarray[$c]=$data;
                    }
                    //echo "</tr>";
                    $rowarray[$i]=$columdataarray;
                    //echo use
                    $rowarray2[$i]=$columdataarray;
                }
                //echo "</table>";
                $i=0;
                $ii=0;
                if(isset($_POST['CSVrow']) && $_POST['CSVrow']=="all"){
                    $counts=count($rowarray);
                }else{
                    $counts=intval(sanitize_text_field($_POST['CSVrow']));
                    rsort($rowarray);//from the latest
                    rsort($rowarray2);//from the latest
                }
                //echo count($rowarray)."<br>";
                foreach ($rowarray2 as $fields2) {
                    if($ii>=$counts){
                       break;
                    }
                    echo "<tr>";
                    foreach ($fields2 as $values2) {
                        echo "<td width='10%' bgcolor='#eeeeee'>".$values2."</td>";
                    }
                    $ii++;
                    echo "</tr>";
                }
                if(isset($_POST['createCSV']) && $_POST['createCSV']=="DL"){
                    /*Create CSV */
                    mb_convert_encoding($rowarray,"SJIS", "UTF-8");
                    //var_dump($rowarray);
                    $csvFileName = 'tmp/tablecolumdata_'.time().'.csv';//CSV file path
                    $fp = fopen($csvFileName, 'w');
                    if(!$fp){
                        print_r('CSV output failed');
                    }
                    foreach ($rowarray as $fields) {
                        if($i>=$counts){
                           break;
                        }
                        /*Put to csv file*/
                        fputcsv($fp, $fields);
                        $i++;
                    }
                    /*Closed: Be careful, because if you forget to close it, you won't be able to proceed.*/
                    fclose($fp);
                    /*Make a path of server*/
                    $server_path = (empty($_SERVER["HTTPS"]) ? "http://" : "https://").$_SERVER["HTTP_HOST"]."/wp-admin/" ;
                    /*Download*/
                    print_r("<META HTTP-EQUIV='Refresh' CONTENT='0;URL=".$server_path.$csvFileName."'>");
                    /*Create CSV */
                }
                echo "</table>";
            }
        }else{
            //Confirming administrator access
            if(current_user_can('administrator')){
                $this->readDB();//Call private fubction
            }else{
                echo <<<NONADMIN
                <script type="text/javascript">aleat("You are no admin!");</script>
NONADMIN;
            }
        }
    }
    private function readDB(){
        //Are using Japanese or another language?
        if(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2)=="ja"){
            $massage01="テーブル選択のラジオボタンをオンにして、そのテーブルのカラムを選択します。確認できたら[Set Form]ボタンを押してください。<br>テーブルまたはカラム名だけ送信することはできません。ラジオボタンがオフになっているテーブルのカラムを送信するとエラーになります。";
        }else{
            $massage01="Select the table selection radio button and select the columns for that table. After confirmation, please press the [Set Form] button. <br>You can not send only table or column names. It is an error to send a table column where the radio button is off.";
        }
        global $wpdb;//Call WP instance and login
        $tables=$wpdb->get_results("SHOW TABLES FROM wordpress ");
        
        echo <<<TITLE
        <h1>Show WordPress All Tables</h1>
        {$massage01}
        <hr width="98%">
        <form method="post" name="form1" action="" >
TITLE;
        for ($i=0;$i<count($tables);$i++){//Disassemble array while counting up
            $tablename[$i]=$tables[$i]->Tables_in_wordpress;
            echo <<<ORITATAMI
            <div onclick="obj=document.getElementById('menu{$i}').style; obj.display=(obj.display=='none')?'block':'none';">
            <h2><input type="radio" name="table_name" value="{$tablename[$i]}">{$tablename[$i]}</h2>
            </div>
            <div id="menu{$i}" style="display:none;clear:both;">
ORITATAMI;
            $colum_search = $wpdb->get_results("SHOW COLUMNS FROM $tablename[$i] ");
            for($c=0;$c<count($colum_search);$c++){
                echo <<<ORITATAMI
                <div>
                <h3>
                &raquo; <input type="checkbox" name="colum_name[]" value="{$colum_search[$c]->Field}">{$colum_search[$c]->Field}</h3></div>
ORITATAMI;
            }
            echo "</div>";
        }
        echo "<input type='submit' value='Set Form'></form>";
    }
}
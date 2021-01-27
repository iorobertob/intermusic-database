<?php
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_csvtable.
 *
 * @package     mod_csvtable
 * @copyright   2021 Ideas-Block <roberto@idea-block.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" >
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.dataTables.min.css" >
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>

<h1> <?php echo($name) ?> </h1>
<table class="display  dataTable collapsed dtr-inline" id="the_table">
	<thead>
		<th>

			<?php for( $i = 0; $i<sizeof($the_big_array[0])-1; $i++ ): ?>

			    <?php echo($the_big_array[0][$i].'</th><th>');  ?>

			<?php endfor; ?>

			<?php 
				echo($the_big_array[0][sizeof($the_big_array[0])-1].'</th></thead><tbody>'); 

				for ( $i = 1; $i < sizeof($the_big_array); $i++)
				{
				    $row = $the_big_array[$i];

				    $is_row_empty = true;
				    for ( $j = 0; $j < sizeof($row); $j++)
				    {
				    	if ($row[$j] != null)
				    	{
				    		$is_row_empty = false;
				    	}
				    }
				    if (!$is_row_empty)
				    {
				    	echo('<tr>');
				   
					    for ( $j = 0; $j < sizeof($row); $j++)
					    {
					        $item = $row[$j];
					        // If there is an URL in the data
					        // if (filter_var($item, FILTER_VALIDATE_URL)) {
					        if ((strpos($item, 'http://') !== false) ||  (strpos($item, 'https://') !== false)){
					            // make a link with the string before the "|" symbol 
					            $split_string = explode ( "|" , $item);
					            echo("<td><a href=\"".$split_string[1]."\" target=\"_blank\">".$split_string[0]." </a></td>");
					        }
					        // Any data, not an URL
					        else {
					            echo("<td>".$item."</td>");
					        }
					    }
				    	echo('</tr>');
				    }
				}
			?>

	</tbody>
</table>

<script>
    $(document).ready(function() 
    {
	    $('#the_table').DataTable({
	        fixedHeader: true,
	        scrollY: '1000px',
	        responsive:true
	        });
    });
</script>

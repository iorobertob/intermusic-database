<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" >
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.dataTables.min.css" >
<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>

<script src="colResizable-1.6.js"></script>
<script src="js_utilities.js"></script>	

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

				    echo('<tr>');

				    for ( $j = 0; $j < sizeof($row); $j++)
				    {
				        $item = $row[$j];
				        // If there is an URL in the data
				        if (filter_var($item, FILTER_VALIDATE_URL)) { 
				            // make a button
				            echo("<td><a href=\"".$item."\"><button>Link</button></a></td>");
				        }
				        // Any data, not an URL
				        else{
				            echo("<td>".$item."</td>");
				        }
				        
				    }
				    echo('</tr>');
				}

			?>

	</tbody>
</table>


<script>
    $(document).ready(function() 
    {
	    $('#the_table').DataTable({
	        fixedHeader: true,
	        scrollY: '500px',
	        responsive:true
	        });

    });
</script>
# CSV Table Plugin #


This plugin takes a CSV file and renders a searchable table in an activity in moodle. 

CSV files have different sperator characeters, "," not always being the default. This plugin offers the optio to select betweeh "," "/" and ";" characters as separators. 

The first row of the CSV table will be used as Columns' headers. 

In order to have active links in anh of the cells, the content of the cell should be formatted as follows:

Some arbitraty text | https://some.link

A "|" character marks the division between the link to be recognised and the string to be used as the link text. In the example above "Some arbitrary text" will show in the table cell and will contain an active link to https://some.link. It is necessary for the url to have either "http://" or "https://" for it to be recognised as a link. Spaces around the "|" character are not necessary. 

This plugin uses [DataTables](https://datatables.net/) for its formatting. 



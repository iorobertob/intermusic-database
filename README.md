# Intermusic Database #

### Important: 
- This module is tightly integrated with other parts of the Intermusic project's system, specially with the custom "poster" module version forked for Inter music from the original poster moodle module. 
- When installing the folder of this module has to be named 'inter' 


## What it does
This module will look for the instances of our custom posters (https://github.com/iorobertob/intermusic_poster) in the current course, or across the whole platform, and display a searchable list of them. 

It will take the metadata set in the configuration of a the custom poster (manually in put or retreived from an Asset Management System -AMS-) and populate a table, with links to the corresponding poster. 

The data is automatically updated via cron tasks and everytime the module is saved to synchonize with new posters created/removed. 

The name of the columns for the metadata of the poster can be automatically populated with defalt values or overriden with custom values in the configuration section. 



